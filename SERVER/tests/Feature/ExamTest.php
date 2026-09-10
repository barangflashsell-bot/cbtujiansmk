<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExamTest extends TestCase
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

    protected function getOrCreateSubject(string $code = 'MAT-EXAM', string $name = 'Matematika Ujian'): Subject
    {
        return Subject::firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'status' => 'active']
        );
    }

    // ==========================================
    // 1. EXAMS CRUD TESTS
    // ==========================================

    public function test_admin_can_list_exams_with_pagination(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/exams?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items',
                    'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
                ],
            ]);
    }

    public function test_admin_can_create_exam(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/exams', [
                'subject_id' => $subject->id,
                'title' => 'Penilaian Akhir Semester 1',
                'description' => 'Ujian Semester Ganjil Matematika',
                'duration_minutes' => 90,
                'passing_score' => 75.00,
                'token' => 'PAS123',
                'start_window' => now()->addHour()->toDateTimeString(),
                'end_window' => now()->addHours(5)->toDateTimeString(),
                'status' => 'published',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Penilaian Akhir Semester 1',
                    'duration_minutes' => 90,
                    'status' => 'published',
                ],
            ]);

        $this->assertDatabaseHas('exams', ['title' => 'Penilaian Akhir Semester 1']);
    }

    public function test_guru_can_create_exam(): void
    {
        $token = $this->getAuthToken('guru', 'guru123');
        $subject = $this->getOrCreateSubject();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/exams', [
                'subject_id' => $subject->id,
                'title' => 'Kuis Harian Aljabar',
                'duration_minutes' => 45,
                'start_window' => now()->toDateTimeString(),
                'end_window' => now()->addHours(2)->toDateTimeString(),
                'status' => 'draft',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Kuis Harian Aljabar',
                    'duration_minutes' => 45,
                ],
            ]);

        $this->assertDatabaseHas('exams', ['title' => 'Kuis Harian Aljabar']);
    }

    public function test_admin_can_view_single_exam(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();
        $user = User::where('username', 'admin')->first();

        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'title' => 'Ujian Tengah Semester',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(3),
            'status' => 'published',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/exams/'.$exam->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $exam->id,
                    'title' => 'Ujian Tengah Semester',
                ],
            ]);
    }

    public function test_admin_can_update_exam(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();
        $user = User::where('username', 'admin')->first();

        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'title' => 'Ujian Sebelum Update',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(3),
            'status' => 'draft',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/exams/'.$exam->id, [
                'title' => 'Ujian Setelah Update',
                'duration_minutes' => 75,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Ujian Setelah Update',
                    'duration_minutes' => 75,
                ],
            ]);

        $this->assertEquals('Ujian Setelah Update', $exam->fresh()->title);
    }

    public function test_admin_can_delete_exam_without_attempts(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();
        $user = User::where('username', 'admin')->first();

        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'title' => 'Ujian Yang Akan Dihapus',
            'duration_minutes' => 30,
            'start_window' => now(),
            'end_window' => now()->addHours(1),
            'status' => 'draft',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/exams/'.$exam->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Paket ujian berhasil dihapus',
            ]);

        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    public function test_exam_validation_fails_for_invalid_input(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/exams', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject_id', 'title', 'duration_minutes', 'start_window', 'end_window']);
    }

    public function test_exam_not_found_returns_404(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/exams/999999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Paket ujian tidak ditemukan',
            ]);
    }

    public function test_peserta_cannot_modify_exams(): void
    {
        $pesertaToken = $this->getAuthToken('peserta', 'peserta123');
        $subject = $this->getOrCreateSubject();

        $response = $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->postJson('/api/v1/exams', [
                'subject_id' => $subject->id,
                'title' => 'Hacker Exam',
                'duration_minutes' => 60,
                'start_window' => now()->toDateTimeString(),
                'end_window' => now()->addHour()->toDateTimeString(),
            ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_request_to_exams_returns_401(): void
    {
        $response = $this->getJson('/api/v1/exams');
        $response->assertStatus(401);
    }

    // ==========================================
    // 2. EXAM QUESTIONS TESTS
    // ==========================================

    public function test_admin_can_attach_question_to_exam(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();
        $user = User::where('username', 'admin')->first();
        $teacher = Teacher::first();

        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'title' => 'Ujian Lampiran Soal',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(2),
            'status' => 'draft',
        ]);

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal untuk paket ujian',
            'score_weight' => 2.50,
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$exam->id}/questions", [
                'question_id' => $question->id,
                'order_index' => 1,
                'weight' => 2.50,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'exam_id' => $exam->id,
                    'question_id' => $question->id,
                    'order_index' => 1,
                ],
            ]);

        $this->assertDatabaseHas('exam_questions', [
            'exam_id' => $exam->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_duplicate_question_in_exam_is_rejected(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();
        $user = User::where('username', 'admin')->first();
        $teacher = Teacher::first();

        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'title' => 'Ujian Cek Duplikasi',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(2),
            'status' => 'draft',
        ]);

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal Kembar Ujian',
            'status' => 'active',
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'order_index' => 1,
            'weight' => 1.00,
        ]);

        // Attempt attaching duplicate question
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$exam->id}/questions", [
                'question_id' => $question->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question_id']);
    }

    public function test_attaching_question_with_mismatched_subject_is_rejected(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subjectA = $this->getOrCreateSubject('IPA-01', 'Ilmu Pengetahuan Alam');
        $subjectB = $this->getOrCreateSubject('IPS-01', 'Ilmu Pengetahuan Sosial');
        $user = User::where('username', 'admin')->first();
        $teacher = Teacher::first();

        $exam = Exam::create([
            'subject_id' => $subjectA->id,
            'created_by' => $user->id,
            'title' => 'Ujian IPA',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(2),
            'status' => 'draft',
        ]);

        $questionIPS = Question::create([
            'subject_id' => $subjectB->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal IPS Salah Kamar',
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$exam->id}/questions", [
                'question_id' => $questionIPS->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question_id']);
    }

    public function test_admin_can_update_exam_question_order_and_weight(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();
        $user = User::where('username', 'admin')->first();
        $teacher = Teacher::first();

        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'title' => 'Ujian Urutan Soal',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(2),
            'status' => 'draft',
        ]);

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal update urutan',
            'status' => 'active',
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'order_index' => 1,
            'weight' => 1.00,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/exams/{$exam->id}/questions/{$question->id}", [
                'order_index' => 5,
                'weight' => 3.50,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'order_index' => 5,
                    'weight' => 3.50,
                ],
            ]);
    }

    public function test_admin_can_detach_question_from_exam(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();
        $user = User::where('username', 'admin')->first();
        $teacher = Teacher::first();

        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'title' => 'Ujian Hapus Butir',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(2),
            'status' => 'draft',
        ]);

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal yang dilepas dari ujian',
            'status' => 'active',
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'order_index' => 1,
            'weight' => 1.00,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/exams/{$exam->id}/questions/{$question->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Butir soal berhasil dihapus dari paket ujian',
            ]);

        $this->assertDatabaseMissing('exam_questions', [
            'exam_id' => $exam->id,
            'question_id' => $question->id,
        ]);
        // Original question in bank soal remains safe
        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }

    // ==========================================
    // 3. ANSWER KEY PROTECTION & SECURITY
    // ==========================================

    public function test_student_viewing_exam_questions_does_not_receive_answer_keys(): void
    {
        $pesertaToken = $this->getAuthToken('peserta', 'peserta123');
        $subject = $this->getOrCreateSubject();
        $user = User::where('username', 'admin')->first();
        $teacher = Teacher::first();

        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'title' => 'Ujian Publik Siswa',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(2),
            'status' => 'active',
        ]);

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Pertanyaan rahasia kunci',
            'status' => 'active',
        ]);

        QuestionOption::create([
            'question_id' => $question->id,
            'option_label' => 'A',
            'content' => 'Pilihan Rahasia',
            'is_correct' => true,
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'order_index' => 1,
            'weight' => 1.00,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->getJson("/api/v1/exams/{$exam->id}/questions");

        $response->assertStatus(200);

        // Verify is_correct is NOT leaked to student
        $options = $response->json('data.items.0.options');
        $this->assertNotEmpty($options);
        foreach ($options as $opt) {
            $this->assertArrayNotHasKey('is_correct', $opt);
        }
    }
}
