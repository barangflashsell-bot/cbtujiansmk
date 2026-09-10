@extends('layouts.app')

@section('title', 'Dashboard Guru — CBT Local Server')
@section('page-title', 'Dashboard Guru')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">
    @if ($errorMessage)
        <div class="alert alert-danger">
            <span>{{ $errorMessage }}</span>
        </div>
    @endif

    <!-- WELCOME CARD -->
    <div class="card">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 class="card-title" style="font-size: 18px;">Selamat Datang, Bapak/Ibu {{ $user->name ?? $user->username }}!</h2>
                <p class="card-description">
                    Portal evaluasi CBT akademik guru. Berikut ringkasan butir soal, sesi ujian, dan hasil pengerjaan siswa yang berada di bawah wewenang Anda:
                </p>
            </div>
            <div style="text-align: right;">
                <span class="offline-badge">
                    <span class="status-dot"></span>
                    <span>Role: Guru / Pengajar</span>
                </span>
            </div>
        </div>
    </div>

    <!-- HERO STATS GRID (GURU SCOPE) -->
    <div class="hero-stats-grid">
        <!-- 1. Soal Guru (Green) -->
        <div class="hero-stat-card green">
            <div class="hero-stat-header">
                <span class="hero-stat-label">Total Questions</span>
                <div class="hero-stat-icon-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </div>
            </div>
            <div class="hero-stat-number">{{ number_format($metrics['total_questions']) }}</div>
            <div class="hero-stat-subtext">Butir soal dibuat</div>
        </div>

        <!-- 2. Paket Ujian Guru (Blue) -->
        <div class="hero-stat-card blue">
            <div class="hero-stat-header">
                <span class="hero-stat-label">Published Exams</span>
                <div class="hero-stat-icon-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                </div>
            </div>
            <div class="hero-stat-number">{{ number_format($metrics['total_exams']) }}</div>
            <div class="hero-stat-subtext">{{ $metrics['active_exams'] }} aktif</div>
        </div>

        <!-- 3. Ujian Aktif Guru (Green) -->
        <div class="hero-stat-card green">
            <div class="hero-stat-header">
                <span class="hero-stat-label">Active Exams</span>
                <div class="hero-stat-icon-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
            </div>
            <div class="hero-stat-number">{{ number_format($metrics['active_exams']) }}</div>
            <div class="hero-stat-subtext">Sesi siap dikerjakan</div>
        </div>

        <!-- 4. Peserta Ujian Guru (Blue) -->
        <div class="hero-stat-card blue">
            <div class="hero-stat-header">
                <span class="hero-stat-label">Total Students</span>
                <div class="hero-stat-icon-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                </div>
            </div>
            <div class="hero-stat-number">{{ number_format($metrics['relevant_students']) }}</div>
            <div class="hero-stat-subtext">Peserta terdaftar</div>
        </div>
    </div>

    <!-- HASIL UJIAN GURU -->
    <div class="card" style="padding: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h3 class="card-title" style="margin-bottom: 0;">Hasil Ujian Terbaru (Ujian Saya)</h3>
            <span class="badge badge-primary">Terbaru</span>
        </div>

        @if ($recentResults->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <p>Belum ada rekaman hasil siswa pada paket ujian yang Anda buat.</p>
            </div>
        @else
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Peserta</th>
                            <th>Ujian / Mapel</th>
                            <th>Nilai Akhir</th>
                            <th>Waktu Selesai</th>
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
                                    {{ $res->created_at ? $res->created_at->format('d/m/Y H:i') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
