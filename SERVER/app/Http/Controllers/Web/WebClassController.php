<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Traits\HandlesExcelImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebClassController extends Controller
{
    use HandlesExcelImport;
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

    /**
     * Download Excel or CSV template for class import.
     */
    public function downloadTemplate(Request $request)
    {
        $headers = ['No', 'Nama Kelas', 'Tingkat', 'Tahun Ajaran', 'Status'];
        $sampleRows = [
            ['1', '10 RPL 1', '10', date('Y') . '/' . (date('Y') + 1), 'active'],
            ['2', '10 RPL 2', '10', date('Y') . '/' . (date('Y') + 1), 'active'],
            ['3', '11 TKJ 1', '11', date('Y') . '/' . (date('Y') + 1), 'active'],
        ];
        $colWidths = [40, 140, 100, 140, 100];

        if ($request->query('format') === 'csv') {
            return $this->streamCsvTemplate('template_data_kelas.csv', $headers, $sampleRows);
        }

        return $this->streamExcelTemplate('template_data_kelas.xls', $headers, $sampleRows, $colWidths);
    }

    /**
     * Import classes from Excel or CSV file.
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
            return redirect()->route('admin.classes.index')->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }

        if (empty($rawRows)) {
            return redirect()->route('admin.classes.index')->with('error', 'File yang diunggah kosong atau tidak memiliki data.');
        }

        $headerRow = array_shift($rawRows);
        $map = [];
        foreach ($headerRow as $idx => $header) {
            $clean = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]/', '', $header)));
            if (in_array($clean, ['namakelas', 'nama', 'kelas', 'classname', 'name', 'rombel'])) {
                $map['nama'] = $idx;
            } elseif (in_array($clean, ['tingkat', 'level', 'jenjang', 'grade'])) {
                $map['level'] = $idx;
            } elseif (in_array($clean, ['tahunajaran', 'tahun', 'academicyear', 'periode'])) {
                $map['academic_year'] = $idx;
            } elseif (in_array($clean, ['status', 'aktif', 'kondisi'])) {
                $map['status'] = $idx;
            }
        }

        if (! isset($map['nama'])) {
            return redirect()->route('admin.classes.index')->with('error', 'Format kolom file tidak sesuai. Pastikan terdapat kolom "Nama Kelas".');
        }

        $updateExisting = (bool) $request->input('update_existing');
        $currentYear = date('Y') . '/' . (date('Y') + 1);

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rawRows as $row) {
            $name = isset($map['nama']) ? trim($row[$map['nama']] ?? '') : '';
            if ($name === '') {
                continue;
            }

            $level = isset($map['level']) ? trim((string) ($row[$map['level']] ?? '')) : '';
            if ($level === '') {
                // Infer level from class name
                $level = preg_match('/\b(10|11|12|7|8|9|X|XI|XII)\b/i', $name, $m) ? strtoupper($m[1]) : '10';
            }

            $academicYear = isset($map['academic_year']) ? trim((string) ($row[$map['academic_year']] ?? '')) : '';
            if ($academicYear === '') {
                $academicYear = $currentYear;
            }

            $rawStatus = isset($map['status']) ? strtolower(trim((string) ($row[$map['status']] ?? ''))) : 'active';
            $status = (in_array($rawStatus, ['inactive', 'nonaktif', 'tidak', '0'])) ? 'inactive' : 'active';

            $existing = Classes::where('name', $name)->first();

            if ($existing) {
                if ($updateExisting) {
                    $existing->update([
                        'level' => $level,
                        'academic_year' => $academicYear,
                        'status' => $status,
                    ]);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                Classes::create([
                    'name' => $name,
                    'level' => $level,
                    'academic_year' => $academicYear,
                    'status' => $status,
                ]);
                $imported++;
            }
        }

        $msg = "Import data kelas selesai: {$imported} kelas baru berhasil ditambahkan";
        if ($updated > 0) {
            $msg .= ", {$updated} data kelas diperbarui";
        }
        if ($skipped > 0) {
            $msg .= ", {$skipped} data dilewati (nama kelas sudah ada)";
        }
        $msg .= '.';

        return redirect()->route('admin.classes.index')->with('success', $msg);
    }
}

