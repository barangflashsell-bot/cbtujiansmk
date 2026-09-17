@extends('layouts.app')

@section('title', 'Rekap Hasil Ujian - CBT Administrator')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Hasil & Nilai Ujian</h1>
        <p class="page-subtitle">Rekapitulasi skor penilaian, status kelulusan, dan publikasi nilai peserta</p>
    </div>
</div>

<div class="card">
    <form method="GET" action="{{ route('admin.results.index') }}" class="search-filter-bar">
        <div class="search-input-group">
            <span class="search-icon">&#128269;</span>
            <input type="text" name="search" class="form-control" placeholder="Cari NIS atau nama peserta..." value="{{ request('search') }}">
        </div>
        <div class="filter-select-group">
            <select name="exam_id" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Paket Ujian</option>
                @foreach($exams as $ex)
                    <option value="{{ $ex->id }}" {{ request('exam_id') == $ex->id ? 'selected' : '' }}>
                        {{ $ex->title }}
                    </option>
                @endforeach
            </select>
            <select name="class_id" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>
            <select name="is_published" class="form-select" onchange="this.form.submit()">
                <option value="">Status Publikasi</option>
                <option value="1" {{ request('is_published') === '1' ? 'selected' : '' }}>Dipublikasikan</option>
                <option value="0" {{ request('is_published') === '0' ? 'selected' : '' }}>Belum Dipublikasikan</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        @if(request('search') || request('exam_id') || request('class_id') || request('is_published') !== null)
            <a href="{{ route('admin.results.index') }}" class="btn btn-secondary">Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Peserta / Kelas</th>
                    <th>Paket Ujian</th>
                    <th style="text-align: center;">Token Ujian</th>
                    <th>Benar / Salah / Kosong</th>
                    <th>Nilai Akhir</th>
                    <th>Kelulusan</th>
                    <th>Publikasi</th>
                    <th style="width: 160px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($results as $idx => $r)
                    @php
                        $passing = (float) ($r->exam->passing_score ?? 75.0);
                        $isPassed = ((float) $r->final_score >= $passing);
                    @endphp
                    <tr>
                        <td>{{ $results->firstItem() + $idx }}</td>
                        <td>
                            <div style="font-weight: 600;">{{ $r->student->user->name ?? '-' }}</div>
                            <div style="font-size: 0.8rem; color: var(--color-slate-500);">
                                NIS: {{ $r->student->nis ?? '-' }} | {{ $r->student->schoolClass->name ?? '-' }}
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500;">{{ $r->exam->title ?? '-' }}</div>
                            <span class="badge badge-info" style="font-size: 0.75rem;">{{ $r->exam->subject->name ?? '-' }}</span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-secondary" style="font-family: monospace; font-weight: 700; letter-spacing: 1px;">
                                {{ $r->exam->token ?? 'WXYZ89' }}
                            </span>
                        </td>
                        <td style="font-size: 0.85rem;">
                            <span style="color: var(--color-emerald-600); font-weight: 600;">{{ $r->correct_count }} Benar</span>,
                            <span style="color: var(--color-rose-600);">{{ $r->wrong_count }} Salah</span>,
                            <span style="color: var(--color-slate-500);">{{ $r->unanswered_count }} Kosong</span>
                        </td>
                        <td>
                            <strong style="font-size: 1.15rem; color: var(--color-slate-900);">
                                {{ number_format((float) $r->final_score, 1) }}
                            </strong>
                        </td>
                        <td>
                            @if($isPassed)
                                <span class="badge badge-success">Lulus</span>
                            @else
                                <span class="badge badge-danger">Belum Lulus</span>
                            @endif
                        </td>
                        <td>
                            @if($r->is_published)
                                <span class="badge badge-success">Publik</span>
                            @else
                                <span class="badge badge-secondary">Private</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div style="display: inline-flex; gap: 4px;">
                                <a href="{{ route('admin.results.show', $r->id) }}" class="btn btn-sm btn-secondary">Detail</a>
                                <form method="POST" action="{{ route('admin.results.publish', $r->id) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $r->is_published ? 'btn-secondary' : 'btn-primary' }}" title="Ubah status publikasi">
                                        {{ $r->is_published ? 'Tarik' : 'Publikasi' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">
                            <div class="empty-icon">&#127919;</div>
                            <div class="empty-title">Belum ada hasil penilaian ujian</div>
                            <div class="empty-desc">Hasil penilaian ujian yang telah diselesaikan peserta akan tercatat di sini.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($results->hasPages())
        <div class="pagination-wrapper">
            {{ $results->links() }}
        </div>
    @endif
</div>
@endsection
