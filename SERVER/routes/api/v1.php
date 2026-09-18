<?php

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AnswerController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BackupController;
use App\Http\Controllers\Api\V1\ClassController;
use App\Http\Controllers\Api\V1\ExamAttemptController;
use App\Http\Controllers\Api\V1\ExamController;
use App\Http\Controllers\Api\V1\ExamParticipantController;
use App\Http\Controllers\Api\V1\ExamQuestionController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MonitoringController;
use App\Http\Controllers\Api\V1\QuestionController;
use App\Http\Controllers\Api\V1\QuestionOptionController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ResultController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\TeacherController;
use App\Http\Controllers\Api\V1\TimerController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CBT REST API - Version 1 Routes
|--------------------------------------------------------------------------
|
| Base Path: /api/v1
|
*/

// Public Health Check
Route::get('/health', [HealthController::class, 'index'])->name('api.v1.health');

// Authentication Routes
Route::prefix('auth')->group(function () {
    // Login with rate limiting to prevent brute force (10 attempts per minute per IP)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.v1.auth.login');

    // Protected Auth Endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
    });
});

// User Management Routes (Admin only)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('api.v1.users.index');
    Route::get('/{id}', [UserController::class, 'show'])->name('api.v1.users.show');
    Route::post('/', [UserController::class, 'store'])->name('api.v1.users.store');
    Route::match(['put', 'patch'], '/{id}', [UserController::class, 'update'])->name('api.v1.users.update');
    Route::delete('/{id}', [UserController::class, 'destroy'])->name('api.v1.users.destroy');
});

// Class Management Routes
Route::middleware('auth:sanctum')->prefix('classes')->group(function () {
    // Read operations (Admin and Teacher)
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/', [ClassController::class, 'index'])->name('api.v1.classes.index');
        Route::get('/{id}', [ClassController::class, 'show'])->name('api.v1.classes.show');
    });

    // Write operations (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::post('/', [ClassController::class, 'store'])->name('api.v1.classes.store');
        Route::match(['put', 'patch'], '/{id}', [ClassController::class, 'update'])->name('api.v1.classes.update');
        Route::delete('/{id}', [ClassController::class, 'destroy'])->name('api.v1.classes.destroy');
    });
});

// Subject Management Routes
Route::middleware('auth:sanctum')->prefix('subjects')->group(function () {
    // Read operations (Admin and Teacher)
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/', [SubjectController::class, 'index'])->name('api.v1.subjects.index');
        Route::get('/{id}', [SubjectController::class, 'show'])->name('api.v1.subjects.show');
    });

    // Write operations (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::post('/', [SubjectController::class, 'store'])->name('api.v1.subjects.store');
        Route::match(['put', 'patch'], '/{id}', [SubjectController::class, 'update'])->name('api.v1.subjects.update');
        Route::delete('/{id}', [SubjectController::class, 'destroy'])->name('api.v1.subjects.destroy');
    });
});

// Student Management Routes
Route::middleware('auth:sanctum')->prefix('students')->group(function () {
    // Read operations (Admin and Teacher)
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/', [StudentController::class, 'index'])->name('api.v1.students.index');
        Route::get('/{id}', [StudentController::class, 'show'])->name('api.v1.students.show');
    });

    // Write operations (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::post('/', [StudentController::class, 'store'])->name('api.v1.students.store');
        Route::match(['put', 'patch'], '/{id}', [StudentController::class, 'update'])->name('api.v1.students.update');
        Route::delete('/{id}', [StudentController::class, 'destroy'])->name('api.v1.students.destroy');
    });
});

// Teacher Management Routes
Route::middleware('auth:sanctum')->prefix('teachers')->group(function () {
    // Read operations (Admin and Teacher)
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/', [TeacherController::class, 'index'])->name('api.v1.teachers.index');
        Route::get('/{id}', [TeacherController::class, 'show'])->name('api.v1.teachers.show');
    });

    // Write operations (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::post('/', [TeacherController::class, 'store'])->name('api.v1.teachers.store');
        Route::match(['put', 'patch'], '/{id}', [TeacherController::class, 'update'])->name('api.v1.teachers.update');
        Route::delete('/{id}', [TeacherController::class, 'destroy'])->name('api.v1.teachers.destroy');
    });
});

// Question & Question Options Management Routes (Bank Soal)
Route::middleware(['auth:sanctum', 'role:admin,teacher'])->prefix('questions')->group(function () {
    Route::get('/', [QuestionController::class, 'index'])->name('api.v1.questions.index');
    Route::post('/', [QuestionController::class, 'store'])->name('api.v1.questions.store');
    Route::get('/{id}', [QuestionController::class, 'show'])->name('api.v1.questions.show');
    Route::match(['put', 'patch'], '/{id}', [QuestionController::class, 'update'])->name('api.v1.questions.update');
    Route::delete('/{id}', [QuestionController::class, 'destroy'])->name('api.v1.questions.destroy');

    // Question Options (Nested under Question)
    Route::prefix('{questionId}/options')->group(function () {
        Route::get('/', [QuestionOptionController::class, 'index'])->name('api.v1.questions.options.index');
        Route::post('/', [QuestionOptionController::class, 'store'])->name('api.v1.questions.options.store');
        Route::get('/{id}', [QuestionOptionController::class, 'show'])->name('api.v1.questions.options.show');
        Route::match(['put', 'patch'], '/{id}', [QuestionOptionController::class, 'update'])->name('api.v1.questions.options.update');
        Route::delete('/{id}', [QuestionOptionController::class, 'destroy'])->name('api.v1.questions.options.destroy');
    });
});

// Exam & Exam Questions Management Routes
Route::middleware('auth:sanctum')->prefix('exams')->group(function () {
    // Read operations (Admin, Teacher, and Student for active/published)
    Route::get('/', [ExamController::class, 'index'])->name('api.v1.exams.index');
    Route::get('/{id}', [ExamController::class, 'show'])->name('api.v1.exams.show');
    Route::get('/{examId}/questions', [ExamQuestionController::class, 'index'])->name('api.v1.exams.questions.index');

    // Management operations (Admin & Teacher only)
    Route::middleware('role:admin,teacher')->group(function () {
        Route::post('/', [ExamController::class, 'store'])->name('api.v1.exams.store');
        Route::match(['put', 'patch'], '/{id}', [ExamController::class, 'update'])->name('api.v1.exams.update');
        Route::delete('/{id}', [ExamController::class, 'destroy'])->name('api.v1.exams.destroy');

        Route::prefix('{examId}/questions')->group(function () {
            Route::post('/', [ExamQuestionController::class, 'store'])->name('api.v1.exams.questions.store');
            Route::match(['put', 'patch'], '/{questionId}', [ExamQuestionController::class, 'update'])->name('api.v1.exams.questions.update');
            Route::delete('/{questionId}', [ExamQuestionController::class, 'destroy'])->name('api.v1.exams.questions.destroy');
        });

        // Exam Participants / Enrollment (Admin & Teacher)
        Route::prefix('{examId}/participants')->group(function () {
            Route::get('/', [ExamParticipantController::class, 'index'])->name('api.v1.exams.participants.index');
            Route::post('/', [ExamParticipantController::class, 'store'])->name('api.v1.exams.participants.store');
            Route::get('/{participantId}', [ExamParticipantController::class, 'show'])->name('api.v1.exams.participants.show');
            Route::match(['put', 'patch'], '/{participantId}', [ExamParticipantController::class, 'update'])->name('api.v1.exams.participants.update');
            Route::delete('/{participantId}', [ExamParticipantController::class, 'destroy'])->name('api.v1.exams.participants.destroy');
        });
    });

    // Student Exam Session (Start / Attempt)
    Route::post('/{id}/start', [ExamAttemptController::class, 'start'])->name('api.v1.exams.start');
    Route::post('/{id}/attempts', [ExamAttemptController::class, 'start'])->name('api.v1.exams.attempts.start');
    Route::get('/{id}/timer', [TimerController::class, 'getExamTimer'])->name('api.v1.exams.timer');
});

// Exam Attempts & Answers Routes
Route::middleware('auth:sanctum')->prefix('attempts')->group(function () {
    Route::get('/{id}', [ExamAttemptController::class, 'show'])->name('api.v1.attempts.show');
    Route::get('/{id}/timer', [TimerController::class, 'getAttemptTimer'])->name('api.v1.attempts.timer');
    Route::post('/{id}/extend-time', [TimerController::class, 'extendTime'])->name('api.v1.attempts.extend_time');
    Route::post('/{id}/submit', [ExamAttemptController::class, 'submit'])->name('api.v1.attempts.submit');
    Route::post('/{id}/grade', [ResultController::class, 'grade'])->name('api.v1.attempts.grade');
    Route::get('/{id}/result', [ResultController::class, 'showByAttempt'])->name('api.v1.attempts.result');

    // Answers Autosave & Listing
    Route::get('/{id}/answers', [AnswerController::class, 'index'])->name('api.v1.attempts.answers.index');
    Route::post('/{id}/answers', [AnswerController::class, 'store'])->name('api.v1.attempts.answers.store');
    Route::post('/{id}/sync', [SyncController::class, 'sync'])->name('api.v1.attempts.sync');
});

// Exam Results Routes
Route::middleware('auth:sanctum')->prefix('results')->group(function () {
    Route::get('/', [ResultController::class, 'index'])->name('api.v1.results.index');
    Route::get('/{id}', [ResultController::class, 'show'])->name('api.v1.results.show');

    // Admin & Teacher publication management & manual essay grading
    Route::middleware('role:admin,teacher')->group(function () {
        Route::patch('/{id}/publish', [ResultController::class, 'publish'])->name('api.v1.results.publish');
        Route::post('/{id}/grade-essay', [ResultController::class, 'gradeEssay'])->name('api.v1.results.grade_essay');
    });
});

// Live Proctoring & Monitoring Routes (Admin & Teacher only)
Route::middleware(['auth:sanctum', 'role:admin,teacher'])->prefix('monitoring')->group(function () {
    Route::get('/live', [MonitoringController::class, 'index'])->name('api.v1.monitoring.live');
    Route::get('/exams/{examId}', [MonitoringController::class, 'showExam'])->name('api.v1.monitoring.exams');
    Route::get('/attempts/{attemptId}', [MonitoringController::class, 'showAttempt'])->name('api.v1.monitoring.attempts');
});

// Academic Reports & Analysis Routes (Admin & Teacher only)
Route::middleware(['auth:sanctum', 'role:admin,teacher'])->prefix('reports')->group(function () {
    Route::get('/exams/{examId}', [ReportController::class, 'examReport'])->name('api.v1.reports.exams');
    Route::get('/classes/{classId}', [ReportController::class, 'classReport'])->name('api.v1.reports.classes');
    Route::get('/item-analysis/{examId}', [ReportController::class, 'itemAnalysis'])->name('api.v1.reports.item_analysis');
});

// System Database Backup Routes (Admin only)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('backups')->group(function () {
    Route::get('/', [BackupController::class, 'index'])->name('api.v1.backups.index');
    Route::post('/', [BackupController::class, 'create'])->name('api.v1.backups.create');
    Route::get('/{filename}/download', [BackupController::class, 'download'])->name('api.v1.backups.download');
    Route::delete('/{filename}', [BackupController::class, 'destroy'])->name('api.v1.backups.destroy');
});

// System Settings Routes
Route::middleware('auth:sanctum')->prefix('settings')->group(function () {
    Route::get('/', [SettingController::class, 'show'])->name('api.v1.settings.show');
    Route::match(['put', 'post'], '/', [SettingController::class, 'update'])->name('api.v1.settings.update');
});

// Activity Logs & Audit Trail Routes
Route::middleware('auth:sanctum')->prefix('activity-logs')->group(function () {
    Route::get('/', [ActivityLogController::class, 'index'])->name('api.v1.activity_logs.index');
    Route::post('/event', [ActivityLogController::class, 'recordEvent'])->name('api.v1.activity_logs.event');
});

// Non-business Authorization Test Endpoints (for verifying role middleware)
Route::middleware('auth:sanctum')->prefix('test')->group(function () {
    Route::get('/admin-only', function () {
        return response()->json([
            'success' => true,
            'message' => 'Akses terotorisasi khusus ADMIN',
            'data' => null,
        ]);
    })->middleware('role:admin,ADMIN')->name('api.v1.test.admin');

    Route::get('/guru-only', function () {
        return response()->json([
            'success' => true,
            'message' => 'Akses terotorisasi khusus GURU',
            'data' => null,
        ]);
    })->middleware('role:guru,teacher,GURU')->name('api.v1.test.guru');

    Route::get('/peserta-only', function () {
        return response()->json([
            'success' => true,
            'message' => 'Akses terotorisasi khusus PESERTA',
            'data' => null,
        ]);
    })->middleware('role:peserta,student,PESERTA')->name('api.v1.test.peserta');
});

// =========================================================================
// AI QUESTION GENERATOR ENDPOINT (GEMINI 1.5/2.5 FLASH REST API + LOCAL KNOWLEDGE BANK)
// =========================================================================
Route::post('/ai/generate-question', function (Request $request) {
    $subject = trim($request->input('subject') ?? $request->input('subject_name') ?? 'Umum');
    $topic = trim($request->input('topic') ?? '');
    $type = trim($request->input('type') ?? 'single_choice');
    $difficulty = trim($request->input('difficulty') ?? 'medium');
    $clientKey = trim($request->input('key') ?? '');

    $apiKey = $clientKey ?: env('GEMINI_API_KEY') ?: '';

    if (!empty($apiKey)) {
        $prompt = "Buatkan 1 butir soal ujian tingkat SMK untuk mata pelajaran \"{$subject}\"" .
            ($topic ? " dengan topik/materi: \"{$topic}\"" : "") .
            ". Tipe soal: " . ($type === 'essay' ? "Essai/Uraian" : "Pilihan Ganda 5 opsi (A, B, C, D, E)") .
            ". Tingkat kesulitan: {$difficulty}.\n" .
            "Format balasan WAJIB berupa JSON murni tanpa markdown/backticks dengan skema:\n" .
            "{\n" .
            '  "type": "' . ($type === 'essay' ? 'essay' : 'single_choice') . '",' . "\n" .
            '  "content": "<p>Teks soal HTML lengkap</p>",' . "\n" .
            '  "options": {"A": "opsi A", "B": "opsi B", "C": "opsi C", "D": "opsi D", "E": "opsi E"},' . "\n" .
            '  "correct_option": "A",' . "\n" .
            '  "score_weight": 2.5,' . "\n" .
            '  "explanation": "Penjelasan singkat jawaban benar"' . "\n" .
            "}";

        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($apiKey);
        $payload = json_encode([
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'responseMimeType' => 'application/json'
            ]
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $payload,
                'timeout' => 15,
                'ignore_errors' => true
            ]
        ]);

        $res = @file_get_contents($apiUrl, false, $ctx);
        if ($res !== false) {
            $jsonRes = json_decode($res, true);
            $rawText = $jsonRes['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $rawText = trim(preg_replace('/^```(?:json)?\s*/i', '', preg_replace('/\s*```$/i', '', $rawText)));
            $parsed = json_decode($rawText, true);

            if ($parsed && !empty($parsed['content'])) {
                return response()->json([
                    'success' => true,
                    'provider' => 'gemini-api',
                    'data' => [
                        'type' => $parsed['type'] ?? $type,
                        'content' => $parsed['content'],
                        'options' => $parsed['options'] ?? [
                            'A' => 'Jawaban A', 'B' => 'Jawaban B', 'C' => 'Jawaban C', 'D' => 'Jawaban D', 'E' => 'Jawaban E'
                        ],
                        'correct_option' => strtoupper($parsed['correct_option'] ?? 'A'),
                        'score_weight' => $parsed['score_weight'] ?? ($type === 'essay' ? 10.0 : 2.5),
                        'explanation' => $parsed['explanation'] ?? 'Disusun oleh Google Gemini AI'
                    ]
                ]);
            }
        }
    }

    // Fallback: Smart local knowledge bank
    $subLower = strtolower($subject);
    $topLower = strtolower($topic);

    if (str_contains($subLower, 'dtkj') || str_contains($subLower, 'dasar') || str_contains($topLower, 'kabel') || str_contains($topLower, 'crimping') || str_contains($topLower, 'lan')) {
        $q = [
            'type' => 'single_choice',
            'content' => '<p>Pada pengkabelan jaringan LAN standar <strong>TIA/EIA-568B</strong> menggunakan kabel UTP Cat6, urutan warna pin ke-1 sampai pin ke-4 pada konektor RJ-45 yang benar adalah...</p>',
            'options' => [
                'A' => 'Putih Orange, Orange, Putih Hijau, Biru',
                'B' => 'Putih Hijau, Hijau, Putih Orange, Biru',
                'C' => 'Putih Orange, Orange, Putih Biru, Hijau',
                'D' => 'Orange, Putih Orange, Biru, Putih Biru',
                'E' => 'Putih Biru, Biru, Putih Hijau, Hijau'
            ],
            'correct_option' => 'A',
            'score_weight' => 2.5,
            'explanation' => 'Standar TIA/EIA-568B: 1. Putih Orange, 2. Orange, 3. Putih Hijau, 4. Biru, 5. Putih Biru, 6. Hijau, 7. Putih Cokelat, 8. Cokelat.'
        ];
    } elseif (str_contains($subLower, 'aij') || str_contains($subLower, 'infrastruktur') || str_contains($topLower, 'vlan') || str_contains($topLower, 'routing') || str_contains($topLower, 'mikrotik')) {
        $q = [
            'type' => 'single_choice',
            'content' => '<p>Sebuah interface router MikroTik dikonfigurasi dengan alamat <strong>192.168.10.65/27</strong>. Jumlah host valid (usable host) yang dapat dialokasikan pada subnet tersebut adalah...</p>',
            'options' => [
                'A' => '14 host',
                'B' => '30 host',
                'C' => '62 host',
                'D' => '126 host',
                'E' => '254 host'
            ],
            'correct_option' => 'B',
            'score_weight' => 2.5,
            'explanation' => 'Prefix /27 memiliki 32 total IP. Usable host = 2^(32-27) - 2 = 32 - 2 = 30 host valid.'
        ];
    } elseif (str_contains($subLower, 'tlj') || str_contains($subLower, 'layanan') || str_contains($topLower, 'voip') || str_contains($topLower, 'sip') || str_contains($topLower, 'asterisk')) {
        $q = [
            'type' => 'single_choice',
            'content' => '<p>Protokol pensinyalan (signaling) pada jaringan <strong>VoIP (Voice over IP)</strong> yang bertugas membangun, memodifikasi, dan mengakhiri sesi komunikasi multimedia secara real-time adalah...</p>',
            'options' => [
                'A' => 'SIP (Session Initiation Protocol)',
                'B' => 'RTP (Real-time Transport Protocol)',
                'C' => 'RTCP (Real-time Control Protocol)',
                'D' => 'H.264 Video Codec',
                'E' => 'SNMP (Simple Network Management Protocol)'
            ],
            'correct_option' => 'A',
            'score_weight' => 2.5,
            'explanation' => 'SIP (Session Initiation Protocol, RFC 3261) merupakan protokol pensinyalan standar untuk inisiasi, manajemen, dan terminasi sesi VoIP.'
        ];
    } else {
        $a = rand(2, 6);
        $b = rand(1, 5);
        $c = rand(1, 4);
        $d = rand(3, 7);
        $det = ($a * $d) - ($b * $c);
        $q = [
            'type' => 'single_choice',
            'content' => "<p>Diberikan matriks ordo 2&times;2: <strong>A = [{$a}, {$b}; {$c}, {$d}]</strong>. Nilai determinan dari matriks A adalah...</p>",
            'options' => [
                'A' => (string)($det),
                'B' => (string)($det + 2),
                'C' => (string)($det - 3),
                'D' => (string)($det + 5),
                'E' => (string)($det - 1)
            ],
            'correct_option' => 'A',
            'score_weight' => 2.5,
            'explanation' => "Determinan matriks 2x2: det(A) = (a * d) - (b * c) = ({$a} * {$d}) - ({$b} * {$c}) = " . ($a * $d) . " - " . ($b * $c) . " = {$det}."
        ];
    }

    return response()->json([
        'success' => true,
        'provider' => 'local-smart-bank',
        'data' => $q
    ]);
});
