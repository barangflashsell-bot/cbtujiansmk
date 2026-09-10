<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebClassController extends Controller
{
    /**
     * Display a listing of classes.
     */
    public function index(Request $request): View
    {
        $query = Classes::withCount('students')->latest('id');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('level', 'like', "%{$search}%");
        }

        $classes = $query->paginate(15)->withQueryString();

        return view('admin.classes.index', [
            'classes' => $classes,
        ]);
    }

    /**
     * Store a newly created class.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'level' => ['required', 'string', 'max:16'],
            'academic_year' => ['required', 'string', 'max:16'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ], [
            'name.required' => 'Nama kelas wajib diisi.',
            'level.required' => 'Tingkat/jenjang kelas wajib diisi.',
            'academic_year.required' => 'Tahun ajaran wajib diisi.',
        ]);

        Classes::create([
            'name' => $validated['name'],
            'level' => $validated['level'],
            'academic_year' => $validated['academic_year'],
            'status' => $validated['status'] ?? 'active',
        ]);

        return redirect()->route('admin.classes.index')->with('success', 'Kelas baru berhasil ditambahkan.');
    }

    /**
     * Update the specified class.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $class = Classes::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'level' => ['required', 'string', 'max:16'],
            'academic_year' => ['required', 'string', 'max:16'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $class->update([
            'name' => $validated['name'],
            'level' => $validated['level'],
            'academic_year' => $validated['academic_year'],
            'status' => $validated['status'] ?? 'active',
        ]);

        return redirect()->route('admin.classes.index')->with('success', 'Data kelas berhasil diperbarui.');
    }

    /**
     * Remove the specified class.
     */
    public function destroy(int $id): RedirectResponse
    {
        $class = Classes::withCount('students')->findOrFail($id);

        if ($class->students_count > 0) {
            return redirect()->route('admin.classes.index')
                ->with('error', 'Kelas tidak dapat dihapus karena masih memiliki relasi siswa.');
        }

        $class->delete();

        return redirect()->route('admin.classes.index')->with('success', 'Kelas berhasil dihapus.');
    }
}
