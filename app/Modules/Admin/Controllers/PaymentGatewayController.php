<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\DepositRequest;
use App\Models\PaymentBankAccount;
use App\Models\PaymentUpiAccount;
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
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_mode' => ['required', 'in:upi,bank'],
        ]);

        AppSetting::set('payment_mode', $validated['payment_mode']);

        Log::channel('admin_security')->info('Payment gateway settings updated', [
            'payment_mode' => $validated['payment_mode'],
        ]);

        return redirect()->route('admin.payment-gateway')->with('success', 'Payment gateway settings updated.');
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

        PaymentUpiAccount::create($data);

        Log::channel('admin_security')->info('UPI payment account created', ['upi_id' => $data['upi_id']]);

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

        return redirect()->route('admin.payment-gateway')->with('success', 'UPI account updated.');
    }

    public function toggleUpiActive(PaymentUpiAccount $upiAccount): RedirectResponse
    {
        $upiAccount->update(['is_active' => ! $upiAccount->is_active]);

        Log::channel('admin_security')->info('UPI payment account toggled', [
            'id' => $upiAccount->id,
            'is_active' => $upiAccount->is_active,
        ]);

        return redirect()->route('admin.payment-gateway')
            ->with('success', $upiAccount->upi_id.' is now '.($upiAccount->is_active ? 'active' : 'disabled').'.');
    }

    public function deleteUpi(PaymentUpiAccount $upiAccount): RedirectResponse
    {
        $upiId = $upiAccount->upi_id;
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
        PaymentBankAccount::create($data);

        Log::channel('admin_security')->info('Bank payment account created', ['bank_name' => $data['bank_name']]);

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

        return redirect()->route('admin.payment-gateway')->with('success', 'Bank account updated.');
    }

    public function toggleBankActive(PaymentBankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->update(['is_active' => ! $bankAccount->is_active]);

        Log::channel('admin_security')->info('Bank payment account toggled', [
            'id' => $bankAccount->id,
            'is_active' => $bankAccount->is_active,
        ]);

        return redirect()->route('admin.payment-gateway')
            ->with('success', $bankAccount->bank_name.' is now '.($bankAccount->is_active ? 'active' : 'disabled').'.');
    }

    public function deleteBank(PaymentBankAccount $bankAccount): RedirectResponse
    {
        $bankName = $bankAccount->bank_name;
        $bankAccount->delete();

        Log::channel('admin_security')->info('Bank payment account deleted', ['bank_name' => $bankName]);

        return redirect()->route('admin.payment-gateway')->with('success', $bankName.' account deleted.');
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
