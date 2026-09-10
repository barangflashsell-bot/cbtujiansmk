<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebSubjectController extends Controller
{
    /**
     * Display a listing of subjects.
     */
    public function index(Request $request): View
    {
        $query = Subject::withCount(['questions', 'exams'])->latest('id');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }

        $subjects = $query->paginate(15)->withQueryString();

        return view('admin.subjects.index', [
            'subjects' => $subjects,
        ]);
    }

    /**
     * Store a newly created subject.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:128'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ], [
            'code.required' => 'Kode mata pelajaran wajib diisi.',
            'code.unique' => 'Kode mata pelajaran sudah digunakan.',
            'name.required' => 'Nama mata pelajaran wajib diisi.',
        ]);

        Subject::create([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'status' => $validated['status'] ?? 'active',
        ]);

        return redirect()->route('admin.subjects.index')->with('success', 'Mata pelajaran baru berhasil ditambahkan.');
    }

    /**
     * Update the specified subject.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:subjects,code,' . $id],
            'name' => ['required', 'string', 'max:128'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $subject->update([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'status' => $validated['status'] ?? 'active',
        ]);

        return redirect()->route('admin.subjects.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    /**
     * Remove the specified subject.
     */
    public function destroy(int $id): RedirectResponse
    {
        $subject = Subject::withCount(['questions', 'exams'])->findOrFail($id);

        if ($subject->questions_count > 0 || $subject->exams_count > 0) {
            return redirect()->route('admin.subjects.index')
                ->with('error', 'Mata pelajaran tidak dapat dihapus karena masih terkait dengan soal atau ujian.');
        }

        $subject->delete();

        return redirect()->route('admin.subjects.index')->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
