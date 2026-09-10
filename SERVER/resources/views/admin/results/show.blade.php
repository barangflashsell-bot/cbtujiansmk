@extends('layouts.app')

@section('title', 'Detail Hasil Ujian - CBT Administrator')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Detail Hasil Ujian #{{ $result->id }}</h1>
        <p class="page-subtitle">Rincian lembar pengerjaan dan penilaian siswa</p>
    </div>
    <div style="display: flex; gap: 8px;">
        <form method="POST" action="{{ route('admin.results.publish', $result->id) }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-sm {{ $result->is_published ? 'btn-secondary' : 'btn-primary' }}">
                {{ $result->is_published ? 'Tarik dari Publikasi' : 'Publikasikan ke Siswa' }}
            </button>
        </form>
        <a href="{{ route('admin.results.index') }}" class="btn btn-secondary btn-sm">&larr; Kembali ke Rekap</a>
    </div>
</div>

<!-- STUDENT & EXAM SUMMARY -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-label">Nama Siswa</div>
        <div class="stat-value" style="font-size: 1.15rem; margin-top: 4px;">{{ $result->student->user->name ?? '-' }}</div>
        <div style="font-size: 0.8rem; color: var(--color-slate-500); margin-top: 4px;">
            NIS: {{ $result->student->nis ?? '-' }} | Kelas: {{ $result->student->schoolClass->name ?? '-' }}
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Paket Ujian</div>
        <div class="stat-value" style="font-size: 1.15rem; margin-top: 4px;">{{ $result->exam->title ?? '-' }}</div>
        <div style="font-size: 0.8rem; color: var(--color-slate-500); margin-top: 4px;">
            Mapel: {{ $result->exam->subject->name ?? '-' }}
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Nilai Akhir</div>
        <div class="stat-value" style="font-size: 1.8rem; color: var(--color-primary-700); margin-top: 4px;">
            {{ number_format((float) $result->final_score, 1) }}
        </div>
        <div style="font-size: 0.8rem; margin-top: 4px;">
            @php
                $passing = (float) ($result->exam->passing_score ?? 75.0);
                $isPassed = ((float) $result->final_score >= $passing);
            @endphp
            KKM: {{ $passing }} | 
            @if($isPassed)
                <span style="color: var(--color-emerald-600); font-weight: 600;">LULUS</span>
            @else
                <span style="color: var(--color-rose-600); font-weight: 600;">BELUM LULUS</span>
            @endif
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Statistik Jawaban</div>
        <div style="margin-top: 8px; font-size: 0.9rem;">
            <div style="color: var(--color-emerald-600); font-weight: 600;">&#10004; Benar: {{ $result->correct_count }}</div>
            <div style="color: var(--color-rose-600); font-weight: 600; margin-top: 2px;">&#10008; Salah: {{ $result->wrong_count }}</div>
            <div style="color: var(--color-slate-500); margin-top: 2px;">&#9898; Kosong: {{ $result->unanswered_count }}</div>
        </div>
    </div>
</div>

<!-- ATTEMPT ANSWERS BREAKDOWN -->
<div class="card">
    <h2 style="font-size: 1.1rem; font-weight: 600; color: var(--color-slate-800); margin-bottom: 16px;">
        Lembar Respon Jawaban Siswa
    </h2>

    @if($result->attempt && $result->attempt->answers->count() > 0)
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Butir Pertanyaan</th>
                        <th style="width: 120px;">Tipe Soal</th>
                        <th style="width: 140px;">Jawaban Siswa</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 80px;">Skor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($result->attempt->answers as $idx => $ans)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <div>{{ Str::limit(strip_tags($ans->question->content ?? '-'), 120) }}</div>
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $ans->question->question_type ?? '-' }}</span>
                            </td>
                            <td>
                                @if($ans->selectedOption)
                                    <strong>{{ $ans->selectedOption->option_label }}.</strong> {{ Str::limit(strip_tags($ans->selectedOption->content), 40) }}
                                @elseif($ans->essay_answer)
                                    <div>{{ Str::limit($ans->essay_answer, 80) }}</div>
                                @else
                                    <span style="color: var(--color-slate-400); font-style: italic;">Tidak dijawab</span>
                                @endif
                            </td>
                            <td>
                                @if($ans->is_correct)
                                    <span class="badge badge-success">Benar</span>
                                @elseif($ans->is_correct === false)
                                    <span class="badge badge-danger">Salah</span>
                                @else
                                    <span class="badge badge-secondary">Menunggu Review</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ number_format((float) ($ans->earned_score ?? 0), 1) }}</strong>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">&#128221;</div>
            <div class="empty-title">Tidak ada rekaman butir jawaban individual</div>
            <div class="empty-desc">Data jawaban terperinci tidak ditemukan pada sesi attempt ini.</div>
        </div>
    @endif
</div>
@endsection
