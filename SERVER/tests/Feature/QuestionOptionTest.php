<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class QuestionOptionTest extends TestCase
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

    protected function getOrCreateSubject(): Subject
    {
        return Subject::firstOrCreate(
            ['code' => 'MAT-TEST'],
            ['name' => 'Matematika Uji Coba', 'status' => 'active']
        );
    }

    // ==========================================
    // 1. QUESTIONS TESTS (ADMIN & GURU)
    // ==========================================

    public function test_admin_can_list_questions_with_pagination(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/questions?per_page=10');

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

    public function test_admin_can_view_single_question_with_options(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();
        $teacher = Teacher::first();

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Berapakah 5 + 5?',
            'score_weight' => 2.00,
            'difficulty' => 'easy',
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/questions/'.$question->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $question->id,
                    'content' => 'Berapakah 5 + 5?',
                    'question_type' => 'single_choice',
                ],
            ]);
    }

    public function test_admin_can_create_question(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/questions', [
                'subject_id' => $subject->id,
                'question_type' => 'single_choice',
                'content' => 'Apakah ibu kota Indonesia?',
                'score_weight' => 1.50,
                'difficulty' => 'medium',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'subject_id' => $subject->id,
                    'content' => 'Apakah ibu kota Indonesia?',
                    'question_type' => 'single_choice',
                ],
            ]);

        $this->assertDatabaseHas('questions', ['content' => 'Apakah ibu kota Indonesia?']);
    }

    public function test_guru_can_create_question_and_auto_associate_teacher(): void
    {
        $token = $this->getAuthToken('guru', 'guru123');
        $subject = $this->getOrCreateSubject();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/questions', [
                'subject_id' => $subject->id,
                'question_type' => 'essay',
                'content' => 'Jelaskan pengertian fotosintesis!',
                'score_weight' => 5.00,
                'difficulty' => 'medium',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'content' => 'Jelaskan pengertian fotosintesis!',
                    'question_type' => 'essay',
                ],
            ]);

        $teacher = Teacher::whereHas('user', function ($q) {
            $q->where('username', 'guru');
        })->first();

        $this->assertDatabaseHas('questions', [
            'content' => 'Jelaskan pengertian fotosintesis!',
            'created_by' => $teacher->id,
        ]);
    }

    public function test_guru_can_update_question(): void
    {
        $token = $this->getAuthToken('guru', 'guru123');
        $subject = $this->getOrCreateSubject();
        $teacher = Teacher::first();

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Konten awal sebelum update',
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/questions/'.$question->id, [
                'content' => 'Konten setelah diperbarui oleh guru',
                'score_weight' => 3.00,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'content' => 'Konten setelah diperbarui oleh guru',
                ],
            ]);

        $this->assertEquals('Konten setelah diperbarui oleh guru', $question->fresh()->content);
    }

    public function test_admin_can_delete_unreferenced_question(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();
        $teacher = Teacher::first();

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'essay',
            'content' => 'Soal yang akan dihapus',
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/questions/'.$question->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Butir soal berhasil dihapus',
            ]);

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        // Deleting question must NOT delete subject
        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }

    public function test_question_attached_to_exam_cannot_be_deleted(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();
        $teacher = Teacher::first();

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal terikat ujian',
            'status' => 'active',
        ]);

        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => User::where('username', 'admin')->first()->id,
            'title' => 'Ujian Proteksi Hapus',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(2),
            'status' => 'draft',
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'order_index' => 1,
            'weight' => 1.00,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/questions/'.$question->id);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Tidak dapat menghapus soal karena sudah terhubung dalam paket ujian atau lembar jawaban siswa',
            ]);

        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }

    public function test_peserta_cannot_crud_questions(): void
    {
        $pesertaToken = $this->getAuthToken('peserta', 'peserta123');

        $responseList = $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->getJson('/api/v1/questions');
        $responseList->assertStatus(403);

        $responsePost = $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->postJson('/api/v1/questions', [
                'content' => 'Hacker question',
            ]);
        $responsePost->assertStatus(403);
    }

    public function test_unauthenticated_request_to_questions_returns_401(): void
    {
        $response = $this->getJson('/api/v1/questions');
        $response->assertStatus(401);
    }

    public function test_question_not_found_returns_404(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/questions/999999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Butir soal tidak ditemukan',
            ]);
    }

    public function test_question_validation_fails_for_missing_required_fields(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/questions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject_id', 'question_type', 'content']);
    }

    public function test_question_subject_relationships(): void
    {
        $subject = $this->getOrCreateSubject();
        $teacher = Teacher::first();

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal untuk cek relasi subject',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(Subject::class, $question->subject);
        $this->assertEquals($subject->id, $question->subject->id);
        $this->assertTrue($subject->questions()->where('id', $question->id)->exists());
    }

    // ==========================================
    // 2. QUESTION OPTIONS TESTS
    // ==========================================

    public function test_admin_can_list_options_for_question(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();
        $teacher = Teacher::first();

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal dengan opsi jawaban',
            'status' => 'active',
        ]);

        QuestionOption::create([
            'question_id' => $question->id,
            'option_label' => 'A',
            'content' => 'Pilihan A',
            'is_correct' => false,
        ]);

        QuestionOption::create([
            'question_id' => $question->id,
            'option_label' => 'B',
            'content' => 'Pilihan B Kunci Jawaban',
            'is_correct' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/questions/{$question->id}/options");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items' => [
                        '*' => ['id', 'question_id', 'option_label', 'content', 'is_correct'],
                    ],
                    'pagination',
                ],
            ]);
    }

    public function test_admin_and_guru_can_create_option_with_is_correct(): void
    {
        $token = $this->getAuthToken('guru', 'guru123');
        $subject = $this->getOrCreateSubject();
        $teacher = Teacher::first();

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal untuk tambah opsi',
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/questions/{$question->id}/options", [
                'option_label' => 'A',
                'content' => 'Pilihan Benar',
                'is_correct' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'option_label' => 'A',
                    'content' => 'Pilihan Benar',
                    'is_correct' => true,
                ],
            ]);

        $this->assertDatabaseHas('question_options', [
            'question_id' => $question->id,
            'option_label' => 'A',
            'is_correct' => 1,
        ]);
    }

    public function test_admin_can_update_option(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();
        $teacher = Teacher::first();

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal untuk update opsi',
            'status' => 'active',
        ]);

        $option = QuestionOption::create([
            'question_id' => $question->id,
            'option_label' => 'A',
            'content' => 'Teks awal opsi',
            'is_correct' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/questions/{$question->id}/options/{$option->id}", [
                'content' => 'Teks opsi diperbarui',
                'is_correct' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'content' => 'Teks opsi diperbarui',
                    'is_correct' => true,
                ],
            ]);

        $this->assertTrue($option->fresh()->is_correct);
    }

    public function test_admin_can_delete_option(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();
        $teacher = Teacher::first();

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal untuk hapus opsi',
            'status' => 'active',
        ]);

        $option = QuestionOption::create([
            'question_id' => $question->id,
            'option_label' => 'D',
            'content' => 'Opsi yang akan dihapus',
            'is_correct' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/questions/{$question->id}/options/{$option->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Opsi jawaban berhasil dihapus',
            ]);

        $this->assertDatabaseMissing('question_options', ['id' => $option->id]);
        // Parent question remains intact
        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }

    public function test_peserta_cannot_manage_options(): void
    {
        $pesertaToken = $this->getAuthToken('peserta', 'peserta123');
        $subject = $this->getOrCreateSubject();
        $teacher = Teacher::first();

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal tes peserta',
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->getJson("/api/v1/questions/{$question->id}/options");

        $response->assertStatus(403);
    }

    public function test_option_returns_404_for_invalid_question(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/questions/999999/options');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Butir soal induk tidak ditemukan',
            ]);
    }

    public function test_option_validation_fails_for_empty_fields(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = $this->getOrCreateSubject();
        $teacher = Teacher::first();

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal tes validasi opsi',
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/questions/{$question->id}/options", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['option_label', 'content']);
    }

    public function test_question_options_relationships(): void
    {
        $subject = $this->getOrCreateSubject();
        $teacher = Teacher::first();

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal relasi opsi',
            'status' => 'active',
        ]);

        $option = QuestionOption::create([
            'question_id' => $question->id,
            'option_label' => 'C',
            'content' => 'Pilihan C',
            'is_correct' => false,
        ]);

        $this->assertInstanceOf(Question::class, $option->question);
        $this->assertEquals($question->id, $option->question->id);
        $this->assertTrue($question->options()->where('id', $option->id)->exists());
    }

    // ==========================================
    // 3. ANSWER KEY SECURITY TESTS
    // ==========================================

    public function test_admin_and_guru_can_access_answer_key_for_authoring(): void
    {
        $guruToken = $this->getAuthToken('guru', 'guru123');
        $subject = $this->getOrCreateSubject();
        $teacher = Teacher::first();

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal cek kunci',
            'status' => 'active',
        ]);

        $option = QuestionOption::create([
            'question_id' => $question->id,
            'option_label' => 'A',
            'content' => 'Kunci Rahasia',
            'is_correct' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$guruToken)
            ->getJson("/api/v1/questions/{$question->id}/options/{$option->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_correct', true);
    }

    public function test_peserta_is_blocked_from_all_question_and_option_endpoints(): void
    {
        $pesertaToken = $this->getAuthToken('peserta', 'peserta123');
        $subject = $this->getOrCreateSubject();
        $teacher = Teacher::first();

        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Soal tertutup untuk peserta',
            'status' => 'active',
        ]);

        $option = QuestionOption::create([
            'question_id' => $question->id,
            'option_label' => 'A',
            'content' => 'Pilihan A',
            'is_correct' => true,
        ]);

        // Attempt question list
        $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->getJson('/api/v1/questions')
            ->assertStatus(403);

        // Attempt single question
        $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->getJson("/api/v1/questions/{$question->id}")
            ->assertStatus(403);

        // Attempt options list
        $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->getJson("/api/v1/questions/{$question->id}/options")
            ->assertStatus(403);

        // Attempt single option
        $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->getJson("/api/v1/questions/{$question->id}/options/{$option->id}")
            ->assertStatus(403);
    }
}
