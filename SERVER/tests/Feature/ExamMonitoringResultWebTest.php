<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\Result;
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExamMonitoringResultWebTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $guruUser1;
    protected User $guruUser2;
    protected Teacher $teacher1;
    protected Teacher $teacher2;
    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->first();

        $teacherRole = Role::where('name', 'teacher')->first();

        $this->guruUser1 = User::firstOrCreate(
            ['username' => 'guru_exam_1'],
            [
                'name' => 'Guru Exam 1',
                'email' => 'guru_exam1@test.com',
                'password' => bcrypt('password'),
                'role_id' => $teacherRole->id,
                'is_active' => true,
            ]
        );
        $this->teacher1 = Teacher::firstOrCreate(
            ['user_id' => $this->guruUser1->id],
            ['nip' => '198501012023011001', 'phone' => '081234567811']
        );

        $this->guruUser2 = User::firstOrCreate(
            ['username' => 'guru_exam_2'],
            [
                'name' => 'Guru Exam 2',
                'email' => 'guru_exam2@test.com',
                'password' => bcrypt('password'),
                'role_id' => $teacherRole->id,
                'is_active' => true,
            ]
        );
        $this->teacher2 = Teacher::firstOrCreate(
            ['user_id' => $this->guruUser2->id],
            ['nip' => '198501012023011002', 'phone' => '081234567822']
        );

        $this->subject = Subject::firstOrCreate(
            ['code' => 'EXAM-SUB-TEST'],
            ['name' => 'Mapel Ujian Test', 'status' => 'active']
        );
    }

    /**
     * 1. Guest redirected to login for all exam, monitoring, result routes
     */
    public function test_01_guest_redirected_to_login(): void
    {
        $this->get('/admin/exams')->assertRedirect(route('login'));
        $this->get('/admin/monitoring')->assertRedirect(route('login'));
        $this->get('/admin/results')->assertRedirect(route('login'));
        $this->get('/guru/exams')->assertRedirect(route('login'));
        $this->get('/guru/monitoring')->assertRedirect(route('login'));
        $this->get('/guru/results')->assertRedirect(route('login'));
    }

    /**
     * 2. Guru forbidden from admin routes
     */
    public function test_02_guru_forbidden_from_admin_routes(): void
    {
        $this->actingAs($this->guruUser1)->get('/admin/exams')->assertStatus(403);
        $this->actingAs($this->guruUser1)->get('/admin/monitoring')->assertStatus(403);
        $this->actingAs($this->guruUser1)->get('/admin/results')->assertStatus(403);
    }

    /**
     * 3. Admin Exam CRUD flow
     */
    public function test_03_admin_exam_crud_flow(): void
    {
        // Validation check
        $this->actingAs($this->admin)->post('/admin/exams', [])
            ->assertSessionHasErrors(['subject_id', 'title', 'duration_minutes', 'start_window', 'end_window', 'status']);

        // Create
        $title = 'Ujian Nasional Simulasi ' . uniqid();
        $response = $this->actingAs($this->admin)->post('/admin/exams', [
            'subject_id' => $this->subject->id,
            'title' => $title,
            'description' => 'Simulasi ujian online',
            'duration_minutes' => 60,
            'passing_score' => 75.0,
            'token' => 'SIMUL1',
            'start_window' => now()->format('Y-m-d H:i:s'),
            'end_window' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'shuffle_questions' => 1,
            'shuffle_options' => 1,
            'show_result' => 1,
            'status' => 'active',
        ]);

        $exam = Exam::where('title', $title)->first();
        $this->assertNotNull($exam);
        $response->assertRedirect(route('admin.exams.show', $exam->id));
        $this->assertDatabaseHas('exams', ['title' => $title, 'token' => 'SIMUL1']);

        // Show page
        $this->actingAs($this->admin)->get("/admin/exams/{$exam->id}")
            ->assertStatus(200)
            ->assertSee($title);

        // Edit & Update
        $updatedTitle = $title . ' REV';
        $responseUpdate = $this->actingAs($this->admin)->put("/admin/exams/{$exam->id}", [
            'subject_id' => $this->subject->id,
            'title' => $updatedTitle,
            'duration_minutes' => 90,
            'start_window' => now()->format('Y-m-d H:i:s'),
            'end_window' => now()->addHours(3)->format('Y-m-d H:i:s'),
            'status' => 'published',
        ]);
        $responseUpdate->assertRedirect(route('admin.exams.show', $exam->id));
        $this->assertDatabaseHas('exams', ['title' => $updatedTitle, 'duration_minutes' => 90]);

        // Delete
        $responseDelete = $this->actingAs($this->admin)->delete("/admin/exams/{$exam->id}");
        $responseDelete->assertRedirect(route('admin.exams.index'));
        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    /**
     * 4. Attach and Detach Question to Exam
     */
    public function test_04_attach_and_detach_question(): void
    {
        $exam = Exam::create([
            'subject_id' => $this->subject->id,
            'created_by' => $this->admin->id,
            'title' => 'Exam Attach Question Test',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(2),
            'status' => 'draft',
        ]);

        $question = Question::create([
            'subject_id' => $this->subject->id,
            'created_by' => $this->teacher1->id,
            'question_type' => 'single_choice',
            'content' => 'Soal untuk dilampirkan ke ujian',
            'score_weight' => 2.5,
            'difficulty' => 'medium',
            'status' => 'active',
        ]);

        // Attach question
        $responseAttach = $this->actingAs($this->admin)->post("/admin/exams/{$exam->id}/questions", [
            'question_id' => $question->id,
            'order_index' => 1,
            'weight' => 2.5,
        ]);
        $responseAttach->assertSessionHas('success');
        $this->assertDatabaseHas('exam_questions', [
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'order_index' => 1,
        ]);

        // Detach question
        $responseDetach = $this->actingAs($this->admin)->delete("/admin/exams/{$exam->id}/questions/{$question->id}");
        $responseDetach->assertSessionHas('success');
        $this->assertDatabaseMissing('exam_questions', [
            'exam_id' => $exam->id,
            'question_id' => $question->id,
        ]);
    }

    /**
     * 5. Participant enrollment and retest toggle
     */
    public function test_05_participant_enrollment_and_retest(): void
    {
        $class = Classes::firstOrCreate(
            ['name' => 'XII-IPA-EXAM'],
            ['level' => '12', 'academic_year' => '2025/2026', 'status' => 'active']
        );

        $studentRole = Role::where('name', 'student')->first();
        $studentUser = User::create([
            'username' => 'std_exam_' . uniqid(),
            'name' => 'Siswa Exam Test',
            'password' => bcrypt('password'),
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'nis' => 'EXAM' . rand(10000, 99999),
            'gender' => 'L',
        ]);

        $exam = Exam::create([
            'subject_id' => $this->subject->id,
            'created_by' => $this->admin->id,
            'title' => 'Exam Enroll Participant Test',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(2),
            'status' => 'draft',
        ]);

        // Enroll by class
        $responseClass = $this->actingAs($this->admin)->post("/admin/exams/{$exam->id}/participants", [
            'class_id' => $class->id,
        ]);
        $responseClass->assertSessionHas('success');
        $this->assertDatabaseHas('exam_participants', [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
        ]);

        $participant = ExamParticipant::where('exam_id', $exam->id)->where('student_id', $student->id)->first();
        $this->assertFalse($participant->allow_retest);

        // Toggle retest
        $responseRetest = $this->actingAs($this->admin)->post("/admin/exams/{$exam->id}/participants/{$participant->id}/retest");
        $responseRetest->assertSessionHas('success');
        $participant->refresh();
        $this->assertTrue($participant->allow_retest);

        // Remove participant
        $responseRemove = $this->actingAs($this->admin)->delete("/admin/exams/{$exam->id}/participants/{$participant->id}");
        $responseRemove->assertSessionHas('success');
        $this->assertDatabaseMissing('exam_participants', ['id' => $participant->id]);
    }

    /**
     * 6. Live monitoring overview and detail
     */
    public function test_06_live_monitoring_overview_and_detail(): void
    {
        $exam = Exam::create([
            'subject_id' => $this->subject->id,
            'created_by' => $this->admin->id,
            'title' => 'Monitoring Live Exam Test',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(2),
            'status' => 'active',
        ]);

        // Overview
        $this->actingAs($this->admin)->get('/admin/monitoring')
            ->assertStatus(200)
            ->assertSee('Live Monitoring Ujian')
            ->assertSee('Monitoring Live Exam Test');

        // Detail
        $this->actingAs($this->admin)->get("/admin/monitoring/exams/{$exam->id}")
            ->assertStatus(200)
            ->assertSee('Monitoring Live Exam Test')
            ->assertSee('Total Peserta Terdaftar');
    }

    /**
     * 7. Results list, detail, and publish toggle
     */
    public function test_07_results_list_detail_and_publish(): void
    {
        $exam = Exam::create([
            'subject_id' => $this->subject->id,
            'created_by' => $this->admin->id,
            'title' => 'Result Exam Test',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(2),
            'status' => 'completed',
        ]);

        $class = Classes::firstOrCreate(
            ['name' => 'XII-IPA-RES'],
            ['level' => '12', 'academic_year' => '2025/2026', 'status' => 'active']
        );
        $studentRole = Role::where('name', 'student')->first();
        $studentUser = User::create([
            'username' => 'std_res_' . uniqid(),
            'name' => 'Siswa Result Test',
            'password' => bcrypt('password'),
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'nis' => 'RES' . rand(10000, 99999),
            'gender' => 'P',
        ]);

        $attempt = \App\Models\ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'started_at' => now()->subMinutes(60),
            'ends_at' => now()->subMinutes(10),
            'submitted_at' => now()->subMinutes(10),
            'status' => 'submitted',
        ]);

        $result = Result::create([
            'attempt_id' => $attempt->id,
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'correct_count' => 18,
            'wrong_count' => 2,
            'unanswered_count' => 0,
            'mc_score' => 90.0,
            'essay_score' => 0.0,
            'score' => 90.0,
            'final_score' => 90.0,
            'status' => 'completed',
            'is_published' => false,
            'graded_at' => now(),
        ]);

        // Results index
        $this->actingAs($this->admin)->get('/admin/results')
            ->assertStatus(200)
            ->assertSee('Hasil')
            ->assertSee('Siswa Result Test');

        // Results detail
        $this->actingAs($this->admin)->get("/admin/results/{$result->id}")
            ->assertStatus(200)
            ->assertSee('Siswa Result Test')
            ->assertSee('90.0');

        // Toggle publish
        $this->actingAs($this->admin)->post("/admin/results/{$result->id}/publish")
            ->assertSessionHas('success');
        $result->refresh();
        $this->assertTrue($result->is_published);
    }

    /**
     * 8. Guru exam ownership and scoping
     */
    public function test_08_guru_exam_ownership_and_scoping(): void
    {
        // Guru 1 creates Exam 1
        $exam1 = Exam::create([
            'subject_id' => $this->subject->id,
            'created_by' => $this->guruUser1->id,
            'title' => 'Exam Milik Guru 1',
            'duration_minutes' => 60,
            'start_window' => now(),
            'end_window' => now()->addHours(2),
            'status' => 'active',
        ]);

        // Guru 1 can view, edit Exam 1
        $this->actingAs($this->guruUser1)->get("/guru/exams/{$exam1->id}")
            ->assertStatus(200)
            ->assertSee('Exam Milik Guru 1');

        $this->actingAs($this->guruUser1)->get("/guru/exams/{$exam1->id}/edit")
            ->assertStatus(200);

        // Guru 2 CANNOT view, edit, update, or delete Exam 1 (403 Forbidden)
        $this->actingAs($this->guruUser2)->get("/guru/exams/{$exam1->id}")
            ->assertStatus(403);

        $this->actingAs($this->guruUser2)->get("/guru/exams/{$exam1->id}/edit")
            ->assertStatus(403);

        $this->actingAs($this->guruUser2)->put("/guru/exams/{$exam1->id}", [
            'subject_id' => $this->subject->id,
            'title' => 'Diubah Guru 2',
            'duration_minutes' => 60,
            'start_window' => now()->format('Y-m-d H:i:s'),
            'end_window' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'status' => 'published',
        ])->assertStatus(403);

        $this->actingAs($this->guruUser2)->delete("/guru/exams/{$exam1->id}")
            ->assertStatus(403);

        // Guru 2 cannot access monitoring or results of Guru 1's exam
        $this->actingAs($this->guruUser2)->get("/guru/monitoring/exams/{$exam1->id}")
            ->assertStatus(403);

        // Exam 1 remains in database
        $this->assertDatabaseHas('exams', ['id' => $exam1->id, 'created_by' => $this->guruUser1->id]);
    }
}
