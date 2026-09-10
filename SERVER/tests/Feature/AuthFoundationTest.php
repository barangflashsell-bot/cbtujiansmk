<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthFoundationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 1. Login berhasil sebagai ADMIN
     */
    public function test_admin_can_login_successfully(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'user' => [
                        'username' => 'admin',
                        'role' => 'admin',
                    ],
                    'token_type' => 'Bearer',
                ],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'username', 'name', 'role'],
                    'token',
                    'token_type',
                ],
            ]);
    }

    /**
     * 2. Login berhasil sebagai GURU
     */
    public function test_guru_can_login_successfully(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'guru',
            'password' => 'guru123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'guru',
                        'role' => 'teacher',
                    ],
                    'token_type' => 'Bearer',
                ],
            ]);
    }

    /**
     * 3. Login berhasil sebagai PESERTA
     */
    public function test_peserta_can_login_successfully(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'peserta',
            'password' => 'peserta123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'peserta',
                        'role' => 'student',
                    ],
                    'token_type' => 'Bearer',
                ],
            ]);
    }

    /**
     * 4. Login gagal karena username salah
     */
    public function test_login_fails_with_invalid_username(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'nonexistent_user',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Username atau password salah',
                'errors' => null,
            ]);
    }

    /**
     * 5. Login gagal karena password salah
     */
    public function test_login_fails_with_invalid_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Username atau password salah',
                'errors' => null,
            ]);
    }

    /**
     * 6. Login validation gagal jika username/password kosong
     */
    public function test_login_validation_fails_when_fields_are_missing(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation error',
            ])
            ->assertJsonValidationErrors(['username', 'password']);
    }

    /**
     * 7. Token dapat digunakan untuk GET /api/v1/auth/me
     */
    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'admin',
                        'role' => 'admin',
                    ],
                ],
            ]);
    }

    /**
     * 8. GET /api/v1/auth/me tanpa token = 401
     */
    public function test_unauthenticated_request_to_me_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated or invalid token',
                'errors' => null,
            ]);
    }

    /**
     * 9. Token invalid = 401
     */
    public function test_invalid_token_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-sample')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated or invalid token',
                'errors' => null,
            ]);
    }

    /**
     * 10 & 11. Logout berhasil dan token setelah logout tidak dapat digunakan lagi
     */
    public function test_logout_revokes_token_successfully(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $token = $loginResponse->json('data.token');

        // Logout request
        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout berhasil',
            ]);

        // Clear in-memory auth guard cache so the next request re-authenticates from the database
        app('auth')->forgetGuards();

        // Attempting to access me endpoint with revoked token must fail with 401
        $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(401);
    }

    /**
     * 12. Role ADMIN dapat melewati middleware ADMIN
     */
    public function test_admin_can_access_admin_only_route(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/test/admin-only');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Akses terotorisasi khusus ADMIN',
            ]);
    }

    /**
     * 13. GURU ditolak dari route ADMIN = 403
     */
    public function test_guru_is_forbidden_from_admin_route(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'guru',
            'password' => 'guru123',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/test/admin-only');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses resource ini.',
            ]);
    }

    /**
     * 14. PESERTA ditolak dari route ADMIN = 403
     */
    public function test_peserta_is_forbidden_from_admin_route(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'peserta',
            'password' => 'peserta123',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/test/admin-only');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses resource ini.',
            ]);
    }

    /**
     * 15. User authenticated tetapi role tidak sesuai = 403
     */
    public function test_admin_is_forbidden_from_peserta_route(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/test/peserta-only');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses resource ini.',
            ]);
    }

    /**
     * 16 & 17. Password dan hash password tidak pernah muncul dalam response
     */
    public function test_passwords_and_hashes_never_appear_in_responses(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $content = $loginResponse->getContent();
        $this->assertStringNotContainsString('admin123', $content);
        $this->assertStringNotContainsString('password_hash', $content);
        $this->assertArrayNotHasKey('password', $loginResponse->json('data.user'));

        $token = $loginResponse->json('data.token');

        $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $meContent = $meResponse->getContent();
        $this->assertStringNotContainsString('admin123', $meContent);
        $this->assertStringNotContainsString('password_hash', $meContent);
        $this->assertArrayNotHasKey('password', $meResponse->json('data.user'));
    }

    /**
     * 18. Secret/environment configuration tidak bocor
     */
    public function test_auth_responses_do_not_leak_secrets_or_env_vars(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $content = $loginResponse->getContent();
        $this->assertStringNotContainsString(config('app.key'), $content);
        $this->assertStringNotContainsString('DB_', $content);
        $this->assertStringNotContainsString('cbt_v1_dev', $content);
    }

    /**
     * 19. Login rate limit triggers 429 after exceeding limit
     */
    public function test_login_rate_limiting_triggers_too_many_requests(): void
    {
        // Route has throttle:10,1 (10 attempts per minute)
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'username' => 'admin',
                'password' => 'wrong-pass-attempt',
            ]);
        }

        // The 11th request must be rate-limited with HTTP 429
        $throttledResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'wrong-pass-attempt',
        ]);

        $throttledResponse->assertStatus(429);
    }
}
