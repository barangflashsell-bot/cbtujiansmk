<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use App\Traits\HandlesExcelImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class WebTeacherController extends Controller
{
    use HandlesExcelImport;
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

    /**
     * Download Excel CSV template for teacher import.
     */
    public function downloadTemplate()
    {
        $headers = ['No', 'Nama Lengkap', 'Username', 'Password', 'NIP', 'No HP'];
        $sampleRows = [
            ['1', 'Drs. H. Bambang Sutrisno M.Kom', 'guru_bambang', '123456', '198001012005011001', '081234567890'],
            ['2', 'Sri Wahyuni S.Pd', 'guru_sri', '123456', '198502022008022002', '081234567891'],
            ['3', 'Ahmad Farhan S.T', 'guru_farhan', '123456', '199003032015031003', '081234567892'],
        ];

        return $this->streamCsvTemplate('template_data_guru.csv', $headers, $sampleRows);
    }

    /**
     * Import teachers from Excel or CSV file.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'update_existing' => ['nullable'],
        ], [
            'file.required' => 'File Excel atau CSV wajib diunggah.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $rawRows = $this->parseUploadedSpreadsheet($file->getRealPath(), $extension);
        } catch (\Throwable $e) {
            return redirect()->route('admin.teachers.index')->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }

        if (empty($rawRows)) {
            return redirect()->route('admin.teachers.index')->with('error', 'File yang diunggah kosong atau tidak memiliki data.');
        }

        $headerRow = array_shift($rawRows);
        $map = [];
        foreach ($headerRow as $idx => $header) {
            $clean = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]/', '', $header)));
            if (in_array($clean, ['nama', 'namalengkap', 'namaguru', 'name', 'fullname', 'teachername'])) {
                $map['nama'] = $idx;
            } elseif (in_array($clean, ['username', 'user', 'userid', 'login'])) {
                $map['username'] = $idx;
            } elseif (in_array($clean, ['password', 'pass', 'sandi', 'katasandi', 'pin'])) {
                $map['password'] = $idx;
            } elseif (in_array($clean, ['nip', 'nomorindukpegawai', 'noindukpegawai'])) {
                $map['nip'] = $idx;
            } elseif (in_array($clean, ['nohp', 'hp', 'phone', 'telepon', 'notelp', 'telp', 'wa', 'whatsapp'])) {
                $map['phone'] = $idx;
            }
        }

        if (! isset($map['nama'])) {
            return redirect()->route('admin.teachers.index')->with('error', 'Format kolom file tidak sesuai. Pastikan terdapat kolom "Nama Lengkap".');
        }

        $teacherRole = Role::where('name', 'teacher')->firstOrFail();
        $updateExisting = (bool) $request->input('update_existing');

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $rawRows,
            $map,
            $teacherRole,
            $updateExisting,
            &$imported,
            &$updated,
            &$skipped
        ) {
            foreach ($rawRows as $row) {
                $nama = isset($map['nama']) ? trim($row[$map['nama']] ?? '') : '';
                if ($nama === '') {
                    continue;
                }

                $nip = isset($map['nip']) ? trim((string) ($row[$map['nip']] ?? '')) : null;
                $username = isset($map['username']) ? trim((string) ($row[$map['username']] ?? '')) : '';

                if ($username === '') {
                    $username = $nip ?: 'guru_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', substr($nama, 0, 10))) . rand(10, 99);
                }

                $password = isset($map['password']) ? trim((string) ($row[$map['password']] ?? '')) : '';
                if ($password === '') {
                    $password = $nip ?: '123456';
                }

                $phone = isset($map['phone']) ? trim((string) ($row[$map['phone']] ?? '')) : null;

                // Check existing teacher by username or NIP
                $existingTeacher = Teacher::when($nip, fn ($q) => $q->where('nip', $nip))
                    ->orWhereHas('user', fn ($uq) => $uq->where('username', $username))
                    ->first();

                if ($existingTeacher) {
                    if ($updateExisting) {
                        $userUpdate = ['name' => $nama, 'is_active' => true];
                        if ($password !== '') {
                            $userUpdate['password'] = Hash::make($password);
                        }
                        $existingTeacher->user->update($userUpdate);

                        $existingTeacher->update([
                            'nip' => $nip ?: $existingTeacher->nip,
                            'phone' => $phone ?: $existingTeacher->phone,
                        ]);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $user = User::create([
                        'username' => $username,
                        'name' => $nama,
                        'password' => Hash::make($password),
                        'role_id' => $teacherRole->id,
                        'is_active' => true,
                    ]);

                    Teacher::create([
                        'user_id' => $user->id,
                        'nip' => $nip ?: null,
                        'phone' => $phone ?: null,
                    ]);
                    $imported++;
                }
            }
        });

        $msg = "Import data guru selesai: {$imported} guru baru berhasil ditambahkan";
        if ($updated > 0) {
            $msg .= ", {$updated} data guru diperbarui";
        }
        if ($skipped > 0) {
            $msg .= ", {$skipped} data dilewati";
        }
        $msg .= '.';

        return redirect()->route('admin.teachers.index')->with('success', $msg);
    }
}

