@extends('layouts.app')

@section('title', 'Bank Soal - CBT Administrator')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Bank Soal</h1>
        <p class="page-subtitle">Kelola butir soal ujian, pilihan jawaban, bobot nilai, dan kunci jawaban</p>
    </div>
    <a href="{{ route('admin.questions.create') }}" class="btn btn-primary">+ Buat Soal Baru</a>
</div>

<div class="card">
    <form method="GET" action="{{ route('admin.questions.index') }}" class="search-filter-bar">
        <div class="search-input-group">
            <span class="search-icon">&#128269;</span>
            <input type="text" name="search" class="form-control" placeholder="Cari isi butir soal..." value="{{ request('search') }}">
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
            <select name="type" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Tipe</option>
                <option value="single_choice" {{ request('type') == 'single_choice' ? 'selected' : '' }}>Pilihan Ganda</option>
                <option value="multiple_choice" {{ request('type') == 'multiple_choice' ? 'selected' : '' }}>Pilihan Majemuk</option>
                <option value="essay" {{ request('type') == 'essay' ? 'selected' : '' }}>Uraian / Essay</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        @if(request('search') || request('subject_id') || request('type'))
            <a href="{{ route('admin.questions.index') }}" class="btn btn-secondary">Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Mata Pelajaran</th>
                    <th>Tipe & Tingkat</th>
                    <th>Isi Butir Soal</th>
                    <th>Bobot</th>
                    <th>Pembuat</th>
                    <th style="width: 140px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($questions as $idx => $q)
                    <tr>
                        <td>{{ $questions->firstItem() + $idx }}</td>
                        <td>
                            <span class="badge badge-info">{{ $q->subject->name ?? '-' }}</span>
                        </td>
                        <td>
                            <div style="font-weight: 600; font-size: 0.85rem;">
                                @if($q->question_type === 'single_choice')
                                    Pilihan Ganda
                                @elseif($q->question_type === 'multiple_choice')
                                    Pilihan Majemuk
                                @else
                                    Uraian / Essay
                                @endif
                            </div>
                            <div style="margin-top: 4px;">
                                @if($q->difficulty === 'easy')
                                    <span class="badge badge-success">Mudah</span>
                                @elseif($q->difficulty === 'medium')
                                    <span class="badge badge-warning">Sedang</span>
                                @else
                                    <span class="badge badge-danger">Sulit</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="max-height: 80px; overflow: hidden; text-overflow: ellipsis;">
                                {{ Str::limit(strip_tags($q->content), 120) }}
                            </div>
                            @if($q->options->count() > 0)
                                <div style="margin-top: 6px; font-size: 0.8rem; color: var(--color-slate-500);">
                                    {{ $q->options->count() }} Pilihan Jawaban 
                                    @php
                                        $correct = $q->options->where('is_correct', true)->pluck('option_label')->implode(', ');
                                    @endphp
                                    @if($correct)
                                        | Kunci: <strong style="color: var(--color-emerald-600);">{{ $correct }}</strong>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $q->score_weight }}</strong>
                        </td>
                        <td>
                            <span style="font-size: 0.85rem; color: var(--color-slate-600);">
                                {{ $q->creator->name ?? 'Admin' }}
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: inline-flex; gap: 6px;">
                                <a href="{{ route('admin.questions.edit', $q->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.questions.destroy', $q->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus butir soal ini?');" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">
                            <div class="empty-icon">&#128221;</div>
                            <div class="empty-title">Belum ada butir soal</div>
                            <div class="empty-desc">Klik tombol "Buat Soal Baru" di atas untuk menambahkan bank soal.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($questions->hasPages())
        <div class="pagination-wrapper">
            {{ $questions->links() }}
        </div>
    @endif
</div>
@endsection
