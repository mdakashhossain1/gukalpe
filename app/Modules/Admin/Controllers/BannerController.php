<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\DepositRequest;
use App\Models\WithdrawRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BannerController extends Controller
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
        return view('Admin::banners.index', [
            'banners' => Banner::orderBy('placement')->orderByDesc('priority')->orderByDesc('id')->get(),
            'placements' => Banner::PLACEMENTS,
            ...$this->sidebarCounts(),
        ]);
    }

    public function create(): View
    {
        return view('Admin::banners.form', [
            'banner' => new Banner(['is_active' => true, 'priority' => 0, 'placement' => 'home']),
            'placements' => Banner::PLACEMENTS,
            ...$this->sidebarCounts(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);
        $data['image'] = $this->storeUploadedImage($request);
        $data['is_active'] = $request->boolean('is_active');

        $banner = Banner::create($data);

        Log::channel('admin_security')->info('Banner created', ['ip' => $request->ip(), 'banner_id' => $banner->id]);

        return redirect()->route('admin.banners')->with('success', 'Banner created.');
    }

    public function edit(Banner $banner): View
    {
        return view('Admin::banners.form', [
            'banner' => $banner,
            'placements' => Banner::PLACEMENTS,
            ...$this->sidebarCounts(),
        ]);
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $this->validated($request, false);
        if ($request->hasFile('image')) {
            $data['image'] = $this->storeUploadedImage($request);
        }
        $data['is_active'] = $request->boolean('is_active');

        $banner->update($data);

        Log::channel('admin_security')->info('Banner updated', ['ip' => $request->ip(), 'banner_id' => $banner->id]);

        return redirect()->route('admin.banners')->with('success', 'Banner updated.');
    }

    public function toggleActive(Banner $banner): RedirectResponse
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        return back()->with('success', $banner->is_active ? 'Banner activated.' : 'Banner hidden.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return redirect()->route('admin.banners')->with('success', 'Banner deleted.');
    }

    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'placement' => ['required', 'in:'.implode(',', array_keys(Banner::PLACEMENTS))],
            'title' => ['nullable', 'string', 'max:100'],
            'image' => [$creating ? 'required' : 'nullable', 'image', 'max:4096'],
            'redirect_link' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }

    // Same convention as PlanManagementController: files go straight into
    // public/assets/<dir> (this app is served out of public/ via a custom
    // index.php - no storage:link symlink).
    private function storeUploadedImage(Request $request): string
    {
        $file = $request->file('image');
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $directory = public_path('assets/banners');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->move($directory, $filename);

        return 'assets/banners/'.$filename;
    }
}
