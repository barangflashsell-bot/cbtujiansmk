@extends('layouts.app')

@section('title', 'Manajemen Backup Database — CBT Administrator')
@section('page-title', 'Backup & Database Snapshot')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- TOP ACTION HEADER -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                Pencadangan Database MySQL Lokal
            </h2>
            <p style="font-size: 0.85rem; color: var(--text-secondary);">
                Snapshot DDL & DML internal murni PDO server lokal, tanpa eksekusi shell command luar.
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('createBackupCard').style.display = 'block'; window.scrollTo({top: document.getElementById('createBackupCard').offsetTop - 80, behavior: 'smooth'});">
            <span>💾</span> Buat Cadangan Baru
        </button>
    </div>

    <!-- STATS CARDS -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--primary-light); color: var(--primary);">&#128190;</div>
            <div class="stat-value">{{ $totalBackups }}</div>
            <div class="stat-label">Total Berkas Snapshot</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--success-light); color: var(--success);">&#128193;</div>
            <div class="stat-value">{{ $totalStorageHuman }}</div>
            <div class="stat-label">Total Penggunaan Storage</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--warning-light); color: var(--warning);">&#128337;</div>
            <div class="stat-value" style="font-size: 1.15rem; margin-top: 4px;">
                {{ $latestBackup ? \Carbon\Carbon::parse($latestBackup['created_at'])->format('d M Y H:i') : '-' }}
            </div>
            <div class="stat-label">Snapshot Terakhir Dibuat</div>
        </div>
    </div>

    <!-- CREATE BACKUP CARD (HIDDEN BY DEFAULT) -->
    <div class="card" id="createBackupCard" style="display: none; border: 2px solid var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="card-title" style="margin-bottom: 0;">Trigger Pembuatan Snapshot Database Baru</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('createBackupCard').style.display = 'none';">&times; Tutup</button>
        </div>
        <form method="POST" action="{{ route('admin.backups.create') }}">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Skenario Operasional Backup</label>
                    <select name="type" class="form-select" required>
                        <option value="manual">Cadangan Manual Rutin (manual)</option>
                        <option value="pre_exam">Cadangan Pra-Ujian / Master Data Siap (pre_exam)</option>
                        <option value="post_exam">Cadangan Pasca-Ujian / Selesai Sesi Pengerjaan (post_exam)</option>
                    </select>
                    <span style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; display: block;">
                        Proses dump dieksekusi murni via koneksi database internal dan disimpan aman di folder non-publik server.
                    </span>
                </div>
                <button type="submit" class="btn btn-primary" onclick="this.innerHTML='Sedang Mencadangkan...'; this.disabled=true; this.form.submit();">
                    Mulai Backup Sekarang
                </button>
            </div>
        </form>
    </div>

    <!-- BACKUP LIST TABLE -->
    <div class="card">
        <h3 class="card-title" style="margin-bottom: 16px;">Daftar Berkas Cadangan (.sql)</h3>

        @if(count($backups) > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Berkas Snapshot</th>
                            <th style="width: 130px;">Tipe</th>
                            <th style="width: 110px;">Ukuran</th>
                            <th style="width: 170px;">Waktu Dibuat</th>
                            <th style="width: 180px;">Integritas SHA-256</th>
                            <th style="width: 160px; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backups as $idx => $b)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    <div style="font-weight: 600; font-family: monospace; font-size: 0.9rem; color: var(--text-primary);">
                                        {{ $b['filename'] }}
                                    </div>
                                </td>
                                <td>
                                    @if($b['type'] === 'pre_exam')
                                        <span class="badge badge-warning">Pra-Ujian</span>
                                    @elseif($b['type'] === 'post_exam')
                                        <span class="badge badge-success">Pasca-Ujian</span>
                                    @else
                                        <span class="badge badge-info">Manual</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $b['size_human'] }}</strong>
                                </td>
                                <td style="font-size: 0.85rem; color: var(--text-secondary);">
                                    {{ \Carbon\Carbon::parse($b['created_at'])->format('d/m/Y H:i:s') }}
                                </td>
                                <td>
                                    <span style="font-family: monospace; font-size: 0.75rem; background: var(--bg-main); padding: 2px 6px; border-radius: 4px; border: 1px solid var(--border-color);" title="{{ $b['checksum_sha256'] }}">
                                        {{ substr($b['checksum_sha256'], 0, 16) }}...
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                        <a href="{{ route('admin.backups.download', $b['filename']) }}" class="btn btn-sm btn-primary" title="Unduh berkas .sql">
                                            &#11015; Unduh
                                        </a>
                                        <form method="POST" action="{{ route('admin.backups.destroy', $b['filename']) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus berkas cadangan database ini?');" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus berkas snapshot">
                                                &#128465;
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <div class="empty-icon">&#128190;</div>
                <div class="empty-title">Belum ada berkas cadangan database</div>
                <div class="empty-desc">Klik tombol "Buat Cadangan Baru" di atas untuk membuat snapshot pertama database CBT Anda.</div>
            </div>
        @endif
    </div>

</div>
@endsection
