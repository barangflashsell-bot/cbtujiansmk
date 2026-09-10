<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
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
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CbtDatabaseTest extends TestCase
{
    use DatabaseTransactions;
    public function test_seeder_populated_roles_and_admin(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'teacher']);
        $this->assertDatabaseHas('roles', ['name' => 'student']);
        $this->assertDatabaseHas('users', ['username' => 'admin']);
    }

    public function test_foreign_key_constraint_enforcement(): void
    {
        $this->expectException(QueryException::class);

        // Attempting to create a student with a non-existent user_id must fail
        Student::create([
            'user_id' => 999999,
            'class_id' => 999999,
            'nis' => 'TEST-NIS-INVALID',
            'gender' => 'L',
        ]);
    }

    public function test_unique_constraint_enforcement(): void
    {
        $teacherRole = Role::where('name', 'teacher')->first();

        User::create([
            'role_id' => $teacherRole->id,
            'username' => 'unique_guru_1',
            'name' => 'Guru Satu',
            'password' => Hash::make('secret123'),
        ]);

        $this->expectException(QueryException::class);

        // Attempting duplicate username must fail
        User::create([
            'role_id' => $teacherRole->id,
            'username' => 'unique_guru_1',
            'name' => 'Guru Duplikat',
            'password' => Hash::make('secret123'),
        ]);
    }

    public function test_full_model_relationships_and_data_flow(): void
    {
        $teacherRole = Role::where('name', 'teacher')->first();
        $studentRole = Role::where('name', 'student')->first();

        // 1. Create Teacher User & Profile
        $teacherUser = User::create([
            'role_id' => $teacherRole->id,
            'username' => 'guru_ipa_1',
            'name' => 'Budi Santoso, S.Pd',
            'password' => Hash::make('password123'),
        ]);

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => '198501012010011001',
            'phone' => '081234567890',
        ]);

        // 2. Create Class
        $class = Classes::create([
            'name' => '9A',
            'level' => '9',
            'academic_year' => '2026/2027',
            'status' => 'active',
        ]);

        // 3. Create Student User & Profile
        $studentUser = User::create([
            'role_id' => $studentRole->id,
            'username' => 'siswa_001',
            'name' => 'Ahmad Dahlan',
            'password' => Hash::make('siswa123'),
        ]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'nis' => 'NIS2026001',
            'nisn' => '0089123456',
            'gender' => 'L',
        ]);

        // 4. Create Subject
        $subject = Subject::create([
            'code' => 'IPA-9',
            'name' => 'Ilmu Pengetahuan Alam',
            'status' => 'active',
        ]);

        // 5. Create Question & Options
        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'question_type' => 'single_choice',
            'content' => 'Organ tubuh manusia yang berfungsi memompa darah adalah?',
            'score_weight' => 2.00,
            'difficulty' => 'easy',
            'status' => 'active',
        ]);

        $optA = QuestionOption::create([
            'question_id' => $question->id,
            'option_label' => 'A',
            'content' => 'Paru-paru',
            'is_correct' => false,
        ]);

        $optB = QuestionOption::create([
            'question_id' => $question->id,
            'option_label' => 'B',
            'content' => 'Jantung',
            'is_correct' => true,
        ]);

        // 6. Create Exam
        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $teacherUser->id,
            'title' => 'Penilaian Tengah Semester IPA',
            'duration_minutes' => 60,
            'passing_score' => 75.00,
            'token' => 'PTS9IPA',
            'start_window' => now()->subHour(),
            'end_window' => now()->addHours(3),
            'status' => 'active',
        ]);

        // Attach Question to Exam
        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'order_index' => 1,
            'weight' => 2.00,
        ]);

        // Attach Student as Participant
        ExamParticipant::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'allow_retest' => false,
        ]);

        // 7. Exam Attempt
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(60),
            'status' => 'in_progress',
            'ip_address' => '192.168.1.50',
            'device_info' => 'Android SDK 34 Flutter Client',
        ]);

        // 8. Answer
        $answer = Answer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $optB->id,
            'selected_option' => 'B',
            'is_marked' => false,
            'is_correct' => true,
            'earned_score' => 2.00,
            'answered_at' => now(),
            'synced_at' => now(),
        ]);

        // 9. Result
        $result = Result::create([
            'attempt_id' => $attempt->id,
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'correct_count' => 1,
            'wrong_count' => 0,
            'unanswered_count' => 0,
            'mc_score' => 100.00,
            'score' => 100.00,
            'final_score' => 100.00,
            'status' => 'completed',
            'is_published' => false,
            'graded_at' => now(),
        ]);

        // 10. Activity Log
        $log = ActivityLog::create([
            'user_id' => $studentUser->id,
            'action' => 'SUBMIT_EXAM',
            'module' => 'EXAM',
            'ip_address' => '192.168.1.50',
            'details' => 'Siswa menyelesaikan ujian PTS IPA',
        ]);

        // VERIFY RELATIONSHIPS
        $this->assertEquals($teacherRole->id, $teacherUser->role->id);
        $this->assertEquals($teacherUser->id, $teacher->user->id);
        $this->assertEquals(1, $teacher->questions->count());

        $this->assertEquals($class->id, $student->schoolClass->id);
        $this->assertEquals($studentUser->id, $student->user->id);
        $this->assertEquals(1, $class->students->count());

        $this->assertEquals(2, $question->options->count());
        $this->assertEquals('IPA-9', $question->subject->code);
        $this->assertEquals(1, $exam->questions->count());
        $this->assertEquals(1, $exam->students->count());

        $this->assertEquals($attempt->id, $answer->attempt->id);
        $this->assertEquals('Jantung', $answer->selectedOption->content);
        $this->assertEquals($attempt->id, $result->attempt->id);
        $this->assertEquals(100.00, (float) $attempt->result->final_score);

        $this->assertEquals('SUBMIT_EXAM', $log->action);
        $this->assertEquals($studentUser->id, $log->user->id);
    }

    public function test_database_transaction_atomicity(): void
    {
        $initialSubjectCount = Subject::count();

        try {
            DB::transaction(function () {
                Subject::create([
                    'code' => 'TEST-TX-1',
                    'name' => 'Mata Pelajaran Transaksi',
                    'status' => 'active',
                ]);

                // Simulate failure to trigger rollback
                throw new Exception('Rollback transaction test');
            });
        } catch (Exception $e) {
            // Expected exception
        }

        // Verify that subject was rolled back and count remains unchanged
        $this->assertEquals($initialSubjectCount, Subject::count());
        $this->assertDatabaseMissing('subjects', ['code' => 'TEST-TX-1']);
    }
}
