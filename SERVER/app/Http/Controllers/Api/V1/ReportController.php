<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Answer;
use App\Models\Classes;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends ApiController
{
    /**
     * Display detailed statistical report and student performance breakdown for an exam.
     */
    public function examReport(Request $request, string $examId): JsonResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        // Role restriction: Admin and Teacher only
        if (! in_array($userRole, ['admin', 'teacher', 'guru'], true)) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk mengakses laporan ujian', null, 403);
        }

        $exam = Exam::with(['subject:id,name,code', 'creator:id,name'])
            ->withCount('examQuestions')
            ->find($examId);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk melihat laporan paket ujian ini', null, 403);
        }

        $classId = $request->query('class_id');

        // Base results query with relations
        $resultsQuery = Result::with([
            'student:id,user_id,class_id,nis',
            'student.user:id,name,username',
            'student.schoolClass:id,name,level',
        ])
            ->where('exam_id', $exam->id);

        if ($classId) {
            $resultsQuery->whereHas('student', function ($sq) use ($classId) {
                $sq->where('class_id', $classId);
            });
        }

        // Summary metrics
        $allResults = (clone $resultsQuery)->get();
        $totalGraded = $allResults->count();

        $participantsQuery = ExamParticipant::where('exam_id', $exam->id);
        if ($classId) {
            $participantsQuery->whereHas('student', function ($sq) use ($classId) {
                $sq->where('class_id', $classId);
            });
        }
        $totalParticipants = $participantsQuery->count();

        $passingScore = (float) ($exam->passing_score ?? 75.00);

        $passedCount = $allResults->filter(function ($r) use ($passingScore) {
            return (float) $r->final_score >= $passingScore;
        })->count();

        $failedCount = $totalGraded - $passedCount;
        $passPercentage = $totalGraded > 0 ? round(($passedCount / $totalGraded) * 100, 1) : 0.0;

        $averageScore = $totalGraded > 0 ? round($allResults->avg('final_score'), 2) : 0.00;
        $highestScore = $totalGraded > 0 ? (float) $allResults->max('final_score') : 0.00;
        $lowestScore = $totalGraded > 0 ? (float) $allResults->min('final_score') : 0.00;

        // Paginated student breakdown
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $paginator = $resultsQuery->orderByDesc('final_score')->paginate($perPage);

        $items = collect($paginator->items())->map(function ($result) use ($passingScore) {
            $score = (float) $result->final_score;

            return [
                'result_id' => $result->id,
                'student' => [
                    'id' => $result->student->id,
                    'nis' => $result->student->nis,
                    'name' => $result->student->user->name ?? null,
                    'class' => $result->student->schoolClass ? [
                        'id' => $result->student->schoolClass->id,
                        'name' => $result->student->schoolClass->name,
                        'level' => $result->student->schoolClass->level,
                    ] : null,
                ],
                'correct_count' => (int) $result->correct_count,
                'wrong_count' => (int) $result->wrong_count,
                'unanswered_count' => (int) $result->unanswered_count,
                'final_score' => $score,
                'is_passed' => $score >= $passingScore,
                'status' => $result->status,
                'is_published' => (bool) $result->is_published,
                'graded_at' => $result->graded_at,
            ];
        });

        return $this->successResponse([
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject' => $exam->subject ? [
                    'id' => $exam->subject->id,
                    'name' => $exam->subject->name,
                    'code' => $exam->subject->code,
                ] : null,
                'passing_score' => $passingScore,
                'total_questions' => (int) $exam->exam_questions_count,
            ],
            'statistics' => [
                'total_participants' => $totalParticipants,
                'total_graded' => $totalGraded,
                'passed_count' => $passedCount,
                'failed_count' => $failedCount,
                'pass_percentage' => $passPercentage,
                'average_score' => $averageScore,
                'highest_score' => $highestScore,
                'lowest_score' => $lowestScore,
            ],
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Laporan rekapitulasi ujian berhasil diambil');
    }

    /**
     * Display performance report and exam results breakdown for a specific school class.
     */
    public function classReport(Request $request, string $classId): JsonResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        if (! in_array($userRole, ['admin', 'teacher', 'guru'], true)) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk mengakses laporan kelas', null, 403);
        }

        $class = Classes::withCount('students')->find($classId);

        if (! $class) {
            return $this->errorResponse('Data kelas tidak ditemukan', null, 404);
        }

        $query = Result::with([
            'exam:id,subject_id,title,passing_score',
            'exam.subject:id,name,code',
            'student:id,user_id,class_id,nis',
            'student.user:id,name,username',
        ])
            ->whereHas('student', function ($sq) use ($class) {
                $sq->where('class_id', $class->id);
            });

        if ($userRole !== 'admin') {
            $query->whereHas('exam', function ($eq) use ($user) {
                $eq->where('created_by', $user->id);
            });
        }

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->query('exam_id'));
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $paginator = $query->latest('id')->paginate($perPage);

        $items = collect($paginator->items())->map(function ($result) {
            $passingScore = (float) ($result->exam->passing_score ?? 75.00);
            $score = (float) $result->final_score;

            return [
                'result_id' => $result->id,
                'exam' => [
                    'id' => $result->exam->id,
                    'title' => $result->exam->title,
                    'subject' => $result->exam->subject->name ?? null,
                ],
                'student' => [
                    'id' => $result->student->id,
                    'nis' => $result->student->nis,
                    'name' => $result->student->user->name ?? null,
                ],
                'final_score' => $score,
                'is_passed' => $score >= $passingScore,
                'correct_count' => (int) $result->correct_count,
                'wrong_count' => (int) $result->wrong_count,
                'unanswered_count' => (int) $result->unanswered_count,
                'graded_at' => $result->graded_at,
            ];
        });

        return $this->successResponse([
            'class' => [
                'id' => $class->id,
                'name' => $class->name,
                'level' => $class->level,
                'total_students' => (int) $class->students_count,
            ],
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Laporan rekapitulasi nilai kelas berhasil diambil');
    }

    /**
     * Display item analysis (analisis butir soal) for an exam.
     */
    public function itemAnalysis(Request $request, string $examId): JsonResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        if (! in_array($userRole, ['admin', 'teacher', 'guru'], true)) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk mengakses analisis butir soal', null, 403);
        }

        $exam = Exam::with(['subject:id,name,code'])->find($examId);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk melihat analisis butir soal ujian ini', null, 403);
        }

        // Fetch exam questions
        $examQuestions = ExamQuestion::where('exam_id', $exam->id)
            ->with(['question' => function ($q) {
                $q->select('id', 'subject_id', 'question_type', 'content');
            }])
            ->orderBy('order_index')
            ->get();

        // Get attempt IDs for this exam
        $attemptIds = $exam->attempts()->pluck('id');
        $totalAttempts = $attemptIds->count();

        // Aggregate answers per question
        $analysis = $examQuestions->map(function ($eq) use ($attemptIds, $totalAttempts) {
            $question = $eq->question;

            $answers = Answer::whereIn('attempt_id', $attemptIds)
                ->where('question_id', $eq->question_id)
                ->get();

            $totalAnswered = $answers->count();
            $correctAnswersCount = $answers->where('is_correct', true)->count();
            $wrongAnswersCount = $totalAnswered - $correctAnswersCount;

            $difficultyIndex = $totalAnswered > 0 ? round(($correctAnswersCount / $totalAnswered) * 100, 1) : 0.0;

            // Determine difficulty classification
            $classification = match (true) {
                $difficultyIndex >= 70.0 => 'mudah',
                $difficultyIndex >= 30.0 => 'sedang',
                default => 'sukar',
            };

            return [
                'question_id' => $question->id,
                'order_index' => (int) $eq->order_index,
                'weight' => (float) $eq->weight,
                'question_type' => $question->question_type,
                'content_preview' => mb_substr(strip_tags((string) $question->content), 0, 100),
                'total_attempts' => $totalAttempts,
                'total_answered' => $totalAnswered,
                'correct_count' => $correctAnswersCount,
                'wrong_count' => $wrongAnswersCount,
                'difficulty_index' => $difficultyIndex,
                'classification' => $classification,
            ];
        });

        return $this->successResponse([
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject' => $exam->subject,
                'total_questions' => $examQuestions->count(),
                'total_examined_attempts' => $totalAttempts,
            ],
            'items' => $analysis,
        ], 'Analisis butir soal berhasil diambil');
    }
}
