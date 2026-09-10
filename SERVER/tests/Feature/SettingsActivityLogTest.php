<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SettingsActivityLogTest extends TestCase
{
    use DatabaseTransactions;

    protected string $settingsPath;
    protected ?string $originalSettingsContent = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settingsPath = storage_path('app/settings.json');

        if (File::exists($this->settingsPath)) {
            $this->originalSettingsContent = File::get($this->settingsPath);
        }
    }

    protected function tearDown(): void
    {
        if ($this->originalSettingsContent !== null) {
            File::put($this->settingsPath, $this->originalSettingsContent);
        } elseif (File::exists($this->settingsPath)) {
            File::delete($this->settingsPath);
        }

        parent::tearDown();
    }

    protected function getAuthToken(string $username, string $password): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);

        return $response->json('data.token');
    }

    // ==========================================
    // SETTINGS TESTS (1-10)
    // ==========================================

    /**
     * 1. Unauthenticated settings access rejected (401)
     */
    public function test_01_unauthenticated_settings_access_rejected(): void
    {
        $this->getJson('/api/v1/settings')
            ->assertStatus(401);

        $this->putJson('/api/v1/settings', ['school_name' => 'Hacker School'])
            ->assertStatus(401);
    }

    /**
     * 2. Unauthorized participant settings access rejected (403)
     */
    public function test_02_unauthorized_participant_settings_access_rejected(): void
    {
        $studentToken = $this->getAuthToken('peserta', 'peserta123');

        $this->withHeader('Authorization', 'Bearer '.$studentToken)
            ->getJson('/api/v1/settings')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * 3. Authorized admin settings access succeeds (200)
     */
    public function test_03_authorized_admin_settings_access_succeeds(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'school_name',
                    'academic_year',
                    'app_name',
                    'server_port',
                    'auto_token_release',
                    'token_refresh_minutes',
                    'allow_student_review',
                ],
            ]);
    }

    /**
     * 4. Invalid setting rejected with validation error (422)
     */
    public function test_04_invalid_setting_rejected(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        // server_port must be between 80 and 65535, token_refresh_minutes between 5 and 180
        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->putJson('/api/v1/settings', [
                'server_port' => 70000,
                'token_refresh_minutes' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * 5. Arbitrary/unknown setting rejected when whitelist is enforced (422)
     */
    public function test_05_arbitrary_unknown_setting_rejected(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->putJson('/api/v1/settings', [
                'school_name' => 'SMK Valid',
                'malicious_key' => 'drop database',
                'unknown_field' => true,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * 6. Sensitive settings are not leaked in show response
     */
    public function test_06_sensitive_settings_are_not_leaked(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/settings');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('db_password', $data);
        $this->assertArrayNotHasKey('app_key', $data);
        $this->assertArrayNotHasKey('secret', $data);
        $this->assertArrayNotHasKey('jwt_secret', $data);
    }

    /**
     * 7. Unauthorized settings update rejected (403 for student and teacher)
     */
    public function test_07_unauthorized_settings_update_rejected(): void
    {
        $studentToken = $this->getAuthToken('peserta', 'peserta123');
        $teacherToken = $this->getAuthToken('guru', 'guru123');

        // Student attempt
        $this->withHeader('Authorization', 'Bearer '.$studentToken)
            ->putJson('/api/v1/settings', [
                'school_name' => 'Hacked School',
            ])
            ->assertStatus(403);

        // Teacher attempt
        $this->withHeader('Authorization', 'Bearer '.$teacherToken)
            ->putJson('/api/v1/settings', [
                'school_name' => 'Teacher School',
            ])
            ->assertStatus(403);
    }

    /**
     * 8. Authorized settings update works
     */
    public function test_08_authorized_settings_update_works(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->putJson('/api/v1/settings', [
                'school_name' => 'SMK Bina Harapan Test',
                'server_port' => 8080,
                'auto_token_release' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'school_name' => 'SMK Bina Harapan Test',
                    'server_port' => 8080,
                    'auto_token_release' => true,
                ],
            ]);

        // Verify persisted on next GET
        $getRes = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/settings');

        $getRes->assertStatus(200)
            ->assertJson([
                'data' => [
                    'school_name' => 'SMK Bina Harapan Test',
                    'server_port' => 8080,
                ],
            ]);
    }

    /**
     * 9. Response format is correct and follows ApiResponse standard
     */
    public function test_09_response_format_correct(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);

        $this->assertIsBool($response->json('success'));
        $this->assertIsString($response->json('message'));
        $this->assertIsArray($response->json('data'));
    }

    /**
     * 10. HTTP status codes are correct
     */
    public function test_10_http_status_correct(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        // Success -> 200
        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/settings')
            ->assertStatus(200);

        // Validation fail -> 422
        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->putJson('/api/v1/settings', ['server_port' => 'bukan_angka'])
            ->assertStatus(422);

        // Empty body -> 422
        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->putJson('/api/v1/settings', [])
            ->assertStatus(422);
    }

    // ==========================================
    // ACTIVITY LOGS TESTS (11-20)
    // ==========================================

    /**
     * 11. Activity log is created for supported administrative action (e.g. settings update)
     */
    public function test_11_activity_log_is_created_for_supported_administrative_action(): void
    {
        $admin = User::where('username', 'admin')->first();
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->putJson('/api/v1/settings', [
                'school_name' => 'SMK Logging Test',
            ])
            ->assertStatus(200);

        $log = ActivityLog::where('user_id', $admin->id)
            ->where('action', 'UPDATE_SETTINGS')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('SETTINGS', $log->module);
        $this->assertStringContainsString('school_name', $log->details);
    }

    /**
     * 12. Actor comes strictly from authenticated user
     */
    public function test_12_actor_comes_from_authenticated_user(): void
    {
        $student = User::where('username', 'peserta')->first();
        $studentToken = $this->getAuthToken('peserta', 'peserta123');

        $response = $this->withHeader('Authorization', 'Bearer '.$studentToken)
            ->postJson('/api/v1/activity-logs/event', [
                'action' => 'WINDOW_FOCUS_LOST',
                'details' => 'Peserta berpindah window aplikasi',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'actor_id' => $student->id,
                    'action' => 'WINDOW_FOCUS_LOST',
                ],
            ]);

        $log = ActivityLog::find($response->json('data.id'));
        $this->assertEquals($student->id, $log->user_id);
    }

    /**
     * 13. Client cannot spoof actor ID via request body
     */
    public function test_13_client_cannot_spoof_actor(): void
    {
        $student = User::where('username', 'peserta')->first();
        $admin = User::where('username', 'admin')->first();
        $studentToken = $this->getAuthToken('peserta', 'peserta123');

        // Student tries to submit spoofed user_id / actor_id of admin
        $response = $this->withHeader('Authorization', 'Bearer '.$studentToken)
            ->postJson('/api/v1/activity-logs/event', [
                'action' => 'DEVICE_LOCKED',
                'user_id' => $admin->id,
                'actor_id' => $admin->id,
                'details' => 'Spoof attempt',
            ]);

        $response->assertStatus(201);
        $logId = $response->json('data.id');

        $log = ActivityLog::find($logId);
        // Actor must remain the authentic student ID, not the spoofed admin ID
        $this->assertEquals($student->id, $log->user_id);
        $this->assertNotEquals($admin->id, $log->user_id);
    }

    /**
     * 14. Participant cannot access administrative logs (403)
     */
    public function test_14_participant_cannot_access_administrative_logs(): void
    {
        $studentToken = $this->getAuthToken('peserta', 'peserta123');

        $this->withHeader('Authorization', 'Bearer '.$studentToken)
            ->getJson('/api/v1/activity-logs')
            ->assertStatus(403);
    }

    /**
     * 15. Unauthorized role (teacher) rejected from viewing audit logs (403)
     */
    public function test_15_unauthorized_role_rejected(): void
    {
        $teacherToken = $this->getAuthToken('guru', 'guru123');

        $this->withHeader('Authorization', 'Bearer '.$teacherToken)
            ->getJson('/api/v1/activity-logs')
            ->assertStatus(403);
    }

    /**
     * 16. Authorized role (admin) can access logs (200)
     */
    public function test_16_authorized_role_can_access_logs(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/activity-logs');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items',
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                    ],
                ],
            ]);
    }

    /**
     * 17. Pagination works properly
     */
    public function test_17_pagination_works(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/activity-logs?per_page=5&page=1');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => 5,
                    ],
                ],
            ]);

        $this->assertLessThanOrEqual(5, count($response->json('data.items')));
    }

    /**
     * 18. Invalid pagination parameters rejected (422)
     */
    public function test_18_invalid_pagination_rejected(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        // per_page exceeds max 100
        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/activity-logs?per_page=500')
            ->assertStatus(422);

        // per_page is negative or zero
        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/activity-logs?per_page=0')
            ->assertStatus(422);
    }

    /**
     * 19. Sensitive data is not exposed in activity log response
     */
    public function test_19_sensitive_data_is_not_exposed(): void
    {
        $admin = User::where('username', 'admin')->first();
        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'SAMPLE_EVENT',
            'module' => 'TEST',
            'ip_address' => '127.0.0.1',
            'details' => json_encode(['safe_info' => 123]),
            'created_at' => now(),
        ]);

        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/activity-logs');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Must not expose user password hashes, remember tokens, etc.
        $this->assertStringNotContainsString('password', $content);
        $this->assertStringNotContainsString('remember_token', $content);
    }

    /**
     * 20. Log query filter cannot access arbitrary fields or tables
     */
    public function test_20_log_query_cannot_access_arbitrary_fields(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        // Whitelisted filters: module, action, user_id
        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/activity-logs?module=SETTINGS&action=UPDATE_SETTINGS');

        $response->assertStatus(200);

        // Safe query filtering does not crash with unexpected query parameters
        $resSafe = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/activity-logs?random_table=users&order_by=id');

        $resSafe->assertStatus(200);
    }

    // ==========================================
    // SECURITY TESTS (21-24)
    // ==========================================

    /**
     * 21. No IDOR: Participants cannot view or filter other users' activity logs
     */
    public function test_21_no_idor(): void
    {
        $studentToken = $this->getAuthToken('peserta', 'peserta123');
        $admin = User::where('username', 'admin')->first();

        $this->withHeader('Authorization', 'Bearer '.$studentToken)
            ->getJson('/api/v1/activity-logs?user_id='.$admin->id)
            ->assertStatus(403);
    }

    /**
     * 22. No mass assignment vulnerability
     */
    public function test_22_no_mass_assignment_vulnerability(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        // Attempting to inject system or database attributes via settings
        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->putJson('/api/v1/settings', [
                'school_name' => 'SMK Valid',
                'is_admin' => true,
                'role_id' => 1,
                'id' => 999,
            ]);

        // Must be rejected with 422 because unknown keys are rejected
        $response->assertStatus(422);
    }

    /**
     * 23. No arbitrary filesystem or config access
     */
    public function test_23_no_arbitrary_filesystem_or_config_access(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->putJson('/api/v1/settings', [
                'school_name' => 'SMK Valid',
                'path' => '../../.env',
                'config_file' => 'config/app.php',
            ]);

        // Unknown keys rejected
        $response->assertStatus(422);
    }

    /**
     * 24. No credential leakage
     */
    public function test_24_no_credential_leakage(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/settings');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertArrayNotHasKey('api_key', $json['data']);
        $this->assertArrayNotHasKey('app_secret', $json['data']);
        $this->assertArrayNotHasKey('db_username', $json['data']);
        $this->assertArrayNotHasKey('db_password', $json['data']);
    }
}
