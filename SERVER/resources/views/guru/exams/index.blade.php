@extends('layouts.app')

@section('title', 'Paket Ujian Saya - CBT Guru')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Paket Ujian Saya</h1>
        <p class="page-subtitle">Kelola jadwal ujian, butir soal, dan daftar siswa yang Anda ampu</p>
    </div>
    <a href="{{ route('guru.exams.create') }}" class="btn btn-primary">+ Buat Paket Ujian</a>
</div>

<div class="card">
    <form method="GET" action="{{ route('guru.exams.index') }}" class="search-filter-bar">
        <div class="search-input-group">
            <span class="search-icon">&#128269;</span>
            <input type="text" name="search" class="form-control" placeholder="Cari judul ujian..." value="{{ request('search') }}">
        </div>
        <div class="filter-select-group">
            <select name="subject_id" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Mapel</option>
                @foreach($subjects as $sb)
                    <option value="{{ $sb->id }}" {{ request('subject_id') == $sb->id ? 'selected' : '' }}>
                        {{ $sb->name }}
                    </option>
                @endforeach
            </select>
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        @if(request('search') || request('subject_id') || request('status'))
            <a href="{{ route('guru.exams.index') }}" class="btn btn-secondary">Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Judul Ujian & Mapel</th>
                    <th>Jadwal Pelaksanaan</th>
                    <th>Durasi / Token</th>
                    <th>Soal / Peserta</th>
                    <th>Status</th>
                    <th style="width: 200px; text-align: center;">Aksi</th>
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
                            </div>
                        </td>
                        <td style="font-size: 0.85rem;">
                            <div>Mulai: <strong>{{ $ex->start_window ? $ex->start_window->format('d/m/Y H:i') : '-' }}</strong></div>
                            <div style="color: var(--color-slate-500);">Selesai: {{ $ex->end_window ? $ex->end_window->format('d/m/Y H:i') : '-' }}</div>
                        </td>
                        <td>
                            <div>{{ $ex->duration_minutes }} Menit</div>
                            @if($ex->token)
                                <span class="badge badge-secondary" style="letter-spacing: 1px;">TOKEN: {{ $ex->token }}</span>
                            @else
                                <span style="font-size: 0.8rem; color: var(--color-slate-400);">Tanpa Token</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-size: 0.85rem;">
                                <strong>{{ $ex->questions_count }}</strong> Butir Soal<br>
                                <strong>{{ $ex->participants_count }}</strong> Peserta
                            </div>
                        </td>
                        <td>
                            @if($ex->status === 'active')
                                <span class="badge badge-success">Aktif</span>
                            @elseif($ex->status === 'published')
                                <span class="badge badge-info">Published</span>
                            @elseif($ex->status === 'completed')
                                <span class="badge badge-secondary">Selesai</span>
                            @else
                                <span class="badge badge-warning">Draft</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div style="display: inline-flex; gap: 4px; flex-wrap: wrap; justify-content: center;">
                                <a href="{{ route('guru.exams.show', $ex->id) }}" class="btn btn-sm btn-primary" title="Kelola Soal & Peserta">Detail</a>
                                <a href="{{ route('guru.exams.edit', $ex->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                                <form method="POST" action="{{ route('guru.exams.destroy', $ex->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus paket ujian ini?');" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" {{ $ex->attempts_count > 0 ? 'disabled title="Ujian sudah memiliki riwayat pengerjaan"' : '' }}>Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">
                            <div class="empty-icon">&#9201;</div>
                            <div class="empty-title">Belum ada paket ujian yang Anda buat</div>
                            <div class="empty-desc">Klik tombol "Buat Paket Ujian" di atas untuk membuat jadwal sesi ujian baru.</div>
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
