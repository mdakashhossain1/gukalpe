<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_backups_page_requires_manage_backups_permission(): void
    {
        // Only super_admin has manage_backups.
        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'finance'])
            ->get(route('admin.backups'))->assertForbidden();

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->get(route('admin.backups'))->assertOk()->assertSee('Database backups');
    }

    public function test_creating_a_backup_is_forbidden_without_permission(): void
    {
        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'support'])
            ->post(route('admin.backups.create'))->assertForbidden();
    }

    public function test_restore_is_forbidden_without_permission(): void
    {
        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'manager'])
            ->post(route('admin.backups.restore', 'backup-20260101-000000.sqlite'))->assertForbidden();
    }
}
