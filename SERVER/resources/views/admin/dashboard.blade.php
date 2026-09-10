@extends('layouts.app')

@section('title', 'CBT Dashboard — Rivendell High School')
@section('page-title', 'CBT Dashboard')

@section('content')
@php
    $totalQuestions = $metrics['total_questions'] ?? 0;
    $publishedExams = $metrics['active_exams'] ?? 0;
    $totalExams = $metrics['total_exams'] ?? 0;
    $draftExams = max(0, $totalExams - $publishedExams);
    $inProgress = $metrics['in_progress_attempts'] ?? 0;

    $completedAttempts = \App\Models\Result::count();
    $avgScore = \App\Models\Result::avg('final_score') ?? \App\Models\Result::avg('score') ?? 0;
    $totalStudentsWithResults = \App\Models\Result::distinct('student_id')->count('student_id');

    $gradeA = \App\Models\Result::whereRaw('COALESCE(final_score, score) >= 80')->count();
    $gradeB = \App\Models\Result::whereRaw('COALESCE(final_score, score) >= 70 AND COALESCE(final_score, score) < 80')->count();
    $gradeC = \App\Models\Result::whereRaw('COALESCE(final_score, score) >= 60 AND COALESCE(final_score, score) < 70')->count();
    $gradeD = \App\Models\Result::whereRaw('COALESCE(final_score, score) >= 50 AND COALESCE(final_score, score) < 60')->count();
    $gradeE = \App\Models\Result::whereRaw('COALESCE(final_score, score) >= 40 AND COALESCE(final_score, score) < 50')->count();
    $gradeF = \App\Models\Result::whereRaw('COALESCE(final_score, score) < 40')->count();

    $recentExams = \App\Models\Exam::with(['subject'])
        ->withCount([
            'participants',
            'attempts as completed_count' => function($q) {
                $q->where('status', 'completed');
            }
        ])
        ->latest('id')
        ->take(4)
        ->get();

    $subjectsPerformance = \App\Models\Subject::withCount('exams')
        ->with(['exams.results'])
        ->take(4)
        ->get()
        ->map(function($subject) {
            $totalAttempts = 0;
            $totalScore = 0;
            foreach ($subject->exams as $ex) {
                foreach ($ex->results as $res) {
                    $totalAttempts++;
                    $totalScore += ($res->final_score ?? $res->score ?? 0);
                }
            }
            $avg = $totalAttempts > 0 ? round($totalScore / $totalAttempts, 1) : 0;
            return [
                'name' => $subject->name,
                'exams_count' => $subject->exams_count,
                'attempts_count' => $totalAttempts,
                'avg' => $avg,
            ];
        });
@endphp

<div style="display: flex; flex-direction: column; gap: 20px;">
    @if ($errorMessage)
        <div class="alert alert-danger">
            <span>{{ $errorMessage }}</span>
        </div>
    @endif

    <!-- TITLE & SUBTITLE (Matching Screenshot) -->
    <div class="dashboard-header" style="margin-bottom: 4px;">
        <h1 class="dashboard-title" style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0;">CBT Dashboard</h1>
        <p class="dashboard-subtitle" style="font-size: 13.5px; color: #64748b; margin-top: 4px; margin-bottom: 0;">Computer-Based Testing Analytics & Overview</p>
    </div>

    <!-- 4 HERO STAT CARDS (Green, Blue, Green, Blue) -->
    <div class="hero-stats-grid">
        <!-- 1. Total Questions (Green) -->
        <div class="hero-stat-card green">
            <div class="hero-stat-header">
                <span class="hero-stat-label">Total Questions</span>
                <div class="hero-stat-icon-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </div>
            </div>
            <div class="hero-stat-number">{{ number_format($totalQuestions) }}</div>
            <div class="hero-stat-subtext">&nbsp;</div>
        </div>

        <!-- 2. Published Exams (Blue) -->
        <div class="hero-stat-card blue">
            <div class="hero-stat-header">
                <span class="hero-stat-label">Published Exams</span>
                <div class="hero-stat-icon-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                </div>
            </div>
            <div class="hero-stat-number">{{ number_format($publishedExams) }}</div>
            <div class="hero-stat-subtext">{{ $draftExams }} drafts</div>
        </div>

        <!-- 3. Completed Attempts (Green) -->
        <div class="hero-stat-card green">
            <div class="hero-stat-header">
                <span class="hero-stat-label">Completed Attempts</span>
                <div class="hero-stat-icon-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
            </div>
            <div class="hero-stat-number">{{ number_format($completedAttempts) }}</div>
            <div class="hero-stat-subtext">{{ $inProgress }} in progress</div>
        </div>

        <!-- 4. Average Score (Blue) -->
        <div class="hero-stat-card blue">
            <div class="hero-stat-header">
                <span class="hero-stat-label">Average Score</span>
                <div class="hero-stat-icon-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                </div>
            </div>
            <div class="hero-stat-number">{{ number_format($avgScore, 2) }}%</div>
            <div class="hero-stat-subtext">{{ $totalStudentsWithResults }} students</div>
        </div>
    </div>

    <!-- GRADE DISTRIBUTION (6 Pastel Cards Row) -->
    <div class="grade-distribution-card">
        <h3 class="grade-distribution-header">Grade Distribution</h3>
        <div class="grade-boxes-grid">
            <div class="grade-box grade-a">
                <div class="grade-box-number">{{ $gradeA }}</div>
                <div class="grade-box-label">Grade A</div>
                <div class="grade-box-range">80-100%</div>
            </div>
            <div class="grade-box grade-b">
                <div class="grade-box-number">{{ $gradeB }}</div>
                <div class="grade-box-label">Grade B</div>
                <div class="grade-box-range">70-79%</div>
            </div>
            <div class="grade-box grade-c">
                <div class="grade-box-number">{{ $gradeC }}</div>
                <div class="grade-box-label">Grade C</div>
                <div class="grade-box-range">60-69%</div>
            </div>
            <div class="grade-box grade-d">
                <div class="grade-box-number">{{ $gradeD }}</div>
                <div class="grade-box-label">Grade D</div>
                <div class="grade-box-range">50-59%</div>
            </div>
            <div class="grade-box grade-e">
                <div class="grade-box-number">{{ $gradeE }}</div>
                <div class="grade-box-label">Grade E</div>
                <div class="grade-box-range">40-49%</div>
            </div>
            <div class="grade-box grade-f">
                <div class="grade-box-number">{{ $gradeF }}</div>
                <div class="grade-box-label">Grade F</div>
                <div class="grade-box-range">0-39%</div>
            </div>
        </div>
    </div>

    <!-- TWO-COLUMN SECTION: RECENT EXAMS & PERFORMANCE BY SUBJECT -->
    <div class="dashboard-two-col">
        <!-- LEFT: Recent Exams -->
        <div class="card">
            <h3 class="card-title" style="margin-bottom: 16px;">Recent Exams</h3>
            @if ($recentExams->isEmpty())
                <div class="empty-state" style="padding: 24px 0;">
                    <div class="empty-state-icon">⏱️</div>
                    <p>Belum ada ujian yang dibuat.</p>
                </div>
            @else
                <div>
                    @foreach ($recentExams as $exam)
                        @php
                            $participantsTotal = $exam->participants_count ?? 0;
                            $completedCount = $exam->completed_count ?? 0;
                            $completionPct = $participantsTotal > 0 ? round(($completedCount / $participantsTotal) * 100) : 0;
                            $examAvg = round($exam->results()->avg('final_score') ?? 0, 1);
                            $isPublished = in_array($exam->status, ['published', 'active']);
                        @endphp
                        <div class="recent-exam-item">
                            <div class="recent-exam-info">
                                <h4>{{ $exam->title }}</h4>
                                <p>{{ strtoupper($exam->subject?->name ?? 'UMUM') }} • {{ $exam->duration_minutes ?? 60 }} Min • Token: {{ $exam->token ?? '-' }}</p>
                                <div class="recent-exam-stats">
                                    {{ $completedCount }}/{{ $participantsTotal }} completed &nbsp;•&nbsp; Avg: {{ $examAvg }}% &nbsp;•&nbsp; {{ $completionPct }}% completion
                                </div>
                            </div>
                            <div>
                                <span class="badge {{ $isPublished ? 'badge-success' : 'badge-neutral' }}" style="font-size: 11px; padding: 3px 8px;">
                                    {{ $isPublished ? 'Published' : 'Draft' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- RIGHT: Performance by Subject -->
        <div class="card">
            <h3 class="card-title" style="margin-bottom: 16px;">Performance by Subject</h3>
            @if ($subjectsPerformance->isEmpty())
                <div class="empty-state" style="padding: 24px 0;">
                    <div class="empty-state-icon">📚</div>
                    <p>Belum ada mata pelajaran terdaftar.</p>
                </div>
            @else
                <div>
                    @foreach ($subjectsPerformance as $sub)
                        <div class="subject-perf-item">
                            <div class="subject-perf-header">
                                <span class="subject-perf-title">{{ $sub['name'] }}</span>
                                <span class="subject-perf-pct">{{ $sub['avg'] }}%</span>
                            </div>
                            <div class="subject-perf-bar-bg">
                                <div class="subject-perf-bar-fill" style="width: {{ min(100, max(0, $sub['avg'])) }}%;"></div>
                            </div>
                            <div class="subject-perf-meta">
                                {{ $sub['exams_count'] }} exams &nbsp; {{ $sub['attempts_count'] }} attempts
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- LOWER SECTION: RECENT RESULTS & RECENT ACTIVITY AUDIT LOG -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px;">
        <!-- Hasil Terbaru -->
        <div class="card" style="padding: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <h3 class="card-title" style="margin-bottom: 0;">Hasil Ujian Terbaru</h3>
                <span class="badge badge-primary">Terbaru</span>
            </div>

            @if ($recentResults->isEmpty())
                <div class="empty-state" style="padding: 20px 0;">
                    <div class="empty-state-icon">📋</div>
                    <p>Belum ada rekaman hasil ujian yang diselesaikan.</p>
                </div>
            @else
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Peserta</th>
                                <th>Ujian / Mapel</th>
                                <th>Nilai</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentResults as $res)
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;">{{ $res->student?->user?->name ?? 'Peserta' }}</div>
                                        <div style="font-size: 11px; color: var(--text-muted);">NIS: {{ $res->student?->nis ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $res->exam?->title ?? '-' }}</div>
                                        <div style="font-size: 11px; color: var(--text-muted);">{{ $res->exam?->subject?->name ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ ($res->final_score ?? $res->score) >= 75 ? 'badge-success' : 'badge-warning' }}">
                                            {{ number_format($res->final_score ?? $res->score ?? 0, 1) }}
                                        </span>
                                    </td>
                                    <td style="font-size: 11.5px; color: var(--text-muted); white-space: nowrap;">
                                        {{ $res->created_at ? $res->created_at->format('d/m H:i') : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Aktivitas Terbaru -->
        <div class="card" style="padding: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <h3 class="card-title" style="margin-bottom: 0;">Aktivitas Sistem Terbaru</h3>
                <span class="badge badge-neutral">Audit Log</span>
            </div>

            @if ($recentActivities->isEmpty())
                <div class="empty-state" style="padding: 20px 0;">
                    <div class="empty-state-icon">📜</div>
                    <p>Belum ada rekaman aktivitas sistem tercatat.</p>
                </div>
            @else
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Aksi</th>
                                <th>Modul</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentActivities as $act)
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;">{{ $act->user?->name ?? $act->user?->username ?? 'Sistem' }}</div>
                                        <div style="font-size: 11px; color: var(--text-muted);">IP: {{ $act->ip_address ?? '127.0.0.1' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-neutral">{{ $act->action }}</span>
                                    </td>
                                    <td>
                                        <span style="font-size: 12px; font-weight: 500;">{{ $act->module }}</span>
                                    </td>
                                    <td style="font-size: 11.5px; color: var(--text-muted); white-space: nowrap;">
                                        {{ $act->created_at ? $act->created_at->format('d/m H:i') : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
