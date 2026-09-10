<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WebAuthLayoutTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 1. Guest can view login page (200 OK)
     */
    public function test_01_guest_can_view_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('CBT Local Server')
            ->assertSee('Username')
            ->assertSee('Password')
            ->assertSee('Masuk ke Dashboard');
    }

    /**
     * 2. Invalid credentials are rejected with validation error
     */
    public function test_02_invalid_credentials_are_rejected(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'wrongpassword123',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    /**
     * 3. Valid admin credentials can login and redirect to admin dashboard
     */
    public function test_03_valid_admin_can_login_and_redirect_to_admin_dashboard(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $response->assertStatus(302)
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
        $this->assertEquals('admin', auth()->user()->username);
    }

    /**
     * 4. Guest cannot access admin dashboard (redirected to login)
     */
    public function test_04_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    /**
     * 5. Guest cannot access guru dashboard (redirected to login)
     */
    public function test_05_guest_cannot_access_guru_dashboard(): void
    {
        $response = $this->get('/guru/dashboard');

        $response->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    /**
     * 6. Authenticated admin can access admin dashboard shell
     */
    public function test_06_authenticated_admin_can_access_admin_dashboard(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200)
            ->assertSee('Dashboard Admin')
            ->assertSee('CBT SERVER')
            ->assertSee('Administrator');
    }

    /**
     * 7. Authenticated guru can access guru dashboard shell
     */
    public function test_07_authenticated_guru_can_access_guru_dashboard(): void
    {
        $guru = User::where('username', 'guru')->first();

        $response = $this->actingAs($guru)->get('/guru/dashboard');

        $response->assertStatus(200)
            ->assertSee('Dashboard Guru')
            ->assertSee('CBT SERVER')
            ->assertSee('Guru');
    }

    /**
     * 8. Guru cannot access admin dashboard (403 forbidden)
     */
    public function test_08_guru_cannot_access_admin_dashboard(): void
    {
        $guru = User::where('username', 'guru')->first();

        $response = $this->actingAs($guru)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    /**
     * 9. Logout terminates authentication and session
     */
    public function test_09_logout_terminates_authentication(): void
    {
        $admin = User::where('username', 'admin')->first();

        $this->actingAs($admin);
        $this->assertAuthenticated();

        $response = $this->post('/logout');

        $response->assertStatus(302)
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /**
     * 10. Sensitive credentials are never exposed in view responses
     */
    public function test_10_sensitive_credentials_are_not_exposed_in_views(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringNotContainsString($admin->password, $content);
        $this->assertStringNotContainsString('password_hash', $content);
        $this->assertStringNotContainsString('app_key', $content);
        $this->assertStringNotContainsString('remember_token', $content);
    }

    /**
     * 11. Student cannot login via web login form
     */
    public function test_11_student_cannot_login_to_web_dashboard(): void
    {
        $response = $this->post('/login', [
            'username' => 'peserta',
            'password' => 'peserta123',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    /**
     * 12. Root URL shows login for guest or redirects to dashboard for authenticated users
     */
    public function test_12_root_url_redirects_appropriately(): void
    {
        // Guest -> 200 OK renders login form
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Masuk ke Dashboard');

        // Admin -> redirects to admin.dashboard
        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin)->get('/')->assertRedirect(route('admin.dashboard'));

        // Guru -> redirects to guru.dashboard
        $guru = User::where('username', 'guru')->first();
        $this->actingAs($guru)->get('/')->assertRedirect(route('guru.dashboard'));
    }
}
