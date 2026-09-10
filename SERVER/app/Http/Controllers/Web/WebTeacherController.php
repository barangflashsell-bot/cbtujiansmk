<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class WebTeacherController extends Controller
{
    /**
     * Display a listing of teachers.
     */
    public function index(Request $request): View
    {
        $query = Teacher::with('user')->latest('id');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            })->orWhere('nip', 'like', "%{$search}%");
        }

        $teachers = $query->paginate(15)->withQueryString();

        return view('admin.teachers.index', [
            'teachers' => $teachers,
        ]);
    }

    /**
     * Store a newly created teacher.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:64', 'unique:users,username'],
            'name' => ['required', 'string', 'max:128'],
            'password' => ['required', 'string', 'min:6'],
            'nip' => ['nullable', 'string', 'max:32', 'unique:teachers,nip'],
            'phone' => ['nullable', 'string', 'max:20'],
        ], [
            'username.required' => 'Username akun guru wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'name.required' => 'Nama lengkap guru wajib diisi.',
            'password.required' => 'Password awal wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'nip.unique' => 'NIP sudah terdaftar.',
        ]);

        DB::transaction(function () use ($validated) {
            $teacherRole = Role::where('name', 'teacher')->firstOrFail();

            $user = User::create([
                'username' => trim($validated['username']),
                'name' => trim($validated['name']),
                'password' => Hash::make($validated['password']),
                'role_id' => $teacherRole->id,
                'is_active' => true,
            ]);

            Teacher::create([
                'user_id' => $user->id,
                'nip' => $validated['nip'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);
        });

        return redirect()->route('admin.teachers.index')->with('success', 'Data guru baru berhasil ditambahkan.');
    }

    /**
     * Update the specified teacher.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $teacher = Teacher::with('user')->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'password' => ['nullable', 'string', 'min:6'],
            'nip' => ['nullable', 'string', 'max:32', 'unique:teachers,nip,' . $id],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($teacher, $validated) {
            $userPayload = [
                'name' => trim($validated['name']),
                'is_active' => (bool) $validated['is_active'],
            ];

            if (! empty($validated['password'])) {
                $userPayload['password'] = Hash::make($validated['password']);
            }

            $teacher->user->update($userPayload);

            $teacher->update([
                'nip' => $validated['nip'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);
        });

        return redirect()->route('admin.teachers.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    /**
     * Remove the specified teacher.
     */
    public function destroy(int $id): RedirectResponse
    {
        $teacher = Teacher::withCount('questions')->findOrFail($id);

        if ($teacher->questions_count > 0) {
            return redirect()->route('admin.teachers.index')
                ->with('error', 'Guru tidak dapat dihapus karena masih memiliki relasi butir soal.');
        }

        DB::transaction(function () use ($teacher) {
            $userId = $teacher->user_id;
            $teacher->delete();
            User::destroy($userId);
        });

        return redirect()->route('admin.teachers.index')->with('success', 'Data guru berhasil dihapus.');
    }
}
