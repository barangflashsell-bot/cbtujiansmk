<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Classes;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TimerTest extends TestCase
{
    use DatabaseTransactions;

    protected function getAuthToken(string $username, string $password): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);

        return $response->json('data.token');
    }

    protected function setupTimerScenario(): array
    {
        $subject = Subject::firstOrCreate(
            ['code' => 'TIMER-TEST'],
            ['name' => 'Mata Pelajaran Timer Test', 'status' => 'active']
        );

        $admin = User::where('username', 'admin')->first();

        $schoolClass = Classes::firstOrCreate(
            ['name' => 'Kelas 9-Timer'],
            ['level' => '9', 'academic_year' => '2025/2026', 'status' => 'active']
        );

        // Active exam with 60 minutes duration
        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'title' => 'Ujian Timer Authoritative Test',
            'duration_minutes' => 60,
            'passing_score' => 75.00,
            'status' => 'published',
            'start_window' => now()->subHours(2),
            'end_window' => now()->addHours(2),
        ]);

        // Student 1 (main participant)
        $studentUser1 = User::where('username', 'peserta')->first();
        $student1 = $studentUser1->student;
        $student1->update(['class_id' => $schoolClass->id]);

        // Student 2 (for IDOR tests)
        $studentUser2 = User::where('username', 'peserta_two')->first();
        if (! $studentUser2) {
            $studentRole = $studentUser1->role_id;
            $studentUser2 = User::create([
                'role_id' => $studentRole,
                'username' => 'peserta_timer_two',
                'name' => 'Peserta Timer Dua',
                'password' => 'password123',
            ]);
            $student2 = Student::create([
                'user_id' => $studentUser2->id,
                'class_id' => $schoolClass->id,
                'nis' => '99887766',
                'nisn' => '9988776655',
                'gender' => 'L',
            ]);
        } else {
            $student2 = $studentUser2->student;
        }

        // Enroll Student 1
        ExamParticipant::firstOrCreate([
            'exam_id' => $exam->id,
            'student_id' => $student1->id,
        ]);

        return [
            'admin' => $admin,
            'exam' => $exam,
            'student1' => $student1,
            'studentUser1' => $studentUser1,
            'student2' => $student2,
            'studentUser2' => $studentUser2,
        ];
    }

    // ==========================================
    // 1. AUTHENTICATION & IDOR TESTS
    // ==========================================

    public function test_unauthenticated_requests_return_401(): void
    {
        $this->getJson('/api/v1/attempts/1/timer')->assertStatus(401);
        $this->getJson('/api/v1/exams/1/timer')->assertStatus(401);
        $this->postJson('/api/v1/attempts/1/extend-time')->assertStatus(401);
    }

    public function test_unauthorized_student_cannot_view_other_student_attempt_timer_returns_403(): void
    {
        $scenario = $this->setupTimerScenario();

        // Attempt belongs to Student 1
        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
            'status' => 'in_progress',
        ]);

        // Student 2 tries to access Student 1's timer
        $token2 = $this->getAuthToken($scenario['studentUser2']->username, 'password123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token2)
            ->getJson("/api/v1/attempts/{$attempt->id}/timer");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses timer sesi ujian ini',
            ]);
    }

    // ==========================================
    // 2. ATTEMPT STATES: NOT_STARTED, IN_PROGRESS, SUBMITTED, TIMEOUT
    // ==========================================

    public function test_not_started_state_returns_full_duration_and_can_continue(): void
    {
        $scenario = $this->setupTimerScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/exams/{$scenario['exam']->id}/timer");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'exam_id',
                    'student_id',
                    'status',
                    'server_time',
                    'duration_seconds',
                    'remaining_seconds',
                    'elapsed_seconds',
                    'is_expired',
                    'can_continue',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'not_started',
                    'duration_seconds' => 3600,
                    'remaining_seconds' => 3600,
                    'elapsed_seconds' => 0,
                    'is_expired' => false,
                    'can_continue' => true,
                ],
            ]);
    }

    public function test_in_progress_state_calculates_remaining_time_server_side(): void
    {
        $scenario = $this->setupTimerScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        // Student started 15 minutes ago, ends in 45 minutes
        $startedAt = now()->subMinutes(15);
        $endsAt = now()->addMinutes(45);

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => $startedAt,
            'ends_at' => $endsAt,
            'status' => 'in_progress',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/attempts/{$attempt->id}/timer");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'attempt_id' => $attempt->id,
                    'status' => 'in_progress',
                    'duration_seconds' => 3600,
                    'is_expired' => false,
                    'can_continue' => true,
                ],
            ]);

        // Remaining seconds should be around 2700s (45 minutes +- a few seconds)
        $remaining = $response->json('data.remaining_seconds');
        $this->assertGreaterThan(2680, $remaining);
        $this->assertLessThanOrEqual(2705, $remaining);
    }

    public function test_submitted_state_cannot_continue_and_remaining_is_zero(): void
    {
        $scenario = $this->setupTimerScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(30),
            'submitted_at' => now()->subMinutes(5),
            'status' => 'submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/attempts/{$attempt->id}/timer");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'submitted',
                    'remaining_seconds' => 0,
                    'is_expired' => true,
                    'can_continue' => false,
                ],
            ]);
    }

    public function test_timeout_state_cannot_continue_and_auto_transitions(): void
    {
        $scenario = $this->setupTimerScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        // Sesi yang ends_at-nya sudah lewat 5 menit lalu
        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(65),
            'ends_at' => now()->subMinutes(5),
            'status' => 'in_progress', // Database status initially in_progress
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/attempts/{$attempt->id}/timer");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'timeout',
                    'remaining_seconds' => 0,
                    'is_expired' => true,
                    'can_continue' => false,
                ],
            ]);

        // Verify attempt row in DB updated to timeout
        $this->assertEquals('timeout', $attempt->fresh()->status);
    }

    // ==========================================
    // 3. SECURITY: CLIENT OVERRIDE IMMUNITY
    // ==========================================

    public function test_client_cannot_override_remaining_time_or_timestamps(): void
    {
        $scenario = $this->setupTimerScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $endsAt = now()->addMinutes(30);
        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(30),
            'ends_at' => $endsAt,
            'status' => 'in_progress',
        ]);

        // Client attempts to pass fake remaining_seconds and future ends_at in query or body
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->json('GET', "/api/v1/attempts/{$attempt->id}/timer?remaining_seconds=999999&ends_at=2099-01-01", [
                'remaining_seconds' => 999999,
                'ends_at' => '2099-01-01',
                'extra_time' => 5000,
            ]);

        $response->assertStatus(200);

        // Remaining seconds must still be around 1800 (30 minutes), not 999999
        $remaining = $response->json('data.remaining_seconds');
        $this->assertLessThanOrEqual(1805, $remaining);
        $this->assertGreaterThan(1780, $remaining);

        // Database remains uncorrupted
        $freshAttempt = $attempt->fresh();
        $this->assertEquals($endsAt->toDateTimeString(), $freshAttempt->ends_at->toDateTimeString());
    }

    public function test_repeated_requests_do_not_extend_time(): void
    {
        $scenario = $this->setupTimerScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
            'status' => 'in_progress',
        ]);

        // Request 1
        $res1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/attempts/{$attempt->id}/timer");
        $rem1 = $res1->json('data.remaining_seconds');

        // Request 2 (replay)
        $res2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/attempts/{$attempt->id}/timer");
        $rem2 = $res2->json('data.remaining_seconds');

        // rem2 must be <= rem1, never greater
        $this->assertLessThanOrEqual($rem1, $rem2);
    }

    // ==========================================
    // 4. ADMIN & TEACHER EXTENSION & AUDIT LOG
    // ==========================================

    public function test_admin_and_teacher_can_extend_time_and_audit_log_is_written(): void
    {
        $scenario = $this->setupTimerScenario();

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(40),
            'ends_at' => now()->addMinutes(20),
            'status' => 'in_progress',
        ]);

        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson("/api/v1/attempts/{$attempt->id}/extend-time", [
                'added_minutes' => 15,
                'reason' => 'Listrik ruang kelas sempat padam',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Waktu ujian berhasil diperpanjang',
                'data' => [
                    'status' => 'in_progress',
                    'can_continue' => true,
                ],
            ]);

        // Remaining seconds should now be around 35 minutes (~2100s)
        $remaining = $response->json('data.remaining_seconds');
        $this->assertGreaterThan(2080, $remaining);

        // Verify audit log in activity_logs
        $log = ActivityLog::where('action', 'EXTEND_TIME')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals('TIMER', $log->module);
        $this->assertStringContainsString('Listrik ruang kelas sempat padam', $log->details);
        $this->assertStringContainsString('15', $log->details);
    }

    public function test_student_cannot_extend_time_returns_403(): void
    {
        $scenario = $this->setupTimerScenario();

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
            'status' => 'in_progress',
        ]);

        $studentToken = $this->getAuthToken('peserta', 'peserta123');

        $response = $this->withHeader('Authorization', 'Bearer '.$studentToken)
            ->postJson("/api/v1/attempts/{$attempt->id}/extend-time", [
                'added_minutes' => 30,
            ]);

        $response->assertStatus(403);
    }

    public function test_boundary_window_caps_ends_at_for_late_student(): void
    {
        $scenario = $this->setupTimerScenario();

        // Create an exam ending in 20 minutes, duration 60 minutes
        $exam = Exam::create([
            'subject_id' => $scenario['exam']->subject_id,
            'created_by' => $scenario['admin']->id,
            'title' => 'Ujian Akhir Window Capped Test',
            'duration_minutes' => 60,
            'status' => 'published',
            'start_window' => now()->subMinutes(60),
            'end_window' => now()->addMinutes(20),
        ]);

        ExamParticipant::create([
            'exam_id' => $exam->id,
            'student_id' => $scenario['student1']->id,
        ]);

        $token = $this->getAuthToken('peserta', 'peserta123');

        // Start exam
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$exam->id}/start");

        $response->assertStatus(200);

        // Remaining seconds must be capped at around 20 minutes (1200s), NOT 60 minutes (3600s)
        $remaining = $response->json('data.remaining_seconds');
        $this->assertLessThanOrEqual(1205, $remaining);
        $this->assertGreaterThan(1180, $remaining);

        // Query timer endpoint
        $timerResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/exams/{$exam->id}/timer");

        $timerResponse->assertStatus(200);
        $timerRemaining = $timerResponse->json('data.remaining_seconds');
        $this->assertLessThanOrEqual(1205, $timerRemaining);
        $this->assertGreaterThan(1180, $timerRemaining);
    }
}
