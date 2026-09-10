@extends('layouts.app')

@section('title', 'Laporan Statistik Ujian: ' . $exam->title . ' — CBT Guru')
@section('page-title', 'Laporan Statistik Ujian Guru')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- TOP HEADER -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                {{ $exam->title }}
            </h2>
            <div style="font-size: 0.85rem; color: var(--text-secondary); display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <span class="badge badge-info">{{ $exam->subject->name ?? '-' }}</span>
                <span>KKM Kelulusan: <strong>{{ number_format($statistics['passing_score'], 1) }}</strong></span>
                <span>Total: <strong>{{ $exam->exam_questions_count }}</strong> Butir Soal</span>
            </div>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="{{ route('guru.reports.exam.export_csv', $exam->id) }}" class="btn btn-primary btn-sm">
                📥 Unduh Excel (CSV)
            </a>
            <a href="{{ route('guru.reports.exam.export_pdf', $exam->id) }}" target="_blank" class="btn btn-secondary btn-sm">
                🖨️ Cetak / PDF
            </a>
            <a href="{{ route('guru.reports.item-analysis', $exam->id) }}" class="btn btn-secondary btn-sm">
                🔍 Analisis Butir Soal
            </a>
            <a href="{{ route('guru.reports.index') }}" class="btn btn-secondary btn-sm">
                &larr; Kembali ke Daftar Laporan
            </a>
        </div>
    </div>

    <!-- STATS OVERVIEW CARDS -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--primary-light); color: var(--primary);">&#128101;</div>
            <div class="stat-value">{{ $statistics['total_participants'] }}</div>
            <div class="stat-label">Total Peserta Terdaftar</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--bg-main); color: var(--text-secondary);">&#9997;</div>
            <div class="stat-value">{{ $statistics['total_graded'] }}</div>
            <div class="stat-label">Total Siswa Dinilai</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--success-light); color: var(--success);">&#9989;</div>
            <div class="stat-value" style="color: var(--success);">
                {{ $statistics['passed_count'] }}
                <span style="font-size: 0.85rem; font-weight: 500;">({{ $statistics['pass_percentage'] }}%)</span>
            </div>
            <div class="stat-label">Tuntas (Lulus KKM)</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--danger-light); color: var(--danger);">&#10060;</div>
            <div class="stat-value" style="color: var(--danger);">
                {{ $statistics['failed_count'] }}
            </div>
            <div class="stat-label">Belum Lulus KKM</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--warning-light); color: var(--warning);">&#128200;</div>
            <div class="stat-value" style="color: var(--warning);">
                {{ number_format($statistics['average_score'], 1) }}
            </div>
            <div class="stat-label">Rata-rata Nilai</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--primary-light); color: var(--primary);">&#127942;</div>
            <div class="stat-value" style="font-size: 1.25rem;">
                {{ number_format($statistics['highest_score'], 1) }} / {{ number_format($statistics['lowest_score'], 1) }}
            </div>
            <div class="stat-label">Tertinggi / Terendah</div>
        </div>
    </div>

    <!-- STUDENT BREAKDOWN TABLE CARD -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
            <h3 class="card-title" style="margin-bottom: 0;">Rincian Nilai Hasil Siswa</h3>

            <form method="GET" action="{{ route('guru.reports.exam', $exam->id) }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <input 
                    type="text" 
                    name="search" 
                    class="form-control" 
                    placeholder="Cari nama atau NIS siswa..." 
                    value="{{ request('search') }}"
                    style="max-width: 220px;"
                >
                <select name="class_id" class="form-select" onchange="this.form.submit()" style="max-width: 180px;">
                    <option value="">Semua Kelas</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-secondary">Filter</button>
                @if(request('search') || request('class_id'))
                    <a href="{{ route('guru.reports.exam', $exam->id) }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>

        @if($results->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>NIS / Nama Siswa</th>
                            <th style="width: 130px;">Kelas</th>
                            <th style="width: 90px;">Benar</th>
                            <th style="width: 90px;">Salah</th>
                            <th style="width: 90px;">Kosong</th>
                            <th style="width: 110px;">Nilai Akhir</th>
                            <th style="width: 130px; text-align: right;">Status KKM</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $idx => $r)
                            @php
                                $score = (float) $r->final_score;
                                $isPassed = ($score >= $statistics['passing_score']);
                            @endphp
                            <tr>
                                <td>{{ $results->firstItem() + $idx }}</td>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-primary);">
                                        {{ $r->student->user->name ?? '-' }}
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                                        NIS: {{ $r->student->nis ?? '-' }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-secondary">{{ $r->student->schoolClass->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <span style="color: var(--success); font-weight: 600;">{{ $r->correct_count }}</span>
                                </td>
                                <td>
                                    <span style="color: var(--danger); font-weight: 600;">{{ $r->wrong_count }}</span>
                                </td>
                                <td>
                                    <span style="color: var(--text-muted);">{{ $r->unanswered_count }}</span>
                                </td>
                                <td>
                                    <strong style="font-size: 1.05rem; color: var(--primary);">
                                        {{ number_format($score, 1) }}
                                    </strong>
                                </td>
                                <td style="text-align: right;">
                                    @if($isPassed)
                                        <span class="badge badge-success">LULUS</span>
                                    @else
                                        <span class="badge badge-danger">BELUM LULUS</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($results->hasPages())
                <div class="pagination-wrapper" style="margin-top: 16px;">
                    {{ $results->links() }}
                </div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-icon">&#128221;</div>
                <div class="empty-title">Belum ada hasil nilai siswa</div>
                <div class="empty-desc">Data nilai akan tampil di sini setelah peserta menyelesaikan pengerjaan ujian Anda.</div>
            </div>
        @endif
    </div>

</div>
@endsection
