<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\DepositRequest;
use App\Models\WithdrawRequest;
use App\Support\AdminRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    private function guard(): void
    {
        abort_unless(AdminRoles::currentCan('manage_roles'), 403);
    }

    public function index(): View
    {
        $this->guard();

        return view('Admin::roles', [
            'roles' => AdminRoles::ROLES,
            'permissions' => AdminRoles::PERMISSIONS,
            'matrix' => AdminRoles::MATRIX,
            'admins' => AdminUser::orderBy('name')->get(),
            'currentRole' => AdminRoles::current(),
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guard();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'username' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('admin_users', 'username')],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'role' => ['required', Rule::in(array_keys(AdminRoles::ROLES))],
        ]);

        AdminUser::create($validated);

        Log::channel('admin_security')->info('Admin user created', [
            'ip' => $request->ip(), 'username' => $validated['username'], 'role' => $validated['role'],
        ]);

        return redirect()->route('admin.roles')->with('success', 'Admin user created.');
    }

    public function toggleActive(AdminUser $adminUser): RedirectResponse
    {
        $this->guard();

        $adminUser->update(['is_active' => ! $adminUser->is_active]);

        return back()->with('success', $adminUser->is_active ? 'Admin activated.' : 'Admin deactivated.');
    }

    public function destroy(AdminUser $adminUser): RedirectResponse
    {
        $this->guard();

        $adminUser->delete();

        return redirect()->route('admin.roles')->with('success', 'Admin user removed.');
    }
}
