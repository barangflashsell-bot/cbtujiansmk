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
use App\Traits\HandlesExcelImport;
use Illuminate\View\View;

class WebStudentController extends Controller
{
    use HandlesExcelImport;
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
        $classes = Classes::withCount('students')->orderBy('name')->get();
        $totalStudentsCount = Student::count();

        return view('admin.students.index', [
            'students' => $students,
            'classes' => $classes,
            'totalStudentsCount' => $totalStudentsCount,
            'selectedClassId' => $request->query('class_id'),
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

    /**
     * Download Excel or CSV template for student import.
     */
    public function downloadTemplate(Request $request)
    {
        $headers = ['No', 'Nama Lengkap', 'Username', 'Password', 'Kelas', 'NIS', 'NISN', 'Jenis Kelamin'];
        $sampleRows = [
            ['1', 'Ahmad Dhani Prasetya', 'peserta01', '123456', '9A', 'NIS001', '0081234567', 'L'],
            ['2', 'Siti Aminah Zahra', 'peserta02', '123456', '9A', 'NIS002', '0081234568', 'P'],
            ['3', 'Budi Santoso Nugroho', 'peserta03', '123456', '9A', 'NIS003', '0081234569', 'L'],
            ['4', 'Dewi Lestari', 'peserta04', '123456', '9B', 'NIS004', '0081234570', 'P'],
            ['5', 'Eko Prasetyo', 'peserta05', '123456', '9B', 'NIS005', '0081234571', 'L'],
        ];
        $colWidths = [40, 220, 140, 100, 90, 100, 120, 120];

        if ($request->query('format') === 'csv') {
            return $this->streamCsvTemplate('template_data_peserta.csv', $headers, $sampleRows);
        }

        return $this->streamExcelTemplate('template_data_peserta.xls', $headers, $sampleRows, $colWidths);
    }

    /**
     * Import student data from Excel or CSV file.
     */
    public function adminImport(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'default_class_id' => ['nullable', 'exists:classes,id'],
            'update_existing' => ['nullable'],
        ], [
            'file.required' => 'File Excel atau CSV wajib diunggah.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $filePath = $file->getRealPath();

        try {
            if ($extension === 'xlsx') {
                $rawRows = $this->parseXlsx($filePath);
            } else {
                $rawRows = $this->parseCsv($filePath);
            }
        } catch (\Throwable $e) {
            return redirect()->route('admin.students.index')->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }

        if (empty($rawRows)) {
            return redirect()->route('admin.students.index')->with('error', 'File yang diunggah kosong atau tidak memiliki data.');
        }

        // Identify header row
        $headerRow = array_shift($rawRows);
        $columnMap = $this->mapImportHeaders($headerRow);

        if (! isset($columnMap['nama']) || (! isset($columnMap['nis']) && ! isset($columnMap['username']))) {
            return redirect()->route('admin.students.index')->with('error', 'Format kolom file tidak dikenali. Pastikan terdapat kolom "Nama Lengkap" dan "NIS" atau "Username".');
        }

        $defaultClassId = $request->input('default_class_id');
        $updateExisting = (bool) $request->input('update_existing');

        $classesMap = Classes::all()->keyBy(fn ($c) => strtolower(trim($c->name)));
        $studentRole = Role::where('name', 'student')->firstOrFail();

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $rawRows,
            $columnMap,
            $defaultClassId,
            $updateExisting,
            &$classesMap,
            $studentRole,
            &$imported,
            &$updated,
            &$skipped
        ) {
            foreach ($rawRows as $row) {
                $nama = isset($columnMap['nama']) ? trim($row[$columnMap['nama']] ?? '') : '';
                if ($nama === '') {
                    continue; // Skip completely empty row
                }

                $nis = isset($columnMap['nis']) ? trim((string) ($row[$columnMap['nis']] ?? '')) : '';
                $username = isset($columnMap['username']) ? trim((string) ($row[$columnMap['username']] ?? '')) : '';

                if ($nis === '' && $username !== '') {
                    $nis = $username;
                }
                if ($username === '' && $nis !== '') {
                    $username = $nis;
                }

                if ($username === '' || $nis === '') {
                    $skipped++;
                    continue;
                }

                $password = isset($columnMap['password']) ? trim((string) ($row[$columnMap['password']] ?? '')) : '';
                if ($password === '') {
                    $password = $nis; // Default password is NIS
                }

                $className = isset($columnMap['kelas']) ? trim((string) ($row[$columnMap['kelas']] ?? '')) : '';
                $classId = null;

                if ($className !== '') {
                    $classKey = strtolower($className);
                    if (isset($classesMap[$classKey])) {
                        $classId = $classesMap[$classKey]->id;
                    } else {
                        // Auto-create class if not exists
                        $newClass = Classes::create([
                            'name' => $className,
                            'level' => preg_match('/\b(10|11|12|7|8|9|X|XI|XII)\b/i', $className, $m) ? strtoupper($m[1]) : '10',
                            'academic_year' => date('Y') . '/' . (date('Y') + 1),
                            'status' => 'active',
                        ]);
                        $classesMap[$classKey] = $newClass;
                        $classId = $newClass->id;
                    }
                } elseif ($defaultClassId) {
                    $classId = $defaultClassId;
                } else {
                    $classId = $classesMap->first()?->id;
                }

                $nisn = isset($columnMap['nisn']) ? trim((string) ($row[$columnMap['nisn']] ?? '')) : null;
                $rawGender = isset($columnMap['gender']) ? strtoupper(trim((string) ($row[$columnMap['gender']] ?? ''))) : 'L';
                $gender = (str_starts_with($rawGender, 'P') || str_contains($rawGender, 'PEREMPUAN')) ? 'P' : 'L';

                // Check existing student by NIS or username
                $existingStudent = Student::where('nis', $nis)
                    ->orWhereHas('user', fn ($uq) => $uq->where('username', $username))
                    ->first();

                if ($existingStudent) {
                    if ($updateExisting) {
                        $userUpdate = ['name' => $nama, 'is_active' => true];
                        if ($password !== '') {
                            $userUpdate['password'] = Hash::make($password);
                        }
                        $existingStudent->user->update($userUpdate);

                        $existingStudent->update([
                            'class_id' => $classId ?: $existingStudent->class_id,
                            'nisn' => $nisn ?: $existingStudent->nisn,
                            'gender' => $gender,
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
                        'role_id' => $studentRole->id,
                        'is_active' => true,
                    ]);

                    Student::create([
                        'user_id' => $user->id,
                        'class_id' => $classId,
                        'nis' => $nis,
                        'nisn' => $nisn ?: null,
                        'gender' => $gender,
                    ]);
                    $imported++;
                }
            }
        });

        $msg = "Import data peserta selesai: {$imported} siswa baru berhasil ditambahkan";
        if ($updated > 0) {
            $msg .= ", {$updated} data siswa diperbarui";
        }
        if ($skipped > 0) {
            $msg .= ", {$skipped} data dilewati";
        }
        $msg .= '.';

        return redirect()->route('admin.students.index')->with('success', $msg);
    }

    /**
     * Map header column names to standard keys.
     */
    private function mapImportHeaders(array $headers): array
    {
        $map = [];
        foreach ($headers as $idx => $header) {
            $clean = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]/', '', $header)));
            if (in_array($clean, ['nama', 'namasiswa', 'namalengkap', 'namapeserta', 'fullname', 'name', 'studentname'])) {
                $map['nama'] = $idx;
            } elseif (in_array($clean, ['username', 'nomorpeserta', 'nopeserta', 'user', 'userid', 'login'])) {
                $map['username'] = $idx;
            } elseif (in_array($clean, ['password', 'pass', 'sandi', 'katasandi', 'pin'])) {
                $map['password'] = $idx;
            } elseif (in_array($clean, ['kelas', 'class', 'rombel', 'tingkat'])) {
                $map['kelas'] = $idx;
            } elseif (in_array($clean, ['nis', 'nomorinduk', 'noinduk'])) {
                $map['nis'] = $idx;
            } elseif (in_array($clean, ['nisn', 'nomorinduksiswanasional'])) {
                $map['nisn'] = $idx;
            } elseif (in_array($clean, ['jeniskelamin', 'jk', 'gender', 'sex'])) {
                $map['gender'] = $idx;
            }
        }

        return $map;
    }

    /**
     * Parse CSV / TXT file content.
     */
    private function parseCsv(string $filePath): array
    {
        $content = file_get_contents($filePath);
        // Remove UTF-8 BOM if present
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        // Auto-detect delimiter
        $lines = explode("\n", $content);
        $firstLine = $lines[0] ?? '';
        $delimiter = ',';
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            $delimiter = "\t";
        }

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $rows = [];
        while (($data = fgetcsv($handle, 4096, $delimiter)) !== false) {
            if (! empty(array_filter($data, fn ($v) => trim((string) $v) !== ''))) {
                $rows[] = array_map(fn ($v) => trim((string) $v), $data);
            }
        }
        fclose($handle);

        return $rows;
    }

    /**
     * Parse native Excel (.xlsx) file using built-in ZipArchive and SimpleXML.
     */
    private function parseXlsx(string $filePath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception('Gagal membuka arsip file Excel (.xlsx).');
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $xml = simplexml_load_string($sharedXml);
            if ($xml !== false) {
                foreach ($xml->si as $val) {
                    if (isset($val->t)) {
                        $sharedStrings[] = (string) $val->t;
                    } elseif (isset($val->r)) {
                        $text = '';
                        foreach ($val->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Read first worksheet
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_starts_with($name, 'xl/worksheets/sheet') && str_ends_with($name, '.xml')) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }
        $zip->close();

        if ($sheetXml === false) {
            throw new \Exception('Worksheet Excel tidak ditemukan dalam file.');
        }

        $xml = simplexml_load_string($sheetXml);
        if ($xml === false || ! isset($xml->sheetData)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            $lastColIdx = 0;
            foreach ($row->c as $c) {
                $cellRef = (string) $c['r'];
                $type = (string) $c['t'];

                // Calculate column index from cellRef (A=0, B=1, etc.)
                preg_match('/^([A-Z]+)(\d+)$/', $cellRef, $matches);
                if (! empty($matches[1])) {
                    $colLetters = $matches[1];
                    $colIdx = 0;
                    for ($len = strlen($colLetters), $k = 0; $k < $len; $k++) {
                        $colIdx = $colIdx * 26 + (ord($colLetters[$k]) - ord('A') + 1);
                    }
                    $colIdx -= 1;
                } else {
                    $colIdx = $lastColIdx;
                }

                while (count($rowData) < $colIdx) {
                    $rowData[] = '';
                }

                $val = '';
                if (isset($c->v)) {
                    $rawVal = (string) $c->v;
                    if ($type === 's' && isset($sharedStrings[(int) $rawVal])) {
                        $val = $sharedStrings[(int) $rawVal];
                    } else {
                        $val = $rawVal;
                    }
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $val = (string) $c->is->t;
                }

                $rowData[] = trim($val);
                $lastColIdx = count($rowData);
            }

            if (! empty(array_filter($rowData, fn ($v) => $v !== ''))) {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }
}

