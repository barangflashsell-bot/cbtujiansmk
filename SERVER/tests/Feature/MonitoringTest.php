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

class MonitoringTest extends TestCase
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

    protected function setupMonitoringScenario(): array
    {
        $subject = Subject::firstOrCreate(
            ['code' => 'MON-TEST'],
            ['name' => 'Mata Pelajaran Monitoring Test', 'status' => 'active']
        );

        $admin = User::where('username', 'admin')->first();

        // Create active exam
        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'title' => 'Ujian Live Proctoring Test',
            'duration_minutes' => 60,
            'start_window' => now()->subMinutes(15),
            'end_window' => now()->addHours(2),
            'status' => 'active',
        ]);

        // Create 2 questions
        $q1 = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'question_type' => 'single_choice',
            'content' => 'Soal 1',
        ]);
        $optA = QuestionOption::create([
            'question_id' => $q1->id,
            'option_label' => 'A',
            'content' => 'Jawaban Benar',
            'is_correct' => true,
        ]);

        $q2 = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'question_type' => 'single_choice',
            'content' => 'Soal 2',
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $q1->id,
            'order_index' => 1,
            'weight' => 50,
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $q2->id,
            'order_index' => 2,
            'weight' => 50,
        ]);

        // Student 1 (in_progress)
        $studentUser = User::where('username', 'peserta')->first();
        $student1 = $studentUser->student;

        // Student 2 (not_started)
        $student2 = Student::where('id', '!=', $student1->id)->first();
        if (! $student2) {
            $otherUser = User::create([
                'role_id' => $studentUser->role_id,
                'username' => 'peserta_mon_two',
                'name' => 'Peserta Monitoring Dua',
                'password' => 'password123',
            ]);
            $student2 = Student::create([
                'user_id' => $otherUser->id,
                'class_id' => $student1->class_id,
                'nis' => '66665555',
                'nisn' => '6666555544',
                'gender' => 'P',
            ]);
        }

        // Enroll both students
        ExamParticipant::create([
            'exam_id' => $exam->id,
            'student_id' => $student1->id,
            'allow_retest' => false,
        ]);
        ExamParticipant::create([
            'exam_id' => $exam->id,
            'student_id' => $student2->id,
            'allow_retest' => false,
        ]);

        // Create attempt for Student 1
        $attempt1 = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student1->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
            'status' => 'in_progress',
            'ip_address' => '192.168.1.55',
            'device_info' => 'Android Client 14 / SM-A525F',
            'last_activity_at' => now()->subMinutes(2),
        ]);

        // Student 1 answered Q1
        Answer::create([
            'attempt_id' => $attempt1->id,
            'question_id' => $q1->id,
            'selected_option_id' => $optA->id,
            'selected_option' => 'A',
            'answered_at' => now()->subMinutes(2),
        ]);

        return [
            'exam' => $exam,
            'student1' => $student1,
            'student2' => $student2,
            'attempt1' => $attempt1,
        ];
    }

    // ==========================================
    // 1. AUTHENTICATION & ROLE RESTRICTION TESTS
    // ==========================================

    public function test_unauthenticated_requests_return_401(): void
    {
        $response = $this->getJson('/api/v1/monitoring/live');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/monitoring/exams/1');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/monitoring/attempts/1');
        $response->assertStatus(401);
    }

    public function test_student_role_cannot_access_monitoring_endpoints_returns_403(): void
    {
        $scenario = $this->setupMonitoringScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/monitoring/live');
        $response->assertStatus(403);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/monitoring/exams/{$scenario['exam']->id}");
        $response->assertStatus(403);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/monitoring/attempts/{$scenario['attempt1']->id}");
        $response->assertStatus(403);
    }

    // ==========================================
    // 2. LIVE MONITORING OVERVIEW TESTS
    // ==========================================

    public function test_admin_can_view_live_monitoring_overview(): void
    {
        $scenario = $this->setupMonitoringScenario();
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/monitoring/live');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'server_time',
                    'active_exams' => [
                        '*' => [
                            'id',
                            'title',
                            'duration_minutes',
                            'participants_count',
                            'in_progress_count',
                            'submitted_count',
                            'not_started_count',
                        ],
                    ],
                ],
            ]);

        $examData = collect($response->json('data.active_exams'))->firstWhere('id', $scenario['exam']->id);
        $this->assertNotNull($examData);
        $this->assertEquals(2, $examData['participants_count']);
        $this->assertEquals(1, $examData['in_progress_count']);
        $this->assertEquals(1, $examData['not_started_count']);
    }

    public function test_teacher_can_view_live_monitoring_overview(): void
    {
        $this->setupMonitoringScenario();
        $token = $this->getAuthToken('guru', 'guru123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/monitoring/live');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    // ==========================================
    // 3. EXAM PARTICIPANTS LIVE PROCTORING TESTS
    // ==========================================

    public function test_admin_can_view_exam_participants_monitoring(): void
    {
        $scenario = $this->setupMonitoringScenario();
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/monitoring/exams/{$scenario['exam']->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'exam' => [
                        'id',
                        'title',
                        'total_questions',
                    ],
                    'summary' => [
                        'total_participants',
                        'in_progress_count',
                        'submitted_count',
                        'not_started_count',
                    ],
                    'items' => [
                        '*' => [
                            'participant_id',
                            'student' => ['id', 'nis', 'name', 'class'],
                            'attempt' => [
                                'status',
                                'started_at',
                                'remaining_seconds',
                                'answered_count',
                                'progress_percentage',
                            ],
                        ],
                    ],
                    'pagination',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_participants' => 2,
                        'in_progress_count' => 1,
                        'not_started_count' => 1,
                    ],
                ],
            ]);

        // Student 1 has answered 1 of 2 questions = 50.0% progress
        $item1 = collect($response->json('data.items'))->firstWhere('student.id', $scenario['student1']->id);
        $this->assertNotNull($item1);
        $this->assertEquals('in_progress', $item1['attempt']['status']);
        $this->assertEquals(1, $item1['attempt']['answered_count']);
        $this->assertEquals(50.0, $item1['attempt']['progress_percentage']);
        $this->assertEquals('192.168.1.55', $item1['attempt']['ip_address']);

        // Student 2 has not started
        $item2 = collect($response->json('data.items'))->firstWhere('student.id', $scenario['student2']->id);
        $this->assertNotNull($item2);
        $this->assertEquals('not_started', $item2['attempt']['status']);
        $this->assertEquals(0, $item2['attempt']['answered_count']);
    }

    public function test_monitoring_filters_by_attempt_status(): void
    {
        $scenario = $this->setupMonitoringScenario();
        $token = $this->getAuthToken('admin', 'admin123');

        // Filter only 'in_progress'
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/monitoring/exams/{$scenario['exam']->id}?status=in_progress");

        $response->assertStatus(200);
        $items = $response->json('data.items');
        $this->assertCount(1, $items);
        $this->assertEquals('in_progress', $items[0]['attempt']['status']);

        // Filter only 'not_started'
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/monitoring/exams/{$scenario['exam']->id}?status=not_started");

        $response->assertStatus(200);
        $items = $response->json('data.items');
        $this->assertCount(1, $items);
        $this->assertEquals('not_started', $items[0]['attempt']['status']);
    }

    public function test_monitoring_filters_by_class_id(): void
    {
        $scenario = $this->setupMonitoringScenario();
        $token = $this->getAuthToken('admin', 'admin123');

        $classId = $scenario['student1']->class_id;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/monitoring/exams/{$scenario['exam']->id}?class_id={$classId}");

        $response->assertStatus(200);
        $this->assertGreaterThan(0, count($response->json('data.items')));
    }

    // ==========================================
    // 4. ATTEMPT TELEMETRY & 404 TESTS
    // ==========================================

    public function test_monitoring_single_attempt_telemetry(): void
    {
        $scenario = $this->setupMonitoringScenario();
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/monitoring/attempts/{$scenario['attempt1']->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'status',
                    'started_at',
                    'ends_at',
                    'remaining_seconds',
                    'ip_address',
                    'device_info',
                    'exam' => ['id', 'title'],
                    'student' => ['id', 'nis', 'name'],
                    'progress' => [
                        'total_questions',
                        'answered_count',
                        'unanswered_count',
                        'progress_percentage',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $scenario['attempt1']->id,
                    'status' => 'in_progress',
                    'progress' => [
                        'total_questions' => 2,
                        'answered_count' => 1,
                        'unanswered_count' => 1,
                        'progress_percentage' => 50.0,
                    ],
                ],
            ]);
    }

    public function test_monitoring_nonexistent_exam_or_attempt_returns_404(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/monitoring/exams/999999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Paket ujian tidak ditemukan',
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/monitoring/attempts/999999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Sesi ujian tidak ditemukan',
            ]);
    }

    // ==========================================
    // 5. SECURITY & READ-ONLY INTEGRITY TESTS
    // ==========================================

    public function test_sensitive_data_and_answer_keys_not_leaked_in_monitoring(): void
    {
        $scenario = $this->setupMonitoringScenario();
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/monitoring/exams/{$scenario['exam']->id}");

        $response->assertStatus(200);

        // Verify no passwords or answer keys in JSON
        $json = json_encode($response->json());
        $this->assertStringNotContainsString('password', $json);
        $this->assertStringNotContainsString('remember_token', $json);
        $this->assertStringNotContainsString('is_correct', $json);
    }

    public function test_teacher_ownership_and_idor_protection_in_monitoring(): void
    {
        $scenario = $this->setupMonitoringScenario(); // exam created by admin
        $teacherToken = $this->getAuthToken('guru', 'guru123');

        // 1. Teacher cannot access exam monitoring created by admin/another teacher
        $response = $this->withHeader('Authorization', 'Bearer '.$teacherToken)
            ->getJson("/api/v1/monitoring/exams/{$scenario['exam']->id}");
        $response->assertStatus(403);

        // 2. Teacher cannot access attempt telemetry for exam created by admin/another teacher
        $response = $this->withHeader('Authorization', 'Bearer '.$teacherToken)
            ->getJson("/api/v1/monitoring/attempts/{$scenario['attempt1']->id}");
        $response->assertStatus(403);

        // 3. Teacher can access exam and attempt created by themselves
        $guruUser = User::where('username', 'guru')->first();
        $scenario['exam']->update(['created_by' => $guruUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$teacherToken)
            ->getJson("/api/v1/monitoring/exams/{$scenario['exam']->id}");
        $response->assertStatus(200);

        $response = $this->withHeader('Authorization', 'Bearer '.$teacherToken)
            ->getJson("/api/v1/monitoring/attempts/{$scenario['attempt1']->id}");
        $response->assertStatus(200);
    }
}
