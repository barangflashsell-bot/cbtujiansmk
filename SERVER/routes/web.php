<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\WebActivityLogController;
use App\Http\Controllers\Web\WebBackupController;
use App\Http\Controllers\Web\WebClassController;
use App\Http\Controllers\Web\WebExamController;
use App\Http\Controllers\Web\WebMonitoringController;
use App\Http\Controllers\Web\WebQuestionController;
use App\Http\Controllers\Web\WebReportController;
use App\Http\Controllers\Web\WebResultController;
use App\Http\Controllers\Web\WebSettingController;
use App\Http\Controllers\Web\WebStudentController;
use App\Http\Controllers\Web\WebSubjectController;
use App\Http\Controllers\Web\WebTeacherController;
use Illuminate\Support\Facades\Route;

// Root Route: show login form for guest or redirect authenticated user to role dashboard
Route::get('/', [AuthController::class, 'showLoginForm'])->name('home');
Route::get('/downloads/cbt-peserta.apk', [WebSettingController::class, 'downloadApk'])->name('public.download_apk');

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ==========================================
    // ADMIN WEB ROUTES (role:admin)
    // ==========================================
    Route::middleware('role:admin')->prefix('admin')->as('admin.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('dashboard');

        // Master Data: Students (Peserta)
        Route::prefix('students')->as('students.')->group(function () {
            Route::get('/', [WebStudentController::class, 'adminIndex'])->name('index');
            Route::get('/template', [WebStudentController::class, 'downloadTemplate'])->name('template');
            Route::post('/import', [WebStudentController::class, 'adminImport'])->name('import');
            Route::post('/', [WebStudentController::class, 'adminStore'])->name('store');
            Route::match(['put', 'patch'], '/{id}', [WebStudentController::class, 'adminUpdate'])->name('update');
            Route::delete('/{id}', [WebStudentController::class, 'adminDestroy'])->name('destroy');
        });

        // Master Data: Teachers (Guru)
        Route::prefix('teachers')->as('teachers.')->group(function () {
            Route::get('/', [WebTeacherController::class, 'index'])->name('index');
            Route::get('/template', [WebTeacherController::class, 'downloadTemplate'])->name('template');
            Route::post('/import', [WebTeacherController::class, 'import'])->name('import');
            Route::post('/', [WebTeacherController::class, 'store'])->name('store');
            Route::match(['put', 'patch'], '/{id}', [WebTeacherController::class, 'update'])->name('update');
            Route::delete('/{id}', [WebTeacherController::class, 'destroy'])->name('destroy');
        });

        // Master Data: Classes (Kelas)
        Route::prefix('classes')->as('classes.')->group(function () {
            Route::get('/', [WebClassController::class, 'index'])->name('index');
            Route::get('/template', [WebClassController::class, 'downloadTemplate'])->name('template');
            Route::post('/import', [WebClassController::class, 'import'])->name('import');
            Route::post('/', [WebClassController::class, 'store'])->name('store');
            Route::match(['put', 'patch'], '/{id}', [WebClassController::class, 'update'])->name('update');
            Route::delete('/{id}', [WebClassController::class, 'destroy'])->name('destroy');
        });

        // Master Data: Subjects (Mata Pelajaran)
        Route::prefix('subjects')->as('subjects.')->group(function () {
            Route::get('/', [WebSubjectController::class, 'index'])->name('index');
            Route::get('/template', [WebSubjectController::class, 'downloadTemplate'])->name('template');
            Route::post('/import', [WebSubjectController::class, 'import'])->name('import');
            Route::post('/', [WebSubjectController::class, 'store'])->name('store');
            Route::match(['put', 'patch'], '/{id}', [WebSubjectController::class, 'update'])->name('update');
            Route::delete('/{id}', [WebSubjectController::class, 'destroy'])->name('destroy');
        });

        // Bank Soal: Questions (Admin)
        Route::prefix('questions')->as('questions.')->group(function () {
            Route::get('/', [WebQuestionController::class, 'index'])->name('index');
            Route::get('/template', [WebQuestionController::class, 'downloadTemplate'])->name('template');
            Route::post('/import', [WebQuestionController::class, 'import'])->name('import');
            Route::get('/create', [WebQuestionController::class, 'create'])->name('create');
            Route::post('/', [WebQuestionController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [WebQuestionController::class, 'edit'])->name('edit');
            Route::match(['put', 'patch'], '/{id}', [WebQuestionController::class, 'update'])->name('update');
            Route::post('/{id}/toggle-status', [WebQuestionController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{id}', [WebQuestionController::class, 'destroy'])->name('destroy');
        });

        // Ujian & Peserta Ujian: Exams (Admin)
        Route::prefix('exams')->as('exams.')->group(function () {
            Route::get('/', [WebExamController::class, 'index'])->name('index');
            Route::get('/create', [WebExamController::class, 'create'])->name('create');
            Route::post('/', [WebExamController::class, 'store'])->name('store');
            Route::get('/{id}', [WebExamController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [WebExamController::class, 'edit'])->name('edit');
            Route::match(['put', 'patch'], '/{id}', [WebExamController::class, 'update'])->name('update');
            Route::post('/{id}/set-schedule', [WebExamController::class, 'setSchedule'])->name('set-schedule');
            Route::get('/{id}/export', [WebExamController::class, 'exportScores'])->name('export');
            Route::delete('/{id}', [WebExamController::class, 'destroy'])->name('destroy');

            // Exam Questions attachment
            Route::post('/{id}/questions', [WebExamController::class, 'attachQuestion'])->name('questions.attach');
            Route::delete('/{id}/questions/{questionId}', [WebExamController::class, 'detachQuestion'])->name('questions.detach');

            // Exam Participants management
            Route::post('/{id}/participants', [WebExamController::class, 'addParticipants'])->name('participants.add');
            Route::delete('/{id}/participants/{participantId}', [WebExamController::class, 'removeParticipant'])->name('participants.remove');
            Route::post('/{id}/participants/{participantId}/retest', [WebExamController::class, 'toggleRetest'])->name('participants.retest');
        });

        // Live Monitoring (Admin)
        Route::prefix('monitoring')->as('monitoring.')->group(function () {
            Route::get('/', [WebMonitoringController::class, 'index'])->name('index');
            Route::get('/exams/{id}', [WebMonitoringController::class, 'show'])->name('show');
        });

        // Hasil & Nilai Ujian (Admin)
        Route::prefix('results')->as('results.')->group(function () {
            Route::get('/', [WebResultController::class, 'index'])->name('index');
            Route::get('/{id}', [WebResultController::class, 'show'])->name('show');
            Route::post('/{id}/publish', [WebResultController::class, 'togglePublish'])->name('publish');
        });

        // Laporan & Analisis Akademik (Admin)
        Route::prefix('reports')->as('reports.')->group(function () {
            Route::get('/', [WebReportController::class, 'index'])->name('index');
            Route::get('/exams/{id}', [WebReportController::class, 'examReport'])->name('exam');
            Route::get('/exams/{id}/export-csv', [WebReportController::class, 'exportExamCsv'])->name('exam.export_csv');
            Route::get('/exams/{id}/export-pdf', [WebReportController::class, 'exportExamPdf'])->name('exam.export_pdf');
            Route::get('/classes/{id}', [WebReportController::class, 'classReport'])->name('class');
            Route::get('/item-analysis/{id}', [WebReportController::class, 'itemAnalysis'])->name('item-analysis');
        });

        // Pemeliharaan Server: Backup System (Admin)
        Route::prefix('backups')->as('backups.')->group(function () {
            Route::get('/', [WebBackupController::class, 'index'])->name('index');
            Route::post('/', [WebBackupController::class, 'create'])->name('create');
            Route::get('/{filename}/download', [WebBackupController::class, 'download'])->name('download');
            Route::delete('/{filename}', [WebBackupController::class, 'destroy'])->name('destroy');
        });

        // Pemeliharaan Server: Settings System (Admin)
        Route::prefix('settings')->as('settings.')->group(function () {
            Route::get('/', [WebSettingController::class, 'index'])->name('index');
            Route::get('/download-apk', [WebSettingController::class, 'downloadApk'])->name('download_apk');
            Route::match(['put', 'patch', 'post'], '/', [WebSettingController::class, 'update'])->name('update');
        });

        // Pemeliharaan Server: Activity Logs / Audit Trail (Admin)
        Route::prefix('activity-logs')->as('activity-logs.')->group(function () {
            Route::get('/', [WebActivityLogController::class, 'index'])->name('index');
        });
    });

    // ==========================================
    // GURU WEB ROUTES (role:teacher,guru)
    // ==========================================
    Route::middleware('role:teacher,guru')->prefix('guru')->as('guru.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'guruDashboard'])->name('dashboard');

        // Master Data: Students (Read-only list for teacher)
        Route::get('/students', [WebStudentController::class, 'guruIndex'])->name('students.index');

        // Bank Soal: Questions (Guru scoped)
        Route::prefix('questions')->as('questions.')->group(function () {
            Route::get('/', [WebQuestionController::class, 'index'])->name('index');
            Route::get('/template', [WebQuestionController::class, 'downloadTemplate'])->name('template');
            Route::post('/import', [WebQuestionController::class, 'import'])->name('import');
            Route::get('/create', [WebQuestionController::class, 'create'])->name('create');
            Route::post('/', [WebQuestionController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [WebQuestionController::class, 'edit'])->name('edit');
            Route::match(['put', 'patch'], '/{id}', [WebQuestionController::class, 'update'])->name('update');
            Route::post('/{id}/toggle-status', [WebQuestionController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{id}', [WebQuestionController::class, 'destroy'])->name('destroy');
        });

        // Ujian & Peserta Ujian: Exams (Guru scoped)
        Route::prefix('exams')->as('exams.')->group(function () {
            Route::get('/', [WebExamController::class, 'index'])->name('index');
            Route::get('/create', [WebExamController::class, 'create'])->name('create');
            Route::post('/', [WebExamController::class, 'store'])->name('store');
            Route::get('/{id}', [WebExamController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [WebExamController::class, 'edit'])->name('edit');
            Route::match(['put', 'patch'], '/{id}', [WebExamController::class, 'update'])->name('update');
            Route::post('/{id}/set-schedule', [WebExamController::class, 'setSchedule'])->name('set-schedule');
            Route::get('/{id}/export', [WebExamController::class, 'exportScores'])->name('export');
            Route::delete('/{id}', [WebExamController::class, 'destroy'])->name('destroy');

            // Exam Questions attachment
            Route::post('/{id}/questions', [WebExamController::class, 'attachQuestion'])->name('questions.attach');
            Route::delete('/{id}/questions/{questionId}', [WebExamController::class, 'detachQuestion'])->name('questions.detach');

            // Exam Participants management
            Route::post('/{id}/participants', [WebExamController::class, 'addParticipants'])->name('participants.add');
            Route::delete('/{id}/participants/{participantId}', [WebExamController::class, 'removeParticipant'])->name('participants.remove');
            Route::post('/{id}/participants/{participantId}/retest', [WebExamController::class, 'toggleRetest'])->name('participants.retest');
        });

        // Live Monitoring (Guru scoped)
        Route::prefix('monitoring')->as('monitoring.')->group(function () {
            Route::get('/', [WebMonitoringController::class, 'index'])->name('index');
            Route::get('/exams/{id}', [WebMonitoringController::class, 'show'])->name('show');
        });

        // Hasil & Nilai Ujian (Guru scoped)
        Route::prefix('results')->as('results.')->group(function () {
            Route::get('/', [WebResultController::class, 'index'])->name('index');
            Route::get('/{id}', [WebResultController::class, 'show'])->name('show');
            Route::post('/{id}/publish', [WebResultController::class, 'togglePublish'])->name('publish');
        });

        // Laporan & Analisis Akademik (Guru scoped)
        Route::prefix('reports')->as('reports.')->group(function () {
            Route::get('/', [WebReportController::class, 'index'])->name('index');
            Route::get('/exams/{id}', [WebReportController::class, 'examReport'])->name('exam');
            Route::get('/exams/{id}/export-csv', [WebReportController::class, 'exportExamCsv'])->name('exam.export_csv');
            Route::get('/exams/{id}/export-pdf', [WebReportController::class, 'exportExamPdf'])->name('exam.export_pdf');
            Route::get('/classes/{id}', [WebReportController::class, 'classReport'])->name('class');
            Route::get('/item-analysis/{id}', [WebReportController::class, 'itemAnalysis'])->name('item-analysis');
        });
    });
});
