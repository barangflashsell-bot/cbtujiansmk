<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Classes;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\Result;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebReportController extends Controller
{
    /**
     * Display reports overview & selection hub.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        if (! in_array($userRole, ['admin', 'teacher', 'guru'], true)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses modul laporan.');
        }

        $examsQuery = Exam::with(['subject', 'creator'])
            ->withCount(['examQuestions', 'participants', 'attempts'])
            ->latest('id');

        // Scoping for Guru: only exams created by authenticated teacher
        if ($userRole !== 'admin') {
            $examsQuery->where('created_by', $user->id);
        }

        if ($request->filled('subject_id')) {
            $examsQuery->where('subject_id', $request->query('subject_id'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $examsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $exams = $examsQuery->paginate(15)->withQueryString();
        $subjects = Subject::where('status', 'active')->orderBy('name')->get();
        $classes = Classes::where('status', 'active')->orderBy('name')->get();

        $viewName = ($userRole === 'admin') ? 'admin.reports.index' : 'guru.reports.index';

        return view($viewName, [
            'exams' => $exams,
            'subjects' => $subjects,
            'classes' => $classes,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Display comprehensive statistical report and student performance for an exam.
     */
    public function examReport(Request $request, int $id): View
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        if (! in_array($userRole, ['admin', 'teacher', 'guru'], true)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses laporan ujian.');
        }

        $exam = Exam::with(['subject', 'creator'])
            ->withCount('examQuestions')
            ->findOrFail($id);

        // Ownership enforcement for Guru (Anti-IDOR)
        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk melihat laporan paket ujian ini.');
        }

        $classId = $request->query('class_id');

        // Base results query
        $resultsQuery = Result::with([
            'student:id,user_id,class_id,nis',
            'student.user:id,name,username',
            'student.schoolClass:id,name,level',
        ])->where('exam_id', $exam->id);

        if ($classId) {
            $resultsQuery->whereHas('student', function ($sq) use ($classId) {
                $sq->where('class_id', $classId);
            });
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $resultsQuery->whereHas('student', function ($sq) use ($search) {
                $sq->where('nis', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Summary metrics calculation
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
        $results = $resultsQuery->orderByDesc('final_score')->paginate(20)->withQueryString();
        $classes = Classes::where('status', 'active')->orderBy('name')->get();

        $statistics = [
            'total_participants' => $totalParticipants,
            'total_graded' => $totalGraded,
            'passed_count' => $passedCount,
            'failed_count' => $failedCount,
            'pass_percentage' => $passPercentage,
            'average_score' => $averageScore,
            'highest_score' => $highestScore,
            'lowest_score' => $lowestScore,
            'passing_score' => $passingScore,
        ];

        $viewName = ($userRole === 'admin') ? 'admin.reports.exam' : 'guru.reports.exam';

        return view($viewName, [
            'exam' => $exam,
            'statistics' => $statistics,
            'results' => $results,
            'classes' => $classes,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Display performance report for a specific school class.
     */
    public function classReport(Request $request, int $id): View
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        if (! in_array($userRole, ['admin', 'teacher', 'guru'], true)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses laporan kelas.');
        }

        $class = Classes::withCount('students')->findOrFail($id);

        $query = Result::with([
            'exam:id,subject_id,title,passing_score,created_by',
            'exam.subject:id,name,code',
            'student:id,user_id,class_id,nis',
            'student.user:id,name,username',
        ])->whereHas('student', function ($sq) use ($class) {
            $sq->where('class_id', $class->id);
        });

        // Guru scoping: only see results for exams created by this teacher
        if ($userRole !== 'admin') {
            $query->whereHas('exam', function ($eq) use ($user) {
                $eq->where('created_by', $user->id);
            });
        }

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->query('exam_id'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('student', function ($sq) use ($search) {
                $sq->where('nis', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $results = $query->latest('id')->paginate(20)->withQueryString();

        // Exams list for filter dropdown
        $examsQuery = Exam::orderBy('title');
        if ($userRole !== 'admin') {
            $examsQuery->where('created_by', $user->id);
        }
        $exams = $examsQuery->get(['id', 'title']);

        $viewName = ($userRole === 'admin') ? 'admin.reports.class' : 'guru.reports.class';

        return view($viewName, [
            'class' => $class,
            'results' => $results,
            'exams' => $exams,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Display item analysis (analisis butir soal) for an exam.
     */
    public function itemAnalysis(Request $request, int $id): View
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        if (! in_array($userRole, ['admin', 'teacher', 'guru'], true)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses analisis butir soal.');
        }

        $exam = Exam::with(['subject'])->findOrFail($id);

        // Anti-IDOR: Guru can only inspect item analysis of their own exams
        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk melihat analisis butir soal ujian ini.');
        }

        $examQuestions = ExamQuestion::where('exam_id', $exam->id)
            ->with(['question' => function ($q) {
                $q->select('id', 'subject_id', 'question_type', 'content');
            }])
            ->orderBy('order_index')
            ->get();

        $attemptIds = $exam->attempts()->pluck('id');
        $totalAttempts = $attemptIds->count();

        // Calculate difficulty index and classification per question
        $items = $examQuestions->map(function ($eq) use ($attemptIds, $totalAttempts) {
            $question = $eq->question;

            $answers = Answer::whereIn('attempt_id', $attemptIds)
                ->where('question_id', $eq->question_id)
                ->get();

            $totalAnswered = $answers->count();
            $correctAnswersCount = $answers->where('is_correct', true)->count();
            $wrongAnswersCount = $totalAnswered - $correctAnswersCount;

            $difficultyIndex = $totalAnswered > 0 ? round(($correctAnswersCount / $totalAnswered) * 100, 1) : 0.0;

            // Classification matching REPORTS.md specification:
            // mudah: >= 70.0%
            // sedang: 30.0% - 69.9%
            // sukar: < 30.0%
            $classification = match (true) {
                $difficultyIndex >= 70.0 => 'mudah',
                $difficultyIndex >= 30.0 => 'sedang',
                default => 'sukar',
            };

            return [
                'question_id' => $question->id ?? $eq->question_id,
                'order_index' => (int) $eq->order_index,
                'weight' => (float) $eq->weight,
                'question_type' => $question->question_type ?? '-',
                'content_preview' => mb_substr(strip_tags((string) ($question->content ?? '-')), 0, 120),
                'total_attempts' => $totalAttempts,
                'total_answered' => $totalAnswered,
                'correct_count' => $correctAnswersCount,
                'wrong_count' => $wrongAnswersCount,
                'difficulty_index' => $difficultyIndex,
                'classification' => $classification,
            ];
        });

        $viewName = ($userRole === 'admin') ? 'admin.reports.item_analysis' : 'guru.reports.item_analysis';

        return view($viewName, [
            'exam' => $exam,
            'items' => $items,
            'totalAttempts' => $totalAttempts,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Export exam results to Excel/CSV.
     */
    public function exportExamCsv(Request $request, int $id)
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        if (! in_array($userRole, ['admin', 'teacher', 'guru'], true)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengunduh laporan ujian.');
        }

        $exam = Exam::with(['subject'])->findOrFail($id);

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengunduh laporan paket ujian ini.');
        }

        $results = Result::with([
            'student:id,user_id,class_id,nis',
            'student.user:id,name',
            'student.schoolClass:id,name',
        ])
            ->where('exam_id', $exam->id)
            ->orderByDesc('final_score')
            ->get();

        $filename = 'Laporan-Nilai-' . \Illuminate\Support\Str::slug($exam->title) . '-' . date('Ymd_His') . '.csv';
        $passingScore = (float) ($exam->passing_score ?? 75.00);

        return response()->streamDownload(function () use ($results, $passingScore) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'No',
                'NIS',
                'Nama Siswa',
                'Kelas',
                'Benar',
                'Salah',
                'Kosong',
                'Nilai Akhir',
                'KKM',
                'Status Kelulusan',
                'Waktu Penilaian',
            ]);

            foreach ($results as $index => $r) {
                $score = (float) $r->final_score;
                $status = ($score >= $passingScore) ? 'LULUS' : 'BELUM LULUS';

                fputcsv($handle, [
                    $index + 1,
                    $r->student->nis ?? '-',
                    $r->student->user->name ?? '-',
                    $r->student->schoolClass->name ?? '-',
                    $r->correct_count,
                    $r->wrong_count,
                    $r->unanswered_count,
                    number_format($score, 2),
                    number_format($passingScore, 2),
                    $status,
                    $r->graded_at ? $r->graded_at->format('Y-m-d H:i:s') : '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Display printable / PDF view of the exam report.
     */
    public function exportExamPdf(Request $request, int $id): View
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        if (! in_array($userRole, ['admin', 'teacher', 'guru'], true)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mencetak laporan ujian.');
        }

        $exam = Exam::with(['subject', 'creator'])
            ->withCount('examQuestions')
            ->findOrFail($id);

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mencetak laporan paket ujian ini.');
        }

        $results = Result::with([
            'student:id,user_id,class_id,nis',
            'student.user:id,name',
            'student.schoolClass:id,name',
        ])
            ->where('exam_id', $exam->id)
            ->orderByDesc('final_score')
            ->get();

        $passingScore = (float) ($exam->passing_score ?? 75.00);
        $totalGraded = $results->count();
        $passedCount = $results->filter(fn ($r) => (float) $r->final_score >= $passingScore)->count();

        $statistics = [
            'total_participants' => $exam->participants()->count(),
            'total_graded' => $totalGraded,
            'passed_count' => $passedCount,
            'failed_count' => $totalGraded - $passedCount,
            'pass_percentage' => $totalGraded > 0 ? round(($passedCount / $totalGraded) * 100, 1) : 0,
            'average_score' => $totalGraded > 0 ? round($results->avg('final_score'), 2) : 0,
            'highest_score' => $totalGraded > 0 ? (float) $results->max('final_score') : 0,
            'lowest_score' => $totalGraded > 0 ? (float) $results->min('final_score') : 0,
            'passing_score' => $passingScore,
        ];

        return view('reports.print_exam', [
            'exam' => $exam,
            'results' => $results,
            'statistics' => $statistics,
            'printedAt' => now(),
        ]);
    }
}
