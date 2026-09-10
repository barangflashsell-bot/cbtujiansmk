<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebMonitoringController extends Controller
{
    /**
     * Display live monitoring overview of exams.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $query = Exam::with(['subject', 'creator'])
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
            ->latest('id');

        if ($userRole !== 'admin') {
            $query->where('created_by', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        $exams = $query->paginate(15)->withQueryString();

        $viewName = ($userRole === 'admin') ? 'admin.monitoring.index' : 'guru.monitoring.index';

        return view($viewName, [
            'exams' => $exams,
            'serverTime' => now(),
            'userRole' => $userRole,
        ]);
    }

    /**
     * Display live telemetry for a specific exam.
     */
    public function show(Request $request, int $id): View
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $exam = Exam::with(['subject', 'creator'])
            ->withCount('examQuestions')
            ->findOrFail($id);

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk memantau paket ujian ini.');
        }

        $totalQuestions = (int) $exam->exam_questions_count;
        $now = now();

        $participantsQuery = ExamParticipant::with([
            'student.user',
            'student.schoolClass',
        ])->where('exam_id', $exam->id);

        if ($request->filled('class_id')) {
            $classId = $request->query('class_id');
            $participantsQuery->whereHas('student', function ($sq) use ($classId) {
                $sq->where('class_id', $classId);
            });
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $participantsQuery->whereHas('student', function ($sq) use ($search) {
                $sq->where('nis', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $participants = $participantsQuery->paginate(25)->withQueryString();

        // Load attempts for current page
        $studentIds = collect($participants->items())->pluck('student_id')->all();
        $attempts = ExamAttempt::withCount('answers')
            ->where('exam_id', $exam->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        // Global stats for the exam summary header
        $stats = [
            'total' => $exam->participants()->count(),
            'in_progress' => $exam->attempts()->where('status', 'in_progress')->count(),
            'submitted' => $exam->attempts()->where('status', 'submitted')->count(),
            'timeout' => $exam->attempts()->where('status', 'timeout')->count(),
        ];
        $stats['not_started'] = max(0, $stats['total'] - ($stats['in_progress'] + $stats['submitted'] + $stats['timeout']));

        $classes = Classes::where('status', 'active')->orderBy('name')->get();
        $viewName = ($userRole === 'admin') ? 'admin.monitoring.show' : 'guru.monitoring.show';

        return view($viewName, [
            'exam' => $exam,
            'participants' => $participants,
            'attempts' => $attempts,
            'totalQuestions' => $totalQuestions,
            'stats' => $stats,
            'classes' => $classes,
            'serverTime' => $now,
            'userRole' => $userRole,
        ]);
    }
}
