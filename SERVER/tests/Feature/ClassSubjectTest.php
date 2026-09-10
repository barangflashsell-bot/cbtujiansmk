<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Question;
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClassSubjectTest extends TestCase
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

    // ==========================================
    // 1. CLASSES API TESTS
    // ==========================================

    public function test_admin_can_list_classes_with_pagination(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/classes?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items' => [
                        '*' => ['id', 'name', 'level', 'academic_year', 'status', 'students_count'],
                    ],
                    'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
                ],
            ]);
    }

    public function test_admin_can_view_single_class(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $class = Classes::first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/classes/'.$class->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $class->id,
                    'name' => $class->name,
                ],
            ]);
    }

    public function test_admin_can_create_class(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/classes', [
                'name' => '8B',
                'level' => '8',
                'academic_year' => '2026/2027',
                'status' => 'active',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '8B',
                    'level' => '8',
                    'academic_year' => '2026/2027',
                ],
            ]);

        $this->assertDatabaseHas('classes', ['name' => '8B', 'level' => '8']);
    }

    public function test_admin_can_update_class(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $class = Classes::create([
            'name' => '7C-Temp',
            'level' => '7',
            'academic_year' => '2026/2027',
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/classes/'.$class->id, [
                'name' => '7C',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '7C',
                ],
            ]);

        $this->assertEquals('7C', $class->fresh()->name);
    }

    public function test_admin_can_delete_class_without_students(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $class = Classes::create([
            'name' => 'Empty-Class',
            'level' => '9',
            'academic_year' => '2026/2027',
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/classes/'.$class->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Kelas berhasil dihapus',
            ]);

        $this->assertDatabaseMissing('classes', ['id' => $class->id]);
    }

    public function test_delete_class_with_enrolled_students_is_prevented(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        // Get class that has student enrolled
        $classWithStudent = Classes::whereHas('students')->first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/classes/'.$classWithStudent->id);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Tidak dapat menghapus kelas karena masih memiliki siswa terdaftar',
            ]);

        $this->assertDatabaseHas('classes', ['id' => $classWithStudent->id]);
    }

    public function test_class_validation_fails_when_fields_are_missing(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/classes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'level', 'academic_year']);
    }

    public function test_class_student_relationship_works(): void
    {
        $class = Classes::whereHas('students')->first();
        $this->assertNotNull($class);
        $this->assertGreaterThan(0, $class->students()->count());
        $this->assertInstanceOf(Student::class, $class->students->first());
    }

    public function test_peserta_cannot_manage_classes(): void
    {
        $pesertaToken = $this->getAuthToken('peserta', 'peserta123');

        $response = $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->getJson('/api/v1/classes');

        $response->assertStatus(403);
    }

    public function test_class_not_found_returns_404(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/classes/999999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Kelas tidak ditemukan',
            ]);
    }

    // ==========================================
    // 2. SUBJECTS API TESTS
    // ==========================================

    public function test_admin_can_list_subjects_with_pagination(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/subjects?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items' => [
                        '*' => ['id', 'code', 'name', 'status', 'questions_count', 'exams_count'],
                    ],
                    'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
                ],
            ]);
    }

    public function test_admin_can_view_single_subject(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = Subject::firstOrCreate(
            ['code' => 'MAT-9'],
            ['name' => 'Matematika Kelas 9', 'status' => 'active']
        );

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/subjects/'.$subject->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $subject->id,
                    'code' => 'MAT-9',
                ],
            ]);
    }

    public function test_admin_can_create_subject(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/subjects', [
                'code' => 'BIN-9',
                'name' => 'Bahasa Indonesia Kelas 9',
                'status' => 'active',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'code' => 'BIN-9',
                    'name' => 'Bahasa Indonesia Kelas 9',
                ],
            ]);

        $this->assertDatabaseHas('subjects', ['code' => 'BIN-9']);
    }

    public function test_duplicate_subject_code_is_rejected(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        Subject::firstOrCreate(
            ['code' => 'DUPLIKAT-01'],
            ['name' => 'Mata Pelajaran Duplikat', 'status' => 'active']
        );

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/subjects', [
                'code' => 'DUPLIKAT-01',
                'name' => 'Mata Pelajaran Kembar',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_admin_can_update_subject(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = Subject::create([
            'code' => 'ING-TMP',
            'name' => 'Bahasa Inggris Temp',
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/subjects/'.$subject->id, [
                'name' => 'Bahasa Inggris Resmi',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Bahasa Inggris Resmi',
                ],
            ]);

        $this->assertEquals('Bahasa Inggris Resmi', $subject->fresh()->name);
    }

    public function test_admin_can_delete_subject_without_questions(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = Subject::create([
            'code' => 'KIM-DEL',
            'name' => 'Kimia Yang Dihapus',
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/subjects/'.$subject->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Mata pelajaran berhasil dihapus',
            ]);

        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    public function test_subject_with_questions_cannot_be_deleted(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $subject = Subject::create([
            'code' => 'BIO-LOCKED',
            'name' => 'Biologi Terikat Soal',
            'status' => 'active',
        ]);

        $teacher = Teacher::first();

        // Attach a question to subject
        Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Contoh soal biologi',
            'score_weight' => 1.00,
            'difficulty' => 'easy',
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/subjects/'.$subject->id);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Tidak dapat menghapus mata pelajaran karena terhubung dengan bank soal atau ujian',
            ]);

        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }

    public function test_subject_questions_relationship_intact(): void
    {
        $subject = Subject::whereHas('questions')->first();
        if ($subject) {
            $this->assertGreaterThan(0, $subject->questions()->count());
            $this->assertInstanceOf(Question::class, $subject->questions->first());
        } else {
            $this->assertTrue(method_exists(Subject::class, 'questions'));
        }
    }

    public function test_peserta_cannot_manage_subjects(): void
    {
        $pesertaToken = $this->getAuthToken('peserta', 'peserta123');

        $response = $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->getJson('/api/v1/subjects');

        $response->assertStatus(403);
    }

    public function test_subject_not_found_returns_404(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/subjects/999999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Mata pelajaran tidak ditemukan',
            ]);
    }

    public function test_unauthenticated_request_to_classes_and_subjects_returns_401(): void
    {
        $response1 = $this->getJson('/api/v1/classes');
        $response1->assertStatus(401);

        $response2 = $this->getJson('/api/v1/subjects');
        $response2->assertStatus(401);
    }
}
