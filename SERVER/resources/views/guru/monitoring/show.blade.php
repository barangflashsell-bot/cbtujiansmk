@extends('layouts.app')

@section('title', 'Telemetri Live: ' . $exam->title . ' - CBT Guru')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Monitoring Live: {{ $exam->title }}</h1>
        <p class="page-subtitle">
            <span class="badge badge-info">{{ $exam->subject->name ?? '-' }}</span>
            <span style="margin-left: 6px;">Total: <strong>{{ $totalQuestions }}</strong> Butir Soal</span>
            <span style="margin-left: 6px;">Durasi: <strong>{{ $exam->duration_minutes }} Menit</strong></span>
            @if($exam->token)
                <span class="badge badge-secondary" style="margin-left: 6px;">TOKEN: {{ $exam->token }}</span>
            @endif
        </p>
    </div>
    <div style="display: flex; gap: 8px; align-items: center;">
        <span style="font-size: 0.85rem; color: var(--color-slate-500);">
            Server: <strong>{{ $serverTime->format('H:i:s') }}</strong>
        </span>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.location.reload();">&#8635; Segarkan Data</button>
        <a href="{{ route('guru.monitoring.index') }}" class="btn btn-secondary btn-sm">&larr; Semua Ujian</a>
    </div>
</div>

<!-- STATS OVERVIEW CARDS -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--color-primary-50); color: var(--color-primary-600);">&#128101;</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-label">Total Peserta Terdaftar</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #fef3c7; color: #d97706;">&#9203;</div>
        <div class="stat-value" style="color: #d97706;">{{ $stats['in_progress'] }}</div>
        <div class="stat-label">Sedang Mengerjakan</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">&#9989;</div>
        <div class="stat-value" style="color: #16a34a;">{{ $stats['submitted'] }}</div>
        <div class="stat-label">Sudah Submit</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #10b981; background: #f0fdf4;">
        <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">🟢</div>
        <div class="stat-value" style="color: #16a34a;">{{ ($stats['in_progress'] ?? 0) + ($stats['submitted'] ?? 0) + ($stats['timeout'] ?? 0) }}</div>
        <div class="stat-label">Presensi Hadir (Login)</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #fee2e2; color: #dc2626;">&#9888;</div>
        <div class="stat-value" style="color: #dc2626;">{{ $stats['timeout'] }}</div>
        <div class="stat-label">Waktu Habis (Timeout)</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--color-slate-100); color: var(--color-slate-600);">&#9200;</div>
        <div class="stat-value" style="color: var(--color-slate-600);">{{ $stats['not_started'] }}</div>
        <div class="stat-label">Belum Memulai / Belum Hadir</div>
    </div>
</div>

<div class="card">
    <form method="GET" action="{{ route('guru.monitoring.show', $exam->id) }}" class="search-filter-bar">
        <div class="search-input-group">
            <span class="search-icon">&#128269;</span>
            <input type="text" name="search" class="form-control" placeholder="Cari NIS atau nama peserta..." value="{{ request('search') }}">
        </div>
        <div class="filter-select-group">
            <select name="class_id" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        @if(request('search') || request('class_id'))
            <a href="{{ route('guru.monitoring.show', $exam->id) }}" class="btn btn-secondary">Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>NIS / Nama Peserta</th>
                    <th>Kelas</th>
                    <th style="min-width: 140px;">Absensi (Jam Login)</th>
                    <th>Status Pengerjaan</th>
                    <th>Sisa Waktu</th>
                    <th>Progres Soal</th>
                    <th>IP / Perangkat</th>
                    <th>Aktivitas Terakhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($participants as $idx => $p)
                    @php
                        $att = $attempts->get($p->student_id);
                        $remainingSeconds = 0;
                        if ($att && $att->status === 'in_progress' && $att->ends_at) {
                            $remainingSeconds = max(0, $serverTime->diffInSeconds($att->ends_at, false));
                        }
                        $loginTimestamp = $att ? ($att->started_at ?? $att->created_at) : ($p->student->user->last_login_at ?? null);
                    @endphp
                    <tr>
                        <td>{{ $participants->firstItem() + $idx }}</td>
                        <td>
                            <div style="font-weight: 600;">{{ $p->student->user->name ?? '-' }}</div>
                            <div style="font-size: 0.8rem; color: var(--color-slate-500);">NIS: {{ $p->student->nis ?? '-' }}</div>
                        </td>
                        <td>
                            <span class="badge badge-secondary">{{ $p->student->schoolClass->name ?? '-' }}</span>
                        </td>
                        <td>
                            @if($loginTimestamp || ($att && $att->status !== 'not_started'))
                                <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 4px; font-weight: 700;">
                                    <span>🟢</span> Hadir ({{ $loginTimestamp ? $loginTimestamp->format('H:i:s') : '07:15:00' }})
                                </span>
                            @else
                                <span class="badge badge-secondary" style="font-size: 0.8rem; color: var(--color-slate-400);">
                                    ⚪ Belum Login
                                </span>
                            @endif
                        </td>
                        <td>
                            @if(! $att)
                                <span class="badge badge-secondary">Belum Mulai</span>
                            @elseif($att->status === 'in_progress')
                                <span class="badge badge-warning">Sedang Mengerjakan</span>
                            @elseif($att->status === 'submitted')
                                <span class="badge badge-success">Sudah Selesai</span>
                            @elseif($att->status === 'timeout')
                                <span class="badge badge-danger">Waktu Habis</span>
                            @else
                                <span class="badge badge-secondary">{{ $att->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($att && $att->status === 'in_progress')
                                @php
                                    $remMins = floor($remainingSeconds / 60);
                                    $remSecs = $remainingSeconds % 60;
                                @endphp
                                <strong style="color: {{ $remainingSeconds < 300 ? 'var(--color-rose-600)' : 'var(--color-slate-800)' }};">
                                    {{ sprintf('%02d:%02d', $remMins, $remSecs) }}
                                </strong>
                            @elseif($att && $att->status === 'submitted')
                                <span style="color: var(--color-emerald-600); font-size: 0.85rem;">Selesai</span>
                            @elseif($att && $att->status === 'timeout')
                                <span style="color: var(--color-rose-600); font-size: 0.85rem;">00:00</span>
                            @else
                                <span style="color: var(--color-slate-400); font-size: 0.85rem;">-</span>
                            @endif
                        </td>
                        <td>
                            @if($att)
                                <div>
                                    <strong>{{ $att->answers_count }}</strong> / {{ $totalQuestions }} Terjawab
                                </div>
                                @php
                                    $percent = ($totalQuestions > 0) ? min(100, round(($att->answers_count / $totalQuestions) * 100)) : 0;
                                @endphp
                                <div style="background: var(--color-slate-200); border-radius: 4px; height: 6px; width: 100px; margin-top: 4px; overflow: hidden;">
                                    <div style="background: var(--color-primary-600); height: 100%; width: {{ $percent }}%;"></div>
                                </div>
                            @else
                                <span style="color: var(--color-slate-400); font-size: 0.85rem;">0 / {{ $totalQuestions }}</span>
                            @endif
                        </td>
                        <td style="font-size: 0.8rem; color: var(--color-slate-600);">
                            @if($att)
                                <div>{{ $att->ip_address ?? 'LAN' }}</div>
                                <div style="color: var(--color-slate-400);">{{ Str::limit($att->device_info ?? '-', 24) }}</div>
                            @else
                                -
                            @endif
                        </td>
                        <td style="font-size: 0.8rem; color: var(--color-slate-600);">
                            @if($att && $att->last_activity_at)
                                <div>{{ $att->last_activity_at->format('H:i:s') }}</div>
                                <div style="color: var(--color-slate-400);">{{ $att->last_activity_at->diffForHumans() }}</div>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">
                            <div class="empty-icon">&#128101;</div>
                            <div class="empty-title">Tidak ada peserta ditemukan</div>
                            <div class="empty-desc">Pastikan peserta sudah didaftarkan pada paket ujian ini.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($participants->hasPages())
        <div class="pagination-wrapper">
            {{ $participants->links() }}
        </div>
    @endif
</div>
@endsection
