<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WebQuestionController extends Controller
{
    /**
     * Display a listing of questions (Role-scoped).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $query = Question::with(['subject', 'creator.user', 'options'])->latest('id');

        $teacher = $user->teacher;

        // Guru can only view questions they created
        if ($userRole !== 'admin') {
            $query->where('created_by', $teacher?->id ?? 0);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->query('subject_id'));
        }

        if ($request->filled('type')) {
            $query->where('question_type', $request->query('type'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where('content', 'like', "%{$search}%");
        }

        $questions = $query->paginate(15)->withQueryString();
        $subjects = Subject::where('status', 'active')->orderBy('name')->get();

        $viewName = ($userRole === 'admin') ? 'admin.questions.index' : 'guru.questions.index';

        return view($viewName, [
            'questions' => $questions,
            'subjects' => $subjects,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Show form to create a new question.
     */
    public function create(Request $request): View
    {
        $userRole = strtolower($request->user()->role->name ?? '');
        $subjects = Subject::where('status', 'active')->orderBy('name')->get();

        $viewName = ($userRole === 'admin') ? 'admin.questions.create' : 'guru.questions.create';

        return view($viewName, [
            'subjects' => $subjects,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Store a newly created question and its options.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $teacher = $user->teacher;
        $teacherId = ($userRole === 'admin')
            ? ($teacher?->id ?? Teacher::first()?->id)
            : $teacher?->id;

        if (! $teacherId) {
            return back()->withInput()->with('error', 'Profil guru tidak ditemukan dalam sistem.');
        }

        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'question_type' => ['required', 'in:single_choice,multiple_choice,essay'],
            'content' => ['required', 'string'],
            'score_weight' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'explanation' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'options.*.label' => ['nullable', 'string', 'max:8'],
            'options.*.content' => ['nullable', 'string'],
            'correct_option' => ['nullable', 'string'],
        ], [
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'content.required' => 'Konten butir soal wajib diisi.',
            'score_weight.required' => 'Bobot nilai soal wajib diisi.',
        ]);

        DB::transaction(function () use ($teacherId, $validated) {
            $question = Question::create([
                'subject_id' => $validated['subject_id'],
                'created_by' => $teacherId,
                'question_type' => $validated['question_type'],
                'content' => $validated['content'],
                'score_weight' => $validated['score_weight'],
                'difficulty' => $validated['difficulty'],
                'explanation' => $validated['explanation'] ?? null,
                'status' => 'active',
            ]);

            // Save options for single_choice or multiple_choice
            if (in_array($validated['question_type'], ['single_choice', 'multiple_choice'], true) && ! empty($validated['options'])) {
                $correctLabel = $validated['correct_option'] ?? 'A';

                foreach ($validated['options'] as $opt) {
                    if (! empty($opt['content']) && ! empty($opt['label'])) {
                        $isCorrect = (strtoupper(trim($opt['label'])) === strtoupper(trim($correctLabel)));

                        $question->options()->create([
                            'option_label' => strtoupper(trim($opt['label'])),
                            'content' => trim($opt['content']),
                            'is_correct' => $isCorrect,
                        ]);
                    }
                }
            }
        });

        $redirectRoute = ($userRole === 'admin') ? 'admin.questions.index' : 'guru.questions.index';

        return redirect()->route($redirectRoute)->with('success', 'Butir soal baru berhasil disimpan.');
    }

    /**
     * Show form to edit an existing question.
     */
    public function edit(Request $request, int $id): View|RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $question = Question::with(['options', 'subject'])->findOrFail($id);

        // Authorization: Guru can only edit questions they created
        if ($userRole !== 'admin' && $question->created_by !== ($user->teacher?->id ?? 0)) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit butir soal ini.');
        }

        $subjects = Subject::where('status', 'active')->orderBy('name')->get();
        $viewName = ($userRole === 'admin') ? 'admin.questions.edit' : 'guru.questions.edit';

        return view($viewName, [
            'question' => $question,
            'subjects' => $subjects,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Update the specified question and its options.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $question = Question::findOrFail($id);

        // Authorization check
        if ($userRole !== 'admin' && $question->created_by !== ($user->teacher?->id ?? 0)) {
            abort(403, 'Anda tidak memiliki izin untuk memperbarui butir soal ini.');
        }

        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'question_type' => ['required', 'in:single_choice,multiple_choice,essay'],
            'content' => ['required', 'string'],
            'score_weight' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'explanation' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'options.*.label' => ['nullable', 'string', 'max:8'],
            'options.*.content' => ['nullable', 'string'],
            'correct_option' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($question, $validated) {
            $question->update([
                'subject_id' => $validated['subject_id'],
                'question_type' => $validated['question_type'],
                'content' => $validated['content'],
                'score_weight' => $validated['score_weight'],
                'difficulty' => $validated['difficulty'],
                'explanation' => $validated['explanation'] ?? null,
            ]);

            if (in_array($validated['question_type'], ['single_choice', 'multiple_choice'], true) && ! empty($validated['options'])) {
                $question->options()->delete();
                $correctLabel = $validated['correct_option'] ?? 'A';

                foreach ($validated['options'] as $opt) {
                    if (! empty($opt['content']) && ! empty($opt['label'])) {
                        $isCorrect = (strtoupper(trim($opt['label'])) === strtoupper(trim($correctLabel)));

                        $question->options()->create([
                            'option_label' => strtoupper(trim($opt['label'])),
                            'content' => trim($opt['content']),
                            'is_correct' => $isCorrect,
                        ]);
                    }
                }
            }
        });

        $redirectRoute = ($userRole === 'admin') ? 'admin.questions.index' : 'guru.questions.index';

        return redirect()->route($redirectRoute)->with('success', 'Butir soal berhasil diperbarui.');
    }

    /**
     * Remove the specified question.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $question = Question::findOrFail($id);

        if ($userRole !== 'admin' && $question->created_by !== ($user->teacher?->id ?? 0)) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus butir soal ini.');
        }

        DB::transaction(function () use ($question) {
            $question->options()->delete();
            $question->delete();
        });

        $redirectRoute = ($userRole === 'admin') ? 'admin.questions.index' : 'guru.questions.index';

        return redirect()->route($redirectRoute)->with('success', 'Butir soal berhasil dihapus.');
    }
}
