<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MasterDataBankSoalWebTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $guruUser1;
    protected User $guruUser2;
    protected Teacher $teacher1;
    protected Teacher $teacher2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->first();

        // Setup Guru 1
        $guruRole = Role::where('name', 'teacher')->first();
        $this->guruUser1 = User::firstOrCreate(
            ['username' => 'guru_test_1'],
            [
                'name' => 'Guru Test 1',
                'email' => 'guru1@test.com',
                'password' => bcrypt('password'),
                'role_id' => $guruRole->id,
                'status' => 'active',
            ]
        );
        $this->teacher1 = Teacher::firstOrCreate(
            ['user_id' => $this->guruUser1->id],
            [
                'nip' => '198001012023011001',
                'name' => 'Guru Test 1',
                'phone' => '081234567801',
            ]
        );

        // Setup Guru 2
        $this->guruUser2 = User::firstOrCreate(
            ['username' => 'guru_test_2'],
            [
                'name' => 'Guru Test 2',
                'email' => 'guru2@test.com',
                'password' => bcrypt('password'),
                'role_id' => $guruRole->id,
                'status' => 'active',
            ]
        );
        $this->teacher2 = Teacher::firstOrCreate(
            ['user_id' => $this->guruUser2->id],
            [
                'nip' => '198001012023011002',
                'name' => 'Guru Test 2',
                'phone' => '081234567802',
            ]
        );
    }

    /**
     * 1. Guest is redirected to login for all protected pages
     */
    public function test_01_guest_redirected_to_login(): void
    {
        $this->get('/admin/classes')->assertRedirect(route('login'));
        $this->get('/admin/subjects')->assertRedirect(route('login'));
        $this->get('/admin/teachers')->assertRedirect(route('login'));
        $this->get('/admin/students')->assertRedirect(route('login'));
        $this->get('/admin/questions')->assertRedirect(route('login'));
        $this->get('/guru/students')->assertRedirect(route('login'));
        $this->get('/guru/questions')->assertRedirect(route('login'));
    }

    /**
     * 2. Guru is forbidden from accessing Admin Master Data routes
     */
    public function test_02_guru_forbidden_from_admin_master_data_routes(): void
    {
        $this->actingAs($this->guruUser1)->get('/admin/classes')->assertStatus(403);
        $this->actingAs($this->guruUser1)->get('/admin/subjects')->assertStatus(403);
        $this->actingAs($this->guruUser1)->get('/admin/teachers')->assertStatus(403);
        $this->actingAs($this->guruUser1)->get('/admin/students')->assertStatus(403);
        $this->actingAs($this->guruUser1)->get('/admin/questions')->assertStatus(403);
    }

    /**
     * 3. Admin can access all Master Data and Bank Soal pages
     */
    public function test_03_admin_can_access_all_master_data_and_bank_soal(): void
    {
        $this->actingAs($this->admin)->get('/admin/classes')->assertStatus(200)->assertSee('Kelas');
        $this->actingAs($this->admin)->get('/admin/subjects')->assertStatus(200)->assertSee('Mata Pelajaran');
        $this->actingAs($this->admin)->get('/admin/teachers')->assertStatus(200)->assertSee('Guru');
        $this->actingAs($this->admin)->get('/admin/students')->assertStatus(200)->assertSee('Peserta');
        $this->actingAs($this->admin)->get('/admin/questions')->assertStatus(200)->assertSee('Bank Soal');
        $this->actingAs($this->admin)->get('/admin/questions/create')->assertStatus(200)->assertSee('Buat Butir Soal Baru');
    }

    /**
     * 4. Guru can access Guru student list and Guru question bank
     */
    public function test_04_guru_can_access_student_list_and_own_bank_soal(): void
    {
        $this->actingAs($this->guruUser1)->get('/guru/students')->assertStatus(200)->assertSee('Daftar Siswa');
        $this->actingAs($this->guruUser1)->get('/guru/questions')->assertStatus(200)->assertSee('Bank Soal Saya');
        $this->actingAs($this->guruUser1)->get('/guru/questions/create')->assertStatus(200)->assertSee('Buat Butir Soal Baru');
    }

    /**
     * 5. Class CRUD tests (Validation, Create, Update, Delete)
     */
    public function test_05_class_crud_flow(): void
    {
        // Validation check
        $this->actingAs($this->admin)->post('/admin/classes', [])
            ->assertSessionHasErrors(['name', 'level', 'academic_year']);

        // Create
        $className = 'X-TEST-' . uniqid();
        $response = $this->actingAs($this->admin)->post('/admin/classes', [
            'name' => $className,
            'level' => '10',
            'academic_year' => '2025/2026',
        ]);
        $response->assertRedirect('/admin/classes');
        $this->assertDatabaseHas('classes', ['name' => $className]);

        $class = Classes::where('name', $className)->first();

        // Update
        $updatedName = $className . '-REV';
        $responseUpdate = $this->actingAs($this->admin)->put("/admin/classes/{$class->id}", [
            'name' => $updatedName,
            'level' => '10',
            'academic_year' => '2025/2026',
            'status' => 'active',
        ]);
        $responseUpdate->assertRedirect('/admin/classes');
        $this->assertDatabaseHas('classes', ['name' => $updatedName]);

        // Delete
        $responseDelete = $this->actingAs($this->admin)->delete("/admin/classes/{$class->id}");
        $responseDelete->assertRedirect('/admin/classes');
        $this->assertDatabaseMissing('classes', ['id' => $class->id]);
    }

    /**
     * 6. Subject CRUD tests (Validation, Create, Update, Delete)
     */
    public function test_06_subject_crud_flow(): void
    {
        // Validation check
        $this->actingAs($this->admin)->post('/admin/subjects', [])
            ->assertSessionHasErrors(['code', 'name']);

        // Create
        $code = 'SUB-' . rand(1000, 9999);
        $name = 'Subject Test ' . $code;
        $response = $this->actingAs($this->admin)->post('/admin/subjects', [
            'code' => $code,
            'name' => $name,
            'description' => 'Test Subject Description',
        ]);
        $response->assertRedirect('/admin/subjects');
        $this->assertDatabaseHas('subjects', ['code' => $code, 'name' => $name]);

        $subject = Subject::where('code', $code)->first();

        // Update
        $responseUpdate = $this->actingAs($this->admin)->put("/admin/subjects/{$subject->id}", [
            'code' => $code,
            'name' => $name . ' Updated',
            'description' => 'Updated Description',
            'status' => 'active',
        ]);
        $responseUpdate->assertRedirect('/admin/subjects');
        $this->assertDatabaseHas('subjects', ['name' => $name . ' Updated']);

        // Delete
        $responseDelete = $this->actingAs($this->admin)->delete("/admin/subjects/{$subject->id}");
        $responseDelete->assertRedirect('/admin/subjects');
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    /**
     * 7. Teacher CRUD flow
     */
    public function test_07_teacher_crud_flow(): void
    {
        $nip = '1990' . rand(10000000, 99999999);
        $username = 'guru_' . uniqid();

        // Validation
        $this->actingAs($this->admin)->post('/admin/teachers', [])
            ->assertSessionHasErrors(['name', 'username', 'password']);

        // Create
        $response = $this->actingAs($this->admin)->post('/admin/teachers', [
            'nip' => $nip,
            'name' => 'Guru Web Test',
            'username' => $username,
            'password' => 'password123',
            'phone' => '081234567890',
        ]);
        $response->assertRedirect('/admin/teachers');
        $this->assertDatabaseHas('teachers', ['nip' => $nip]);
        $this->assertDatabaseHas('users', ['username' => $username, 'name' => 'Guru Web Test']);

        $teacher = Teacher::where('nip', $nip)->first();

        // Update
        $responseUpdate = $this->actingAs($this->admin)->put("/admin/teachers/{$teacher->id}", [
            'name' => 'Guru Web Test Edit',
            'nip' => $nip,
            'phone' => '089999999999',
            'is_active' => 1,
        ]);
        $responseUpdate->assertRedirect('/admin/teachers');
        $this->assertDatabaseHas('users', ['id' => $teacher->user_id, 'name' => 'Guru Web Test Edit']);

        // Delete
        $userId = $teacher->user_id;
        $responseDelete = $this->actingAs($this->admin)->delete("/admin/teachers/{$teacher->id}");
        $responseDelete->assertRedirect('/admin/teachers');
        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    /**
     * 8. Student CRUD flow
     */
    public function test_08_student_crud_flow(): void
    {
        $class = Classes::firstOrCreate(
            ['name' => 'TEST-CLASS-STD'],
            ['level' => '10', 'academic_year' => '2025/2026', 'status' => 'active']
        );

        $nis = 'NIS' . rand(100000, 999999);
        $username = 'siswa_' . uniqid();

        // Validation
        $this->actingAs($this->admin)->post('/admin/students', [])
            ->assertSessionHasErrors(['nis', 'name', 'class_id', 'username', 'password', 'gender']);

        // Create
        $response = $this->actingAs($this->admin)->post('/admin/students', [
            'nis' => $nis,
            'nisn' => '00' . rand(10000000, 99999999),
            'name' => 'Siswa Web Test',
            'class_id' => $class->id,
            'gender' => 'L',
            'username' => $username,
            'password' => 'password123',
        ]);
        $response->assertRedirect('/admin/students');
        $this->assertDatabaseHas('students', ['nis' => $nis]);
        $this->assertDatabaseHas('users', ['username' => $username, 'name' => 'Siswa Web Test']);

        $student = Student::where('nis', $nis)->first();

        // Update
        $responseUpdate = $this->actingAs($this->admin)->put("/admin/students/{$student->id}", [
            'nis' => $nis,
            'name' => 'Siswa Web Test Edit',
            'class_id' => $class->id,
            'gender' => 'P',
            'is_active' => 1,
        ]);
        $responseUpdate->assertRedirect('/admin/students');
        $this->assertDatabaseHas('students', ['id' => $student->id, 'gender' => 'P']);
        $this->assertDatabaseHas('users', ['id' => $student->user_id, 'name' => 'Siswa Web Test Edit']);

        // Delete
        $userId = $student->user_id;
        $responseDelete = $this->actingAs($this->admin)->delete("/admin/students/{$student->id}");
        $responseDelete->assertRedirect('/admin/students');
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    /**
     * 9. Question CRUD with Options (Admin)
     */
    public function test_09_question_crud_with_options_by_admin(): void
    {
        $subject = Subject::firstOrCreate(
            ['code' => 'SB-Q-TEST'],
            ['name' => 'Mata Pelajaran Soal Test', 'status' => 'active']
        );

        // Validation
        $this->actingAs($this->admin)->post('/admin/questions', [])
            ->assertSessionHasErrors(['subject_id', 'question_type', 'content', 'score_weight', 'difficulty']);

        // Create single_choice question with options A, B, C, D
        $response = $this->actingAs($this->admin)->post('/admin/questions', [
            'subject_id' => $subject->id,
            'question_type' => 'single_choice',
            'content' => 'Berapa hasil dari 5 + 5?',
            'score_weight' => 2.5,
            'difficulty' => 'easy',
            'explanation' => '5 ditambah 5 hasilnya 10.',
            'correct_option' => 'B',
            'options' => [
                ['label' => 'A', 'content' => '8'],
                ['label' => 'B', 'content' => '10'],
                ['label' => 'C', 'content' => '12'],
                ['label' => 'D', 'content' => '15'],
            ],
        ]);
        $response->assertRedirect('/admin/questions');

        $question = Question::where('content', 'Berapa hasil dari 5 + 5?')->first();
        $this->assertNotNull($question);
        $this->assertEquals('single_choice', $question->question_type);
        $this->assertEquals(4, $question->options()->count());

        $correctOption = $question->options()->where('is_correct', true)->first();
        $this->assertNotNull($correctOption);
        $this->assertEquals('B', $correctOption->option_label);
        $this->assertEquals('10', $correctOption->content);

        // Update question content and correct answer to C
        $responseUpdate = $this->actingAs($this->admin)->put("/admin/questions/{$question->id}", [
            'subject_id' => $subject->id,
            'question_type' => 'single_choice',
            'content' => 'Berapa hasil dari 6 + 6?',
            'score_weight' => 3.0,
            'difficulty' => 'medium',
            'explanation' => '6 ditambah 6 hasilnya 12.',
            'correct_option' => 'C',
            'options' => [
                ['label' => 'A', 'content' => '8'],
                ['label' => 'B', 'content' => '10'],
                ['label' => 'C', 'content' => '12'],
                ['label' => 'D', 'content' => '15'],
            ],
        ]);
        $responseUpdate->assertRedirect('/admin/questions');

        $question->refresh();
        $this->assertEquals('Berapa hasil dari 6 + 6?', $question->content);
        $newCorrect = $question->options()->where('is_correct', true)->first();
        $this->assertEquals('C', $newCorrect->option_label);

        // Delete question
        $responseDelete = $this->actingAs($this->admin)->delete("/admin/questions/{$question->id}");
        $responseDelete->assertRedirect('/admin/questions');
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('question_options', ['question_id' => $question->id]);
    }

    /**
     * 10. Guru question creation and ownership authorization
     */
    public function test_10_guru_question_creation_and_ownership_authorization(): void
    {
        $subject = Subject::firstOrCreate(
            ['code' => 'SB-GURU-TEST'],
            ['name' => 'Mapel Guru Test', 'status' => 'active']
        );

        // Guru 1 creates a question
        $responseCreate = $this->actingAs($this->guruUser1)->post('/guru/questions', [
            'subject_id' => $subject->id,
            'question_type' => 'essay',
            'content' => 'Jelaskan konsep fotosintesis pada tumbuhan!',
            'score_weight' => 5.0,
            'difficulty' => 'hard',
            'explanation' => 'Fotosintesis adalah proses pembentukan karbohidrat...',
        ]);
        $responseCreate->assertRedirect('/guru/questions');

        $question = Question::where('content', 'Jelaskan konsep fotosintesis pada tumbuhan!')->first();
        $this->assertNotNull($question);
        $this->assertEquals($this->teacher1->id, $question->created_by);

        // Guru 1 can view edit page of own question
        $this->actingAs($this->guruUser1)->get("/guru/questions/{$question->id}/edit")
            ->assertStatus(200)
            ->assertSee('Jelaskan konsep fotosintesis pada tumbuhan!');

        // Guru 2 CANNOT edit Guru 1's question (403 Forbidden)
        $this->actingAs($this->guruUser2)->get("/guru/questions/{$question->id}/edit")
            ->assertStatus(403);

        // Guru 2 CANNOT update Guru 1's question (403 Forbidden)
        $this->actingAs($this->guruUser2)->put("/guru/questions/{$question->id}", [
            'subject_id' => $subject->id,
            'question_type' => 'essay',
            'content' => 'Diubah oleh Guru 2 tanpa izin!',
            'score_weight' => 5.0,
            'difficulty' => 'hard',
        ])->assertStatus(403);

        // Guru 2 CANNOT delete Guru 1's question (403 Forbidden)
        $this->actingAs($this->guruUser2)->delete("/guru/questions/{$question->id}")
            ->assertStatus(403);

        // Question remains untouched
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'created_by' => $this->teacher1->id]);

        // Guru 1 can delete own question
        $this->actingAs($this->guruUser1)->delete("/guru/questions/{$question->id}")
            ->assertRedirect('/guru/questions');
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }
}
