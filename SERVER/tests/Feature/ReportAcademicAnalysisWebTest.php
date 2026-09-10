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
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportAcademicAnalysisWebTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $guru1;
    protected User $guru2;
    protected Teacher $teacher1;
    protected Teacher $teacher2;
    protected User $studentUser;
    protected Student $student;
    protected Classes $class;
    protected Subject $subject;
    protected Exam $exam1;
    protected Exam $exam2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->first();

        $teacherRole = Role::where('name', 'teacher')->first();
        $this->guru1 = User::firstOrCreate(
            ['username' => 'guru_rep_1'],
            [
                'name' => 'Guru Laporan 1',
                'email' => 'guru_rep1@test.com',
                'password' => bcrypt('password'),
                'role_id' => $teacherRole->id,
                'is_active' => true,
            ]
        );
        $this->teacher1 = Teacher::firstOrCreate(
            ['user_id' => $this->guru1->id],
            ['nip' => '198501012023011005', 'phone' => '081234567881']
        );

        $this->guru2 = User::firstOrCreate(
            ['username' => 'guru_rep_2'],
            [
                'name' => 'Guru Laporan 2',
                'email' => 'guru_rep2@test.com',
                'password' => bcrypt('password'),
                'role_id' => $teacherRole->id,
                'is_active' => true,
            ]
        );
        $this->teacher2 = Teacher::firstOrCreate(
            ['user_id' => $this->guru2->id],
            ['nip' => '198501012023011006', 'phone' => '081234567882']
        );

        $this->class = Classes::firstOrCreate(
            ['name' => 'XII-IPA-REP'],
            ['level' => '12', 'academic_year' => '2025/2026', 'status' => 'active']
        );

        $studentRole = Role::where('name', 'student')->first();
        $this->studentUser = User::create([
            'username' => 'std_rep_' . uniqid(),
            'name' => 'Siswa Laporan Test',
            'password' => bcrypt('password'),
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);
        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'class_id' => $this->class->id,
            'nis' => 'REP' . rand(10000, 99999),
            'gender' => 'L',
        ]);

        $this->subject = Subject::firstOrCreate(
            ['code' => 'REP-MAT-12'],
            ['name' => 'Matematika Laporan', 'status' => 'active']
        );

        // Exam 1 owned by Guru 1
        $this->exam1 = Exam::create([
            'subject_id' => $this->subject->id,
            'created_by' => $this->guru1->id,
            'title' => 'UAS Matematika Kelas 12 - GURU 1',
            'duration_minutes' => 90,
            'passing_score' => 75.0,
            'start_window' => now()->subDay(),
            'end_window' => now()->addDay(),
            'status' => 'active',
        ]);

        // Exam 2 owned by Guru 2
        $this->exam2 = Exam::create([
            'subject_id' => $this->subject->id,
            'created_by' => $this->guru2->id,
            'title' => 'UAS Matematika Kelas 12 - GURU 2',
            'duration_minutes' => 90,
            'passing_score' => 70.0,
            'start_window' => now()->subDay(),
            'end_window' => now()->addDay(),
            'status' => 'active',
        ]);

        // Attach question to Exam 1
        $question = Question::create([
            'subject_id' => $this->subject->id,
            'created_by' => $this->teacher1->id,
            'question_type' => 'single_choice',
            'content' => 'Berapa hasil dari 5 + 5?',
            'score_weight' => 10.0,
            'difficulty' => 'easy',
            'status' => 'active',
        ]);
        $optionCorrect = QuestionOption::create([
            'question_id' => $question->id,
            'option_label' => 'A',
            'content' => '10',
            'is_correct' => true,
        ]);

        ExamQuestion::create([
            'exam_id' => $this->exam1->id,
            'question_id' => $question->id,
            'order_index' => 1,
            'weight' => 10.0,
        ]);

        // Enroll participant in Exam 1
        ExamParticipant::create([
            'exam_id' => $this->exam1->id,
            'student_id' => $this->student->id,
            'allow_retest' => false,
        ]);

        // Create attempt and answer
        $attempt = ExamAttempt::create([
            'exam_id' => $this->exam1->id,
            'student_id' => $this->student->id,
            'started_at' => now()->subMinutes(60),
            'ends_at' => now()->subMinutes(10),
            'submitted_at' => now()->subMinutes(10),
            'status' => 'submitted',
        ]);

        Answer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $optionCorrect->id,
            'is_correct' => true,
            'score_awarded' => 10.0,
        ]);

        // Result record
        Result::create([
            'attempt_id' => $attempt->id,
            'exam_id' => $this->exam1->id,
            'student_id' => $this->student->id,
            'correct_count' => 1,
            'wrong_count' => 0,
            'unanswered_count' => 0,
            'score' => 85.0,
            'final_score' => 85.0,
            'status' => 'completed',
            'is_published' => true,
            'graded_at' => now(),
        ]);
    }

    /**
     * 1. Guest redirected to login
     */
    public function test_01_guest_redirected_to_login(): void
    {
        $this->get('/admin/reports')->assertRedirect(route('login'));
        $this->get("/admin/reports/exams/{$this->exam1->id}")->assertRedirect(route('login'));
        $this->get("/admin/reports/classes/{$this->class->id}")->assertRedirect(route('login'));
        $this->get("/admin/reports/item-analysis/{$this->exam1->id}")->assertRedirect(route('login'));

        $this->get('/guru/reports')->assertRedirect(route('login'));
        $this->get("/guru/reports/exams/{$this->exam1->id}")->assertRedirect(route('login'));
        $this->get("/guru/reports/classes/{$this->class->id}")->assertRedirect(route('login'));
        $this->get("/guru/reports/item-analysis/{$this->exam1->id}")->assertRedirect(route('login'));
    }

    /**
     * 2. Student forbidden from accessing reports
     */
    public function test_02_student_forbidden_from_reports(): void
    {
        $this->actingAs($this->studentUser)->get('/admin/reports')->assertStatus(403);
        $this->actingAs($this->studentUser)->get("/admin/reports/exams/{$this->exam1->id}")->assertStatus(403);
        $this->actingAs($this->studentUser)->get('/guru/reports')->assertStatus(403);
        $this->actingAs($this->studentUser)->get("/guru/reports/exams/{$this->exam1->id}")->assertStatus(403);
    }

    /**
     * 3. Guru forbidden from admin reports
     */
    public function test_03_guru_forbidden_from_admin_reports(): void
    {
        $this->actingAs($this->guru1)->get('/admin/reports')->assertStatus(403);
        $this->actingAs($this->guru1)->get("/admin/reports/exams/{$this->exam1->id}")->assertStatus(403);
    }

    /**
     * 4. Admin can view reports index, exam report, class report, and item analysis
     */
    public function test_04_admin_can_view_all_reports(): void
    {
        // Reports hub index
        $this->actingAs($this->admin)->get('/admin/reports')
            ->assertStatus(200)
            ->assertSee('Pusat Rekapitulasi & Analisis Hasil Ujian', false)
            ->assertSee($this->exam1->title)
            ->assertSee($this->exam2->title);

        // Exam statistical report
        $this->actingAs($this->admin)->get("/admin/reports/exams/{$this->exam1->id}")
            ->assertStatus(200)
            ->assertSee($this->exam1->title)
            ->assertSee('Tuntas (Lulus KKM)')
            ->assertSee('LULUS');

        // Class performance report
        $this->actingAs($this->admin)->get("/admin/reports/classes/{$this->class->id}")
            ->assertStatus(200)
            ->assertSee($this->class->name)
            ->assertSee('Siswa Laporan Test');

        // Item difficulty analysis
        $this->actingAs($this->admin)->get("/admin/reports/item-analysis/{$this->exam1->id}")
            ->assertStatus(200)
            ->assertSee('Analisis Butir Soal')
            ->assertSee('Berapa hasil dari 5 + 5?')
            ->assertSee('Mudah'); // 100% difficulty index -> 'mudah'
    }

    /**
     * 5. Guru can view own exam reports and is isolated from other guru exams
     */
    public function test_05_guru_reports_ownership_and_anti_idor(): void
    {
        // Guru 1 can view own exam report
        $this->actingAs($this->guru1)->get("/guru/reports/exams/{$this->exam1->id}")
            ->assertStatus(200)
            ->assertSee($this->exam1->title)
            ->assertSee('Tuntas (Lulus KKM)');

        // Guru 1 can view item analysis of own exam
        $this->actingAs($this->guru1)->get("/guru/reports/item-analysis/{$this->exam1->id}")
            ->assertStatus(200)
            ->assertSee('Mudah');

        // Guru 1 CANNOT view Exam 2 created by Guru 2 (Anti-IDOR HTTP 403)
        $this->actingAs($this->guru1)->get("/guru/reports/exams/{$this->exam2->id}")
            ->assertStatus(403);

        $this->actingAs($this->guru1)->get("/guru/reports/item-analysis/{$this->exam2->id}")
            ->assertStatus(403);

        // Guru 1 hub only shows own exams
        $this->actingAs($this->guru1)->get('/guru/reports')
            ->assertStatus(200)
            ->assertSee($this->exam1->title)
            ->assertDontSee($this->exam2->title);
    }

    /**
     * 6. Nonexistent exam or class returns 404
     */
    public function test_06_nonexistent_resources_return_404(): void
    {
        $this->actingAs($this->admin)->get('/admin/reports/exams/99999')->assertStatus(404);
        $this->actingAs($this->admin)->get('/admin/reports/classes/99999')->assertStatus(404);
        $this->actingAs($this->admin)->get('/admin/reports/item-analysis/99999')->assertStatus(404);
    }

    /**
     * 7. Export exam results to CSV and PDF (with anti-IDOR verification)
     */
    public function test_07_export_csv_and_pdf_reports(): void
    {
        // Admin CSV export
        $response = $this->actingAs($this->admin)->get("/admin/reports/exams/{$this->exam1->id}/export-csv");
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Admin PDF print view
        $this->actingAs($this->admin)->get("/admin/reports/exams/{$this->exam1->id}/export-pdf")
            ->assertStatus(200)
            ->assertSee('LAPORAN HASIL NILAI UJIAN BERBASIS KOMPUTER (CBT)')
            ->assertSee('Siswa Laporan Test');

        // Guru 1 can export own exam
        $this->actingAs($this->guru1)->get("/guru/reports/exams/{$this->exam1->id}/export-csv")
            ->assertStatus(200);

        $this->actingAs($this->guru1)->get("/guru/reports/exams/{$this->exam1->id}/export-pdf")
            ->assertStatus(200);

        // Guru 1 CANNOT export exam created by Guru 2 (Anti-IDOR HTTP 403)
        $this->actingAs($this->guru1)->get("/guru/reports/exams/{$this->exam2->id}/export-csv")
            ->assertStatus(403);

        $this->actingAs($this->guru1)->get("/guru/reports/exams/{$this->exam2->id}/export-pdf")
            ->assertStatus(403);
    }
}
