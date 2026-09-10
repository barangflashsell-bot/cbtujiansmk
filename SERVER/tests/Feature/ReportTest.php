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
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportTest extends TestCase
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

    protected function setupReportScenario(): array
    {
        $subject = Subject::firstOrCreate(
            ['code' => 'REP-TEST'],
            ['name' => 'Mata Pelajaran Reports Test', 'status' => 'active']
        );

        $admin = User::where('username', 'admin')->first();

        // School Class
        $schoolClass = Classes::firstOrCreate(
            ['name' => 'Kelas 9-A Reports'],
            ['level' => '9', 'academic_year' => '2025/2026', 'status' => 'active']
        );

        // Exam
        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'title' => 'Ujian Akhir Semester Reports Test',
            'duration_minutes' => 90,
            'passing_score' => 75.00,
            'status' => 'completed',
            'start_window' => now()->subDays(2),
            'end_window' => now()->subDay(),
        ]);

        // Questions
        $q1 = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'question_type' => 'single_choice',
            'content' => 'Soal 1 Analisis',
        ]);
        $opt1A = QuestionOption::create([
            'question_id' => $q1->id,
            'option_label' => 'A',
            'content' => 'Kunci Benar',
            'is_correct' => true,
        ]);

        $q2 = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'question_type' => 'single_choice',
            'content' => 'Soal 2 Analisis',
        ]);
        $opt2A = QuestionOption::create([
            'question_id' => $q2->id,
            'option_label' => 'A',
            'content' => 'Kunci Benar',
            'is_correct' => true,
        ]);
        $opt2B = QuestionOption::create([
            'question_id' => $q2->id,
            'option_label' => 'B',
            'content' => 'Pilihan Salah',
            'is_correct' => false,
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $q1->id,
            'order_index' => 1,
            'weight' => 50.00,
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $q2->id,
            'order_index' => 2,
            'weight' => 50.00,
        ]);

        // Student 1 (Passed)
        $studentUser1 = User::where('username', 'peserta')->first();
        $student1 = $studentUser1->student;
        $student1->update(['class_id' => $schoolClass->id]);

        // Student 2 (Failed)
        $student2 = Student::where('id', '!=', $student1->id)->first();
        if (! $student2) {
            $otherUser = User::create([
                'role_id' => $studentUser1->role_id,
                'username' => 'peserta_rep_two',
                'name' => 'Peserta Reports Dua',
                'password' => 'password123',
            ]);
            $student2 = Student::create([
                'user_id' => $otherUser->id,
                'class_id' => $schoolClass->id,
                'nis' => '55554444',
                'nisn' => '5555444433',
                'gender' => 'L',
            ]);
        } else {
            $student2->update(['class_id' => $schoolClass->id]);
        }

        // Enroll both students
        ExamParticipant::create(['exam_id' => $exam->id, 'student_id' => $student1->id]);
        ExamParticipant::create(['exam_id' => $exam->id, 'student_id' => $student2->id]);

        // Attempt 1: Student 1 answers both correctly -> Score 100 (Passed)
        $attempt1 = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student1->id,
            'started_at' => now()->subDay(),
            'ends_at' => now()->subDay()->addHour(),
            'submitted_at' => now()->subDay()->addHour(),
            'status' => 'submitted',
        ]);
        Answer::create(['attempt_id' => $attempt1->id, 'question_id' => $q1->id, 'selected_option_id' => $opt1A->id, 'is_correct' => true, 'earned_score' => 50.00]);
        Answer::create(['attempt_id' => $attempt1->id, 'question_id' => $q2->id, 'selected_option_id' => $opt2A->id, 'is_correct' => true, 'earned_score' => 50.00]);
        Result::create([
            'attempt_id' => $attempt1->id,
            'exam_id' => $exam->id,
            'student_id' => $student1->id,
            'correct_count' => 2,
            'wrong_count' => 0,
            'unanswered_count' => 0,
            'final_score' => 100.00,
            'score' => 100.00,
            'is_published' => true,
        ]);

        // Attempt 2: Student 2 answers only Q1 correctly -> Score 50 (Failed)
        $attempt2 = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student2->id,
            'started_at' => now()->subDay(),
            'ends_at' => now()->subDay()->addHour(),
            'submitted_at' => now()->subDay()->addHour(),
            'status' => 'submitted',
        ]);
        Answer::create(['attempt_id' => $attempt2->id, 'question_id' => $q1->id, 'selected_option_id' => $opt1A->id, 'is_correct' => true, 'earned_score' => 50.00]);
        Answer::create(['attempt_id' => $attempt2->id, 'question_id' => $q2->id, 'selected_option_id' => $opt2B->id, 'is_correct' => false, 'earned_score' => 0.00]);
        Result::create([
            'attempt_id' => $attempt2->id,
            'exam_id' => $exam->id,
            'student_id' => $student2->id,
            'correct_count' => 1,
            'wrong_count' => 1,
            'unanswered_count' => 0,
            'final_score' => 50.00,
            'score' => 50.00,
            'is_published' => true,
        ]);

        return [
            'exam' => $exam,
            'schoolClass' => $schoolClass,
            'student1' => $student1,
            'student2' => $student2,
            'q1' => $q1,
            'q2' => $q2,
        ];
    }

    // ==========================================
    // 1. AUTHENTICATION & ROLE RESTRICTION TESTS
    // ==========================================

    public function test_unauthenticated_requests_return_401(): void
    {
        $response = $this->getJson('/api/v1/reports/exams/1');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/reports/classes/1');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/reports/item-analysis/1');
        $response->assertStatus(401);
    }

    public function test_student_role_cannot_access_reports_returns_403(): void
    {
        $scenario = $this->setupReportScenario();
        $token = $this->getAuthToken('peserta', 'peserta123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/reports/exams/{$scenario['exam']->id}");
        $response->assertStatus(403);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/reports/classes/{$scenario['schoolClass']->id}");
        $response->assertStatus(403);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/reports/item-analysis/{$scenario['exam']->id}");
        $response->assertStatus(403);
    }

    // ==========================================
    // 2. EXAM REPORT & STATISTICS TESTS
    // ==========================================

    public function test_admin_and_teacher_can_view_exam_statistical_report(): void
    {
        $scenario = $this->setupReportScenario();
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson("/api/v1/reports/exams/{$scenario['exam']->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'exam' => ['id', 'title', 'passing_score', 'total_questions'],
                    'statistics' => [
                        'total_participants',
                        'total_graded',
                        'passed_count',
                        'failed_count',
                        'pass_percentage',
                        'average_score',
                        'highest_score',
                        'lowest_score',
                    ],
                    'items' => [
                        '*' => [
                            'result_id',
                            'student' => ['id', 'nis', 'name', 'class'],
                            'final_score',
                            'is_passed',
                        ],
                    ],
                    'pagination',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'statistics' => [
                        'total_participants' => 2,
                        'total_graded' => 2,
                        'passed_count' => 1,
                        'failed_count' => 1,
                        'pass_percentage' => 50.0,
                        'average_score' => 75.0, // (100 + 50) / 2 = 75.0
                        'highest_score' => 100.0,
                        'lowest_score' => 50.0,
                    ],
                ],
            ]);

        // Teacher access check
        $guruToken = $this->getAuthToken('guru', 'guru123');
        $teacherResponse = $this->withHeader('Authorization', 'Bearer '.$guruToken)
            ->getJson("/api/v1/reports/exams/{$scenario['exam']->id}");
        $teacherResponse->assertStatus(200);
    }

    public function test_exam_report_filters_by_class_id(): void
    {
        $scenario = $this->setupReportScenario();
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson("/api/v1/reports/exams/{$scenario['exam']->id}?class_id={$scenario['schoolClass']->id}");

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('data.statistics.total_graded'));
    }

    // ==========================================
    // 3. CLASS REPORT TESTS
    // ==========================================

    public function test_admin_and_teacher_can_view_class_report(): void
    {
        $scenario = $this->setupReportScenario();
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson("/api/v1/reports/classes/{$scenario['schoolClass']->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'class' => ['id', 'name', 'level', 'total_students'],
                    'items' => [
                        '*' => [
                            'result_id',
                            'exam' => ['id', 'title'],
                            'student' => ['id', 'nis', 'name'],
                            'final_score',
                            'is_passed',
                        ],
                    ],
                    'pagination',
                ],
            ]);
    }

    // ==========================================
    // 4. ITEM ANALYSIS TESTS
    // ==========================================

    public function test_admin_and_teacher_can_view_item_analysis(): void
    {
        $scenario = $this->setupReportScenario();
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson("/api/v1/reports/item-analysis/{$scenario['exam']->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'exam' => ['id', 'title', 'total_questions'],
                    'items' => [
                        '*' => [
                            'question_id',
                            'order_index',
                            'weight',
                            'total_attempts',
                            'total_answered',
                            'correct_count',
                            'wrong_count',
                            'difficulty_index',
                            'classification',
                        ],
                    ],
                ],
            ]);

        // Q1 was answered correctly by 2 of 2 attempts -> difficulty_index = 100.0% ('mudah')
        $item1 = collect($response->json('data.items'))->firstWhere('question_id', $scenario['q1']->id);
        $this->assertNotNull($item1);
        $this->assertEquals(2, $item1['correct_count']);
        $this->assertEquals(100.0, $item1['difficulty_index']);
        $this->assertEquals('mudah', $item1['classification']);

        // Q2 was answered correctly by 1 of 2 attempts -> difficulty_index = 50.0% ('sedang')
        $item2 = collect($response->json('data.items'))->firstWhere('question_id', $scenario['q2']->id);
        $this->assertNotNull($item2);
        $this->assertEquals(1, $item2['correct_count']);
        $this->assertEquals(50.0, $item2['difficulty_index']);
        $this->assertEquals('sedang', $item2['classification']);
    }

    // ==========================================
    // 5. SECURITY & 404 TESTS
    // ==========================================

    public function test_nonexistent_resources_return_404(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/reports/exams/999999');
        $response->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Paket ujian tidak ditemukan']);

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/reports/classes/999999');
        $response->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Data kelas tidak ditemukan']);

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/reports/item-analysis/999999');
        $response->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Paket ujian tidak ditemukan']);
    }

    public function test_sensitive_data_not_leaked_in_reports(): void
    {
        $scenario = $this->setupReportScenario();
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson("/api/v1/reports/exams/{$scenario['exam']->id}");

        $response->assertStatus(200);
        $json = json_encode($response->json());
        $this->assertStringNotContainsString('password', $json);
        $this->assertStringNotContainsString('remember_token', $json);
    }

    public function test_teacher_ownership_and_idor_protection_in_reports(): void
    {
        $scenario = $this->setupReportScenario(); // created by admin
        $teacherToken = $this->getAuthToken('guru', 'guru123');

        // Teacher cannot access reports of exams created by another user
        $response = $this->withHeader('Authorization', 'Bearer '.$teacherToken)
            ->getJson("/api/v1/reports/exams/{$scenario['exam']->id}");
        $response->assertStatus(403);

        $response = $this->withHeader('Authorization', 'Bearer '.$teacherToken)
            ->getJson("/api/v1/reports/item-analysis/{$scenario['exam']->id}");
        $response->assertStatus(403);

        // Teacher CAN access reports of their own exam
        $guruUser = User::where('username', 'guru')->first();
        $scenario['exam']->update(['created_by' => $guruUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$teacherToken)
            ->getJson("/api/v1/reports/exams/{$scenario['exam']->id}");
        $response->assertStatus(200);

        $response = $this->withHeader('Authorization', 'Bearer '.$teacherToken)
            ->getJson("/api/v1/reports/item-analysis/{$scenario['exam']->id}");
        $response->assertStatus(200);
    }

    public function test_report_generation_does_not_mutate_exam_data(): void
    {
        $scenario = $this->setupReportScenario();
        $adminToken = $this->getAuthToken('admin', 'admin123');

        $result = Result::where('exam_id', $scenario['exam']->id)->first();
        $attempt = ExamAttempt::where('exam_id', $scenario['exam']->id)->first();

        $initialScore = $result->final_score;
        $initialPublished = $result->is_published;
        $initialAttemptStatus = $attempt->status;

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson("/api/v1/reports/exams/{$scenario['exam']->id}");

        $result->refresh();
        $attempt->refresh();

        $this->assertEquals($initialScore, $result->final_score);
        $this->assertEquals($initialPublished, $result->is_published);
        $this->assertEquals($initialAttemptStatus, $attempt->status);
    }
}
