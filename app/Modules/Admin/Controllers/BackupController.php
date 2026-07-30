<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DepositRequest;
use App\Models\WithdrawRequest;
use App\Support\AdminRoles;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Database backups (plan.md Section 40). SQLite-native: a backup is a copy of
 * the DB file. Create / download / delete are safe; restore overwrites the
 * live DB and is therefore Super-Admin-only and snapshots the current DB first.
 * (For a MySQL deployment this would shell out to mysqldump instead — guarded
 * with a clear message until then.)
 */
class BackupController extends Controller
{
    private function guard(): void
    {
        abort_unless(AdminRoles::currentCan('manage_backups'), 403);
    }

    private function backupDir(): string
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    private function dbPath(): string
    {
        return (string) DB::connection()->getConfig('database');
    }

    // Only allow our own generated filenames — blocks path traversal.
    private function safePath(string $file): ?string
    {
        if (! preg_match('/^[a-zA-Z0-9\-]+\.sqlite$/', $file)) {
            return null;
        }
        $path = $this->backupDir().'/'.$file;

        return is_file($path) ? $path : null;
    }

    public function index(): View
    {
        $this->guard();

        $backups = collect(glob($this->backupDir().'/*.sqlite') ?: [])
            ->map(fn ($p) => [
                'name' => basename($p),
                'size' => filesize($p),
                'modified' => Carbon::createFromTimestamp(filemtime($p)),
            ])
            ->sortByDesc('modified')->values();

        return view('Admin::backups', [
            'backups' => $backups,
            'isSqlite' => $this->isSqlite(),
            'dbSize' => ($this->isSqlite() && is_file($this->dbPath())) ? filesize($this->dbPath()) : null,
            'canRestore' => AdminRoles::current() === 'super_admin',
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function create(): RedirectResponse
    {
        $this->guard();

        if (! $this->isSqlite()) {
            return back()->with('error', 'Automatic backup currently supports SQLite only. For MySQL, use mysqldump.');
        }

        $name = 'backup-'.now()->format('Ymd-His').'.sqlite';
        copy($this->dbPath(), $this->backupDir().'/'.$name);

        Log::channel('admin_security')->info('DB backup created', ['file' => $name]);

        return back()->with('success', "Backup created: {$name}");
    }

    public function download(string $file): BinaryFileResponse|RedirectResponse
    {
        $this->guard();

        $path = $this->safePath($file);

        return $path
            ? response()->download($path)
            : back()->with('error', 'Backup not found.');
    }

    public function destroy(string $file): RedirectResponse
    {
        $this->guard();

        if ($path = $this->safePath($file)) {
            unlink($path);
        }

        return back()->with('success', 'Backup deleted.');
    }

    public function restore(string $file): RedirectResponse
    {
        $this->guard();
        abort_unless(AdminRoles::current() === 'super_admin', 403);

        if (! $this->isSqlite()) {
            return back()->with('error', 'Restore currently supports SQLite only.');
        }

        $path = $this->safePath($file);
        if (! $path) {
            return back()->with('error', 'Backup not found.');
        }

        // Snapshot the current DB before overwriting, so a bad restore is undoable.
        $snapshot = 'pre-restore-'.now()->format('Ymd-His').'.sqlite';
        copy($this->dbPath(), $this->backupDir().'/'.$snapshot);

        DB::disconnect();
        copy($path, $this->dbPath());

        Log::channel('admin_security')->warning('DB restored from backup', ['file' => $file, 'snapshot' => $snapshot]);

        return back()->with('success', "Database restored from {$file}. A pre-restore snapshot ({$snapshot}) was saved in case you need to roll back.");
    }
}
