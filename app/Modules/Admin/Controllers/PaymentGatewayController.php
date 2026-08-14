<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\AppSetting;
use App\Models\DepositRequest;
use App\Models\PaymentBankAccount;
use App\Models\PaymentUpiAccount;
use App\Models\PaymentUsdtAccount;
use App\Models\WithdrawRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Admin control surface for the manual payment gateway - which of UPI/Bank
 * transfer is the active collection method, and the pool of UPI/bank
 * accounts that DepositRequestController picks from at random on every
 * /add-money page load. Kept as its own controller for the same reason
 * PlanManagementController is - AdminController was already large.
 */
class PaymentGatewayController extends Controller
{
    private function sidebarCounts(): array
    {
        return [
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ];
    }

    public function index(): View
    {
        return view('Admin::payment-gateway.index', [
            ...$this->sidebarCounts(),
            'settings' => AppSetting::many(AppSetting::DEFAULTS),
            'upiAccounts' => PaymentUpiAccount::ordered()->get(),
            'bankAccounts' => PaymentBankAccount::ordered()->get(),
            'usdtAccounts' => PaymentUsdtAccount::ordered()->get(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        // Method-level amount ranges: one [min, max] each for UPI/Bank/USDT.
        // Both bounds optional - a blank max means "no upper limit", and
        // leaving BOTH blank disables that method (it's never shown). max
        // must be >= min.
        $validated = $request->validate([
            'upi_min_amount' => ['nullable', 'numeric', 'min:0'],
            'upi_max_amount' => ['nullable', 'numeric', 'min:0', 'gte:upi_min_amount'],
            'bank_min_amount' => ['nullable', 'numeric', 'min:0'],
            'bank_max_amount' => ['nullable', 'numeric', 'min:0', 'gte:bank_min_amount'],
            'usdt_min_amount' => ['nullable', 'numeric', 'min:0'],
            'usdt_max_amount' => ['nullable', 'numeric', 'min:0', 'gte:usdt_min_amount'],
        ]);

        foreach (['upi_min_amount', 'upi_max_amount', 'bank_min_amount', 'bank_max_amount', 'usdt_min_amount', 'usdt_max_amount'] as $key) {
            // Store '' for a blank field so the deposit-side range logic reads it
            // as "no bound" rather than 0.
            AppSetting::set($key, isset($validated[$key]) && $validated[$key] !== null ? (string) $validated[$key] : '');
        }

        Log::channel('admin_security')->info('Payment gateway amount ranges updated', $validated);
        AdminAuditLog::record($request, 'payment_gateway_ranges_updated', null, null, $validated);

        return redirect()->route('admin.payment-gateway')->with('success', 'Payment ranges updated.');
    }

    // --- UPI accounts -----------------------------------------------------

    public function createUpi(): View
    {
        return view('Admin::payment-gateway.upi-form', [
            ...$this->sidebarCounts(),
            'account' => new PaymentUpiAccount(['is_active' => true]),
        ]);
    }

    public function storeUpi(Request $request): RedirectResponse
    {
        $request->validate(['qr_image' => ['required', 'image', 'max:4096']]);
        $data = $this->validatedUpi($request);
        $data['qr_image'] = $this->storeUploadedImage($request, 'qr_image');

        $upiAccount = PaymentUpiAccount::create($data);

        Log::channel('admin_security')->info('UPI payment account created', ['upi_id' => $data['upi_id']]);
        AdminAuditLog::record($request, 'upi_account_created', $upiAccount);

        return redirect()->route('admin.payment-gateway')->with('success', 'UPI account added.');
    }

    public function editUpi(PaymentUpiAccount $upiAccount): View
    {
        return view('Admin::payment-gateway.upi-form', [
            ...$this->sidebarCounts(),
            'account' => $upiAccount,
        ]);
    }

    public function updateUpi(Request $request, PaymentUpiAccount $upiAccount): RedirectResponse
    {
        $request->validate(['qr_image' => ['nullable', 'image', 'max:4096']]);
        $data = $this->validatedUpi($request);
        if ($request->hasFile('qr_image')) {
            $data['qr_image'] = $this->storeUploadedImage($request, 'qr_image');
        }

        $upiAccount->update($data);

        Log::channel('admin_security')->info('UPI payment account updated', ['id' => $upiAccount->id]);
        AdminAuditLog::record($request, 'upi_account_updated', $upiAccount);

        return redirect()->route('admin.payment-gateway')->with('success', 'UPI account updated.');
    }

    public function toggleUpiActive(Request $request, PaymentUpiAccount $upiAccount): RedirectResponse
    {
        $upiAccount->update(['is_active' => ! $upiAccount->is_active]);

        Log::channel('admin_security')->info('UPI payment account toggled', [
            'id' => $upiAccount->id,
            'is_active' => $upiAccount->is_active,
        ]);
        AdminAuditLog::record($request, 'upi_account_toggled', $upiAccount, null, ['is_active' => $upiAccount->is_active]);

        return redirect()->route('admin.payment-gateway')
            ->with('success', $upiAccount->upi_id.' is now '.($upiAccount->is_active ? 'active' : 'disabled').'.');
    }

    // Simple up/down reorder over the existing sort_order column (client
    // item 9.7: "Priority/Rotation Order" admin control) - swaps this
    // account's sort_order with its immediate neighbor in the ordered()
    // list, rather than requiring admins to hand-edit numbers.
    public function moveUpi(Request $request, PaymentUpiAccount $upiAccount): RedirectResponse
    {
        $this->moveInOrder(PaymentUpiAccount::ordered()->get(), $upiAccount, (string) $request->input('direction'));
        AdminAuditLog::record($request, 'upi_account_reordered', $upiAccount);

        return redirect()->route('admin.payment-gateway');
    }

    public function moveBank(Request $request, PaymentBankAccount $bankAccount): RedirectResponse
    {
        $this->moveInOrder(PaymentBankAccount::ordered()->get(), $bankAccount, (string) $request->input('direction'));
        AdminAuditLog::record($request, 'bank_account_reordered', $bankAccount);

        return redirect()->route('admin.payment-gateway');
    }

    public function moveUsdt(Request $request, PaymentUsdtAccount $usdtAccount): RedirectResponse
    {
        $this->moveInOrder(PaymentUsdtAccount::ordered()->get(), $usdtAccount, (string) $request->input('direction'));
        AdminAuditLog::record($request, 'usdt_account_reordered', $usdtAccount);

        return redirect()->route('admin.payment-gateway');
    }

    // Reassigns sort_order sequentially (0, 1, 2...) across the whole list
    // after swapping the target's position - a plain value-swap would be a
    // silent no-op whenever two rows share the same sort_order (e.g. every
    // row still at its 0 default), so this always produces a visible move.
    private function moveInOrder($ordered, $account, string $direction): void
    {
        $items = $ordered->values();
        $index = $items->search(fn ($a) => $a->id === $account->id);
        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if ($index === false || ! $items->has($swapIndex)) {
            return;
        }

        $reordered = $items->all();
        [$reordered[$index], $reordered[$swapIndex]] = [$reordered[$swapIndex], $reordered[$index]];

        foreach ($reordered as $position => $item) {
            $item->update(['sort_order' => $position]);
        }
    }

    public function deleteUpi(Request $request, PaymentUpiAccount $upiAccount): RedirectResponse
    {
        $upiId = $upiAccount->upi_id;
        AdminAuditLog::record($request, 'upi_account_deleted', $upiAccount, null, ['upi_id' => $upiId]);
        $upiAccount->delete();

        Log::channel('admin_security')->info('UPI payment account deleted', ['upi_id' => $upiId]);

        return redirect()->route('admin.payment-gateway')->with('success', $upiId.' deleted.');
    }

    // --- Bank accounts ------------------------------------------------------

    public function createBank(): View
    {
        return view('Admin::payment-gateway.bank-form', [
            ...$this->sidebarCounts(),
            'account' => new PaymentBankAccount(['is_active' => true]),
        ]);
    }

    public function storeBank(Request $request): RedirectResponse
    {
        $data = $this->validatedBank($request);
        $bankAccount = PaymentBankAccount::create($data);

        Log::channel('admin_security')->info('Bank payment account created', ['bank_name' => $data['bank_name']]);
        AdminAuditLog::record($request, 'bank_account_created', $bankAccount);

        return redirect()->route('admin.payment-gateway')->with('success', 'Bank account added.');
    }

    public function editBank(PaymentBankAccount $bankAccount): View
    {
        return view('Admin::payment-gateway.bank-form', [
            ...$this->sidebarCounts(),
            'account' => $bankAccount,
        ]);
    }

    public function updateBank(Request $request, PaymentBankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->update($this->validatedBank($request));

        Log::channel('admin_security')->info('Bank payment account updated', ['id' => $bankAccount->id]);
        AdminAuditLog::record($request, 'bank_account_updated', $bankAccount);

        return redirect()->route('admin.payment-gateway')->with('success', 'Bank account updated.');
    }

    public function toggleBankActive(Request $request, PaymentBankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->update(['is_active' => ! $bankAccount->is_active]);

        Log::channel('admin_security')->info('Bank payment account toggled', [
            'id' => $bankAccount->id,
            'is_active' => $bankAccount->is_active,
        ]);
        AdminAuditLog::record($request, 'bank_account_toggled', $bankAccount, null, ['is_active' => $bankAccount->is_active]);

        return redirect()->route('admin.payment-gateway')
            ->with('success', $bankAccount->bank_name.' is now '.($bankAccount->is_active ? 'active' : 'disabled').'.');
    }

    public function deleteBank(Request $request, PaymentBankAccount $bankAccount): RedirectResponse
    {
        $bankName = $bankAccount->bank_name;
        AdminAuditLog::record($request, 'bank_account_deleted', $bankAccount, null, ['bank_name' => $bankName]);
        $bankAccount->delete();

        Log::channel('admin_security')->info('Bank payment account deleted', ['bank_name' => $bankName]);

        return redirect()->route('admin.payment-gateway')->with('success', $bankName.' account deleted.');
    }

    // --- USDT (TRC20) accounts -----------------------------------------

    public function createUsdt(): View
    {
        return view('Admin::payment-gateway.usdt-form', [
            ...$this->sidebarCounts(),
            'account' => new PaymentUsdtAccount(['is_active' => true]),
        ]);
    }

    public function storeUsdt(Request $request): RedirectResponse
    {
        $data = $this->validatedUsdt($request);
        if ($request->hasFile('qr_image')) {
            $data['qr_image'] = $this->storeUploadedImage($request, 'qr_image');
        }

        $usdtAccount = PaymentUsdtAccount::create($data);

        Log::channel('admin_security')->info('USDT payment account created', ['usdt_address' => $data['usdt_address']]);
        AdminAuditLog::record($request, 'usdt_account_created', $usdtAccount);

        return redirect()->route('admin.payment-gateway')->with('success', 'USDT account added.');
    }

    public function editUsdt(PaymentUsdtAccount $usdtAccount): View
    {
        return view('Admin::payment-gateway.usdt-form', [
            ...$this->sidebarCounts(),
            'account' => $usdtAccount,
        ]);
    }

    public function updateUsdt(Request $request, PaymentUsdtAccount $usdtAccount): RedirectResponse
    {
        $data = $this->validatedUsdt($request);
        if ($request->hasFile('qr_image')) {
            $data['qr_image'] = $this->storeUploadedImage($request, 'qr_image');
        }

        $usdtAccount->update($data);

        Log::channel('admin_security')->info('USDT payment account updated', ['id' => $usdtAccount->id]);
        AdminAuditLog::record($request, 'usdt_account_updated', $usdtAccount);

        return redirect()->route('admin.payment-gateway')->with('success', 'USDT account updated.');
    }

    public function toggleUsdtActive(Request $request, PaymentUsdtAccount $usdtAccount): RedirectResponse
    {
        $usdtAccount->update(['is_active' => ! $usdtAccount->is_active]);

        Log::channel('admin_security')->info('USDT payment account toggled', [
            'id' => $usdtAccount->id,
            'is_active' => $usdtAccount->is_active,
        ]);
        AdminAuditLog::record($request, 'usdt_account_toggled', $usdtAccount, null, ['is_active' => $usdtAccount->is_active]);

        return redirect()->route('admin.payment-gateway')
            ->with('success', $usdtAccount->usdt_address.' is now '.($usdtAccount->is_active ? 'active' : 'disabled').'.');
    }

    public function deleteUsdt(Request $request, PaymentUsdtAccount $usdtAccount): RedirectResponse
    {
        $address = $usdtAccount->usdt_address;
        AdminAuditLog::record($request, 'usdt_account_deleted', $usdtAccount, null, ['usdt_address' => $address]);
        $usdtAccount->delete();

        Log::channel('admin_security')->info('USDT payment account deleted', ['usdt_address' => $address]);

        return redirect()->route('admin.payment-gateway')->with('success', 'USDT account deleted.');
    }

    // --- Shared helpers -------------------------------------------------

    private function validatedUpi(Request $request): array
    {
        $validated = $request->validate([
            'upi_id' => ['required', 'string', 'max:100', 'regex:/^[\w.\-]{2,256}@[a-zA-Z]{2,64}$/'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'mobile_number' => ['nullable', 'digits:10'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function validatedBank(Request $request): array
    {
        // IFSC is always uppercase in practice - normalized before
        // validation so admins can type it in lowercase without a rejection.
        $request->merge(['ifsc_code' => strtoupper(trim((string) $request->input('ifsc_code')))]);

        $validated = $request->validate([
            'account_holder_name' => ['required', 'string', 'max:150'],
            'account_number' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9]+$/'],
            'ifsc_code' => ['required', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'bank_name' => ['required', 'string', 'max:100'],
            'branch_name' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function validatedUsdt(Request $request): array
    {
        $validated = $request->validate([
            // Same TRC20 shape check as the withdrawal-side usdt_address
            // rule (WithdrawRequestController) - T + 33 base58 characters.
            'usdt_address' => ['required', 'string', 'regex:/^T[1-9A-HJ-NP-Za-km-z]{33}$/'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'qr_image' => ['nullable', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'usdt_address.regex' => 'Enter a valid TRC20 (Tron) wallet address - starts with T, 34 characters.',
        ]);

        unset($validated['qr_image']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    // Saved straight into public/assets/payment-qr, mirroring
    // PlanManagementController::storeUploadedImage() - this app is served
    // directly out of public/ via a custom index.php, no storage:link
    // symlink involved anywhere else either.
    private function storeUploadedImage(Request $request, string $field): string
    {
        $file = $request->file($field);
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $directory = public_path('assets/payment-qr');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->move($directory, $filename);

        return 'assets/payment-qr/'.$filename;
    }
}
