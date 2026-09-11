@extends('layouts.app')

@section('title', 'Dashboard Administrator — CBT Server SMK')
@section('page-title', 'Dashboard Administrator')

@section('content')
@php
    $totalQuestions = $metrics['total_questions'] ?? 0;
    $publishedExams = $metrics['active_exams'] ?? 0;
    $totalExams = $metrics['total_exams'] ?? 0;
    $totalStudents = $metrics['total_students'] ?? 0;
    $totalTeachers = $metrics['total_teachers'] ?? 0;
    $totalClasses = $metrics['total_classes'] ?? 0;
    $totalSubjects = $metrics['total_subjects'] ?? 0;
    $inProgress = $metrics['in_progress_attempts'] ?? 0;

    $completedAttempts = \App\Models\Result::count();
    $avgScore = \App\Models\Result::avg('final_score') ?? \App\Models\Result::avg('score') ?? 0;

    $gradeA = \App\Models\Result::whereRaw('COALESCE(final_score, score) >= 80')->count();
    $gradeB = \App\Models\Result::whereRaw('COALESCE(final_score, score) >= 70 AND COALESCE(final_score, score) < 80')->count();
    $gradeC = \App\Models\Result::whereRaw('COALESCE(final_score, score) >= 60 AND COALESCE(final_score, score) < 70')->count();
    $gradeD = \App\Models\Result::whereRaw('COALESCE(final_score, score) >= 50 AND COALESCE(final_score, score) < 60')->count();
    $gradeE = \App\Models\Result::whereRaw('COALESCE(final_score, score) >= 40 AND COALESCE(final_score, score) < 50')->count();
    $gradeF = \App\Models\Result::whereRaw('COALESCE(final_score, score) < 40')->count();
@endphp

<div style="display: flex; flex-direction: column; gap: 24px;">
    @if ($errorMessage)
        <div class="alert alert-danger">
            <span>{{ $errorMessage }}</span>
        </div>
    @endif

    <!-- PETAK 1: WELCOME & SERVER STATUS BANNER -->
    <div class="card" style="padding: 20px 24px; border-left: 4px solid var(--primary); background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 class="welcome-heading">
                    <span>👋</span>
                    <span>Selamat Datang, {{ $user->name ?? $user->username }}!</span>
                </h2>
                <p class="card-description" style="margin: 0;">
                    Panel Kendali Utama <strong>CBT Server SMK</strong>. Seluruh modul evaluasi berbasis LAN siap beroperasi.
                </p>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="offline-badge">
                    <span class="status-dot"></span>
                    <span>Server: Local LAN Offline Aktif</span>
                </span>
            </div>
        </div>
    </div>

    <!-- PETAK 2: RINGKASAN METRIK SISTEM (STATS GRID) -->
    <div>
        <div class="section-heading-box">
            <h3 class="section-heading-title">
                <span class="heading-icon-badge blue">📊</span>
                <span class="heading-text blue">Ringkasan Data & Statistik Sistem</span>
            </h3>
        </div>

        <div class="stats-grid">
            <!-- 1. Total Peserta -->
            <div class="stat-card purple">
                <div class="stat-header">
                    <span class="stat-label">Total Peserta</span>
                    <span class="stat-icon purple">👥</span>
                </div>
                <div class="stat-number">{{ number_format($totalStudents) }}</div>
                <div class="stat-subtext">Siswa terdaftar aktif</div>
            </div>

            <!-- 2. Total Guru -->
            <div class="stat-card teal">
                <div class="stat-header">
                    <span class="stat-label">Guru & Pengawas</span>
                    <span class="stat-icon teal">👨‍🏫</span>
                </div>
                <div class="stat-number">{{ number_format($totalTeachers) }}</div>
                <div class="stat-subtext">Pengajar di sistem</div>
            </div>

            <!-- 3. Total Kelas -->
            <div class="stat-card amber">
                <div class="stat-header">
                    <span class="stat-label">Rombel / Kelas</span>
                    <span class="stat-icon amber">🏫</span>
                </div>
                <div class="stat-number">{{ number_format($totalClasses) }}</div>
                <div class="stat-subtext">Kelas aktif sekolah</div>
            </div>

            <!-- 4. Total Mapel -->
            <div class="stat-card rose">
                <div class="stat-header">
                    <span class="stat-label">Mata Pelajaran</span>
                    <span class="stat-icon rose">📚</span>
                </div>
                <div class="stat-number">{{ number_format($totalSubjects) }}</div>
                <div class="stat-subtext">Kurikulum mapel</div>
            </div>

            <!-- 5. Bank Soal -->
            <div class="stat-card orange">
                <div class="stat-header">
                    <span class="stat-label">Bank Soal</span>
                    <span class="stat-icon orange">📝</span>
                </div>
                <div class="stat-number">{{ number_format($totalQuestions) }}</div>
                <div class="stat-subtext">Butir soal terdaftar</div>
            </div>

            <!-- 6. Paket Ujian -->
            <div class="stat-card emerald">
                <div class="stat-header">
                    <span class="stat-label">Paket Ujian</span>
                    <span class="stat-icon emerald">⏱️</span>
                </div>
                <div class="stat-number">{{ number_format($totalExams) }}</div>
                <div class="stat-subtext">{{ $publishedExams }} ujian aktif</div>
            </div>
        </div>
    </div>

    <!-- PETAK 3: PETAK-PETAK FITUR & MODUL UTAMA (TOOLS PINTAS TERTATA RAPI) -->
    <div>
        <div class="section-heading-box">
            <h3 class="section-heading-title">
                <span class="heading-icon-badge amber">🛠️</span>
                <span class="heading-text amber">Modul & Fitur Utama (Akses Cepat)</span>
            </h3>
        </div>

        <div class="feature-box-grid">
            <!-- 1. Bank Soal -->
            <a href="{{ route('admin.questions.index') }}" class="feature-box-card">
                <div class="feature-box-icon blue">📝</div>
                <div class="feature-box-info">
                    <div class="feature-box-title">Bank Soal</div>
                    <div class="feature-box-desc">Kelola butir soal, opsi jawaban, dan kunci nilai ujian</div>
                </div>
            </a>

            <!-- 2. Paket Ujian -->
            <a href="{{ route('admin.exams.index') }}" class="feature-box-card">
                <div class="feature-box-icon green">⏱️</div>
                <div class="feature-box-info">
                    <div class="feature-box-title">Paket Ujian</div>
                    <div class="feature-box-desc">Jadwal sesi ujian, atur durasi waktu, token & peserta</div>
                </div>
            </a>

            <!-- 3. Monitoring LAN -->
            <a href="{{ route('admin.monitoring.index') }}" class="feature-box-card">
                <div class="feature-box-icon amber">📡</div>
                <div class="feature-box-info">
                    <div class="feature-box-title">Monitoring Ujian</div>
                    <div class="feature-box-desc">Pantau progres pengerjaan peserta secara langsung di LAN</div>
                </div>
            </a>

            <!-- 4. Hasil Ujian -->
            <a href="{{ route('admin.results.index') }}" class="feature-box-card">
                <div class="feature-box-icon purple">🎯</div>
                <div class="feature-box-info">
                    <div class="feature-box-title">Hasil & Nilai</div>
                    <div class="feature-box-desc">Rekapitulasi perolehan skor, jawaban siswa & evaluasi</div>
                </div>
            </a>

            <!-- 5. Data Peserta -->
            <a href="{{ route('admin.students.index') }}" class="feature-box-card">
                <div class="feature-box-icon rose">👥</div>
                <div class="feature-box-info">
                    <div class="feature-box-title">Data Peserta</div>
                    <div class="feature-box-desc">Manajemen akun login siswa, NIS, kelas dan rombel</div>
                </div>
            </a>

            <!-- 6. Backup Data -->
            <a href="{{ route('admin.backups.index') }}" class="feature-box-card">
                <div class="feature-box-icon slate">💾</div>
                <div class="feature-box-info">
                    <div class="feature-box-title">Backup & Restore</div>
                    <div class="feature-box-desc">Pencadangan berkas database dan pemulihan sistem offline</div>
                </div>
            </a>
        </div>
    </div>

    <!-- PETAK 4: DISTRIBUSI NILAI SISWA (PETAK PASTEL A-F) -->
    <div class="grade-distribution-card">
        <div class="section-heading-box" style="margin-bottom: 14px;">
            <h3 class="section-heading-title" style="margin-bottom: 0;">
                <span class="heading-icon-badge purple">📈</span>
                <span class="heading-text purple">Distribusi Grade Nilai Evaluasi</span>
            </h3>
            <span style="font-size: 12px; color: var(--text-muted);">Total Selesai: <strong>{{ number_format($completedAttempts) }}</strong> pengerjaan</span>
        </div>

        <div class="grade-boxes-grid">
            <div class="grade-box grade-a">
                <div class="grade-box-number">{{ $gradeA }}</div>
                <div class="grade-box-label">Grade A</div>
                <div class="grade-box-range">80 - 100%</div>
            </div>
            <div class="grade-box grade-b">
                <div class="grade-box-number">{{ $gradeB }}</div>
                <div class="grade-box-label">Grade B</div>
                <div class="grade-box-range">70 - 79%</div>
            </div>
            <div class="grade-box grade-c">
                <div class="grade-box-number">{{ $gradeC }}</div>
                <div class="grade-box-label">Grade C</div>
                <div class="grade-box-range">60 - 69%</div>
            </div>
            <div class="grade-box grade-d">
                <div class="grade-box-number">{{ $gradeD }}</div>
                <div class="grade-box-label">Grade D</div>
                <div class="grade-box-range">50 - 59%</div>
            </div>
            <div class="grade-box grade-e">
                <div class="grade-box-number">{{ $gradeE }}</div>
                <div class="grade-box-label">Grade E</div>
                <div class="grade-box-range">40 - 49%</div>
            </div>
            <div class="grade-box grade-f">
                <div class="grade-box-number">{{ $gradeF }}</div>
                <div class="grade-box-label">Grade F</div>
                <div class="grade-box-range">0 - 39%</div>
            </div>
        </div>
    </div>

    <!-- PETAK 5: HASIL UJIAN TERBARU & LOG AKTIVITAS (TABEL RAPI BERKOTAK) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
        <!-- Hasil Ujian Terbaru -->
        <div class="card" style="padding: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                <h3 class="card-title" style="margin-bottom: 0; font-size: 15px;">
                    <span class="heading-icon-badge emerald" style="width: 26px; height: 26px; font-size: 13px;">📋</span>
                    <span class="heading-text emerald">Hasil Ujian Terbaru</span>
                </h3>
                <a href="{{ route('admin.results.index') }}" class="btn btn-secondary btn-sm" style="font-size: 11.5px;">Lihat Semua</a>
            </div>

            @if ($recentResults->isEmpty())
                <div class="empty-state" style="padding: 28px 0;">
                    <div class="empty-state-icon">📋</div>
                    <p style="margin-bottom: 0;">Belum ada hasil ujian yang diselesaikan.</p>
                </div>
            @else
                <div class="data-table-wrapper" style="margin-top: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Peserta</th>
                                <th>Paket Ujian</th>
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

        <!-- Log Aktivitas Sistem -->
        <div class="card" style="padding: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                <h3 class="card-title" style="margin-bottom: 0; font-size: 15px;">
                    <span class="heading-icon-badge orange" style="width: 26px; height: 26px; font-size: 13px;">📜</span>
                    <span class="heading-text orange">Log Aktivitas Sistem</span>
                </h3>
                <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-secondary btn-sm" style="font-size: 11.5px;">Lihat Semua</a>
            </div>

            @if ($recentActivities->isEmpty())
                <div class="empty-state" style="padding: 28px 0;">
                    <div class="empty-state-icon">📜</div>
                    <p style="margin-bottom: 0;">Belum ada log aktivitas tercatat.</p>
                </div>
            @else
                <div class="data-table-wrapper" style="margin-top: 0;">
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
