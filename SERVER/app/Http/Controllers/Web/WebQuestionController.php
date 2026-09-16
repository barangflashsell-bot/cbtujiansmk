<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Models\Teacher;
use App\Traits\HandlesExcelImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WebQuestionController extends Controller
{
    use HandlesExcelImport;
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

        $subjectsQuery = Subject::where('status', 'active');
        if ($userRole !== 'admin') {
            $subjectsQuery->withCount([
                'questions' => fn($q) => $q->where('created_by', $teacher?->id ?? 0),
                'questions as pg_count' => fn($q) => $q->where('created_by', $teacher?->id ?? 0)->where('question_type', 'single_choice'),
                'questions as pg_multi_count' => fn($q) => $q->where('created_by', $teacher?->id ?? 0)->where('question_type', 'multiple_choice'),
                'questions as essay_count' => fn($q) => $q->where('created_by', $teacher?->id ?? 0)->where('question_type', 'essay'),
                'questions as tf_count' => fn($q) => $q->where('created_by', $teacher?->id ?? 0)->where('question_type', 'true_false'),
                'questions as match_count' => fn($q) => $q->where('created_by', $teacher?->id ?? 0)->where('question_type', 'matching'),
            ]);
        } else {
            $subjectsQuery->withCount([
                'questions',
                'questions as pg_count' => fn($q) => $q->where('question_type', 'single_choice'),
                'questions as pg_multi_count' => fn($q) => $q->where('question_type', 'multiple_choice'),
                'questions as essay_count' => fn($q) => $q->where('question_type', 'essay'),
                'questions as tf_count' => fn($q) => $q->where('question_type', 'true_false'),
                'questions as match_count' => fn($q) => $q->where('question_type', 'matching'),
            ]);
        }

        if ($request->filled('search_subject')) {
            $searchSubject = $request->query('search_subject');
            $subjectsQuery->where(function ($q) use ($searchSubject) {
                $q->where('name', 'like', "%{$searchSubject}%")
                  ->orWhere('code', 'like', "%{$searchSubject}%");
            });
        }

        $subjects = $subjectsQuery->orderBy('name')->get();
        $teachers = ($userRole === 'admin') ? Teacher::with('user')->get() : collect();

        $viewName = ($userRole === 'admin') ? 'admin.questions.index' : 'guru.questions.index';

        return view($viewName, [
            'questions' => $questions,
            'subjects' => $subjects,
            'teachers' => $teachers,
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

    /**
     * Download Excel or CSV template for question import (Format Gambar 4).
     */
    public function downloadTemplate(Request $request)
    {
        $headers = [
            'NO',
            'Soal/Pertanyaan',
            'Jenis ( 1=PG, 2=Essai)',
            'Jawaban A',
            'Jawaban B',
            'Jawaban C',
            'Jawaban D',
            'Jawaban E',
            'Kunci Jawaban (A/B/C/D/E)',
        ];

        $sampleRows = [
            ['1', 'Berapakah hasil perhitungan dari 25 x 4?', '1', '50', '75', '100', '125', '150', 'C'],
            ['2', 'Jelaskan fungsi sistem komputer dan sebutkan komponen utamanya!', '2', '', '', '', '', '', ''],
        ];
        $colWidths = [45, 360, 160, 130, 130, 130, 130, 130, 180];

        if ($request->query('format') === 'csv') {
            return $this->streamCsvTemplate('format_import_soal.csv', $headers, $sampleRows);
        }

        return $this->streamExcelTemplate('format_import_soal.xls', $headers, $sampleRows, $colWidths);
    }

    /**
     * Import questions from Excel or CSV file.
     */
    public function import(Request $request): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $teacher = $user->teacher;
        $teacherId = ($userRole === 'admin')
            ? ($teacher?->id ?? Teacher::first()?->id)
            : $teacher?->id;

        if (! $teacherId) {
            $firstTeacher = Teacher::first();
            if ($firstTeacher) {
                $teacherId = $firstTeacher->id;
            } else {
                return back()->with('error', 'Silakan daftarkan minimal 1 data guru terlebih dahulu sebelum mengimpor soal.');
            }
        }

        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'default_subject_id' => ['nullable', 'exists:subjects,id'],
        ], [
            'file.required' => 'File Excel atau CSV butir soal wajib diunggah.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $rawRows = $this->parseUploadedSpreadsheet($file->getRealPath(), $extension);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }

        if (empty($rawRows)) {
            return back()->with('error', 'File yang diunggah kosong atau tidak memiliki data.');
        }

        $headerRow = array_shift($rawRows);
        $map = [];
        foreach ($headerRow as $idx => $header) {
            $clean = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]/', '', $header)));
            if (in_array($clean, ['matapelajaran', 'mapel', 'subject', 'subjectname'])) {
                $map['subject'] = $idx;
            } elseif (in_array($clean, ['tipesoal', 'tipe', 'type', 'questiontype', 'jenis', 'jenis1pg2essai', 'jenissoal'])) {
                $map['type'] = $idx;
            } elseif (in_array($clean, ['pertanyaan', 'butirsoal', 'soal', 'content', 'question', 'isi', 'soalpertanyaan'])) {
                $map['content'] = $idx;
            } elseif (in_array($clean, ['opsia', 'pilihana', 'a', 'optiona', 'jawabana'])) {
                $map['opsi_a'] = $idx;
            } elseif (in_array($clean, ['opsib', 'pilihanb', 'b', 'optionb', 'jawabanb'])) {
                $map['opsi_b'] = $idx;
            } elseif (in_array($clean, ['opsic', 'pilihanc', 'c', 'optionc', 'jawabanc'])) {
                $map['opsi_c'] = $idx;
            } elseif (in_array($clean, ['opsid', 'pilihand', 'd', 'optiond', 'jawaband'])) {
                $map['opsi_d'] = $idx;
            } elseif (in_array($clean, ['opsie', 'pilihane', 'e', 'optione', 'jawabane'])) {
                $map['opsi_e'] = $idx;
            } elseif (in_array($clean, ['kuncijawaban', 'kunci', 'jawaban', 'correct', 'answer', 'key', 'kuncijawabanabcde'])) {
                $map['correct'] = $idx;
            } elseif (in_array($clean, ['bobot', 'bobotnilai', 'skor', 'nilai', 'weight', 'scoreweight'])) {
                $map['weight'] = $idx;
            } elseif (in_array($clean, ['tingkatkesulitan', 'kesulitan', 'difficulty'])) {
                $map['difficulty'] = $idx;
            }
        }

        // Positional fallback for exact Gambar 4 format: [NO, Soal, Jenis, Opsi A, B, C, D, E, Kunci]
        if (! isset($map['content']) && count($headerRow) >= 9) {
            $map['content'] = 1;
            $map['type'] = 2;
            $map['opsi_a'] = 3;
            $map['opsi_b'] = 4;
            $map['opsi_c'] = 5;
            $map['opsi_d'] = 6;
            $map['opsi_e'] = 7;
            $map['correct'] = 8;
        }

        if (! isset($map['content'])) {
            return back()->with('error', 'Format kolom file tidak sesuai. Pastikan terdapat kolom "Soal/Pertanyaan".');
        }

        $defaultSubjectId = $request->input('default_subject_id');
        $subjectsMap = Subject::all()->keyBy(fn ($s) => strtolower(trim($s->name)));
        $subjectsCodeMap = Subject::all()->keyBy(fn ($s) => strtolower(trim($s->code)));

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $rawRows,
            $map,
            $teacherId,
            $defaultSubjectId,
            &$subjectsMap,
            &$subjectsCodeMap,
            &$imported,
            &$skipped
        ) {
            foreach ($rawRows as $row) {
                $content = isset($map['content']) ? trim((string) ($row[$map['content']] ?? '')) : '';
                if ($content === '') {
                    $skipped++;
                    continue;
                }

                // Resolve Subject
                $subjectName = isset($map['subject']) ? trim((string) ($row[$map['subject']] ?? '')) : '';
                $subjectId = null;

                if ($subjectName !== '') {
                    $subKey = strtolower($subjectName);
                    if (isset($subjectsMap[$subKey])) {
                        $subjectId = $subjectsMap[$subKey]->id;
                    } elseif (isset($subjectsCodeMap[$subKey])) {
                        $subjectId = $subjectsCodeMap[$subKey]->id;
                    } else {
                        // Auto-create subject
                        $newSub = Subject::create([
                            'code' => strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $subjectName), 0, 6)),
                            'name' => $subjectName,
                            'status' => 'active',
                        ]);
                        $subjectsMap[$subKey] = $newSub;
                        $subjectId = $newSub->id;
                    }
                } elseif ($defaultSubjectId) {
                    $subjectId = $defaultSubjectId;
                } else {
                    $subjectId = $subjectsMap->first()?->id ?? Subject::first()?->id;
                }

                if (! $subjectId) {
                    $skipped++;
                    continue;
                }

                // Determine question type (1 = PG, 2 = Essai)
                $rawType = isset($map['type']) ? strtolower(trim((string) ($row[$map['type']] ?? ''))) : 'single_choice';
                if ($rawType === '2' || str_contains($rawType, 'essay') || str_contains($rawType, 'essai') || str_contains($rawType, 'uraian')) {
                    $questionType = 'essay';
                } elseif (str_contains($rawType, 'multiple') || str_contains($rawType, 'majemuk') || str_contains($rawType, 'kompleks')) {
                    $questionType = 'multiple_choice';
                } else {
                    $questionType = 'single_choice';
                }

                // Score weight
                $weight = isset($map['weight']) ? (float) ($row[$map['weight']] ?? 20) : 20;
                if ($weight <= 0) {
                    $weight = 20;
                }

                // Difficulty
                $rawDiff = isset($map['difficulty']) ? strtolower(trim((string) ($row[$map['difficulty']] ?? ''))) : 'medium';
                if (in_array($rawDiff, ['mudah', 'easy', 'rendah', '1'])) {
                    $difficulty = 'easy';
                } elseif (in_array($rawDiff, ['sulit', 'hard', 'tinggi', '3'])) {
                    $difficulty = 'hard';
                } else {
                    $difficulty = 'medium';
                }

                $correctKeys = isset($map['correct'])
                    ? array_map('trim', explode(',', strtoupper((string) ($row[$map['correct']] ?? ''))))
                    : ['A'];

                $question = Question::create([
                    'subject_id' => $subjectId,
                    'created_by' => $teacherId,
                    'question_type' => $questionType,
                    'content' => $content,
                    'score_weight' => $weight,
                    'difficulty' => $difficulty,
                    'status' => 'active',
                ]);

                // Options (A through E)
                if (in_array($questionType, ['single_choice', 'multiple_choice'], true)) {
                    $labels = ['A', 'B', 'C', 'D', 'E'];
                    foreach ($labels as $label) {
                        $key = 'opsi_' . strtolower($label);
                        $optContent = isset($map[$key]) ? trim((string) ($row[$map[$key]] ?? '')) : '';

                        if ($optContent !== '') {
                            $isCorrect = in_array($label, $correctKeys, true);

                            $question->options()->create([
                                'option_label' => $label,
                                'content' => $optContent,
                                'is_correct' => $isCorrect,
                            ]);
                        }
                    }
                }

                $imported++;
            }
        });

        $redirectRoute = ($userRole === 'admin') ? 'admin.questions.index' : 'guru.questions.index';
        $msg = "Import bank soal selesai: {$imported} butir soal baru berhasil ditambahkan";
        if ($skipped > 0) {
            $msg .= ", {$skipped} baris kosong/tidak valid dilewati";
        }
        $msg .= '.';

        return redirect()->route($redirectRoute)->with('success', $msg);
    }

    /**
     * Toggle active/inactive status of a question.
     */
    public function toggleStatus(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $question = Question::findOrFail($id);

        if ($userRole !== 'admin' && $question->created_by !== ($user->teacher?->id ?? 0)) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah status butir soal ini.');
        }

        $newStatus = ($question->status === 'inactive') ? 'active' : 'inactive';
        $question->update(['status' => $newStatus]);

        $redirectRoute = ($userRole === 'admin') ? 'admin.questions.index' : 'guru.questions.index';
        $msg = ($newStatus === 'active')
            ? 'Soal berhasil diaktifkan kembali dan akan tampil pada siswa.'
            : 'Soal berhasil dinonaktifkan (teks dicoret) dan tidak akan tampil pada siswa.';

        return redirect()->back()->with('success', $msg);
    }
}


