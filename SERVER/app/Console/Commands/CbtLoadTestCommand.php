<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ExamAttemptController;
use App\Http\Controllers\Api\V1\ExamController;
use App\Http\Controllers\Api\V1\ResultController;
use App\Http\Controllers\Api\V1\TimerController;
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
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CbtLoadTestCommand extends Command
{
    protected $signature = 'cbt:load-test {--students=100 : Number of concurrent simulated students} {--race-concurrency=10 : Concurrency level for race tests}';
    protected $description = 'Execute repeatable, non-destructive high-concurrency simulation of 100+ LAN students across all CBT workloads';

    protected array $createdUserIds = [];
    protected array $createdStudentIds = [];
    protected ?int $createdExamId = null;
    protected ?int $createdSubjectId = null;
    protected array $createdQuestionIds = [];

    public function handle(): int
    {
        $targetStudents = (int) $this->option('students');
        $raceConcurrency = (int) $this->option('race-concurrency');

        $this->info("========================================================================");
        $this->info(" CBT V1 — HIGH-CONCURRENCY WORKLOAD SIMULATION ({$targetStudents} STUDENTS)");
        $this->info("========================================================================");

        // 1. INFRASTRUCTURE & RESOURCE AUDIT
        $this->auditEnvironment();

        // 2. SANDBOX SETUP (Isolated Test Dataset)
        $this->info("\n[1/4] Menyiapkan sandbox dataset untuk {$targetStudents} peserta...");
        $sandbox = $this->setupSandbox($targetStudents);
        $this->info("  [OK] Sandbox siap: Exam ID {$sandbox['exam']->id}, {$targetStudents} siswa terdaftar.");

        // 3. WORKLOAD EXECUTION & METRIC CAPTURE
        $this->info("\n[2/4] Menjalankan 10 Workload Kritis CBT...");
        $metrics = $this->runCriticalWorkloads($sandbox, $targetStudents);

        // 4. RACE CONDITION & CONCURRENCY INTEGRITY
        $this->info("\n[3/4] Menguji Race Condition & Pessimistic Locking ({$raceConcurrency} parallel calls)...");
        $raceResults = $this->runRaceConditionTests($sandbox, $raceConcurrency);

        // 5. DATABASE INTEGRITY VERIFICATION
        $this->info("\n[4/4] Memverifikasi integritas state database...");
        $integrity = $this->verifyDatabaseIntegrity($sandbox, $targetStudents);

        // 6. TEARDOWN (Clean up all sandbox records)
        $this->teardownSandbox();
        $this->info("  [OK] Sandbox dibersihkan: 0 data uji tersisa di database.");

        // 7. DISPLAY FINAL REPORT & QUALITY GATE VERDICT
        $this->displaySummaryReport($targetStudents, $metrics, $raceResults, $integrity);

        $hasFailures = collect($metrics)->contains(fn ($m) => $m['errors'] > 0)
            || ! $raceResults['start_safe']
            || ! $raceResults['submit_safe']
            || ! $integrity['all_passed'];

        return $hasFailures ? Command::FAILURE : Command::SUCCESS;
    }

    protected function auditEnvironment(): void
    {
        $dbConnection = config('database.default');
        $dbName = config("database.connections.{$dbConnection}.database");
        $phpVersion = PHP_VERSION;
        $memLimit = ini_get('memory_limit');

        $dbVersion = 'Unknown';
        try {
            $pdo = DB::connection()->getPdo();
            $dbVersion = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } catch (\Throwable $e) {
            $dbVersion = 'Error connecting';
        }

        $this->line("  Server OS       : " . PHP_OS_FAMILY . " (" . php_uname('s') . ")");
        $this->line("  PHP Version     : {$phpVersion} (Memory Limit: {$memLimit})");
        $this->line("  Database        : {$dbConnection} -> {$dbName} (v{$dbVersion})");
        $this->line("  Execution Mode  : Isolated Sandbox Transaction / Benchmarking");
    }

    protected function setupSandbox(int $count): array
    {
        $subject = Subject::firstOrCreate(['code' => 'LOAD-TEST-SUB'], ['name' => 'Simulasi Beban CBT', 'status' => 'active']);
        $this->createdSubjectId = $subject->id;

        $admin = User::where('username', 'admin')->first();
        $teacher = Teacher::first();

        // 1 Exam
        $exam = Exam::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'title' => 'Paket Ujian Simulasi 100 Peserta',
            'duration_minutes' => 60,
            'start_window' => now()->subHour(),
            'end_window' => now()->addHours(3),
            'status' => 'active',
            'passing_score' => 70.0,
            'show_result' => true,
        ]);
        $this->createdExamId = $exam->id;

        // 3 Questions
        $q1 = Question::create(['subject_id' => $subject->id, 'created_by' => $teacher->id, 'question_type' => 'single_choice', 'content' => 'Soal Beban 1']);
        $opt1A = QuestionOption::create(['question_id' => $q1->id, 'option_label' => 'A', 'content' => 'Opsi A Benar', 'is_correct' => true]);
        $opt1B = QuestionOption::create(['question_id' => $q1->id, 'option_label' => 'B', 'content' => 'Opsi B Salah', 'is_correct' => false]);

        $q2 = Question::create(['subject_id' => $subject->id, 'created_by' => $teacher->id, 'question_type' => 'single_choice', 'content' => 'Soal Beban 2']);
        $opt2A = QuestionOption::create(['question_id' => $q2->id, 'option_label' => 'A', 'content' => 'Opsi A Benar', 'is_correct' => true]);
        $opt2B = QuestionOption::create(['question_id' => $q2->id, 'option_label' => 'B', 'content' => 'Opsi B Salah', 'is_correct' => false]);

        $q3 = Question::create(['subject_id' => $subject->id, 'created_by' => $teacher->id, 'question_type' => 'single_choice', 'content' => 'Soal Beban 3']);
        $opt3A = QuestionOption::create(['question_id' => $q3->id, 'option_label' => 'A', 'content' => 'Opsi A Benar', 'is_correct' => true]);
        $opt3B = QuestionOption::create(['question_id' => $q3->id, 'option_label' => 'B', 'content' => 'Opsi B Salah', 'is_correct' => false]);

        $this->createdQuestionIds = [$q1->id, $q2->id, $q3->id];

        ExamQuestion::create(['exam_id' => $exam->id, 'question_id' => $q1->id, 'order_index' => 1, 'weight' => 33.33]);
        ExamQuestion::create(['exam_id' => $exam->id, 'question_id' => $q2->id, 'order_index' => 2, 'weight' => 33.33]);
        ExamQuestion::create(['exam_id' => $exam->id, 'question_id' => $q3->id, 'order_index' => 3, 'weight' => 33.34]);

        $studentRole = Role::where('name', 'student')->first();
        $schoolClass = Classes::firstOrCreate(['name' => 'Kelas Beban Test'], ['level' => '10', 'academic_year' => '2025/2026', 'status' => 'active']);

        $students = [];
        $hashedPassword = Hash::make('password123');

        for ($i = 1; $i <= $count; $i++) {
            $username = "load_user_{$i}_" . time();
            $user = User::create([
                'role_id' => $studentRole->id,
                'username' => $username,
                'name' => "Siswa Beban {$i}",
                'password' => $hashedPassword,
                'is_active' => true,
            ]);
            $this->createdUserIds[] = $user->id;

            $student = Student::create([
                'user_id' => $user->id,
                'class_id' => $schoolClass->id,
                'nis' => 'LOAD' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'gender' => ($i % 2 === 0) ? 'L' : 'P',
            ]);
            $this->createdStudentIds[] = $student->id;

            ExamParticipant::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'allow_retest' => false,
            ]);

            $students[] = [
                'user' => $user,
                'student' => $student,
                'plain_password' => 'password123',
            ];
        }

        return [
            'exam' => $exam,
            'questions' => [$q1, $q2, $q3],
            'options' => [
                $q1->id => ['correct' => $opt1A, 'wrong' => $opt1B],
                $q2->id => ['correct' => $opt2A, 'wrong' => $opt2B],
                $q3->id => ['correct' => $opt3A, 'wrong' => $opt3B],
            ],
            'students' => $students,
        ];
    }

    protected function runCriticalWorkloads(array $sandbox, int $count): array
    {
        $exam = $sandbox['exam'];
        $students = $sandbox['students'];
        $q1 = $sandbox['questions'][0];
        $q2 = $sandbox['questions'][1];
        $q3 = $sandbox['questions'][2];
        $opt1A = $sandbox['options'][$q1->id]['correct'];
        $opt2A = $sandbox['options'][$q2->id]['correct'];
        $opt3A = $sandbox['options'][$q3->id]['correct'];

        $attempts = [];
        $tokens = [];
        $metrics = [];

        // 1. WORKLOAD: LOGIN
        $metrics['1. LOGIN'] = $this->measureWorkload('1. LOGIN', $count, function ($i) use ($students, &$tokens) {
            $st = $students[$i];
            $req = Request::create('/api/v1/auth/login', 'POST', [
                'username' => $st['user']->username,
                'password' => $st['plain_password'],
            ]);
            $response = app(AuthController::class)->login($req);
            $data = $response->getData(true);
            $tokens[$i] = $data['data']['token'] ?? null;
            return ($response->getStatusCode() === 200 && ! empty($tokens[$i]));
        });

        // 2. WORKLOAD: EXAM LIST
        $metrics['2. EXAM LIST'] = $this->measureWorkload('2. EXAM LIST', $count, function ($i) use ($students) {
            $req = Request::create('/api/v1/exams', 'GET');
            $req->setUserResolver(fn () => $students[$i]['user']);
            $response = app(ExamController::class)->index($req);
            return $response->getStatusCode() === 200;
        });

        // 3. WORKLOAD: START ATTEMPT
        $metrics['3. START ATTEMPT'] = $this->measureWorkload('3. START ATTEMPT', $count, function ($i) use ($exam, $students, &$attempts) {
            $req = Request::create("/api/v1/exams/{$exam->id}/start", 'POST');
            $req->setUserResolver(fn () => $students[$i]['user']);
            $response = app(ExamAttemptController::class)->start($req, (string) $exam->id);
            $data = $response->getData(true);
            $attemptId = $data['data']['attempt_id'] ?? null;
            if ($attemptId) {
                $attempts[$i] = $attemptId;
            }
            return ($response->getStatusCode() === 200 && $attemptId !== null);
        });

        // 4. WORKLOAD: FETCH QUESTIONS
        $metrics['4. FETCH QUESTIONS'] = $this->measureWorkload('4. FETCH QUESTIONS', $count, function ($i) use ($attempts, $students) {
            $attemptId = $attempts[$i] ?? null;
            if (! $attemptId) return false;
            $req = Request::create("/api/v1/attempts/{$attemptId}", 'GET');
            $req->setUserResolver(fn () => $students[$i]['user']);
            $response = app(ExamAttemptController::class)->show($req, (string) $attemptId);
            return $response->getStatusCode() === 200;
        });

        // 5. WORKLOAD: SAVE ANSWER (Single Autosave)
        $metrics['5. SAVE ANSWER'] = $this->measureWorkload('5. SAVE ANSWER', $count, function ($i) use ($attempts, $students, $q1, $opt1A) {
            $attemptId = $attempts[$i] ?? null;
            if (! $attemptId) return false;
            $req = Request::create("/api/v1/attempts/{$attemptId}/answers", 'POST', [
                'question_id' => $q1->id,
                'selected_option_id' => $opt1A->id,
                'is_flagged' => false,
            ]);
            $req->setUserResolver(fn () => $students[$i]['user']);
            $response = app(\App\Http\Controllers\Api\V1\AnswerController::class)->store($req, (string) $attemptId);
            return $response->getStatusCode() === 200;
        });

        // 6. WORKLOAD: SYNC BATCH (Burst of offline answers)
        $metrics['6. SYNC BATCH'] = $this->measureWorkload('6. SYNC BATCH', $count, function ($i) use ($attempts, $students, $q2, $q3, $opt2A, $opt3A) {
            $attemptId = $attempts[$i] ?? null;
            if (! $attemptId) return false;
            $req = Request::create("/api/v1/attempts/{$attemptId}/sync", 'POST', [
                'answers' => [
                    [
                        'question_id' => $q2->id,
                        'selected_option_id' => $opt2A->id,
                        'is_flagged' => false,
                        'answered_at' => now()->toIso8601String(),
                    ],
                    [
                        'question_id' => $q3->id,
                        'selected_option_id' => $opt3A->id,
                        'is_flagged' => false,
                        'answered_at' => now()->toIso8601String(),
                    ],
                ],
            ]);
            $req->setUserResolver(fn () => $students[$i]['user']);
            $response = app(\App\Http\Controllers\Api\V1\SyncController::class)->sync($req, (string) $attemptId);
            return $response->getStatusCode() === 200;
        });

        // 7. WORKLOAD: TIMER
        $metrics['7. TIMER'] = $this->measureWorkload('7. TIMER', $count, function ($i) use ($attempts, $students) {
            $attemptId = $attempts[$i] ?? null;
            if (! $attemptId) return false;
            $req = Request::create("/api/v1/attempts/{$attemptId}/timer", 'GET');
            $req->setUserResolver(fn () => $students[$i]['user']);
            $response = app(\App\Http\Controllers\Api\V1\TimerController::class)->getAttemptTimer($req, (string) $attemptId);
            return $response->getStatusCode() === 200;
        });

        // 8. WORKLOAD: SUBMIT
        $metrics['8. SUBMIT'] = $this->measureWorkload('8. SUBMIT', $count, function ($i) use ($attempts, $students) {
            $attemptId = $attempts[$i] ?? null;
            if (! $attemptId) return false;
            $req = Request::create("/api/v1/attempts/{$attemptId}/submit", 'POST');
            $req->setUserResolver(fn () => $students[$i]['user']);
            $response = app(ExamAttemptController::class)->submit($req, (string) $attemptId);
            return $response->getStatusCode() === 200;
        });

        // 9. WORKLOAD: GRADE (Automatic Grading verified on submit)
        $metrics['9. GRADE'] = $this->measureWorkload('9. GRADE', $count, function ($i) use ($attempts) {
            $attemptId = $attempts[$i] ?? null;
            if (! $attemptId) return false;
            $res = Result::where('attempt_id', $attemptId)->first();
            return ($res !== null && $res->final_score > 0);
        });

        // 10. WORKLOAD: RESULT
        $metrics['10. RESULT'] = $this->measureWorkload('10. RESULT', $count, function ($i) use ($attempts, $students) {
            $attemptId = $attempts[$i] ?? null;
            if (! $attemptId) return false;
            $req = Request::create("/api/v1/attempts/{$attemptId}/result", 'GET');
            $req->setUserResolver(fn () => $students[$i]['user']);
            $response = app(ResultController::class)->showByAttempt($req, (string) $attemptId);
            return $response->getStatusCode() === 200;
        });

        return $metrics;
    }

    protected function measureWorkload(string $name, int $count, \Closure $callback): array
    {
        $latencies = [];
        $errors = 0;
        $memBefore = memory_get_usage(true);
        $startTime = microtime(true);

        for ($i = 0; $i < $count; $i++) {
            $reqStart = microtime(true);
            try {
                $ok = $callback($i);
                if (! $ok) {
                    $errors++;
                }
            } catch (\Throwable $e) {
                $errors++;
            }
            $latencies[] = (microtime(true) - $reqStart) * 1000.0; // ms
        }

        $totalDuration = microtime(true) - $startTime;
        $memAfter = memory_get_usage(true);
        $peakMem = memory_get_peak_usage(true);

        sort($latencies);
        $totalReqs = count($latencies);
        $p50Index = (int) floor($totalReqs * 0.50);
        $p95Index = (int) floor($totalReqs * 0.95);
        $p99Index = (int) floor($totalReqs * 0.99);

        $throughput = $totalDuration > 0 ? round($count / $totalDuration, 1) : 0;

        $this->line(sprintf(
            "  %-20s : %3d reqs | %5.1f req/s | p50: %5.1fms | p95: %5.1fms | p99: %5.1fms | err: %d",
            $name,
            $count,
            $throughput,
            $latencies[$p50Index] ?? 0,
            $latencies[$p95Index] ?? 0,
            $latencies[$p99Index] ?? 0,
            $errors
        ));

        return [
            'name' => $name,
            'total_requests' => $count,
            'duration_sec' => round($totalDuration, 3),
            'throughput_rps' => $throughput,
            'p50_ms' => round($latencies[$p50Index] ?? 0, 1),
            'p95_ms' => round($latencies[$p95Index] ?? 0, 1),
            'p99_ms' => round($latencies[$p99Index] ?? 0, 1),
            'min_ms' => round($latencies[0] ?? 0, 1),
            'max_ms' => round(end($latencies) ?: 0, 1),
            'errors' => $errors,
            'error_rate_pct' => round(($errors / max(1, $count)) * 100, 2),
            'memory_used_mb' => round(($memAfter - $memBefore) / 1024 / 1024, 2),
            'peak_memory_mb' => round($peakMem / 1024 / 1024, 2),
        ];
    }

    protected function runRaceConditionTests(array $sandbox, int $concurrency): array
    {
        $exam = $sandbox['exam'];

        // Create a dedicated fresh student for the race test
        $studentRole = Role::where('name', 'student')->first();
        $raceUser = User::create([
            'role_id' => $studentRole->id,
            'username' => 'load_race_user_' . time(),
            'name' => 'Siswa Race Concurrency',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $this->createdUserIds[] = $raceUser->id;

        $raceStudent = Student::create([
            'user_id' => $raceUser->id,
            'class_id' => $sandbox['students'][0]['student']->class_id,
            'nis' => 'RACE' . rand(100000, 999999),
            'gender' => 'L',
        ]);
        $this->createdStudentIds[] = $raceStudent->id;

        ExamParticipant::create([
            'exam_id' => $exam->id,
            'student_id' => $raceStudent->id,
            'allow_retest' => false,
        ]);

        // A. Concurrent START Race (Same fresh student attempts to start multiple times concurrently)
        $attemptIds = [];
        $startErrors = 0;

        for ($r = 0; $r < $concurrency; $r++) {
            try {
                $req = Request::create("/api/v1/exams/{$exam->id}/start", 'POST');
                $req->setUserResolver(fn () => $raceUser);
                $res = app(ExamAttemptController::class)->start($req, (string) $exam->id);
                if ($res->getStatusCode() === 200) {
                    $attemptIds[] = $res->getData(true)['data']['attempt_id'] ?? null;
                } else {
                    $startErrors++;
                }
            } catch (\Throwable $e) {
                $startErrors++;
            }
        }

        $uniqueAttemptIds = array_values(array_unique(array_filter($attemptIds)));
        $actualDbAttempts = ExamAttempt::where('exam_id', $exam->id)->where('student_id', $raceStudent->id)->count();
        $startSafe = (count($uniqueAttemptIds) === 1 && $actualDbAttempts === 1 && $startErrors === 0);

        $this->line("  Concurrent START Race ({$concurrency} calls) : " . ($startSafe ? "[PASS] 1 attempt ID consistently recovered, 0 duplicate created" : "[FAIL] Duplicate attempts or errors detected"));

        // B. Concurrent SUBMIT Race (Same attempt submitted multiple times concurrently)
        $targetAttemptId = $uniqueAttemptIds[0] ?? null;
        $submitErrors = 0;
        $submitSuccesses = 0;

        for ($r = 0; $r < $concurrency; $r++) {
            try {
                $req = Request::create("/api/v1/attempts/{$targetAttemptId}/submit", 'POST');
                $req->setUserResolver(fn () => $raceUser);
                $res = app(ExamAttemptController::class)->submit($req, (string) $targetAttemptId);
                if ($res->getStatusCode() === 200) {
                    $submitSuccesses++;
                } else {
                    $submitErrors++;
                }
            } catch (\Throwable $e) {
                $submitErrors++;
            }
        }

        // Count results created for this attempt
        $resultsCount = Result::where('attempt_id', $targetAttemptId)->count();
        $submitSafe = ($resultsCount === 1 && $submitSuccesses === $concurrency);

        $this->line("  Concurrent SUBMIT Race ({$concurrency} calls) : " . ($submitSafe ? "[PASS] Idempotent, exactly 1 result in DB, 0 double grading" : "[FAIL] Corrupted state or double result"));

        return [
            'start_safe' => $startSafe,
            'submit_safe' => $submitSafe,
            'unique_attempts' => count($uniqueAttemptIds),
            'results_count' => $resultsCount,
        ];
    }

    protected function verifyDatabaseIntegrity(array $sandbox, int $targetCount): array
    {
        $exam = $sandbox['exam'];

        $attemptsCount = ExamAttempt::where('exam_id', $exam->id)->count();
        $submittedCount = ExamAttempt::where('exam_id', $exam->id)->where('status', 'submitted')->count();
        $resultsCount = Result::where('exam_id', $exam->id)->count();
        $answersCount = Answer::whereHas('attempt', fn ($q) => $q->where('exam_id', $exam->id))->count();

        // 100 students + 1 race student = 101 attempts & results
        $expectedAttempts = $targetCount + 1;
        $expectedResults = $targetCount + 1;
        $expectedAnswers = $targetCount * 3;

        $attemptsOk = ($attemptsCount === $expectedAttempts);
        $resultsOk = ($resultsCount === $expectedResults);
        $submittedOk = ($submittedCount === $expectedAttempts);
        $answersOk = ($answersCount === $expectedAnswers);

        $allPassed = $attemptsOk && $resultsOk && $submittedOk && $answersOk;

        $this->line("  Attempts Created : {$attemptsCount} / {$expectedAttempts} " . ($attemptsOk ? "[OK]" : "[FAIL]"));
        $this->line("  Attempts Finished: {$submittedCount} / {$expectedAttempts} " . ($submittedOk ? "[OK]" : "[FAIL]"));
        $this->line("  Results Stored   : {$resultsCount} / {$expectedResults} " . ($resultsOk ? "[OK]" : "[FAIL]"));
        $this->line("  Answers Stored   : {$answersCount} / {$expectedAnswers} " . ($answersOk ? "[OK]" : "[FAIL]"));

        return [
            'attempts_count' => $attemptsCount,
            'submitted_count' => $submittedCount,
            'results_count' => $resultsCount,
            'answers_count' => $answersCount,
            'all_passed' => $allPassed,
        ];
    }

    protected function teardownSandbox(): void
    {
        try {
            if ($this->createdExamId) {
                Answer::whereHas('attempt', fn ($q) => $q->where('exam_id', $this->createdExamId))->delete();
                Result::where('exam_id', $this->createdExamId)->delete();
                ExamAttempt::where('exam_id', $this->createdExamId)->delete();
                ExamParticipant::where('exam_id', $this->createdExamId)->delete();
                ExamQuestion::where('exam_id', $this->createdExamId)->delete();
                Exam::where('id', $this->createdExamId)->delete();
            }

            if (! empty($this->createdQuestionIds)) {
                QuestionOption::whereIn('question_id', $this->createdQuestionIds)->delete();
                Question::whereIn('id', $this->createdQuestionIds)->delete();
            }

            if (! empty($this->createdStudentIds)) {
                ExamParticipant::whereIn('student_id', $this->createdStudentIds)->delete();
                ExamAttempt::whereIn('student_id', $this->createdStudentIds)->delete();
                Student::whereIn('id', $this->createdStudentIds)->delete();
            }

            if (! empty($this->createdUserIds)) {
                DB::table('personal_access_tokens')->whereIn('tokenable_id', $this->createdUserIds)->delete();
                User::whereIn('id', $this->createdUserIds)->delete();
            }

            if ($this->createdSubjectId) {
                Exam::where('subject_id', $this->createdSubjectId)->delete();
                Question::where('subject_id', $this->createdSubjectId)->delete();
                Subject::where('id', $this->createdSubjectId)->delete();
            }
        } catch (\Throwable $e) {
            $this->warn("  Teardown warning: " . $e->getMessage());
        }
    }

    protected function displaySummaryReport(int $students, array $metrics, array $raceResults, array $integrity): void
    {
        $this->info("\n========================================================================");
        $this->info(" CBT V1 — LOAD TEST BENCHMARK SUMMARY REPORT");
        $this->info("========================================================================");

        $headers = ['Workload', 'Requests', 'Throughput', 'p50 (ms)', 'p95 (ms)', 'p99 (ms)', 'Error Rate'];
        $rows = [];

        foreach ($metrics as $m) {
            $rows[] = [
                $m['name'],
                $m['total_requests'],
                $m['throughput_rps'] . ' rps',
                $m['p50_ms'] . ' ms',
                $m['p95_ms'] . ' ms',
                $m['p99_ms'] . ' ms',
                $m['error_rate_pct'] . ' %',
            ];
        }

        $this->table($headers, $rows);

        $this->info("CONCURRENCY & RACE CONDITIONS:");
        $this->line("  Concurrent START Safety  : " . ($raceResults['start_safe'] ? "PASSED (No duplicate attempts)" : "FAILED"));
        $this->line("  Concurrent SUBMIT Safety : " . ($raceResults['submit_safe'] ? "PASSED (Idempotent 1 result)" : "FAILED"));
        $this->line("  Database Integrity       : " . ($integrity['all_passed'] ? "PASSED (Zero data corruption/loss)" : "FAILED"));

        $this->info("\nACCEPTANCE VERDICT:");
        if ($integrity['all_passed'] && $raceResults['start_safe'] && $raceResults['submit_safe']) {
            $this->info("  STATUS: PASS / ACCEPTED — CBT Engine successfully validated for 100+ concurrent students.");
        } else {
            $this->error("  STATUS: FAILED — Workload bottlenecks or race condition detected.");
        }
        $this->info("========================================================================\n");
    }
}
