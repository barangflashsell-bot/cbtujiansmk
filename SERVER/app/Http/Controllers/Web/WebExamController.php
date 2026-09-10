<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WebExamController extends Controller
{
    /**
     * Display a listing of exams.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $query = Exam::with(['subject', 'creator'])
            ->withCount(['questions', 'participants', 'attempts'])
            ->latest('id');

        // Guru can only view exams they created
        if ($userRole !== 'admin') {
            $query->where('created_by', $user->id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->query('subject_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $exams = $query->paginate(15)->withQueryString();
        $subjects = Subject::where('status', 'active')->orderBy('name')->get();

        $viewName = ($userRole === 'admin') ? 'admin.exams.index' : 'guru.exams.index';

        return view($viewName, [
            'exams' => $exams,
            'subjects' => $subjects,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Show form to create a new exam.
     */
    public function create(Request $request): View
    {
        $userRole = strtolower($request->user()->role->name ?? '');
        $subjects = Subject::where('status', 'active')->orderBy('name')->get();

        $viewName = ($userRole === 'admin') ? 'admin.exams.create' : 'guru.exams.create';

        return view($viewName, [
            'subjects' => $subjects,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Store a newly created exam.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'passing_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'token' => ['nullable', 'string', 'max:16'],
            'start_window' => ['required', 'date'],
            'end_window' => ['required', 'date', 'after:start_window'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            'show_result' => ['nullable', 'boolean'],
            'allow_review' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,published,active,completed'],
        ], [
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'title.required' => 'Judul paket ujian wajib diisi.',
            'duration_minutes.required' => 'Durasi waktu ujian wajib diisi.',
            'start_window.required' => 'Jadwal waktu mulai wajib diisi.',
            'end_window.required' => 'Jadwal batas akhir waktu wajib diisi.',
            'end_window.after' => 'Batas akhir waktu harus setelah waktu mulai.',
        ]);

        $exam = Exam::create([
            'subject_id' => $validated['subject_id'],
            'created_by' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'duration_minutes' => $validated['duration_minutes'],
            'passing_score' => $validated['passing_score'] ?? 75.00,
            'token' => ! empty($validated['token']) ? strtoupper(trim($validated['token'])) : null,
            'start_window' => $validated['start_window'],
            'end_window' => $validated['end_window'],
            'shuffle_questions' => $request->boolean('shuffle_questions'),
            'shuffle_options' => $request->boolean('shuffle_options'),
            'show_result' => $request->boolean('show_result'),
            'allow_review' => $request->boolean('allow_review'),
            'status' => $validated['status'],
        ]);

        $redirectRoute = ($userRole === 'admin') ? 'admin.exams.show' : 'guru.exams.show';

        return redirect()->route($redirectRoute, $exam->id)->with('success', 'Paket ujian baru berhasil dibuat.');
    }

    /**
     * Show detail of an exam (questions & participants management).
     */
    public function show(Request $request, int $id): View
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $exam = Exam::with(['subject', 'creator'])
            ->withCount(['questions', 'participants', 'attempts'])
            ->findOrFail($id);

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk melihat paket ujian ini.');
        }

        // Load attached questions with pivot order_index and weight
        $examQuestions = $exam->questions()
            ->with('creator.user')
            ->orderByPivot('order_index')
            ->get();

        // Available questions from the same subject not yet attached
        $attachedIds = $examQuestions->pluck('id')->all();
        $availableQuestionsQuery = Question::where('subject_id', $exam->subject_id)
            ->where('status', 'active');
        if (! empty($attachedIds)) {
            $availableQuestionsQuery->whereNotIn('id', $attachedIds);
        }
        $availableQuestions = $availableQuestionsQuery->latest('id')->limit(50)->get();

        // Load participants with student profiles, class, and attempts
        $participants = ExamParticipant::with(['student.user', 'student.schoolClass'])
            ->where('exam_id', $exam->id)
            ->latest('id')
            ->paginate(20, ['*'], 'participants_page')
            ->withQueryString();

        // Classes list for mass assigning students
        $classes = Classes::where('status', 'active')->orderBy('name')->get();

        $viewName = ($userRole === 'admin') ? 'admin.exams.show' : 'guru.exams.show';

        return view($viewName, [
            'exam' => $exam,
            'examQuestions' => $examQuestions,
            'availableQuestions' => $availableQuestions,
            'participants' => $participants,
            'classes' => $classes,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Show form to edit an existing exam.
     */
    public function edit(Request $request, int $id): View
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $exam = Exam::findOrFail($id);

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit paket ujian ini.');
        }

        $subjects = Subject::where('status', 'active')->orderBy('name')->get();
        $viewName = ($userRole === 'admin') ? 'admin.exams.edit' : 'guru.exams.edit';

        return view($viewName, [
            'exam' => $exam,
            'subjects' => $subjects,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Update the specified exam.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $exam = Exam::findOrFail($id);

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk memperbarui paket ujian ini.');
        }

        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'passing_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'token' => ['nullable', 'string', 'max:16'],
            'start_window' => ['required', 'date'],
            'end_window' => ['required', 'date', 'after:start_window'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            'show_result' => ['nullable', 'boolean'],
            'allow_review' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,published,active,completed'],
        ]);

        $exam->update([
            'subject_id' => $validated['subject_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'duration_minutes' => $validated['duration_minutes'],
            'passing_score' => $validated['passing_score'] ?? 75.00,
            'token' => ! empty($validated['token']) ? strtoupper(trim($validated['token'])) : null,
            'start_window' => $validated['start_window'],
            'end_window' => $validated['end_window'],
            'shuffle_questions' => $request->boolean('shuffle_questions'),
            'shuffle_options' => $request->boolean('shuffle_options'),
            'show_result' => $request->boolean('show_result'),
            'allow_review' => $request->boolean('allow_review'),
            'status' => $validated['status'],
        ]);

        $redirectRoute = ($userRole === 'admin') ? 'admin.exams.show' : 'guru.exams.show';

        return redirect()->route($redirectRoute, $exam->id)->with('success', 'Paket ujian berhasil diperbarui.');
    }

    /**
     * Remove the specified exam.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $exam = Exam::withCount('attempts')->findOrFail($id);

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus paket ujian ini.');
        }

        if ($exam->attempts_count > 0) {
            return back()->with('error', 'Paket ujian tidak dapat dihapus karena sudah memiliki rekaman pengerjaan siswa.');
        }

        DB::transaction(function () use ($exam) {
            $exam->examQuestions()->delete();
            $exam->participants()->delete();
            $exam->delete();
        });

        $redirectRoute = ($userRole === 'admin') ? 'admin.exams.index' : 'guru.exams.index';

        return redirect()->route($redirectRoute)->with('success', 'Paket ujian berhasil dihapus.');
    }

    /**
     * Attach a question to the exam.
     */
    public function attachQuestion(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $exam = Exam::withCount('attempts')->findOrFail($id);

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk memodifikasi butir soal ujian ini.');
        }

        if ($exam->attempts_count > 0) {
            return back()->with('error', 'Tidak dapat menambah soal karena ujian sudah memiliki riwayat pengerjaan siswa.');
        }

        $validated = $request->validate([
            'question_id' => ['required', 'exists:questions,id'],
            'order_index' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $question = Question::findOrFail($validated['question_id']);

        if ($question->subject_id !== $exam->subject_id) {
            return back()->with('error', 'Mata pelajaran butir soal harus cocok dengan mata pelajaran paket ujian.');
        }

        // Determine order_index
        $nextOrder = $validated['order_index'] ?? ($exam->questions()->count() + 1);
        $weight = $validated['weight'] ?? ($question->score_weight ?? 1.0);

        // Attach question if not already attached
        if (! $exam->questions()->where('question_id', $question->id)->exists()) {
            $exam->questions()->attach($question->id, [
                'order_index' => $nextOrder,
                'weight' => $weight,
            ]);
        }

        return back()->with('success', 'Butir soal berhasil ditambahkan ke dalam paket ujian.');
    }

    /**
     * Detach a question from the exam.
     */
    public function detachQuestion(Request $request, int $id, int $questionId): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $exam = Exam::withCount('attempts')->findOrFail($id);

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk melepaskan butir soal ujian ini.');
        }

        if ($exam->attempts_count > 0) {
            return back()->with('error', 'Tidak dapat melepaskan butir soal karena ujian sudah memiliki riwayat pengerjaan.');
        }

        $exam->questions()->detach($questionId);

        return back()->with('success', 'Butir soal berhasil dilepaskan dari paket ujian.');
    }

    /**
     * Add participants (by Class or individually).
     */
    public function addParticipants(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $exam = Exam::findOrFail($id);

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mendaftarkan peserta ke ujian ini.');
        }

        $validated = $request->validate([
            'class_id' => ['nullable', 'exists:classes,id'],
            'student_id' => ['nullable', 'exists:students,id'],
        ]);

        $enrolledCount = 0;

        DB::transaction(function () use ($exam, $validated, &$enrolledCount) {
            if (! empty($validated['class_id'])) {
                // Enroll all students from this class
                $students = Student::where('class_id', $validated['class_id'])->get();
                foreach ($students as $student) {
                    if (! ExamParticipant::where('exam_id', $exam->id)->where('student_id', $student->id)->exists()) {
                        ExamParticipant::create([
                            'exam_id' => $exam->id,
                            'student_id' => $student->id,
                            'allow_retest' => false,
                        ]);
                        $enrolledCount++;
                    }
                }
            } elseif (! empty($validated['student_id'])) {
                if (! ExamParticipant::where('exam_id', $exam->id)->where('student_id', $validated['student_id'])->exists()) {
                    ExamParticipant::create([
                        'exam_id' => $exam->id,
                        'student_id' => $validated['student_id'],
                        'allow_retest' => false,
                    ]);
                    $enrolledCount++;
                }
            }
        });

        return back()->with('success', "{$enrolledCount} peserta berhasil didaftarkan ke paket ujian.");
    }

    /**
     * Remove a participant from the exam.
     */
    public function removeParticipant(Request $request, int $id, int $participantId): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $exam = Exam::findOrFail($id);

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus peserta dari ujian ini.');
        }

        $participant = ExamParticipant::where('exam_id', $exam->id)->findOrFail($participantId);

        // Check if student already started an attempt
        $hasAttempt = DB::table('exam_attempts')
            ->where('exam_id', $exam->id)
            ->where('student_id', $participant->student_id)
            ->exists();

        if ($hasAttempt) {
            return back()->with('error', 'Peserta tidak dapat dihapus karena sudah memiliki sesi pengerjaan ujian.');
        }

        $participant->delete();

        return back()->with('success', 'Peserta ujian berhasil dihapus dari daftar pendaftaran.');
    }

    /**
     * Toggle allow_retest permission for participant.
     */
    public function toggleRetest(Request $request, int $id, int $participantId): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $exam = Exam::findOrFail($id);

        if ($userRole !== 'admin' && $exam->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah hak ujian ulang peserta.');
        }

        $participant = ExamParticipant::where('exam_id', $exam->id)->findOrFail($participantId);
        $participant->update([
            'allow_retest' => ! $participant->allow_retest,
        ]);

        $statusMsg = $participant->allow_retest ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Hak ujian ulang peserta berhasil {$statusMsg}.");
    }
}
