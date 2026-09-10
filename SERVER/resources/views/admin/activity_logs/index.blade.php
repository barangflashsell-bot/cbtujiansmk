@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem — CBT Administrator')
@section('page-title', 'Log Aktivitas & Audit Trail')

@section('content')
<div style="display: flex; flex-direction: column; gap: 20px;">

    <!-- TOP HEADER -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                Riwayat Audit & Aktivitas Server
            </h2>
            <p style="font-size: 0.85rem; color: var(--text-secondary);">
                Catatan riwayat integritas autentikasi, anti-cheat pengerjaan siswa, administrasi settings, dan backup (Read-Only).
            </p>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="window.location.reload();">
            &#8635; Segarkan Log
        </button>
    </div>

    <!-- FILTER BAR -->
    <div class="card">
        <form method="GET" action="{{ route('admin.activity-logs.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 220px;">
                <input 
                    type="text" 
                    name="search" 
                    class="form-control" 
                    placeholder="Cari aksi, detail, atau alamat IP..." 
                    value="{{ request('search') }}"
                >
            </div>

            <div style="min-width: 160px;">
                <select name="module" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Modul</option>
                    @foreach($modules as $m)
                        <option value="{{ $m }}" {{ request('module') === $m ? 'selected' : '' }}>
                            {{ $m }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="min-width: 180px;">
                <select name="user_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Pengguna</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->username }})
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-secondary">Filter</button>
            @if(request('search') || request('module') || request('user_id'))
                <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>
    </div>

    <!-- LOGS TABLE -->
    <div class="card">
        @if($logs->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th style="width: 160px;">Waktu Kejadian</th>
                            <th style="width: 170px;">Pengguna / Aktor</th>
                            <th style="width: 120px;">Modul</th>
                            <th style="width: 180px;">Aksi / Event</th>
                            <th style="width: 120px;">Alamat IP</th>
                            <th>Rincian Kontekstual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $idx => $log)
                            <tr>
                                <td>{{ $logs->firstItem() + $idx }}</td>
                                <td>
                                    <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-primary);">
                                        {{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-' }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        {{ $log->created_at ? $log->created_at->diffForHumans() : '' }}
                                    </div>
                                </td>
                                <td>
                                    @if($log->user)
                                        <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-primary);">
                                            {{ $log->user->name }}
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                            &#64;{{ $log->user->username }}
                                        </div>
                                    @else
                                        <span class="badge badge-secondary">System / Guest</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->module === 'AUTH')
                                        <span class="badge badge-info">AUTH</span>
                                    @elseif($log->module === 'SETTINGS')
                                        <span class="badge badge-warning">SETTINGS</span>
                                    @elseif($log->module === 'ANTI_CHEAT')
                                        <span class="badge badge-danger">ANTI_CHEAT</span>
                                    @elseif($log->module === 'BACKUP')
                                        <span class="badge badge-success">BACKUP</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $log->module ?? 'GENERAL' }}</span>
                                    @endif
                                </td>
                                <td>
                                    <code style="font-size: 0.8rem; font-weight: 600; background: var(--bg-main); padding: 2px 6px; border-radius: 4px; border: 1px solid var(--border-color); color: var(--text-primary);">
                                        {{ $log->action }}
                                    </code>
                                </td>
                                <td style="font-size: 0.85rem; color: var(--text-secondary); font-family: monospace;">
                                    {{ $log->ip_address ?? '-' }}
                                </td>
                                <td>
                                    @php
                                        $decoded = json_decode($log->details ?? '', true);
                                    @endphp
                                    @if(is_array($decoded))
                                        <div style="font-family: monospace; font-size: 0.75rem; background: var(--bg-main); padding: 6px 10px; border-radius: 4px; border: 1px solid var(--border-color); max-width: 400px; word-break: break-all;">
                                            @foreach($decoded as $k => $v)
                                                <div><strong>{{ $k }}:</strong> {{ is_array($v) ? json_encode($v) : $v }}</div>
                                            @endforeach
                                        </div>
                                    @elseif(!empty($log->details))
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                            {{ $log->details }}
                                        </div>
                                    @else
                                        <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="pagination-wrapper" style="margin-top: 16px;">
                    {{ $logs->links() }}
                </div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-icon">&#128220;</div>
                <div class="empty-title">Tidak ada log aktivitas ditemukan</div>
                <div class="empty-desc">Belum ada rekaman log audit yang cocok dengan kriteria filter pencarian.</div>
            </div>
        @endif
    </div>

</div>
@endsection
