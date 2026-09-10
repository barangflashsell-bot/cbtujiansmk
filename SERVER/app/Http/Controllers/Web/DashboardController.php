<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Classes;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\Question;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Admin Dashboard with real metric cards and summary data.
     */
    public function adminDashboard(Request $request): View
    {
        $user = $request->user()->load('role');

        try {
            $metrics = [
                'total_students' => Student::count(),
                'total_teachers' => Teacher::count(),
                'total_classes' => Classes::count(),
                'total_subjects' => Subject::count(),
                'total_questions' => Question::count(),
                'total_exams' => Exam::count(),
                'active_exams' => Exam::whereIn('status', ['published', 'active'])->count(),
                'in_progress_attempts' => ExamAttempt::where('status', 'in_progress')->count(),
            ];

            $recentResults = Result::with(['student.user', 'exam.subject'])
                ->latest('id')
                ->take(5)
                ->get();

            $recentActivities = ActivityLog::with('user:id,name,username')
                ->latest('id')
                ->take(5)
                ->get();

            $errorMessage = null;
        } catch (\Throwable $e) {
            Log::error('Admin Dashboard metric fetch failed: ' . $e->getMessage());

            $metrics = [
                'total_students' => 0,
                'total_teachers' => 0,
                'total_classes' => 0,
                'total_subjects' => 0,
                'total_questions' => 0,
                'total_exams' => 0,
                'active_exams' => 0,
                'in_progress_attempts' => 0,
            ];
            $recentResults = collect();
            $recentActivities = collect();
            $errorMessage = 'Gagal memuat ringkasan data metrik sistem. Silakan muat ulang halaman.';
        }

        return view('admin.dashboard', [
            'user' => $user,
            'metrics' => $metrics,
            'recentResults' => $recentResults,
            'recentActivities' => $recentActivities,
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * Display the Guru Dashboard with teacher-scoped metric cards and summary data.
     */
    public function guruDashboard(Request $request): View
    {
        $user = $request->user()->load(['role', 'teacher']);
        $teacherUserId = $user->id;

        try {
            $metrics = [
                'total_questions' => Question::where('created_by', $teacherUserId)->count(),
                'total_exams' => Exam::where('created_by', $teacherUserId)->count(),
                'active_exams' => Exam::where('created_by', $teacherUserId)
                    ->whereIn('status', ['published', 'active'])
                    ->count(),
                'relevant_students' => ExamParticipant::whereHas('exam', function ($q) use ($teacherUserId) {
                    $q->where('created_by', $teacherUserId);
                })->distinct('student_id')->count('student_id'),
            ];

            $recentResults = Result::whereHas('exam', function ($q) use ($teacherUserId) {
                $q->where('created_by', $teacherUserId);
            })
            ->with(['student.user', 'exam.subject'])
            ->latest('id')
            ->take(5)
            ->get();

            $errorMessage = null;
        } catch (\Throwable $e) {
            Log::error('Guru Dashboard metric fetch failed: ' . $e->getMessage());

            $metrics = [
                'total_questions' => 0,
                'total_exams' => 0,
                'active_exams' => 0,
                'relevant_students' => 0,
            ];
            $recentResults = collect();
            $errorMessage = 'Gagal memuat ringkasan data metrik guru. Silakan muat ulang halaman.';
        }

        return view('guru.dashboard', [
            'user' => $user,
            'metrics' => $metrics,
            'recentResults' => $recentResults,
            'errorMessage' => $errorMessage,
        ]);
    }
}
