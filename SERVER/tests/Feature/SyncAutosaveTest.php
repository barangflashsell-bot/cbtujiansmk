<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Classes;
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

class SyncAutosaveTest extends TestCase
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

    protected function setupSyncScenario(): array
    {
        $subject = Subject::firstOrCreate(
            ['code' => 'SYNC-TEST'],
            ['name' => 'Mata Pelajaran Sync Test', 'status' => 'active']
        );

        $admin = User::where('username', 'admin')->first();

        $schoolClass = Classes::firstOrCreate(
            ['name' => 'Kelas 9-Sync'],
            ['level' => '9', 'academic_year' => '2025/2026', 'status' => 'active']
        );

        // Active exam
        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'title' => 'Ujian Sync & Autosave Test',
            'duration_minutes' => 60,
            'passing_score' => 75.00,
            'status' => 'published',
            'start_window' => now()->subHours(1),
            'end_window' => now()->addHours(2),
        ]);

        // Questions belonging to this exam
        $q1 = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'question_type' => 'single_choice',
            'content' => 'Soal Sync 1',
        ]);
        $opt1A = QuestionOption::create([
            'question_id' => $q1->id,
            'option_label' => 'A',
            'content' => 'Opsi Benar 1',
            'is_correct' => true,
        ]);
        $opt1B = QuestionOption::create([
            'question_id' => $q1->id,
            'option_label' => 'B',
            'content' => 'Opsi Salah 1',
            'is_correct' => false,
        ]);

        $q2 = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'question_type' => 'single_choice',
            'content' => 'Soal Sync 2',
        ]);
        $opt2A = QuestionOption::create([
            'question_id' => $q2->id,
            'option_label' => 'A',
            'content' => 'Opsi Benar 2',
            'is_correct' => true,
        ]);

        // Question NOT in this exam
        $otherQuestion = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'question_type' => 'single_choice',
            'content' => 'Soal di luar ujian',
        ]);
        $otherOption = QuestionOption::create([
            'question_id' => $otherQuestion->id,
            'option_label' => 'A',
            'content' => 'Opsi Luar',
            'is_correct' => true,
        ]);

        // Attach Q1 and Q2 to exam
        ExamQuestion::create(['exam_id' => $exam->id, 'question_id' => $q1->id, 'order_index' => 1, 'weight' => 50.00]);
        ExamQuestion::create(['exam_id' => $exam->id, 'question_id' => $q2->id, 'order_index' => 2, 'weight' => 50.00]);

        // Student 1 (main participant)
        $studentUser1 = User::where('username', 'peserta')->first();
        $student1 = $studentUser1->student;
        $student1->update(['class_id' => $schoolClass->id]);

        // Student 2 (for IDOR tests)
        $studentUser2 = User::where('username', 'peserta_sync_two')->first();
        if (! $studentUser2) {
            $studentUser2 = User::create([
                'role_id' => $studentUser1->role_id,
                'username' => 'peserta_sync_two',
                'name' => 'Peserta Sync Dua',
                'password' => 'password123',
            ]);
            $student2 = Student::create([
                'user_id' => $studentUser2->id,
                'class_id' => $schoolClass->id,
                'nis' => '88990011',
                'nisn' => '8899001122',
                'gender' => 'P',
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
            'q1' => $q1,
            'opt1A' => $opt1A,
            'opt1B' => $opt1B,
            'q2' => $q2,
            'opt2A' => $opt2A,
            'otherQuestion' => $otherQuestion,
            'otherOption' => $otherOption,
            'student1' => $student1,
            'studentUser1' => $studentUser1,
            'student2' => $student2,
            'studentUser2' => $studentUser2,
        ];
    }

    // ==========================================
    // 1. AUTHENTICATION & IDOR TESTS
    // ==========================================

    public function test_unauthenticated_requests_are_rejected_401(): void
    {
        $this->postJson('/api/v1/attempts/1/answers', [
            'question_id' => 1,
            'selected_option_id' => 1,
        ])->assertStatus(401);

        $this->postJson('/api/v1/attempts/1/sync', [
            'answers' => [
                ['question_id' => 1, 'selected_option_id' => 1],
            ],
        ])->assertStatus(401);
    }

    public function test_participant_cannot_autosave_another_participants_attempt(): void
    {
        $scenario = $this->setupSyncScenario();

        // Attempt belongs to Student 1
        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
            'status' => 'in_progress',
        ]);

        // Student 2 tries to autosave to Student 1's attempt
        $token2 = $this->getAuthToken($scenario['studentUser2']->username, 'password123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token2)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['q1']->id,
                'selected_option_id' => $scenario['opt1A']->id,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak dapat mengubah jawaban sesi ujian milik peserta lain',
            ]);

        // Also test sync endpoint
        $syncResponse = $this->withHeader('Authorization', 'Bearer '.$token2)
            ->postJson("/api/v1/attempts/{$attempt->id}/sync", [
                'answers' => [
                    ['question_id' => $scenario['q1']->id, 'selected_option_id' => $scenario['opt1A']->id],
                ],
            ]);

        $syncResponse->assertStatus(403);
    }

    // ==========================================
    // 2. RESOURCE VALIDATION (404, 422)
    // ==========================================

    public function test_invalid_attempt_rejected_with_404(): void
    {
        $token = $this->getAuthToken('peserta', 'peserta123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/attempts/999999/answers', [
                'question_id' => 1,
                'selected_option_id' => 1,
            ]);

        $response->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Sesi ujian tidak ditemukan']);
    }

    public function test_invalid_question_id_rejected_with_422(): void
    {
        $scenario = $this->setupSyncScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
            'status' => 'in_progress',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => 999999,
                'selected_option_id' => 1,
            ]);

        $response->assertStatus(422);
    }

    public function test_question_not_belonging_to_exam_rejected_with_422(): void
    {
        $scenario = $this->setupSyncScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
            'status' => 'in_progress',
        ]);

        // Question exists, but belongs to another exam
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['otherQuestion']->id,
                'selected_option_id' => $scenario['otherOption']->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Butir soal bukan merupakan bagian dari paket ujian ini',
            ]);
    }

    // ==========================================
    // 3. ATTEMPT STATUS RESTRICTIONS
    // ==========================================

    public function test_autosave_on_valid_in_progress_attempt_succeeds(): void
    {
        $scenario = $this->setupSyncScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
            'status' => 'in_progress',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['q1']->id,
                'selected_option_id' => $scenario['opt1A']->id,
                'is_flagged' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'synced_at',
                    'remaining_seconds',
                    'answer' => [
                        'id',
                        'attempt_id',
                        'question_id',
                        'selected_option_id',
                        'selected_option',
                        'is_flagged',
                        'answered_at',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'SAVED',
                    'answer' => [
                        'question_id' => $scenario['q1']->id,
                        'selected_option_id' => $scenario['opt1A']->id,
                        'selected_option' => 'A',
                        'is_flagged' => true,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $scenario['q1']->id,
            'selected_option_id' => $scenario['opt1A']->id,
            'selected_option' => 'A',
            'is_flagged' => true,
        ]);
    }

    public function test_submitted_attempt_cannot_be_modified(): void
    {
        $scenario = $this->setupSyncScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(40),
            'ends_at' => now()->addMinutes(20),
            'submitted_at' => now()->subMinutes(5),
            'status' => 'submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['q1']->id,
                'selected_option_id' => $scenario['opt1A']->id,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Sesi ujian telah selesai atau terkunci. Perubahan jawaban ditolak',
            ]);
    }

    public function test_timeout_attempt_cannot_be_modified(): void
    {
        $scenario = $this->setupSyncScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(70),
            'ends_at' => now()->subMinutes(10), // expired 10 mins ago
            'status' => 'timeout',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['q1']->id,
                'selected_option_id' => $scenario['opt1A']->id,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Sesi ujian telah selesai atau terkunci. Perubahan jawaban ditolak',
            ]);
    }

    // ==========================================
    // 4. IDEMPOTENCY & ANSWER UPDATES
    // ==========================================

    public function test_repeated_autosave_is_safe_and_idempotent(): void
    {
        $scenario = $this->setupSyncScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
            'status' => 'in_progress',
        ]);

        // First autosave
        $res1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['q1']->id,
                'selected_option_id' => $scenario['opt1A']->id,
            ]);
        $res1->assertStatus(200);

        // Replay duplicate autosave (e.g. network retry)
        $res2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['q1']->id,
                'selected_option_id' => $scenario['opt1A']->id,
            ]);
        $res2->assertStatus(200);

        // Ensure exactly ONE row exists in answers table for this attempt and question
        $answerRowsCount = Answer::where('attempt_id', $attempt->id)
            ->where('question_id', $scenario['q1']->id)
            ->count();
        $this->assertEquals(1, $answerRowsCount);
    }

    public function test_answer_update_replaces_existing_answer_correctly(): void
    {
        $scenario = $this->setupSyncScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
            'status' => 'in_progress',
        ]);

        // Student initially answers A
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['q1']->id,
                'selected_option_id' => $scenario['opt1A']->id,
            ])->assertStatus(200);

        // Student changes answer to B
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['q1']->id,
                'selected_option_id' => $scenario['opt1B']->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'answer' => [
                        'selected_option_id' => $scenario['opt1B']->id,
                        'selected_option' => 'B',
                    ],
                ],
            ]);

        // Verify in database
        $this->assertDatabaseHas('answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $scenario['q1']->id,
            'selected_option_id' => $scenario['opt1B']->id,
            'selected_option' => 'B',
        ]);
        $this->assertDatabaseMissing('answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $scenario['q1']->id,
            'selected_option_id' => $scenario['opt1A']->id,
        ]);
    }

    // ==========================================
    // 5. DATA LEAKAGE & SENSITIVE DATA PROTECTION
    // ==========================================

    public function test_no_answer_key_or_sensitive_data_leaked_in_response(): void
    {
        $scenario = $this->setupSyncScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
            'status' => 'in_progress',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/answers", [
                'question_id' => $scenario['q1']->id,
                'selected_option_id' => $scenario['opt1A']->id,
            ]);

        $response->assertStatus(200);

        $json = json_encode($response->json());
        $this->assertStringNotContainsString('is_correct', $json);
        $this->assertStringNotContainsString('earned_score', $json);
        $this->assertStringNotContainsString('password', $json);
        $this->assertStringNotContainsString('token', $json);
    }

    // ==========================================
    // 6. BATCH OFFLINE SYNC (RECOVERY)
    // ==========================================

    public function test_batch_sync_offline_queue_succeeds_and_is_idempotent(): void
    {
        $scenario = $this->setupSyncScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $attempt = ExamAttempt::create([
            'exam_id' => $scenario['exam']->id,
            'student_id' => $scenario['student1']->id,
            'started_at' => now()->subMinutes(20),
            'ends_at' => now()->addMinutes(40),
            'status' => 'in_progress',
        ]);

        // Simulating Android reconnecting after Wi-Fi lost and sending queued answers
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/sync", [
                'answers' => [
                    [
                        'question_id' => $scenario['q1']->id,
                        'selected_option_id' => $scenario['opt1B']->id,
                        'is_flagged' => false,
                    ],
                    [
                        'question_id' => $scenario['q2']->id,
                        'selected_option_id' => $scenario['opt2A']->id,
                        'is_flagged' => true,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'synced_count',
                    'synced_at',
                    'remaining_seconds',
                    'server_time',
                    'answers' => [
                        '*' => [
                            'id',
                            'question_id',
                            'selected_option_id',
                            'selected_option',
                            'is_flagged',
                            'synced_at',
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'SYNCED',
                    'synced_count' => 2,
                ],
            ]);

        $this->assertEquals(2, Answer::where('attempt_id', $attempt->id)->count());

        // Replaying same sync request does not duplicate rows
        $repeatResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/attempts/{$attempt->id}/sync", [
                'answers' => [
                    [
                        'question_id' => $scenario['q1']->id,
                        'selected_option_id' => $scenario['opt1B']->id,
                    ],
                    [
                        'question_id' => $scenario['q2']->id,
                        'selected_option_id' => $scenario['opt2A']->id,
                    ],
                ],
            ]);
        $repeatResponse->assertStatus(200);

        $this->assertEquals(2, Answer::where('attempt_id', $attempt->id)->count());
    }
}
