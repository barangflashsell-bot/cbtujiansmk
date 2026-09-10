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
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $teacher1;
    protected User $teacher2;
    protected User $studentUser1;
    protected User $studentUser2;
    protected Student $student1;
    protected Student $student2;
    protected Exam $exam1;
    protected Exam $exam2;
    protected ExamAttempt $attempt1;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::where('name', 'admin')->first();
        $teacherRole = Role::where('name', 'teacher')->first();
        $studentRole = Role::where('name', 'student')->first();

        $this->admin = User::where('username', 'admin')->first();
        $this->teacher1 = User::where('username', 'guru')->first();

        // Create second teacher for IDOR tests
        $this->teacher2 = User::firstOrCreate(
            ['username' => 'guru_security_two'],
            [
                'role_id' => $teacherRole->id,
                'name' => 'Guru Security Dua',
                'password' => 'password123',
                'is_active' => true,
            ]
        );
        Teacher::firstOrCreate(['user_id' => $this->teacher2->id], ['nip' => 'SEC002']);

        $this->studentUser1 = User::where('username', 'peserta')->first();
        $this->student1 = $this->studentUser1->student;

        $this->studentUser2 = User::firstOrCreate(
            ['username' => 'peserta_sec_two'],
            [
                'role_id' => $studentRole->id,
                'name' => 'Peserta Security Dua',
                'password' => 'password123',
                'is_active' => true,
            ]
        );
        $this->student2 = Student::firstOrCreate(
            ['user_id' => $this->studentUser2->id],
            [
                'class_id' => $this->student1->class_id,
                'nis' => '99988877',
                'nisn' => '9998887766',
                'gender' => 'L',
            ]
        );

        $subject = Subject::firstOrCreate(['code' => 'SEC-TEST'], ['name' => 'Keamanan Siber CBT', 'status' => 'active']);

        // Exam 1 created by Teacher 1
        $this->exam1 = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $this->teacher1->id,
            'title' => 'Ujian Keamanan 1',
            'duration_minutes' => 60,
            'start_window' => now()->subHour(),
            'end_window' => now()->addHours(2),
            'status' => 'active',
            'show_result' => false,
        ]);

        // Exam 2 created by Teacher 2
        $this->exam2 = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $this->teacher2->id,
            'title' => 'Ujian Keamanan 2',
            'duration_minutes' => 60,
            'start_window' => now()->subHour(),
            'end_window' => now()->addHours(2),
            'status' => 'active',
            'show_result' => false,
        ]);

        $teacher1Record = Teacher::firstOrCreate(['user_id' => $this->teacher1->id], ['nip' => 'SEC001']);

        // Question with secret answer key
        $q = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher1Record->id,
            'question_type' => 'single_choice',
            'content' => 'Apakah kunci jawaban boleh bocor?',
        ]);
        QuestionOption::create([
            'question_id' => $q->id,
            'option_label' => 'A',
            'content' => 'Tidak Boleh (Rahasia Server)',
            'is_correct' => true,
        ]);
        QuestionOption::create([
            'question_id' => $q->id,
            'option_label' => 'B',
            'content' => 'Boleh',
            'is_correct' => false,
        ]);

        ExamQuestion::create([
            'exam_id' => $this->exam1->id,
            'question_id' => $q->id,
            'order_index' => 1,
            'weight' => 100,
        ]);

        ExamParticipant::create(['exam_id' => $this->exam1->id, 'student_id' => $this->student1->id]);

        $this->attempt1 = ExamAttempt::create([
            'exam_id' => $this->exam1->id,
            'student_id' => $this->student1->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
            'status' => 'in_progress',
        ]);
    }

    protected function getApiToken(User $user): string
    {
        return $user->createToken('test-sec-token')->plainTextToken;
    }

    // ========================================================
    // 1. HTTP SECURITY HEADERS AUDIT
    // ========================================================

    public function test_security_headers_are_attached_to_all_responses(): void
    {
        $response = $this->get('/api/v1/health');

        $response->assertStatus(200)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-XSS-Protection', '1; mode=block')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    // ========================================================
    // 2. AUTHENTICATION & REVOCATION AUDIT
    // ========================================================

    public function test_invalid_credentials_rejected_without_information_leak(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'peserta',
            'password' => 'wrong_password_xyz',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Username atau password salah',
            ]);
    }

    public function test_revoked_token_cannot_access_protected_endpoints(): void
    {
        $token = $this->getApiToken($this->studentUser1);
        $tokenId = (int) explode('|', $token)[0];

        // Access /me succeeds
        $res1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');
        $res1->assertStatus(200);

        // Logout revokes current token
        $resLogout = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');
        $resLogout->assertStatus(200);

        // Verify token is deleted from personal_access_tokens in database
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);

        // Reset auth guard state for subsequent request
        auth()->forgetGuards();

        // Next request using revoked token is rejected with 401
        $res2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');
        $res2->assertStatus(401);
    }

    // ========================================================
    // 3. AUTHORIZATION, PRIVILEGE ESCALATION & IDOR AUDIT
    // ========================================================

    public function test_student_cannot_access_admin_or_teacher_endpoints_vertical_escalation(): void
    {
        $studentToken = $this->getApiToken($this->studentUser1);

        // Student tries to access admin users
        $resAdmin = $this->withHeader('Authorization', 'Bearer '.$studentToken)
            ->getJson('/api/v1/users');
        $resAdmin->assertStatus(403);

        // Student tries to access live monitoring
        $resMon = $this->withHeader('Authorization', 'Bearer '.$studentToken)
            ->getJson('/api/v1/monitoring/live');
        $resMon->assertStatus(403);

        // Student tries to access academic reports
        $resRep = $this->withHeader('Authorization', 'Bearer '.$studentToken)
            ->getJson("/api/v1/reports/exams/{$this->exam1->id}");
        $resRep->assertStatus(403);
    }

    public function test_teacher_cannot_access_admin_management_endpoints(): void
    {
        $teacherToken = $this->getApiToken($this->teacher1);

        $resUsers = $this->withHeader('Authorization', 'Bearer '.$teacherToken)
            ->getJson('/api/v1/users');
        $resUsers->assertStatus(403);

        $resSettings = $this->withHeader('Authorization', 'Bearer '.$teacherToken)
            ->getJson('/api/v1/backups');
        $resSettings->assertStatus(403);
    }

    public function test_teacher_cannot_access_other_teacher_resources_horizontal_escalation(): void
    {
        $teacher1Token = $this->getApiToken($this->teacher1);

        // Teacher 1 attempts to view monitoring of exam created by Teacher 2
        $res = $this->withHeader('Authorization', 'Bearer '.$teacher1Token)
            ->getJson("/api/v1/monitoring/exams/{$this->exam2->id}");
        $res->assertStatus(403);

        // Teacher 1 attempts to view report of exam created by Teacher 2
        $res2 = $this->withHeader('Authorization', 'Bearer '.$teacher1Token)
            ->getJson("/api/v1/reports/exams/{$this->exam2->id}");
        $res2->assertStatus(403);
    }

    public function test_student_cannot_tamper_other_student_attempt_or_answers_idor(): void
    {
        $student2Token = $this->getApiToken($this->studentUser2);

        // Student 2 tries to view Student 1's attempt
        $res = $this->withHeader('Authorization', 'Bearer '.$student2Token)
            ->getJson("/api/v1/attempts/{$this->attempt1->id}");
        $res->assertStatus(403);

        // Student 2 tries to submit answer for Student 1's attempt
        $resAnswer = $this->withHeader('Authorization', 'Bearer '.$student2Token)
            ->postJson("/api/v1/attempts/{$this->attempt1->id}/answers", [
                'question_id' => 1,
                'selected_option_id' => 1,
            ]);
        $resAnswer->assertStatus(403);

        // Student 2 tries to submit Student 1's exam
        $resSubmit = $this->withHeader('Authorization', 'Bearer '.$student2Token)
            ->postJson("/api/v1/attempts/{$this->attempt1->id}/submit");
        $resSubmit->assertStatus(403);
    }

    // ========================================================
    // 4. ANSWER KEY & SCORE LEAKAGE PREVENTION AUDIT
    // ========================================================

    public function test_answer_key_is_never_leaked_to_student(): void
    {
        $studentToken = $this->getApiToken($this->studentUser1);

        // Start / fetch questions
        $response = $this->withHeader('Authorization', 'Bearer '.$studentToken)
            ->postJson("/api/v1/exams/{$this->exam1->id}/start");

        $response->assertStatus(200);

        $payload = json_encode($response->json());
        // Verify is_correct attribute is completely absent
        $this->assertStringNotContainsString('"is_correct"', $payload);
    }

    public function test_unpublished_results_are_forbidden_for_student(): void
    {
        // Submit attempt to create Result
        Result::create([
            'attempt_id' => $this->attempt1->id,
            'exam_id' => $this->exam1->id,
            'student_id' => $this->student1->id,
            'correct_count' => 1,
            'wrong_count' => 0,
            'unanswered_count' => 0,
            'final_score' => 100.00,
            'score' => 100.00,
            'is_published' => false,
        ]);

        $studentToken = $this->getApiToken($this->studentUser1);

        // Since show_result=false and is_published=false, access must be 403 Forbidden
        $response = $this->withHeader('Authorization', 'Bearer '.$studentToken)
            ->getJson("/api/v1/attempts/{$this->attempt1->id}/result");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Hasil ujian belum dipublikasikan oleh guru/admin',
            ]);
    }

    // ========================================================
    // 5. INJECTION & SAFE QUERY HANDLING AUDIT
    // ========================================================

    public function test_sql_injection_payloads_in_search_handled_safely(): void
    {
        $adminToken = $this->getApiToken($this->admin);

        $sqliPayloads = [
            "' OR '1'='1",
            "1; DROP TABLE users; --",
            "' UNION SELECT id, username, password FROM users --",
        ];

        foreach ($sqliPayloads as $payload) {
            $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
                ->getJson('/api/v1/users?search='.urlencode($payload));

            // Must respond cleanly with HTTP 200 without SQL errors or 500
            $response->assertStatus(200);
        }
    }
}
