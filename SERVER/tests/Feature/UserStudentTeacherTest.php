<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserStudentTeacherTest extends TestCase
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
    // 1. USERS API TESTS
    // ==========================================

    public function test_admin_can_list_users_with_pagination(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/users?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items' => [
                        '*' => ['id', 'username', 'name', 'role'],
                    ],
                    'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
                ],
            ]);
    }

    public function test_admin_can_view_single_user(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $admin = User::where('username', 'admin')->first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/users/'.$admin->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $admin->id,
                    'username' => 'admin',
                ],
            ]);
    }

    public function test_admin_can_create_user_and_password_is_hashed(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $teacherRole = Role::where('name', 'teacher')->first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/users', [
                'username' => 'guru_baru_01',
                'name' => 'Guru Matematika Baru',
                'email' => 'gurubaru@cbt.local',
                'password' => 'rahasia123',
                'role_id' => $teacherRole->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'username' => 'guru_baru_01',
                    'name' => 'Guru Matematika Baru',
                ],
            ]);

        $createdUser = User::where('username', 'guru_baru_01')->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue(Hash::check('rahasia123', $createdUser->password));
        $this->assertStringNotContainsString('rahasia123', $response->getContent());
        $this->assertArrayNotHasKey('password', $response->json('data'));
    }

    public function test_admin_can_update_user(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $teacherRole = Role::where('name', 'teacher')->first();

        $user = User::create([
            'username' => 'user_to_edit',
            'name' => 'Nama Lama',
            'password' => Hash::make('password123'),
            'role_id' => $teacherRole->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/users/'.$user->id, [
                'name' => 'Nama Baru Diperbarui',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Nama Baru Diperbarui',
                ],
            ]);

        $this->assertEquals('Nama Baru Diperbarui', $user->fresh()->name);
    }

    public function test_admin_can_delete_user(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $teacherRole = Role::where('name', 'teacher')->first();

        $user = User::create([
            'username' => 'user_to_delete',
            'name' => 'Akan Dihapus',
            'password' => Hash::make('password123'),
            'role_id' => $teacherRole->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/users/'.$user->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User berhasil dihapus',
            ]);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_duplicate_username_is_rejected(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $teacherRole = Role::where('name', 'teacher')->first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/users', [
                'username' => 'admin', // already taken
                'name' => 'Duplikat Admin',
                'password' => 'admin123',
                'role_id' => $teacherRole->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $guruToken = $this->getAuthToken('guru', 'guru123');
        $pesertaToken = $this->getAuthToken('peserta', 'peserta123');

        // Guru cannot list users
        $response1 = $this->withHeader('Authorization', 'Bearer '.$guruToken)
            ->getJson('/api/v1/users');
        $response1->assertStatus(403);

        // Peserta cannot list users
        $response2 = $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->getJson('/api/v1/users');
        $response2->assertStatus(403);
    }

    // ==========================================
    // 2. STUDENTS API TESTS
    // ==========================================

    public function test_admin_can_list_students_with_class_and_user_relations(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/students');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items' => [
                        '*' => ['id', 'nis', 'gender', 'user', 'school_class'],
                    ],
                    'pagination',
                ],
            ]);
    }

    public function test_admin_can_create_student(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $class = Classes::first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/students', [
                'username' => 'siswa_api_01',
                'name' => 'Budi Siswa API',
                'password' => 'siswa123',
                'class_id' => $class->id,
                'nis' => 'NIS-API-001',
                'nisn' => '0089123401',
                'gender' => 'L',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nis' => 'NIS-API-001',
                    'user' => [
                        'username' => 'siswa_api_01',
                        'name' => 'Budi Siswa API',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('students', ['nis' => 'NIS-API-001']);
    }

    public function test_admin_can_view_single_student(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $student = Student::first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/students/'.$student->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $student->id,
                    'nis' => $student->nis,
                ],
            ]);
    }

    public function test_admin_can_update_student(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $student = Student::first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/students/'.$student->id, [
                'nisn' => '999888777',
                'name' => 'Nama Siswa Update',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nisn' => '999888777',
                ],
            ]);

        $this->assertEquals('999888777', $student->fresh()->nisn);
        $this->assertEquals('Nama Siswa Update', $student->user->fresh()->name);
    }

    public function test_admin_can_delete_student_without_attempts(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $studentRole = Role::where('name', 'student')->first();
        $class = Classes::first();

        $user = User::create([
            'username' => 'siswa_hapus',
            'name' => 'Siswa Hapus',
            'password' => Hash::make('pass123'),
            'role_id' => $studentRole->id,
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'nis' => 'NIS-DEL-999',
            'gender' => 'P',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/students/'.$student->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data siswa berhasil dihapus',
            ]);

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_peserta_cannot_manage_students(): void
    {
        $pesertaToken = $this->getAuthToken('peserta', 'peserta123');

        $response = $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->getJson('/api/v1/students');

        $response->assertStatus(403);
    }

    // ==========================================
    // 3. TEACHERS API TESTS
    // ==========================================

    public function test_admin_can_list_teachers_with_user_relation(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/teachers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items' => [
                        '*' => ['id', 'nip', 'phone', 'user'],
                    ],
                    'pagination',
                ],
            ]);
    }

    public function test_admin_can_create_teacher(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/teachers', [
                'username' => 'guru_fisika_01',
                'name' => 'Dewi Fisika, M.Pd',
                'password' => 'guru123',
                'nip' => '199001012015012001',
                'phone' => '0899887766',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nip' => '199001012015012001',
                    'user' => [
                        'username' => 'guru_fisika_01',
                        'name' => 'Dewi Fisika, M.Pd',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('teachers', ['nip' => '199001012015012001']);
    }

    public function test_admin_can_view_single_teacher(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $teacher = Teacher::first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/teachers/'.$teacher->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $teacher->id,
                    'nip' => $teacher->nip,
                ],
            ]);
    }

    public function test_admin_can_update_teacher(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $teacher = Teacher::first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/teachers/'.$teacher->id, [
                'phone' => '087711223344',
                'name' => 'Guru Nama Update',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'phone' => '087711223344',
                ],
            ]);

        $this->assertEquals('087711223344', $teacher->fresh()->phone);
        $this->assertEquals('Guru Nama Update', $teacher->user->fresh()->name);
    }

    public function test_admin_can_delete_teacher_without_questions(): void
    {
        $token = $this->getAuthToken('admin', 'admin123');
        $teacherRole = Role::where('name', 'teacher')->first();

        $user = User::create([
            'username' => 'guru_hapus',
            'name' => 'Guru Hapus',
            'password' => Hash::make('pass123'),
            'role_id' => $teacherRole->id,
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'nip' => '199512312020011999',
            'phone' => '0855443322',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/teachers/'.$teacher->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data guru berhasil dihapus',
            ]);

        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_peserta_cannot_manage_teachers(): void
    {
        $pesertaToken = $this->getAuthToken('peserta', 'peserta123');

        $response = $this->withHeader('Authorization', 'Bearer '.$pesertaToken)
            ->getJson('/api/v1/teachers');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/users');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/students');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/teachers');
        $response->assertStatus(401);
    }
}
