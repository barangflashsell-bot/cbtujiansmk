<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExamParticipantTest extends TestCase
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

    protected function createTestExam(): Exam
    {
        $subject = Subject::firstOrCreate(
            ['code' => 'PART-TEST'],
            ['name' => 'Mata Pelajaran Peserta Ujian', 'status' => 'active']
        );

        $admin = User::where('username', 'admin')->first();

        return Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'title' => 'Ujian Peserta Test',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(2),
            'status' => 'published',
        ]);
    }

    // ==========================================
    // 1. PARTICIPANT ENROLLMENT TESTS
    // ==========================================

    public function test_admin_can_list_participants_with_pagination(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $exam = $this->createTestExam();
        $student = Student::first();

        ExamParticipant::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'allow_retest' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/exams/{$exam->id}/participants?per_page=10");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items' => [
                        '*' => [
                            'id',
                            'exam_id',
                            'student_id',
                            'allow_retest',
                            'student' => ['id', 'nis', 'nisn', 'user'],
                        ],
                    ],
                    'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
                ],
            ]);
    }

    public function test_admin_can_enroll_single_student(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $exam = $this->createTestExam();
        $student = Student::first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$exam->id}/participants", [
                'student_id' => $student->id,
                'allow_retest' => false,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                    'allow_retest' => false,
                ],
            ]);

        $this->assertDatabaseHas('exam_participants', [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_guru_can_enroll_student(): void
    {
        $token = $this->getAuthToken('guru', 'guru123');
        $exam = $this->createTestExam();
        $student = Student::first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$exam->id}/participants", [
                'student_id' => $student->id,
                'allow_retest' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                    'allow_retest' => true,
                ],
            ]);
    }

    public function test_duplicate_student_enrollment_is_rejected(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $exam = $this->createTestExam();
        $student = Student::first();

        ExamParticipant::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'allow_retest' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$exam->id}/participants", [
                'student_id' => $student->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_id']);
    }

    public function test_admin_can_enroll_entire_class(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $exam = $this->createTestExam();
        $class = Classes::first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$exam->id}/participants", [
                'class_id' => $class->id,
                'allow_retest' => false,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertGreaterThan(0, $response->json('data.enrolled_count'));
    }

    public function test_admin_can_view_enrollment_detail(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $exam = $this->createTestExam();
        $student = Student::first();

        $participant = ExamParticipant::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'allow_retest' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/exams/{$exam->id}/participants/{$participant->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $participant->id,
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                ],
            ]);
    }

    public function test_admin_can_update_enrollment(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $exam = $this->createTestExam();
        $student = Student::first();

        $participant = ExamParticipant::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'allow_retest' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/exams/{$exam->id}/participants/{$participant->id}", [
                'allow_retest' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'allow_retest' => true,
                ],
            ]);

        $this->assertTrue($participant->fresh()->allow_retest);
    }

    public function test_admin_can_cancel_enrollment_without_attempts(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $exam = $this->createTestExam();
        $student = Student::first();

        $participant = ExamParticipant::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'allow_retest' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/exams/{$exam->id}/participants/{$participant->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pendaftaran peserta ujian berhasil dibatalkan',
            ]);

        $this->assertDatabaseMissing('exam_participants', ['id' => $participant->id]);
    }

    public function test_cancel_enrollment_with_existing_attempt_is_prevented(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $exam = $this->createTestExam();
        $student = Student::first();

        $participant = ExamParticipant::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'allow_retest' => false,
        ]);

        // Attach an exam attempt
        ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(60),
            'status' => 'in_progress',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/exams/{$exam->id}/participants/{$participant->id}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Tidak dapat membatalkan pendaftaran karena siswa sudah memiliki sesi/riwayat pengerjaan ujian',
            ]);

        $this->assertDatabaseHas('exam_participants', ['id' => $participant->id]);
    }

    // ==========================================
    // 2. SECURITY & VALIDATION TESTS
    // ==========================================

    public function test_peserta_cannot_manage_participants(): void
    {
        $pesertaToken = $this->getAuthToken('peserta', 'peserta123');
        $exam = $this->createTestExam();
        $student = Student::first();

        $response = $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->postJson("/api/v1/exams/{$exam->id}/participants", [
                'student_id' => $student->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/exams/1/participants');
        $response->assertStatus(401);
    }

    public function test_invalid_exam_returns_404(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/exams/999999/participants');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Paket ujian tidak ditemukan',
            ]);
    }

    public function test_invalid_student_id_fails_validation(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $exam = $this->createTestExam();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$exam->id}/participants", [
                'student_id' => 999999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_id']);
    }

    public function test_sensitive_data_not_leaked_in_participant_response(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $exam = $this->createTestExam();
        $student = Student::first();

        ExamParticipant::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'allow_retest' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/exams/{$exam->id}/participants");

        $response->assertStatus(200);

        $userData = $response->json('data.items.0.student.user');
        $this->assertNotNull($userData);
        $this->assertArrayNotHasKey('password', $userData);
        $this->assertArrayNotHasKey('remember_token', $userData);
    }

    public function test_relationships_between_exam_participant_and_student(): void
    {
        $exam = $this->createTestExam();
        $student = Student::first();

        $participant = ExamParticipant::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'allow_retest' => false,
        ]);

        $this->assertInstanceOf(Exam::class, $participant->exam);
        $this->assertEquals($exam->id, $participant->exam->id);

        $this->assertInstanceOf(Student::class, $participant->student);
        $this->assertEquals($student->id, $participant->student->id);

        $this->assertTrue($exam->participants()->where('id', $participant->id)->exists());
        $this->assertTrue($student->examParticipants()->where('id', $participant->id)->exists());
    }
}
