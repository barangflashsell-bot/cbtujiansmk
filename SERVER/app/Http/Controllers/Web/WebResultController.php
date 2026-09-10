<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Exam;
use App\Models\Result;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebResultController extends Controller
{
    /**
     * Display a listing of exam results.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $query = Result::with([
            'exam.subject',
            'student.user',
            'student.schoolClass',
        ])->latest('id');

        // Guru scoping: only results for exams they created
        if ($userRole !== 'admin') {
            $query->whereHas('exam', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->query('exam_id'));
        }

        if ($request->filled('class_id')) {
            $classId = $request->query('class_id');
            $query->whereHas('student', function ($sq) use ($classId) {
                $sq->where('class_id', $classId);
            });
        }

        if ($request->filled('is_published')) {
            $isPublished = ($request->query('is_published') === '1');
            $query->where('is_published', $isPublished);
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

        $results = $query->paginate(20)->withQueryString();

        $examsQuery = Exam::orderBy('title');
        if ($userRole !== 'admin') {
            $examsQuery->where('created_by', $user->id);
        }
        $exams = $examsQuery->get();

        $classes = Classes::where('status', 'active')->orderBy('name')->get();
        $viewName = ($userRole === 'admin') ? 'admin.results.index' : 'guru.results.index';

        return view($viewName, [
            'results' => $results,
            'exams' => $exams,
            'classes' => $classes,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Display the specified result details.
     */
    public function show(Request $request, int $id): View
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $result = Result::with([
            'exam.subject',
            'student.user',
            'student.schoolClass',
            'attempt.answers.question.options',
        ])->findOrFail($id);

        if ($userRole !== 'admin' && $result->exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk melihat hasil ujian ini.');
        }

        $viewName = ($userRole === 'admin') ? 'admin.results.show' : 'guru.results.show';

        return view($viewName, [
            'result' => $result,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Toggle publication status of a result.
     */
    public function togglePublish(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $result = Result::with('exam')->findOrFail($id);

        if ($userRole !== 'admin' && $result->exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mempublikasikan hasil ujian ini.');
        }

        $result->update([
            'is_published' => ! $result->is_published,
        ]);

        $statusMsg = $result->is_published ? 'dipublikasikan ke peserta' : 'ditarik dari publikasi';

        return back()->with('success', "Hasil ujian siswa berhasil {$statusMsg}.");
    }
}
