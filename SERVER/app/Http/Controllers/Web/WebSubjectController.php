<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Traits\HandlesExcelImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebSubjectController extends Controller
{
    use HandlesExcelImport;
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

        return redirect()->route('admin.questions.index')->with('success', 'Mata pelajaran baru berhasil ditambahkan.');
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

        return redirect()->route('admin.questions.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    /**
     * Remove the specified subject.
     */
    public function destroy(int $id): RedirectResponse
    {
        $subject = Subject::withCount(['questions', 'exams'])->findOrFail($id);

        if ($subject->questions_count > 0 || $subject->exams_count > 0) {
            return redirect()->route('admin.questions.index')
                ->with('error', 'Mata pelajaran tidak dapat dihapus karena masih terkait dengan soal atau ujian.');
        }

        $subject->delete();

        return redirect()->route('admin.questions.index')->with('success', 'Mata pelajaran berhasil dihapus.');
    }

    /**
     * Download Excel or CSV template for subject import.
     */
    public function downloadTemplate(Request $request)
    {
        $headers = ['No', 'Kode Mapel', 'Nama Mata Pelajaran', 'Status'];
        $sampleRows = [
            ['1', 'MTK', 'Matematika Wajib', 'active'],
            ['2', 'BIND', 'Bahasa Indonesia', 'active'],
            ['3', 'BING', 'Bahasa Inggris', 'active'],
            ['4', 'PROG', 'Pemrograman Dasar', 'active'],
        ];
        $colWidths = [40, 120, 240, 120];

        if ($request->query('format') === 'csv') {
            return $this->streamCsvTemplate('template_mata_pelajaran.csv', $headers, $sampleRows);
        }

        return $this->streamExcelTemplate('template_mata_pelajaran.xls', $headers, $sampleRows, $colWidths);
    }

    /**
     * Import subjects from Excel or CSV file.
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
            return redirect()->route('admin.subjects.index')->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }

        if (empty($rawRows)) {
            return redirect()->route('admin.subjects.index')->with('error', 'File yang diunggah kosong atau tidak memiliki data.');
        }

        $headerRow = array_shift($rawRows);
        $map = [];
        foreach ($headerRow as $idx => $header) {
            $clean = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]/', '', $header)));
            if (in_array($clean, ['kodemapel', 'kode', 'code', 'subjectcode'])) {
                $map['code'] = $idx;
            } elseif (in_array($clean, ['namamatapelajaran', 'namamapel', 'nama', 'matapelajaran', 'mapel', 'subjectname', 'name'])) {
                $map['name'] = $idx;
            } elseif (in_array($clean, ['status', 'aktif', 'kondisi'])) {
                $map['status'] = $idx;
            }
        }

        if (! isset($map['name'])) {
            return redirect()->route('admin.subjects.index')->with('error', 'Format kolom file tidak sesuai. Pastikan terdapat kolom "Nama Mata Pelajaran".');
        }

        $updateExisting = (bool) $request->input('update_existing');

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rawRows as $row) {
            $name = isset($map['name']) ? trim($row[$map['name']] ?? '') : '';
            if ($name === '') {
                continue;
            }

            $code = isset($map['code']) ? strtoupper(trim((string) ($row[$map['code']] ?? ''))) : '';
            if ($code === '') {
                // Auto-generate code from name (e.g. "Bahasa Indonesia" -> "BIND")
                $words = explode(' ', preg_replace('/[^a-zA-Z0-9\s]/', '', $name));
                if (count($words) >= 2) {
                    $code = strtoupper(substr($words[0], 0, 2) . substr($words[1], 0, 2));
                } else {
                    $code = strtoupper(substr($name, 0, 4));
                }
            }

            $rawStatus = isset($map['status']) ? strtolower(trim((string) ($row[$map['status']] ?? ''))) : 'active';
            $status = (in_array($rawStatus, ['inactive', 'nonaktif', 'tidak', '0'])) ? 'inactive' : 'active';

            $existing = Subject::where('code', $code)->first();

            if ($existing) {
                if ($updateExisting) {
                    $existing->update([
                        'name' => $name,
                        'status' => $status,
                    ]);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                Subject::create([
                    'code' => $code,
                    'name' => $name,
                    'status' => $status,
                ]);
                $imported++;
            }
        }

        $msg = "Import mata pelajaran selesai: {$imported} mapel baru berhasil ditambahkan";
        if ($updated > 0) {
            $msg .= ", {$updated} mapel diperbarui";
        }
        if ($skipped > 0) {
            $msg .= ", {$skipped} data dilewati (kode mapel sudah terdaftar)";
        }
        $msg .= '.';

        return redirect()->route('admin.subjects.index')->with('success', $msg);
    }
}

