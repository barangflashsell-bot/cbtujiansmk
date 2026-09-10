<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Classes;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamParticipantController extends ApiController
{
    /**
     * Display a paginated listing of participants enrolled in the specified exam.
     */
    public function index(Request $request, string $examId): JsonResponse
    {
        $exam = Exam::find($examId);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $paginator = $exam->participants()
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'user_id', 'class_id', 'nis', 'nisn', 'gender');
                },
                'student.user' => function ($query) {
                    $query->select('id', 'name', 'username');
                },
                'student.schoolClass' => function ($query) {
                    $query->select('id', 'name', 'level');
                },
            ])
            ->latest('id')
            ->paginate($perPage);

        return $this->successResponse([
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Daftar peserta ujian berhasil diambil');
    }

    /**
     * Enroll a student or a class of students into the specified exam.
     */
    public function store(Request $request, string $examId): JsonResponse
    {
        $exam = Exam::find($examId);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'student_id' => ['required_without:class_id', 'nullable', 'integer', 'exists:students,id'],
            'class_id' => ['required_without:student_id', 'nullable', 'integer', 'exists:classes,id'],
            'allow_retest' => ['nullable', 'boolean'],
        ]);

        $allowRetest = (bool) ($validated['allow_retest'] ?? false);

        // Case 1: Individual Student Enrollment
        if (! empty($validated['student_id'])) {
            $studentId = (int) $validated['student_id'];

            // Duplicate enrollment check
            if ($exam->participants()->where('student_id', $studentId)->exists()) {
                return $this->errorResponse('Siswa ini sudah terdaftar sebagai peserta dalam ujian', [
                    'student_id' => ['Siswa ini sudah terdaftar sebagai peserta dalam ujian.'],
                ], 422);
            }

            $participant = DB::transaction(function () use ($exam, $studentId, $allowRetest) {
                return ExamParticipant::create([
                    'exam_id' => $exam->id,
                    'student_id' => $studentId,
                    'allow_retest' => $allowRetest,
                ]);
            });

            $participant->load([
                'student.user:id,name,username',
                'student.schoolClass:id,name,level',
            ]);

            return $this->successResponse($participant, 'Peserta berhasil didaftarkan ke ujian', 201);
        }

        // Case 2: Batch Class Enrollment
        if (! empty($validated['class_id'])) {
            $class = Classes::find($validated['class_id']);
            $students = $class->students;

            if ($students->isEmpty()) {
                return $this->errorResponse('Kelas tidak memiliki siswa untuk didaftarkan', [
                    'class_id' => ['Tidak ada siswa terdaftar dalam kelas yang dipilih.'],
                ], 422);
            }

            $enrolledCount = DB::transaction(function () use ($exam, $students, $allowRetest) {
                $count = 0;
                foreach ($students as $student) {
                    if (! $exam->participants()->where('student_id', $student->id)->exists()) {
                        ExamParticipant::create([
                            'exam_id' => $exam->id,
                            'student_id' => $student->id,
                            'allow_retest' => $allowRetest,
                        ]);
                        $count++;
                    }
                }

                return $count;
            });

            return $this->successResponse([
                'enrolled_count' => $enrolledCount,
                'class_name' => $class->name,
            ], "Sebanyak {$enrolledCount} siswa dari kelas {$class->name} berhasil didaftarkan", 201);
        }

        return $this->errorResponse('Data siswa atau kelas wajib disediakan', null, 422);
    }

    /**
     * Display the specified enrollment detail.
     */
    public function show(string $examId, string $participantId): JsonResponse
    {
        $exam = Exam::find($examId);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        $participant = $exam->participants()
            ->with([
                'student.user:id,name,username',
                'student.schoolClass:id,name,level',
            ])
            ->find($participantId);

        if (! $participant) {
            return $this->errorResponse('Data peserta ujian tidak ditemukan', null, 404);
        }

        return $this->successResponse($participant, 'Detail peserta ujian berhasil diambil');
    }

    /**
     * Update the specified enrollment (e.g. allow_retest).
     */
    public function update(Request $request, string $examId, string $participantId): JsonResponse
    {
        $exam = Exam::find($examId);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        $participant = $exam->participants()->find($participantId);

        if (! $participant) {
            return $this->errorResponse('Data peserta ujian tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'allow_retest' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($participant, $validated) {
            $participant->update([
                'allow_retest' => $validated['allow_retest'],
            ]);
        });

        $participant->load([
            'student.user:id,name,username',
            'student.schoolClass:id,name,level',
        ]);

        return $this->successResponse($participant, 'Status peserta ujian berhasil diperbarui');
    }

    /**
     * Remove / cancel enrollment from the specified exam.
     */
    public function destroy(string $examId, string $participantId): JsonResponse
    {
        $exam = Exam::find($examId);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        $participant = $exam->participants()->find($participantId);

        if (! $participant) {
            return $this->errorResponse('Data peserta ujian tidak ditemukan', null, 404);
        }

        // Integrity check: prevent deletion if student has already started/attempted the exam
        $hasAttempts = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $participant->student_id)
            ->exists();

        if ($hasAttempts) {
            return $this->errorResponse('Tidak dapat membatalkan pendaftaran karena siswa sudah memiliki sesi/riwayat pengerjaan ujian', null, 400);
        }

        DB::transaction(function () use ($participant) {
            $participant->delete();
        });

        return $this->successResponse(null, 'Pendaftaran peserta ujian berhasil dibatalkan');
    }
}
