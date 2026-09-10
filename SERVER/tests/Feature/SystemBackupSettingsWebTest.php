<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SystemBackupSettingsWebTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $guru;
    protected User $student;
    protected string $backupDir;
    protected string $settingsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->first();

        $teacherRole = Role::where('name', 'teacher')->first();
        $this->guru = User::firstOrCreate(
            ['username' => 'guru_sys_test'],
            [
                'name' => 'Guru System Test',
                'email' => 'guru_sys@test.com',
                'password' => bcrypt('password'),
                'role_id' => $teacherRole->id,
                'is_active' => true,
            ]
        );

        $studentRole = Role::where('name', 'student')->first();
        $this->student = User::firstOrCreate(
            ['username' => 'std_sys_test'],
            [
                'name' => 'Student System Test',
                'email' => 'student_sys@test.com',
                'password' => bcrypt('password'),
                'role_id' => $studentRole->id,
                'is_active' => true,
            ]
        );

        $this->backupDir = storage_path('app/backups');
        if (! File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }

        $this->settingsPath = storage_path('app/settings.json');
    }

    /**
     * 1. Guest redirected to login for system routes
     */
    public function test_01_guest_redirected_to_login(): void
    {
        $this->get('/admin/backups')->assertRedirect(route('login'));
        $this->get('/admin/settings')->assertRedirect(route('login'));
        $this->get('/admin/activity-logs')->assertRedirect(route('login'));
    }

    /**
     * 2. Guru forbidden from system maintenance routes
     */
    public function test_02_guru_forbidden_from_system_routes(): void
    {
        $this->actingAs($this->guru)->get('/admin/backups')->assertStatus(403);
        $this->actingAs($this->guru)->post('/admin/backups', [])->assertStatus(403);
        $this->actingAs($this->guru)->get('/admin/settings')->assertStatus(403);
        $this->actingAs($this->guru)->put('/admin/settings', [])->assertStatus(403);
        $this->actingAs($this->guru)->get('/admin/activity-logs')->assertStatus(403);
    }

    /**
     * 3. Student forbidden from system maintenance routes
     */
    public function test_03_student_forbidden_from_system_routes(): void
    {
        $this->actingAs($this->student)->get('/admin/backups')->assertStatus(403);
        $this->actingAs($this->student)->post('/admin/backups', [])->assertStatus(403);
        $this->actingAs($this->student)->get('/admin/settings')->assertStatus(403);
        $this->actingAs($this->student)->put('/admin/settings', [])->assertStatus(403);
        $this->actingAs($this->student)->get('/admin/activity-logs')->assertStatus(403);
    }

    /**
     * 4. Admin can view backups, create backup, download and delete backup
     */
    public function test_04_admin_backup_lifecycle(): void
    {
        // View index
        $this->actingAs($this->admin)->get('/admin/backups')
            ->assertStatus(200)
            ->assertSee('Backup &amp; Database Snapshot', false)
            ->assertSee('Pencadangan Database MySQL Lokal');

        // Create backup
        $response = $this->actingAs($this->admin)->post('/admin/backups', [
            'type' => 'manual',
        ]);
        $response->assertRedirect(route('admin.backups.index'));
        $response->assertSessionHas('success');

        // Verify file created in storage/app/backups
        $files = File::files($this->backupDir);
        $sqlFiles = array_filter($files, fn ($f) => $f->getExtension() === 'sql');
        $this->assertNotEmpty($sqlFiles);

        $latestFile = end($sqlFiles);
        $filename = $latestFile->getFilename();

        // Admin can download backup
        $downloadResponse = $this->actingAs($this->admin)->get("/admin/backups/{$filename}/download");
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('Content-Type', 'application/sql');

        // Path traversal / invalid filename blocked with 400
        $this->actingAs($this->admin)->get('/admin/backups/invalid;payload.sql/download')
            ->assertStatus(400);

        // Delete backup
        $deleteResponse = $this->actingAs($this->admin)->delete("/admin/backups/{$filename}");
        $deleteResponse->assertRedirect(route('admin.backups.index'));
        $deleteResponse->assertSessionHas('success');
        $this->assertFalse(File::exists($latestFile->getRealPath()));

        // Audit log created for backup operations
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'module' => 'BACKUP',
            'action' => 'CREATE_BACKUP',
        ]);
    }

    /**
     * 5. Admin can view and update system settings
     */
    public function test_05_admin_settings_view_and_update(): void
    {
        // View settings page
        $this->actingAs($this->admin)->get('/admin/settings')
            ->assertStatus(200)
            ->assertSee('Pengaturan Sistem Server')
            ->assertSee('Identitas Sekolah & Tahun Ajaran', false);

        // Validation test: missing required fields
        $this->actingAs($this->admin)->put('/admin/settings', [
            'school_name' => '',
            'server_port' => 70000, // exceeds 65535
        ])->assertSessionHasErrors(['school_name', 'academic_year', 'server_port']);

        // Update settings successfully
        $updateResponse = $this->actingAs($this->admin)->put('/admin/settings', [
            'school_name' => 'SMK Negeri 1 CBT Test',
            'school_address' => 'Jl. Pengujian Web No. 4E',
            'academic_year' => '2025/2026',
            'app_name' => 'CBT Local System Pro',
            'server_port' => 8080,
            'auto_token_release' => 1,
            'token_refresh_minutes' => 30,
            'allow_student_review' => 1,
            'logo_url' => '/images/logo-test.png',
        ]);

        $updateResponse->assertRedirect(route('admin.settings.index'));
        $updateResponse->assertSessionHas('success');

        // Verify settings saved in file
        $this->assertTrue(File::exists($this->settingsPath));
        $saved = json_decode(File::get($this->settingsPath), true);
        $this->assertEquals('SMK Negeri 1 CBT Test', $saved['school_name']);
        $this->assertEquals(8080, $saved['server_port']);
        $this->assertTrue($saved['auto_token_release']);
        $this->assertTrue($saved['allow_student_review']);

        // Audit log created for update settings
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'module' => 'SETTINGS',
            'action' => 'UPDATE_SETTINGS',
        ]);
    }

    /**
     * 6. Admin can view activity logs with filters (read-only)
     */
    public function test_06_admin_activity_logs_view_and_filter(): void
    {
        // Seed a sample activity log
        ActivityLog::create([
            'user_id' => $this->admin->id,
            'action' => 'TEST_AUDIT_LOG_ACTION',
            'module' => 'AUTH',
            'ip_address' => '127.0.0.1',
            'details' => json_encode(['test_key' => 'test_value']),
            'created_at' => now(),
        ]);

        // View index
        $this->actingAs($this->admin)->get('/admin/activity-logs')
            ->assertStatus(200)
            ->assertSee('Log Aktivitas &amp; Audit Trail', false)
            ->assertSee('TEST_AUDIT_LOG_ACTION');

        // Filter by module
        $this->actingAs($this->admin)->get('/admin/activity-logs?module=AUTH')
            ->assertStatus(200)
            ->assertSee('TEST_AUDIT_LOG_ACTION');

        // Filter by search query
        $this->actingAs($this->admin)->get('/admin/activity-logs?search=TEST_AUDIT')
            ->assertStatus(200)
            ->assertSee('TEST_AUDIT_LOG_ACTION');
    }
}
