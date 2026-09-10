<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\ActivityLog;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimerController extends ApiController
{
    /**
     * Get authoritative server-side timer state for a specific attempt.
     */
    public function getAttemptTimer(Request $request, string $attemptId): JsonResponse
    {
        $attempt = ExamAttempt::with('exam')->find($attemptId);

        if (! $attempt) {
            return $this->errorResponse('Sesi ujian tidak ditemukan', null, 404);
        }

        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        // Ownership authorization: student can only view their own attempt timer
        if (in_array($userRole, ['student', 'peserta'], true)) {
            if (! $user->student || $attempt->student_id !== $user->student->id) {
                return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk mengakses timer sesi ujian ini', null, 403);
            }
        }

        $now = now();
        $timerData = $this->calculateTimer($attempt, $now);

        return $this->successResponse($timerData, 'Status timer ujian berhasil diambil');
    }

    /**
     * Get authoritative server-side timer state for an exam (handles not_started, recovery, in_progress).
     */
    public function getExamTimer(Request $request, string $examId): JsonResponse
    {
        $exam = Exam::find($examId);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        if (in_array($userRole, ['student', 'peserta'], true)) {
            $student = $user->student;
            if (! $student) {
                return $this->errorResponse('Hanya siswa/peserta yang dapat mengakses timer ujian ini', null, 403);
            }

            // Check enrollment
            $isEnrolled = ExamParticipant::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->exists();

            if (! $isEnrolled) {
                return $this->errorResponse('Siswa tidak terdaftar pada paket ujian ini', null, 403);
            }

            $attempt = ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->first();

            $now = now();

            if (! $attempt || $attempt->status === 'not_started') {
                return $this->successResponse([
                    'attempt_id' => $attempt?->id,
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                    'status' => 'not_started',
                    'server_time' => $now->toIso8601String(),
                    'started_at' => null,
                    'ends_at' => null,
                    'duration_seconds' => $exam->duration_minutes * 60,
                    'remaining_seconds' => $exam->duration_minutes * 60,
                    'elapsed_seconds' => 0,
                    'is_expired' => false,
                    'can_continue' => true,
                ], 'Status timer ujian berhasil diambil');
            }

            $timerData = $this->calculateTimer($attempt, $now);

            return $this->successResponse($timerData, 'Status timer ujian berhasil diambil');
        }

        // For Admin / Teacher, if student_id is provided in query params
        if ($request->filled('student_id')) {
            $studentId = $request->query('student_id');
            $attempt = ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', $studentId)
                ->first();

            if (! $attempt) {
                return $this->successResponse([
                    'attempt_id' => null,
                    'exam_id' => $exam->id,
                    'student_id' => (int) $studentId,
                    'status' => 'not_started',
                    'server_time' => now()->toIso8601String(),
                    'started_at' => null,
                    'ends_at' => null,
                    'duration_seconds' => $exam->duration_minutes * 60,
                    'remaining_seconds' => $exam->duration_minutes * 60,
                    'elapsed_seconds' => 0,
                    'is_expired' => false,
                    'can_continue' => true,
                ], 'Status timer ujian berhasil diambil');
            }

            $timerData = $this->calculateTimer($attempt, now());

            return $this->successResponse($timerData, 'Status timer ujian berhasil diambil');
        }

        // General exam schedule timer overview for admin/teacher
        return $this->successResponse([
            'exam_id' => $exam->id,
            'server_time' => now()->toIso8601String(),
            'start_window' => $exam->start_window,
            'end_window' => $exam->end_window,
            'duration_minutes' => $exam->duration_minutes,
            'duration_seconds' => $exam->duration_minutes * 60,
            'status' => $exam->status,
        ], 'Informasi jadwal waktu ujian berhasil diambil');
    }

    /**
     * Admin/Teacher manual time extension for an attempt.
     */
    public function extendTime(Request $request, string $attemptId): JsonResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        if (! in_array($userRole, ['admin', 'teacher', 'guru'], true)) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk melakukan perpanjangan waktu', null, 403);
        }

        $validated = $request->validate([
            'added_minutes' => 'required|integer|min:1|max:180',
            'reason' => 'nullable|string|max:255',
        ]);

        $attempt = ExamAttempt::find($attemptId);
        if (! $attempt) {
            return $this->errorResponse('Sesi ujian tidak ditemukan', null, 404);
        }

        if (in_array($attempt->status, ['submitted'], true)) {
            return $this->errorResponse('Tidak dapat memperpanjang waktu untuk ujian yang sudah disubmit', null, 400);
        }

        $addedMinutes = (int) $validated['added_minutes'];
        $oldEndsAt = $attempt->ends_at;

        // Base extension on existing ends_at or now if ends_at passed
        $baseTime = $attempt->ends_at && now()->lt($attempt->ends_at) ? $attempt->ends_at : now();
        $newEndsAt = Carbon::parse($baseTime)->addMinutes($addedMinutes);

        $attempt->update([
            'ends_at' => $newEndsAt,
            'status' => 'in_progress',
            'last_activity_at' => now(),
        ]);

        // Audit log in activity_logs
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'EXTEND_TIME',
            'module' => 'TIMER',
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'details' => json_encode([
                'attempt_id' => $attempt->id,
                'student_id' => $attempt->student_id,
                'exam_id' => $attempt->exam_id,
                'added_minutes' => $addedMinutes,
                'old_ends_at' => $oldEndsAt?->toIso8601String(),
                'new_ends_at' => $newEndsAt->toIso8601String(),
                'reason' => $validated['reason'] ?? 'Manual technical extension',
            ]),
            'created_at' => now(),
        ]);

        $timerData = $this->calculateTimer($attempt, now());

        return $this->successResponse($timerData, 'Waktu ujian berhasil diperpanjang');
    }

    /**
     * Authoritative calculation of timer state based strictly on server timestamps.
     */
    protected function calculateTimer(ExamAttempt $attempt, Carbon $now): array
    {
        $status = $attempt->status;
        $endsAt = $attempt->ends_at ? Carbon::parse($attempt->ends_at) : null;
        $startedAt = $attempt->started_at ? Carbon::parse($attempt->started_at) : null;
        $durationSeconds = (int) (($attempt->exam->duration_minutes ?? 0) * 60);

        if ($status === 'in_progress') {
            if ($endsAt && $now->gte($endsAt)) {
                // Time expired -> transition to timeout
                $attempt->update(['status' => 'timeout']);
                $status = 'timeout';
            }
        }

        $isExpired = in_array($status, ['submitted', 'timeout', 'blocked'], true);
        $canContinue = ($status === 'in_progress' && ! $isExpired);

        $remainingSeconds = 0;
        $elapsedSeconds = 0;

        if ($status === 'in_progress' && $endsAt && $startedAt) {
            $remainingSeconds = max(0, (int) $endsAt->diffInSeconds($now, false) * -1);
            $elapsedSeconds = max(0, (int) $now->diffInSeconds($startedAt));
        } elseif ($status === 'not_started') {
            $remainingSeconds = $durationSeconds;
            $elapsedSeconds = 0;
            $canContinue = true;
            $isExpired = false;
        }

        return [
            'attempt_id' => $attempt->id,
            'exam_id' => $attempt->exam_id,
            'student_id' => $attempt->student_id,
            'status' => $status,
            'server_time' => $now->toIso8601String(),
            'started_at' => $startedAt?->toIso8601String(),
            'ends_at' => $endsAt?->toIso8601String(),
            'duration_seconds' => $durationSeconds,
            'remaining_seconds' => $remainingSeconds,
            'elapsed_seconds' => $elapsedSeconds,
            'is_expired' => $isExpired,
            'can_continue' => $canContinue,
        ];
    }
}
