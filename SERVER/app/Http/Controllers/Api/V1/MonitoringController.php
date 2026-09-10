<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringController extends ApiController
{
    /**
     * Display live overview of active exams and live participant statistics.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        // Strict role protection: only admin and teacher
        if (! in_array($userRole, ['admin', 'teacher', 'guru'], true)) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk mengakses fitur monitoring', null, 403);
        }

        $query = Exam::with(['subject:id,name,code'])
            ->withCount([
                'participants',
                'attempts as in_progress_count' => function ($q) {
                    $q->where('status', 'in_progress');
                },
                'attempts as submitted_count' => function ($q) {
                    $q->where('status', 'submitted');
                },
                'attempts as timeout_count' => function ($q) {
                    $q->where('status', 'timeout');
                },
            ])
            ->whereIn('status', ['published', 'active'])
            ->latest('id');

        if ($userRole !== 'admin') {
            $query->where('created_by', $user->id);
        }

        $exams = $query->get()->map(function ($exam) {
            $notStarted = max(0, $exam->participants_count - ($exam->in_progress_count + $exam->submitted_count + $exam->timeout_count));

            return [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject' => $exam->subject ? [
                    'id' => $exam->subject->id,
                    'name' => $exam->subject->name,
                    'code' => $exam->subject->code,
                ] : null,
                'duration_minutes' => $exam->duration_minutes,
                'start_window' => $exam->start_window,
                'end_window' => $exam->end_window,
                'status' => $exam->status,
                'participants_count' => $exam->participants_count,
                'in_progress_count' => (int) $exam->in_progress_count,
                'submitted_count' => (int) $exam->submitted_count,
                'timeout_count' => (int) $exam->timeout_count,
                'not_started_count' => $notStarted,
            ];
        });

        return $this->successResponse([
            'server_time' => now(),
            'active_exams' => $exams,
        ], 'Data live monitoring ujian berhasil diambil');
    }

    /**
     * Display live monitoring for all participants enrolled in a specific exam.
     */
    public function showExam(Request $request, string $examId): JsonResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        if (! in_array($userRole, ['admin', 'teacher', 'guru'], true)) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk mengakses fitur monitoring', null, 403);
        }

        $exam = Exam::with(['subject:id,name,code'])
            ->withCount('examQuestions')
            ->find($examId);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk memantau paket ujian ini', null, 403);
        }

        $now = now();
        $totalQuestions = (int) $exam->exam_questions_count;

        // Query participants with their student profiles and attempt status
        $participantsQuery = ExamParticipant::with([
            'student:id,user_id,class_id,nis,gender',
            'student.user:id,name,username',
            'student.schoolClass:id,name,level',
        ])
            ->where('exam_id', $exam->id);

        // Optional class filter
        if ($request->filled('class_id')) {
            $classId = $request->query('class_id');
            $participantsQuery->whereHas('student', function ($sq) use ($classId) {
                $sq->where('class_id', $classId);
            });
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $paginator = $participantsQuery->paginate($perPage);

        // Load attempts for the current page participants in bulk to avoid N+1
        $studentIds = collect($paginator->items())->pluck('student_id')->all();
        $attempts = ExamAttempt::withCount('answers')
            ->where('exam_id', $exam->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $statusFilter = $request->query('status');

        $items = collect($paginator->items())->map(function ($participant) use ($attempts, $now, $totalQuestions) {
            $attempt = $attempts->get($participant->student_id);

            if ($attempt) {
                // Auto timeout computation for monitoring telemetry
                $status = $attempt->status;
                if ($status === 'in_progress' && $now->gt($attempt->ends_at)) {
                    $status = 'timeout';
                }

                $remainingSeconds = max(0, (int) $attempt->ends_at->diffInSeconds($now, false) * -1);
                $answeredCount = (int) $attempt->answers_count;
                $progressPercentage = $totalQuestions > 0 ? round(($answeredCount / $totalQuestions) * 100, 1) : 0.0;

                $attemptData = [
                    'id' => $attempt->id,
                    'status' => $status,
                    'started_at' => $attempt->started_at,
                    'ends_at' => $attempt->ends_at,
                    'submitted_at' => $attempt->submitted_at,
                    'remaining_seconds' => $remainingSeconds,
                    'last_activity_at' => $attempt->last_activity_at,
                    'ip_address' => $attempt->ip_address,
                    'device_info' => $attempt->device_info,
                    'answered_count' => $answeredCount,
                    'total_questions' => $totalQuestions,
                    'progress_percentage' => $progressPercentage,
                ];
            } else {
                $attemptData = [
                    'id' => null,
                    'status' => 'not_started',
                    'started_at' => null,
                    'ends_at' => null,
                    'submitted_at' => null,
                    'remaining_seconds' => null,
                    'last_activity_at' => null,
                    'ip_address' => null,
                    'device_info' => null,
                    'answered_count' => 0,
                    'total_questions' => $totalQuestions,
                    'progress_percentage' => 0.0,
                ];
            }

            return [
                'participant_id' => $participant->id,
                'allow_retest' => (bool) $participant->allow_retest,
                'student' => [
                    'id' => $participant->student->id,
                    'nis' => $participant->student->nis,
                    'name' => $participant->student->user->name ?? null,
                    'username' => $participant->student->user->username ?? null,
                    'class' => $participant->student->schoolClass ? [
                        'id' => $participant->student->schoolClass->id,
                        'name' => $participant->student->schoolClass->name,
                        'level' => $participant->student->schoolClass->level,
                    ] : null,
                ],
                'attempt' => $attemptData,
            ];
        });

        // Filter by attempt status if specified in query
        if ($statusFilter) {
            $items = $items->filter(function ($item) use ($statusFilter) {
                return $item['attempt']['status'] === $statusFilter;
            })->values();
        }

        // Summary counts for this exam
        $totalParticipants = ExamParticipant::where('exam_id', $exam->id)->count();
        $inProgressCount = ExamAttempt::where('exam_id', $exam->id)->where('status', 'in_progress')->where('ends_at', '>', $now)->count();
        $submittedCount = ExamAttempt::where('exam_id', $exam->id)->where('status', 'submitted')->count();
        $timeoutCount = ExamAttempt::where('exam_id', $exam->id)->where(function ($q) use ($now) {
            $q->where('status', 'timeout')
                ->orWhere(function ($sq) use ($now) {
                    $sq->where('status', 'in_progress')->where('ends_at', '<=', $now);
                });
        })->count();
        $notStartedCount = max(0, $totalParticipants - ($inProgressCount + $submittedCount + $timeoutCount));

        return $this->successResponse([
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject' => $exam->subject,
                'total_questions' => $totalQuestions,
                'duration_minutes' => $exam->duration_minutes,
                'status' => $exam->status,
            ],
            'summary' => [
                'total_participants' => $totalParticipants,
                'in_progress_count' => $inProgressCount,
                'submitted_count' => $submittedCount,
                'timeout_count' => $timeoutCount,
                'not_started_count' => $notStartedCount,
            ],
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Data live monitoring peserta ujian berhasil diambil');
    }

    /**
     * Display live telemetry for a single student attempt.
     */
    public function showAttempt(Request $request, string $attemptId): JsonResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        if (! in_array($userRole, ['admin', 'teacher', 'guru'], true)) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk mengakses fitur monitoring', null, 403);
        }

        $attempt = ExamAttempt::with([
            'exam.subject:id,name,code',
            'student.user:id,name,username',
            'student.schoolClass:id,name,level',
        ])
            ->withCount('answers')
            ->find($attemptId);

        if (! $attempt) {
            return $this->errorResponse('Sesi ujian tidak ditemukan', null, 404);
        }

        if ($userRole !== 'admin' && $attempt->exam->created_by !== $user->id) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk memantau sesi ujian ini', null, 403);
        }

        $now = now();
        $totalQuestions = (int) $attempt->exam->examQuestions()->count();
        $answeredCount = (int) $attempt->answers_count;
        $unansweredCount = max(0, $totalQuestions - $answeredCount);
        $progressPercentage = $totalQuestions > 0 ? round(($answeredCount / $totalQuestions) * 100, 1) : 0.0;
        $remainingSeconds = max(0, (int) $attempt->ends_at->diffInSeconds($now, false) * -1);

        $status = $attempt->status;
        if ($status === 'in_progress' && $now->gt($attempt->ends_at)) {
            $status = 'timeout';
        }

        return $this->successResponse([
            'id' => $attempt->id,
            'status' => $status,
            'started_at' => $attempt->started_at,
            'ends_at' => $attempt->ends_at,
            'submitted_at' => $attempt->submitted_at,
            'remaining_seconds' => $remainingSeconds,
            'last_activity_at' => $attempt->last_activity_at,
            'ip_address' => $attempt->ip_address,
            'device_info' => $attempt->device_info,
            'exam' => [
                'id' => $attempt->exam->id,
                'title' => $attempt->exam->title,
                'duration_minutes' => $attempt->exam->duration_minutes,
                'total_questions' => $totalQuestions,
                'subject' => $attempt->exam->subject,
            ],
            'student' => [
                'id' => $attempt->student->id,
                'nis' => $attempt->student->nis,
                'name' => $attempt->student->user->name ?? null,
                'username' => $attempt->student->user->username ?? null,
                'class' => $attempt->student->schoolClass ? [
                    'id' => $attempt->student->schoolClass->id,
                    'name' => $attempt->student->schoolClass->name,
                    'level' => $attempt->student->schoolClass->level,
                ] : null,
            ],
            'progress' => [
                'total_questions' => $totalQuestions,
                'answered_count' => $answeredCount,
                'unanswered_count' => $unansweredCount,
                'progress_percentage' => $progressPercentage,
            ],
        ], 'Detail live telemetry sesi ujian berhasil diambil');
    }
}
