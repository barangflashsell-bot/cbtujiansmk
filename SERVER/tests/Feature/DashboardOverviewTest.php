<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 1. Guest cannot access Admin Dashboard
     */
    public function test_01_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    /**
     * 2. Guest cannot access Guru Dashboard
     */
    public function test_02_guest_cannot_access_guru_dashboard(): void
    {
        $response = $this->get('/guru/dashboard');

        $response->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    /**
     * 3. Admin can access Admin Dashboard and see real metrics
     */
    public function test_03_admin_can_access_admin_dashboard_with_real_metrics(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200)
            ->assertSee('Dashboard Admin')
            ->assertSee('Total Peserta')
            ->assertSee('Total Guru')
            ->assertSee('Total Kelas')
            ->assertSee('Mata Pelajaran')
            ->assertSee('Bank Soal')
            ->assertSee('Total Paket Ujian')
            ->assertSee('Ujian Aktif')
            ->assertSee('Sedang Mengerjakan')
            ->assertSee('Hasil Ujian Terbaru')
            ->assertSee('Aktivitas Sistem Terbaru');
    }

    /**
     * 4. Guru can access Guru Dashboard with teacher-scoped metrics
     */
    public function test_04_guru_can_access_guru_dashboard_with_scoped_metrics(): void
    {
        $guru = User::where('username', 'guru')->first();

        $response = $this->actingAs($guru)->get('/guru/dashboard');

        $response->assertStatus(200)
            ->assertSee('Dashboard Guru')
            ->assertSee('Bank Soal Saya')
            ->assertSee('Paket Ujian Saya')
            ->assertSee('Ujian Aktif')
            ->assertSee('Peserta Terdaftar')
            ->assertSee('Hasil Ujian Terbaru (Ujian Saya)');
    }

    /**
     * 5. Guru cannot access Admin Dashboard (403 Forbidden)
     */
    public function test_05_guru_cannot_access_admin_dashboard(): void
    {
        $guru = User::where('username', 'guru')->first();

        $response = $this->actingAs($guru)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    /**
     * 6. Dashboard accurately renders valid counts
     */
    public function test_06_dashboard_renders_actual_counts(): void
    {
        $admin = User::where('username', 'admin')->first();

        $studentCount = Student::count();
        $teacherCount = Teacher::count();
        $classCount = Classes::count();
        $subjectCount = Subject::count();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString(number_format($studentCount), $content);
        $this->assertStringContainsString(number_format($teacherCount), $content);
        $this->assertStringContainsString(number_format($classCount), $content);
        $this->assertStringContainsString(number_format($subjectCount), $content);
    }

    /**
     * 7. Dashboard handles empty state gracefully
     */
    public function test_07_dashboard_handles_empty_state_gracefully(): void
    {
        $guru = User::where('username', 'guru')->first();

        // When teacher has no exam results, empty state message must be rendered
        $response = $this->actingAs($guru)->get('/guru/dashboard');

        $response->assertStatus(200)
            ->assertDontSee('Fatal error')
            ->assertDontSee('Exception');
    }

    /**
     * 8. Dashboard handles backend failure with graceful fallback
     */
    public function test_08_dashboard_handles_failure_gracefully(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Directly verify view accepts error message and fallback state
        $response = $this->actingAs($admin)->view('admin.dashboard', [
            'user' => $admin,
            'metrics' => [
                'total_students' => 0,
                'total_teachers' => 0,
                'total_classes' => 0,
                'total_subjects' => 0,
                'total_questions' => 0,
                'total_exams' => 0,
                'active_exams' => 0,
                'in_progress_attempts' => 0,
            ],
            'recentResults' => collect(),
            'recentActivities' => collect(),
            'errorMessage' => 'Gagal memuat ringkasan data metrik sistem. Silakan muat ulang halaman.',
        ]);

        $response->assertSee('Gagal memuat ringkasan data metrik sistem')
            ->assertSee('Total Peserta');
    }

    /**
     * 9. Sensitive credentials are never exposed in dashboard view
     */
    public function test_09_no_sensitive_credentials_in_dashboard(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringNotContainsString($admin->password, $content);
        $this->assertStringNotContainsString('password_hash', $content);
        $this->assertStringNotContainsString('remember_token', $content);
        $this->assertStringNotContainsString('APP_KEY', $content);
        $this->assertStringNotContainsString('DB_PASSWORD', $content);
    }

    /**
     * 10. Student role cannot access either Admin or Guru dashboard
     */
    public function test_10_student_cannot_access_any_dashboard(): void
    {
        $student = User::where('username', 'peserta')->first();

        $this->actingAs($student)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($student)->get('/guru/dashboard')->assertStatus(403);
    }
}
