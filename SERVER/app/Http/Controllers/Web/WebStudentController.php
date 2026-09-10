<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class WebStudentController extends Controller
{
    /**
     * Display student listing for Admin (with full management actions).
     */
    public function adminIndex(Request $request): View
    {
        $query = Student::with(['user', 'schoolClass'])->latest('id');

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        $students = $query->paginate(15)->withQueryString();
        $classes = Classes::orderBy('name')->get();

        return view('admin.students.index', [
            'students' => $students,
            'classes' => $classes,
        ]);
    }

    /**
     * Display student listing for Guru (Read-Only).
     */
    public function guruIndex(Request $request): View
    {
        $query = Student::with(['user', 'schoolClass'])->latest('id');

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nis', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $students = $query->paginate(15)->withQueryString();
        $classes = Classes::orderBy('name')->get();

        return view('guru.students.index', [
            'students' => $students,
            'classes' => $classes,
        ]);
    }

    /**
     * Store a newly created student.
     */
    public function adminStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:64', 'unique:users,username'],
            'name' => ['required', 'string', 'max:128'],
            'password' => ['required', 'string', 'min:6'],
            'class_id' => ['required', 'exists:classes,id'],
            'nis' => ['required', 'string', 'max:32', 'unique:students,nis'],
            'nisn' => ['nullable', 'string', 'max:32'],
            'gender' => ['required', 'in:L,P'],
        ], [
            'username.required' => 'Nomor peserta / username wajib diisi.',
            'username.unique' => 'Username sudah terdaftar.',
            'name.required' => 'Nama lengkap peserta wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'class_id.required' => 'Kelas wajib dipilih.',
            'nis.required' => 'NIS wajib diisi.',
            'nis.unique' => 'NIS sudah terdaftar.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
        ]);

        DB::transaction(function () use ($validated) {
            $studentRole = Role::where('name', 'student')->firstOrFail();

            $user = User::create([
                'username' => trim($validated['username']),
                'name' => trim($validated['name']),
                'password' => Hash::make($validated['password']),
                'role_id' => $studentRole->id,
                'is_active' => true,
            ]);

            Student::create([
                'user_id' => $user->id,
                'class_id' => $validated['class_id'],
                'nis' => trim($validated['nis']),
                'nisn' => isset($validated['nisn']) ? trim($validated['nisn']) : null,
                'gender' => $validated['gender'],
            ]);
        });

        return redirect()->route('admin.students.index')->with('success', 'Peserta ujian baru berhasil ditambahkan.');
    }

    /**
     * Update the specified student.
     */
    public function adminUpdate(Request $request, int $id): RedirectResponse
    {
        $student = Student::with('user')->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'password' => ['nullable', 'string', 'min:6'],
            'class_id' => ['required', 'exists:classes,id'],
            'nis' => ['required', 'string', 'max:32', 'unique:students,nis,' . $id],
            'nisn' => ['nullable', 'string', 'max:32'],
            'gender' => ['required', 'in:L,P'],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($student, $validated) {
            $userPayload = [
                'name' => trim($validated['name']),
                'is_active' => (bool) $validated['is_active'],
            ];

            if (! empty($validated['password'])) {
                $userPayload['password'] = Hash::make($validated['password']);
            }

            $student->user->update($userPayload);

            $student->update([
                'class_id' => $validated['class_id'],
                'nis' => trim($validated['nis']),
                'nisn' => isset($validated['nisn']) ? trim($validated['nisn']) : null,
                'gender' => $validated['gender'],
            ]);
        });

        return redirect()->route('admin.students.index')->with('success', 'Data peserta berhasil diperbarui.');
    }

    /**
     * Remove the specified student.
     */
    public function adminDestroy(int $id): RedirectResponse
    {
        $student = Student::withCount('examAttempts')->findOrFail($id);

        if ($student->exam_attempts_count > 0) {
            return redirect()->route('admin.students.index')
                ->with('error', 'Peserta tidak dapat dihapus karena sudah memiliki rekaman attempt ujian.');
        }

        DB::transaction(function () use ($student) {
            $userId = $student->user_id;
            $student->delete();
            User::destroy($userId);
        });

        return redirect()->route('admin.students.index')->with('success', 'Data peserta berhasil dihapus.');
    }
}
