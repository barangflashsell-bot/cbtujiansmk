@extends('layouts.app')

@section('title', 'Live Monitoring Ujian - CBT Administrator')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Live Monitoring Ujian</h1>
        <p class="page-subtitle">Pantau aktivitas sesi pengerjaan ujian siswa secara langsung di jaringan lokal (LAN)</p>
    </div>
    <div style="font-size: 0.85rem; color: var(--color-slate-500); display: flex; align-items: center; gap: 8px;">
        <span>Waktu Server: <strong>{{ $serverTime->format('H:i:s d/m/Y') }}</strong></span>
        <button type="button" class="btn btn-sm btn-secondary" onclick="window.location.reload();">&#8635; Refresh Data</button>
    </div>
</div>

<div class="card">
    <form method="GET" action="{{ route('admin.monitoring.index') }}" class="search-filter-bar">
        <div class="search-input-group">
            <span class="search-icon">&#128269;</span>
            <input type="text" name="search" class="form-control" placeholder="Cari judul ujian..." value="{{ request('search') }}">
        </div>
        <div class="filter-select-group">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active (Sedang Berlangsung)</option>
                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published (Terjadwal)</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed (Selesai)</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        @if(request('search') || request('status'))
            <a href="{{ route('admin.monitoring.index') }}" class="btn btn-secondary">Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Judul Ujian & Mapel</th>
                    <th>Jadwal / Durasi</th>
                    <th>Total Peserta</th>
                    <th>Sedang Mengerjakan</th>
                    <th>Sudah Selesai</th>
                    <th>Status Ujian</th>
                    <th style="width: 140px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($exams as $idx => $ex)
                    <tr>
                        <td>{{ $exams->firstItem() + $idx }}</td>
                        <td>
                            <div style="font-weight: 600; font-size: 0.95rem;">{{ $ex->title }}</div>
                            <div style="font-size: 0.8rem; color: var(--color-slate-500); margin-top: 2px;">
                                <span class="badge badge-info">{{ $ex->subject->name ?? '-' }}</span>
                                <span style="margin-left: 6px;">Oleh: {{ $ex->creator->name ?? 'Admin' }}</span>
                            </div>
                        </td>
                        <td style="font-size: 0.85rem;">
                            <div>{{ $ex->start_window ? $ex->start_window->format('d/m/Y H:i') : '-' }} s/d {{ $ex->end_window ? $ex->end_window->format('H:i') : '-' }}</div>
                            <div style="color: var(--color-slate-500);">Durasi: {{ $ex->duration_minutes }} Menit</div>
                        </td>
                        <td>
                            <strong style="font-size: 1.1rem; color: var(--color-slate-800);">{{ $ex->participants_count }}</strong> Siswa
                        </td>
                        <td>
                            <span class="badge badge-warning" style="font-size: 0.85rem; padding: 4px 10px;">
                                {{ $ex->in_progress_count }} Mengerjakan
                            </span>
                        </td>
                        <td>
                            <div style="font-size: 0.85rem;">
                                <span style="color: var(--color-emerald-600); font-weight: 600;">{{ $ex->submitted_count }} Submit</span>
                                @if($ex->timeout_count > 0)
                                    <span style="color: var(--color-rose-600); margin-left: 4px;">({{ $ex->timeout_count }} Timeout)</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($ex->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif($ex->status === 'published')
                                <span class="badge badge-info">Published</span>
                            @elseif($ex->status === 'completed')
                                <span class="badge badge-secondary">Completed</span>
                            @else
                                <span class="badge badge-warning">Draft</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <a href="{{ route('admin.monitoring.show', $ex->id) }}" class="btn btn-sm btn-primary">
                                📡 Pantau Live
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">
                            <div class="empty-icon">&#128225;</div>
                            <div class="empty-title">Tidak ada paket ujian untuk dipantau</div>
                            <div class="empty-desc">Paket ujian yang aktif atau terjadwal akan muncul pada daftar monitoring ini.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($exams->hasPages())
        <div class="pagination-wrapper">
            {{ $exams->links() }}
        </div>
    @endif
</div>
@endsection
