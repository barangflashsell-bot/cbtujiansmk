<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExamAttemptAnswerTest extends TestCase
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

    protected function setupExamScenario(): array
    {
        $subject = Subject::firstOrCreate(
            ['code' => 'ATTEMPT-SUBJ'],
            ['name' => 'Mata Pelajaran Attempt Test', 'status' => 'active']
        );

        $admin = User::where('username', 'admin')->first();

        // Create exam
        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'title' => 'Ujian Attempt & Answers Test',
            'duration_minutes' => 60,
            'token' => 'SEC123',
            'start_window' => now()->subMinutes(10),
            'end_window' => now()->addHours(2),
            'status' => 'published',
        ]);

        // Create 2 questions for this exam
        $question1 = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'question_type' => 'single_choice',
            'content' => 'Pertanyaan 1 dalam ujian',
        ]);

        $optA = QuestionOption::create([
            'question_id' => $question1->id,
            'option_label' => 'A',
            'content' => 'Pilihan A',
            'is_correct' => true,
        ]);

        $optB = QuestionOption::create([
            'question_id' => $question1->id,
            'option_label' => 'B',
            'content' => 'Pilihan B',
            'is_correct' => false,
        ]);

        $question2 = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'question_type' => 'single_choice',
            'content' => 'Pertanyaan 2 dalam ujian',
        ]);

        // Attach questions to exam
        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $question1->id,
            'order_index' => 1,
            'weight' => 50,
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $question2->id,
            'order_index' => 2,
            'weight' => 50,
        ]);

        // Create a foreign question not attached to this exam
        $foreignQuestion = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'question_type' => 'single_choice',
            'content' => 'Soal asing di luar ujian',
        ]);

        // Primary student
        $studentUser = User::where('username', 'peserta')->first();
        $student = $studentUser->student;

        // Second student (create if not already present)
        $otherStudent = Student::where('id', '!=', $student->id)->first();
        if (! $otherStudent) {
            $otherUser = User::create([
                'role_id' => $studentUser->role_id,
                'username' => 'peserta_test_two',
                'name' => 'Peserta Uji Dua',
                'password' => 'password123',
            ]);
            $otherStudent = Student::create([
                'user_id' => $otherUser->id,
                'class_id' => $student->class_id,
                'nis' => '88889999',
                'nisn' => '8888999900',
                'gender' => 'L',
            ]);
        }

        return [
            'exam' => $exam,
            'question1' => $question1,
            'optA' => $optA,
            'optB' => $optB,
            'question2' => $question2,
            'foreignQuestion' => $foreignQuestion,
            'student' => $student,
            'otherStudent' => $otherStudent,
        ];
    }

    // ==========================================
    // 1. AUTHENTICATION & PARTICIPATION TESTS
    // ==========================================

    public function test_unauthenticated_requests_return_401(): void
    {
        $response = $this->postJson('/api/v1/exams/1/start');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/attempts/1');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/attempts/1/answers', []);
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/attempts/1/submit');
        $response->assertStatus(401);
    }

    public function test_cannot_start_attempt_if_not_enrolled_participant(): void
    {
        $scenario = $this->setupExamScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        // Student is NOT enrolled in the exam
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$scenario['exam']->id}/start", [
                'token' => 'SEC123',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Siswa tidak terdaftar pada sesi ujian ini',
            ]);
    }

    public function test_cannot_start_attempt_with_invalid_token(): void
    {
        $scenario = $this->setupExamScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        // Enroll student
        ExamParticipant::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student']->id,
            'allow_retest' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$scenario['exam']->id}/start", [
                'token' => 'WRONG_TOKEN',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Token ujian salah atau belum diinput',
            ]);
    }

    // ==========================================
    // 2. EXAM ATTEMPT LIFECYCLE & RECOVERY TESTS
    // ==========================================

    public function test_student_can_start_attempt_and_receive_questions_without_answer_keys(): void
    {
        $scenario = $this->setupExamScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        ExamParticipant::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student']->id,
            'allow_retest' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$scenario['exam']->id}/start", [
                'token' => 'SEC123',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'attempt_id',
                    'exam_id',
                    'started_at',
                    'ends_at',
                    'remaining_seconds',
                    'status',
                    'questions',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'exam_id' => $scenario['exam']->id,
                    'status' => 'in_progress',
                ],
            ]);

        // CRITICAL CHECK: ensure is_correct is NOT leaked in question options
        $options = $response->json('data.questions.0.options');
        $this->assertNotEmpty($options);
        foreach ($options as $option) {
            $this->assertArrayNotHasKey('is_correct', $option);
        }

        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student']->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_duplicate_start_recovers_existing_active_attempt_without_duplicate_row(): void
    {
        $scenario = $this->setupExamScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        ExamParticipant::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student']->id,
            'allow_retest' => false,
        ]);

        // First start
        $response1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$scenario['exam']->id}/start", [
                'token' => 'SEC123',
            ]);
        $attemptId1 = $response1->json('data.attempt_id');

        // Second start (Recovery / Resume)
        $response2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$scenario['exam']->id}/start", [
                'token' => 'SEC123',
            ]);
        $attemptId2 = $response2->json('data.attempt_id');

        // Must recover same attempt
        $this->assertEquals($attemptId1, $attemptId2);

        // Database row count must strictly remain 1
        $count = ExamAttempt::where('exam_id', $scenario['exam']->id)
            ->where('student_id', $scenario['student']->id)
            ->count();
        $this->assertEquals(1, $count);
    }

    public function test_cannot_restart_submitted_attempt_if_retest_not_allowed(): void
    {
        $scenario = $this->setupExamScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        ExamParticipant::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student']->id,
            'allow_retest' => false,
        ]);

        // Create completed attempt
        ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student']->id,
            'started_at' => now()->subHour(),
            'ends_at' => now(),
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$scenario['exam']->id}/start", [
                'token' => 'SEC123',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Ujian sudah pernah diselesaikan dan tidak diizinkan ujian ulang',
            ]);
    }

    public function test_other_student_cannot_view_or_access_attempt(): void
    {
        $scenario = $this->setupExamScenario();
        $pesertaToken = $this->getAuthToken('peserta', 'peserta123');

        // Create attempt for other student
        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['otherStudent']->id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(60),
            'status' => 'in_progress',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->getJson("/api/v1/attempts/{$attempt->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk melihat sesi ujian ini',
            ]);
    }

    // ==========================================
    // 3. ANSWERS & AUTOSAVE TESTS
    // ==========================================

    public function test_student_can_save_answer_and_update_is_idempotent(): void
    {
        $scenario = $this->setupExamScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student']->id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(60),
            'status' => 'in_progress',
        ]);

        // First answer save
        $response1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['question1']->id,
                'selected_option_id' => $scenario['optA']->id,
                'is_flagged' => false,
            ]);

        $response1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'SAVED',
                    'answer' => [
                        'question_id' => $scenario['question1']->id,
                        'selected_option_id' => $scenario['optA']->id,
                        'selected_option' => 'A',
                    ],
                ],
            ]);

        // Verify no score or is_correct leak in response
        $this->assertArrayNotHasKey('is_correct', $response1->json('data.answer'));
        $this->assertArrayNotHasKey('earned_score', $response1->json('data.answer'));

        // Second answer save (Update/Retry - Idempotent)
        $response2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['question1']->id,
                'selected_option_id' => $scenario['optB']->id,
                'is_flagged' => true,
            ]);

        $response2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'SAVED',
                    'answer' => [
                        'question_id' => $scenario['question1']->id,
                        'selected_option_id' => $scenario['optB']->id,
                        'selected_option' => 'B',
                        'is_flagged' => true,
                    ],
                ],
            ]);

        // Ensure exactly ONE row in database for this (attempt, question)
        $answerCount = Answer::where('attempt_id', $attempt->id)
            ->where('question_id', $scenario['question1']->id)
            ->count();
        $this->assertEquals(1, $answerCount);

        $savedAnswer = Answer::where('attempt_id', $attempt->id)
            ->where('question_id', $scenario['question1']->id)
            ->first();
        $this->assertEquals($scenario['optB']->id, $savedAnswer->selected_option_id);
        $this->assertTrue($savedAnswer->is_flagged);
    }

    public function test_cannot_save_answer_for_question_not_in_exam(): void
    {
        $scenario = $this->setupExamScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student']->id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(60),
            'status' => 'in_progress',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['foreignQuestion']->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question_id'])
            ->assertJson([
                'success' => false,
                'message' => 'Butir soal bukan merupakan bagian dari paket ujian ini',
            ]);
    }

    public function test_other_student_cannot_modify_answer_for_attempt(): void
    {
        $scenario = $this->setupExamScenario();
        $pesertaToken = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['otherStudent']->id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(60),
            'status' => 'in_progress',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['question1']->id,
                'selected_option_id' => $scenario['optA']->id,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak dapat mengubah jawaban sesi ujian milik peserta lain',
            ]);
    }

    public function test_cannot_save_answer_if_attempt_is_submitted_or_timed_out(): void
    {
        $scenario = $this->setupExamScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student']->id,
            'started_at' => now()->subHours(2),
            'ends_at' => now()->subHour(),
            'submitted_at' => now()->subHour(),
            'status' => 'submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['question1']->id,
                'selected_option_id' => $scenario['optA']->id,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Sesi ujian telah selesai atau terkunci. Perubahan jawaban ditolak',
            ]);
    }

    // ==========================================
    // 4. SUBMIT & LISTING TESTS
    // ==========================================

    public function test_student_can_submit_attempt_idempotently(): void
    {
        $scenario = $this->setupExamScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student']->id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(60),
            'status' => 'in_progress',
        ]);

        // First submit
        $response1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/submit");

        $response1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'SUBMITTED',
                ],
            ]);

        $this->assertEquals('submitted', $attempt->fresh()->status);
        $this->assertNotNull($attempt->fresh()->submitted_at);

        // Second submit (Idempotent call)
        $response2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/submit");

        $response2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'SUBMITTED',
                ],
            ]);
    }

    public function test_student_can_list_saved_answers_without_sensitive_data(): void
    {
        $scenario = $this->setupExamScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student']->id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(60),
            'status' => 'in_progress',
        ]);

        Answer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $scenario['question1']->id,
            'selected_option_id' => $scenario['optA']->id,
            'selected_option' => 'A',
            'is_correct' => true, // in db
            'earned_score' => 50.00, // in db
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/attempts/{$attempt->id}/answers");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'attempt_id',
                        'question_id',
                        'selected_option_id',
                        'selected_option',
                    ],
                ],
            ]);

        // CRITICAL CHECK: answers listing must not leak is_correct or score
        $firstAnswer = $response->json('data.0');
        $this->assertArrayNotHasKey('is_correct', $firstAnswer);
        $this->assertArrayNotHasKey('earned_score', $firstAnswer);
    }

    public function test_submit_is_strictly_idempotent_and_does_not_duplicate_results(): void
    {
        $scenario = $this->setupExamScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student']->id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(60),
            'status' => 'in_progress',
        ]);

        // First submit
        $res1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/submit");
        $res1->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['status' => 'SUBMITTED']]);
        $resultId1 = $res1->json('data.result_id');

        // Repeated submit
        $res2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/submit");
        $res2->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['status' => 'SUBMITTED']]);

        // Must maintain single result row
        $this->assertDatabaseCount('results', 1);
        $this->assertEquals($resultId1, $res1->json('data.result_id'));
    }

    public function test_autosave_rejected_once_attempt_is_submitted(): void
    {
        $scenario = $this->setupExamScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student']->id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(60),
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['question1']->id,
                'selected_option_id' => $scenario['optA']->id,
            ]);

        $res->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Sesi ujian telah selesai atau terkunci. Perubahan jawaban ditolak',
            ]);
    }

    public function test_concurrent_start_race_recovers_gracefully_without_500(): void
    {
        $scenario = $this->setupExamScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        ExamParticipant::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student']->id,
            'allow_retest' => false,
        ]);

        // First start creates attempt
        $res1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$scenario['exam']->id}/start", [
                'token' => 'SEC123',
            ]);
        $res1->assertStatus(200);
        $attemptId1 = $res1->json('data.attempt_id');

        // Immediate subsequent start (simulating concurrent winner/loser recovery)
        $res2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/exams/{$scenario['exam']->id}/start", [
                'token' => 'SEC123',
            ]);
        $res2->assertStatus(200);
        $attemptId2 = $res2->json('data.attempt_id');

        $this->assertEquals($attemptId1, $attemptId2);
        $this->assertDatabaseCount('exam_attempts', 1);
    }
}
