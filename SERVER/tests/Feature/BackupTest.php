<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use DatabaseTransactions;

    protected string $backupDir;
    protected array $createdTestFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupDir = storage_path('app/backups');
        if (! File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->createdTestFiles as $filename) {
            $filePath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
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
    // 1. AUTHENTICATION & ROLE RESTRICTION TESTS
    // ==========================================

    public function test_unauthenticated_requests_return_401(): void
    {
        $this->getJson('/api/v1/backups')->assertStatus(401);
        $this->postJson('/api/v1/backups')->assertStatus(401);
        $this->getJson('/api/v1/backups/test.sql/download')->assertStatus(401);
        $this->deleteJson('/api/v1/backups/test.sql')->assertStatus(401);
    }

    public function test_unauthorized_student_and_teacher_roles_return_403(): void
    {
        // Student token
        $studentToken = $this->getAuthToken('peserta', 'peserta123');

        $this->withHeader('Authorization', 'Bearer '.$studentToken)
            ->getJson('/api/v1/backups')
            ->assertStatus(403);

        $this->withHeader('Authorization', 'Bearer '.$studentToken)
            ->postJson('/api/v1/backups')
            ->assertStatus(403);

        // Teacher token
        $teacherToken = $this->getAuthToken('guru', 'guru123');

        $this->withHeader('Authorization', 'Bearer '.$teacherToken)
            ->getJson('/api/v1/backups')
            ->assertStatus(403);

        $this->withHeader('Authorization', 'Bearer '.$teacherToken)
            ->postJson('/api/v1/backups')
            ->assertStatus(403);
    }

    // ==========================================
    // 2. BACKUP CREATION & RESPONSE FORMAT
    // ==========================================

    public function test_admin_can_create_backup_with_valid_response_format(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/v1/backups', [
                'type' => 'manual',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'filename',
                    'type',
                    'size_bytes',
                    'size_human',
                    'tables_count',
                    'total_records',
                    'checksum_sha256',
                    'created_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'type' => 'manual',
                ],
            ]);

        $filename = $response->json('data.filename');
        $this->assertNotEmpty($filename);
        $this->createdTestFiles[] = $filename;

        // Verify file was written to disk
        $filePath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;
        $this->assertFileExists($filePath);
        $this->assertGreaterThan(0, filesize($filePath));

        // Verify SQL contents header and foreign key checks
        $content = file_get_contents($filePath);
        $this->assertStringContainsString('CBT System Database Backup Snapshot', $content);
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS = 0;', $content);
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS = 1;', $content);
    }

    public function test_backup_response_does_not_leak_database_credentials_or_secrets(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/v1/backups', [
                'type' => 'pre_exam',
            ]);

        $response->assertStatus(201);
        $this->createdTestFiles[] = $response->json('data.filename');

        $rawResponse = json_encode($response->json());
        $this->assertStringNotContainsString('DB_PASSWORD', $rawResponse);
        $this->assertStringNotContainsString('DB_USERNAME', $rawResponse);
        $this->assertStringNotContainsString('cbt_v1_dev', $rawResponse);
        $this->assertStringNotContainsString('APP_KEY', $rawResponse);
        $this->assertStringNotContainsString('password', $rawResponse);
    }

    // ==========================================
    // 3. LIST, DOWNLOAD & DELETE TESTS
    // ==========================================

    public function test_admin_can_list_download_and_delete_backup(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        // Create a backup first
        $createResponse = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/v1/backups', ['type' => 'post_exam']);
        $createResponse->assertStatus(201);

        $filename = $createResponse->json('data.filename');
        $this->createdTestFiles[] = $filename;

        // List backups
        $listResponse = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/backups');

        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_backups',
                    'items' => [
                        '*' => [
                            'filename',
                            'size_bytes',
                            'size_human',
                            'created_at',
                            'checksum_sha256',
                        ],
                    ],
                ],
            ]);

        $items = collect($listResponse->json('data.items'));
        $this->assertTrue($items->contains('filename', $filename));

        // Download backup
        $downloadResponse = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson("/api/v1/backups/{$filename}/download");

        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('content-type', 'application/sql');

        // Delete backup
        $deleteResponse = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->deleteJson("/api/v1/backups/{$filename}");

        $deleteResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['filename' => $filename],
            ]);

        // Verify file no longer exists
        $filePath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;
        $this->assertFileDoesNotExist($filePath);
    }

    // ==========================================
    // 4. SECURITY: PATH TRAVERSAL & INJECTION
    // ==========================================

    public function test_path_traversal_attempts_are_blocked(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $traversalPayloads = [
            '..%2F..%2F.env',
            '../../.env',
            '..%5C..%5Cwindows%5Csystem32',
            'foo/bar.sql',
            'valid.sql%00.php',
            'arbitrary_file.txt',
        ];

        foreach ($traversalPayloads as $payload) {
            $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
                ->getJson("/api/v1/backups/{$payload}/download");

            // Either 400 Bad Request or 404 Not Found, never 200 or 500
            $this->assertContains($response->status(), [400, 404]);

            $delResponse = $this->withHeader('Authorization', 'Bearer '.$adminToken)
                ->deleteJson("/api/v1/backups/{$payload}");
            $this->assertContains($delResponse->status(), [400, 404]);
        }
    }

    public function test_command_injection_payload_in_type_is_rejected(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $maliciousPayloads = [
            'manual; rm -rf /',
            'manual && dir',
            'manual | whoami',
            '`reboot`',
            '$(calc)',
        ];

        foreach ($maliciousPayloads as $payload) {
            $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
                ->postJson('/api/v1/backups', [
                    'type' => $payload,
                ]);

            $response->assertStatus(422); // Validation error (not in allowed enum)
        }
    }

    public function test_nonexistent_backup_file_returns_404(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/backups/cbt_backup_nonexistent_20260101_000000_12345678.sql/download')
            ->assertStatus(404);

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->deleteJson('/api/v1/backups/cbt_backup_nonexistent_20260101_000000_12345678.sql')
            ->assertStatus(404);
    }

    // ==========================================
    // 5. DATA CONSISTENCY & INTEGRITY
    // ==========================================

    public function test_database_remains_consistent_after_backup(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        // Record initial counts
        $usersCountBefore = DB::table('users')->count();
        $rolesCountBefore = DB::table('roles')->count();
        $examsCountBefore = DB::table('exams')->count();

        // Perform backup
        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/v1/backups', ['type' => 'manual']);

        $response->assertStatus(201);
        $this->createdTestFiles[] = $response->json('data.filename');

        // Assert counts remain identical
        $this->assertEquals($usersCountBefore, DB::table('users')->count());
        $this->assertEquals($rolesCountBefore, DB::table('roles')->count());
        $this->assertEquals($examsCountBefore, DB::table('exams')->count());
    }
}
