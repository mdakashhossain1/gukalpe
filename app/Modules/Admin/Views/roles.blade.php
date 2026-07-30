@extends('layouts.admin')

@section('title', 'Roles & admins')

@section('content')

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="roles" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Roles & admins" />

        <div class="px-6 md:px-10 py-8 md:py-10">

            <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Roles &amp; admins</h1>
            <p class="text-[13.5px] text-[#64748B] mb-6">The master password always signs in as <strong>Super Admin</strong>. Create named admins with a role to give scoped access. You are currently acting as <strong>{{ \App\Support\AdminRoles::label($currentRole) }}</strong>.</p>

            @if (session('success'))
                <div class="mb-5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-[13px] font-semibold px-4 py-2.5">{{ session('success') }}</div>
            @endif

            {{-- Permission matrix --}}
            <div class="bg-white rounded-xl border border-[#E5E9EB] overflow-hidden mb-8">
                <div class="px-5 py-3.5 border-b border-[#E5E9EB]"><h2 class="text-[14px] font-bold text-[#0F172A]">Permission matrix</h2></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[720px]">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                                <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Permission</th>
                                @foreach ($roles as $key => $label)
                                    <th class="px-3 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-center">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($permissions as $permKey => $permLabel)
                                <tr class="border-b border-[#F1F5F9] last:border-0">
                                    <td class="px-5 py-3 text-[13px] font-semibold text-[#0F172A]">{{ $permLabel }}</td>
                                    @foreach ($roles as $roleKey => $roleLabel)
                                        <td class="px-3 py-3 text-center">
                                            @if (in_array($permKey, $matrix[$roleKey] ?? [], true))
                                                <i class="fa-solid fa-check text-emerald-500 text-[13px]"></i>
                                            @else
                                                <i class="fa-solid fa-minus text-[#CBD5E1] text-[11px]"></i>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Admin users list --}}
                <div class="lg:col-span-2 bg-white rounded-xl border border-[#E5E9EB] overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-[#E5E9EB]"><h2 class="text-[14px] font-bold text-[#0F172A]">Admin users</h2></div>
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                                <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Name</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Username</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Role</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Status</th>
                                <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($admins as $admin)
                                <tr class="border-b border-[#F1F5F9] last:border-0">
                                    <td class="px-5 py-3 text-[13px] font-semibold text-[#0F172A]">{{ $admin->name }}</td>
                                    <td class="px-4 py-3 text-[13px] font-mono text-[#334155]">{{ $admin->username }}</td>
                                    <td class="px-4 py-3 text-[12.5px] font-semibold text-[#334155]">{{ $admin->roleLabel() }}</td>
                                    <td class="px-4 py-3"><span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full {{ $admin->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500' }}">{{ $admin->is_active ? 'Active' : 'Off' }}</span></td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex gap-2 justify-end">
                                            <form method="POST" action="{{ route('admin.roles.toggle-active', $admin) }}">@csrf
                                                <button type="submit" class="h-8 px-3 rounded-lg border border-[#CBD5E1] text-[#334155] text-[12px] font-bold hover:bg-[#F1F5F9] transition-colors">{{ $admin->is_active ? 'Disable' : 'Enable' }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.roles.delete', $admin) }}" onsubmit="return confirm('Remove this admin?');">@csrf
                                                <button type="submit" class="h-8 px-3 rounded-lg border border-red-200 text-red-600 text-[12px] font-bold hover:bg-red-50 transition-colors">Remove</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-[13.5px] text-[#94A3B8] italic">No named admins yet — only the master password is in use.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Create admin --}}
                <div class="bg-white rounded-xl border border-[#E5E9EB] p-5">
                    <h2 class="text-[14px] font-bold text-[#0F172A] mb-4">Add admin</h2>
                    <form method="POST" action="{{ route('admin.roles.store') }}" class="flex flex-col gap-3.5">
                        @csrf
                        <div>
                            <label for="name" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                            @error('name')<p class="text-[12px] font-semibold text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="username" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Username</label>
                            <input type="text" name="username" id="username" value="{{ old('username') }}" required class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                            @error('username')<p class="text-[12px] font-semibold text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Password</label>
                            <input type="password" name="password" id="password" required class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                            @error('password')<p class="text-[12px] font-semibold text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="role" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Role</label>
                            <select name="role" id="role" class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                                @foreach ($roles as $key => $label)
                                    <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="h-10 rounded-lg bg-[#0F172A] text-white font-semibold text-[13.5px] hover:bg-[#1E293B] transition-colors active:scale-[0.99] mt-1">Create admin</button>
                    </form>
                </div>
            </div>

        </div>
    </main>
</div>

@endsection
