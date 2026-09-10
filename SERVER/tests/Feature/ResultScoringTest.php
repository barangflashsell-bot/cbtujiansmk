<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ResultScoringTest extends TestCase
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

    protected function setupGradingScenario(): array
    {
        $subject = Subject::firstOrCreate(
            ['code' => 'SCORE-TEST'],
            ['name' => 'Mata Pelajaran Scoring Test', 'status' => 'active']
        );

        $admin = User::where('username', 'admin')->first();

        // Create exam with show_result = false initially
        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'title' => 'Ujian Scoring Engine Test',
            'duration_minutes' => 60,
            'show_result' => false,
            'start_window' => now()->subHour(),
            'end_window' => now()->addHour(),
            'status' => 'published',
        ]);

        // Q1 (Weight 40)
        $q1 = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'question_type' => 'single_choice',
            'content' => 'Soal 1',
        ]);
        $q1_optA = QuestionOption::create([
            'question_id' => $q1->id,
            'option_label' => 'A',
            'content' => 'Jawaban Benar Q1',
            'is_correct' => true,
        ]);
        $q1_optB = QuestionOption::create([
            'question_id' => $q1->id,
            'option_label' => 'B',
            'content' => 'Jawaban Salah Q1',
            'is_correct' => false,
        ]);
        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $q1->id,
            'order_index' => 1,
            'weight' => 40.00,
        ]);

        // Q2 (Weight 30)
        $q2 = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'question_type' => 'single_choice',
            'content' => 'Soal 2',
        ]);
        $q2_optA = QuestionOption::create([
            'question_id' => $q2->id,
            'option_label' => 'A',
            'content' => 'Jawaban Benar Q2',
            'is_correct' => true,
        ]);
        $q2_optB = QuestionOption::create([
            'question_id' => $q2->id,
            'option_label' => 'B',
            'content' => 'Jawaban Salah Q2',
            'is_correct' => false,
        ]);
        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $q2->id,
            'order_index' => 2,
            'weight' => 30.00,
        ]);

        // Q3 (Weight 30)
        $q3 = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'question_type' => 'single_choice',
            'content' => 'Soal 3',
        ]);
        $q3_optA = QuestionOption::create([
            'question_id' => $q3->id,
            'option_label' => 'A',
            'content' => 'Jawaban Benar Q3',
            'is_correct' => true,
        ]);
        $q3_optB = QuestionOption::create([
            'question_id' => $q3->id,
            'option_label' => 'B',
            'content' => 'Jawaban Salah Q3',
            'is_correct' => false,
        ]);
        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $q3->id,
            'order_index' => 3,
            'weight' => 30.00,
        ]);

        // Student 1 (peserta)
        $studentUser = User::where('username', 'peserta')->first();
        $student1 = $studentUser->student;

        // Student 2 (create if not exists)
        $student2 = Student::where('id', '!=', $student1->id)->first();
        if (! $student2) {
            $otherUser = User::create([
                'role_id' => $studentUser->role_id,
                'username' => 'peserta_scoring_two',
                'name' => 'Peserta Scoring Dua',
                'password' => 'password123',
            ]);
            $student2 = Student::create([
                'user_id' => $otherUser->id,
                'class_id' => $student1->class_id,
                'nis' => '77776666',
                'nisn' => '7777666655',
                'gender' => 'L',
            ]);
        }

        // Enroll Student 1
        ExamParticipant::create([
            'exam_id' => $exam->id,
            'student_id' => $student1->id,
            'allow_retest' => false,
        ]);

        return [
            'exam' => $exam,
            'student1' => $student1,
            'student2' => $student2,
            'q1' => $q1,
            'q1_optA' => $q1_optA,
            'q1_optB' => $q1_optB,
            'q2' => $q2,
            'q2_optA' => $q2_optA,
            'q2_optB' => $q2_optB,
            'q3' => $q3,
            'q3_optA' => $q3_optA,
            'q3_optB' => $q3_optB,
        ];
    }

    // ==========================================
    // 1. AUTHENTICATION & VALIDATION TESTS
    // ==========================================

    public function test_unauthenticated_requests_return_401(): void
    {
        $response = $this->getJson('/api/v1/results');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/results/1');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/attempts/1/grade');
        $response->assertStatus(401);

        $response = $this->patchJson('/api/v1/results/1/publish', ['is_published' => true]);
        $response->assertStatus(401);
    }

    public function test_cannot_grade_active_in_progress_attempt_before_time_ends(): void
    {
        $scenario = $this->setupGradingScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(45), // Still has 45 minutes
            'status' => 'in_progress',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/grade");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Sesi ujian masih berlangsung dan belum dapat dinilai',
            ]);
    }

    // ==========================================
    // 2. ACCURATE SCORING & CALCULATION TESTS
    // ==========================================

    public function test_submitted_attempt_is_graded_accurately(): void
    {
        $scenario = $this->setupGradingScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(30),
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        // Student answers Q1 correctly (optA)
        Answer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $scenario['q1']->id,
            'selected_option_id' => $scenario['q1_optA']->id,
            'selected_option' => 'A',
        ]);

        // Student answers Q2 wrongly (optB)
        Answer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $scenario['q2']->id,
            'selected_option_id' => $scenario['q2_optB']->id,
            'selected_option' => 'B',
        ]);

        // Q3 is left unanswered (no answer record)

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/grade");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'exam_id' => $scenario['exam']->id,
                    'student_id' => $scenario['student1']->id,
                    'correct_count' => 1,
                    'wrong_count' => 1,
                    'unanswered_count' => 1,
                    'mc_score' => 40.00,
                    'score' => 40.00,
                    'final_score' => 40.00,
                    'status' => 'completed',
                ],
            ]);

        // Verify database persistence
        $this->assertDatabaseHas('results', [
            'attempt_id' => $attempt->id,
            'correct_count' => 1,
            'wrong_count' => 1,
            'unanswered_count' => 1,
            'final_score' => 40.00,
        ]);
    }

    public function test_timeout_attempt_can_be_graded_accurately(): void
    {
        $scenario = $this->setupGradingScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subHours(2),
            'ends_at' => now()->subHour(),
            'status' => 'timeout',
        ]);

        // Student answered Q1 correctly and Q2 correctly
        Answer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $scenario['q1']->id,
            'selected_option_id' => $scenario['q1_optA']->id,
        ]);
        Answer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $scenario['q2']->id,
            'selected_option_id' => $scenario['q2_optA']->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/grade");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'correct_count' => 2,
                    'wrong_count' => 0,
                    'unanswered_count' => 1,
                    'final_score' => 70.00, // 40 + 30 = 70 out of 100
                ],
            ]);
    }

    public function test_duplicate_grading_is_idempotent_and_does_not_create_duplicate_rows(): void
    {
        $scenario = $this->setupGradingScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subHour(),
            'ends_at' => now(),
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        // First grading
        $response1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/grade");
        $resultId1 = $response1->json('data.id');

        // Second grading (Retry)
        $response2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/grade");
        $resultId2 = $response2->json('data.id');

        $this->assertEquals($resultId1, $resultId2);

        // Ensure database row count is strictly 1
        $count = Result::where('attempt_id', $attempt->id)->count();
        $this->assertEquals(1, $count);
    }

    // ==========================================
    // 3. SERVER SOURCE OF TRUTH & ANTI-INJECTION
    // ==========================================

    public function test_client_cannot_inject_score_or_counts(): void
    {
        $scenario = $this->setupGradingScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subHour(),
            'ends_at' => now(),
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        // Malicious client attempts to inject fake 100 score and 100 correct answers
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/grade", [
                'score' => 100.00,
                'final_score' => 100.00,
                'correct_count' => 100,
                'wrong_count' => 0,
                'unanswered_count' => 0,
            ]);

        $response->assertStatus(200);

        // Server MUST calculate 0.00 because no correct answers exist in database
        $this->assertEquals(0.00, (float) $response->json('data.final_score'));
        $this->assertEquals(0, $response->json('data.correct_count'));
        $this->assertEquals(3, $response->json('data.unanswered_count'));
    }

    // ==========================================
    // 4. AUTHORIZATION & DATA PROTECTION TESTS
    // ==========================================

    public function test_student_cannot_view_unpublished_result(): void
    {
        $scenario = $this->setupGradingScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subHour(),
            'ends_at' => now(),
            'status' => 'submitted',
        ]);

        $result = Result::create([
            'attempt_id' => $attempt->id,
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'correct_count' => 1,
            'final_score' => 40.00,
            'is_published' => false, // UNPUBLISHED
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/results/{$result->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Hasil ujian belum dipublikasikan oleh guru/admin',
            ]);
    }

    public function test_student_can_view_own_published_result(): void
    {
        $scenario = $this->setupGradingScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subHour(),
            'ends_at' => now(),
            'status' => 'submitted',
        ]);

        $result = Result::create([
            'attempt_id' => $attempt->id,
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'correct_count' => 1,
            'final_score' => 40.00,
            'is_published' => true, // PUBLISHED
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/results/{$result->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $result->id,
                    'final_score' => '40.00',
                ],
            ]);
    }

    public function test_student_cannot_view_another_students_result(): void
    {
        $scenario = $this->setupGradingScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student2']->id, // Other student
            'started_at' => now()->subHour(),
            'ends_at' => now(),
            'status' => 'submitted',
        ]);

        $result = Result::create([
            'attempt_id' => $attempt->id,
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student2']->id,
            'correct_count' => 1,
            'final_score' => 40.00,
            'is_published' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/results/{$result->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk melihat hasil ujian ini',
            ]);
    }

    public function test_admin_and_teacher_can_view_all_results_and_publish(): void
    {
        $scenario = $this->setupGradingScenario();
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subHour(),
            'ends_at' => now(),
            'status' => 'submitted',
        ]);

        $result = Result::create([
            'attempt_id' => $attempt->id,
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'correct_count' => 1,
            'final_score' => 40.00,
            'is_published' => false,
        ]);

        // Admin view results list
        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/results?exam_id='.$scenario['exam']->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items',
                    'pagination',
                ],
            ]);

        // Admin publish result
        $publishResponse = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->patchJson("/api/v1/results/{$result->id}/publish", [
                'is_published' => true,
            ]);

        $publishResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_published' => true,
                ],
            ]);

        $this->assertTrue($result->fresh()->is_published);
    }

    public function test_submit_attempt_automatically_generates_accurate_result(): void
    {
        $scenario = $this->setupGradingScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(60),
            'status' => 'in_progress',
        ]);

        // Answer Q1 correctly
        Answer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $scenario['q1']->id,
            'selected_option_id' => $scenario['q1_optA']->id,
        ]);

        // Submit attempt
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/submit");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'SUBMITTED',
                ],
            ]);

        // Check result was automatically created
        $this->assertDatabaseHas('results', [
            'attempt_id' => $attempt->id,
            'correct_count' => 1,
            'wrong_count' => 0,
            'unanswered_count' => 2,
            'final_score' => 40.00,
        ]);
    }

    public function test_admin_or_teacher_can_manually_grade_essay_and_recalculate_score(): void
    {
        $scenario = $this->setupGradingScenario();
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(60),
            'status' => 'submitted',
        ]);

        // Q1 (Weight 40) answered correctly
        Answer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $scenario['q1']->id,
            'selected_option_id' => $scenario['q1_optA']->id,
        ]);

        // Q3 (Essay, Weight 30) answered with text
        $scenario['q3']->update(['question_type' => 'essay']);
        $essayAnswer = Answer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $scenario['q3']->id,
            'essay_answer' => 'Ini jawaban essay siswa secara lengkap.',
        ]);

        // Initial grading gives score 40.00 (essay is 0 initially)
        $result = app(\App\Http\Controllers\Api\V1\ResultController::class)->calculateAndStoreResult($attempt);
        $this->assertEquals(40.00, (float) $result->final_score);
        $this->assertEquals(0.00, (float) $result->essay_score);

        // Teacher/Admin grades the essay with 15 points
        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson("/api/v1/results/{$result->id}/grade-essay", [
                'answer_id' => $essayAnswer->id,
                'earned_score' => 15,
                'is_correct' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'essay_score' => 15.00,
                    'final_score' => 55.00, // 40 + 15 = 55
                ],
            ]);

        $this->assertEquals(55.00, (float) $result->fresh()->final_score);
        $this->assertEquals(15.00, (float) $result->fresh()->essay_score);
    }
}
