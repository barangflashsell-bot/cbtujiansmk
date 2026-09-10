@extends('layouts.app')

@section('title', 'Laporan Rekapitulasi Kelas: ' . $class->name . ' — CBT Administrator')
@section('page-title', 'Laporan Rekapitulasi Kelas')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- TOP HEADER -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                Rekapitulasi Nilai: {{ $class->name }}
            </h2>
            <div style="font-size: 0.85rem; color: var(--text-secondary); display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <span>Tingkat: <strong>{{ $class->level ?? '-' }}</strong></span>
                <span>Tahun Ajaran: <strong>{{ $class->academic_year ?? '-' }}</strong></span>
                <span>Total Siswa: <strong>{{ $class->students_count }}</strong> Siswa</span>
            </div>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary btn-sm">
            &larr; Kembali ke Daftar Laporan
        </a>
    </div>

    <!-- REKAP TABLE CARD -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
            <h3 class="card-title" style="margin-bottom: 0;">Rekapitulasi Perolehan Skor Siswa</h3>

            <form method="GET" action="{{ route('admin.reports.class', $class->id) }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <input 
                    type="text" 
                    name="search" 
                    class="form-control" 
                    placeholder="Cari nama atau NIS siswa..." 
                    value="{{ request('search') }}"
                    style="max-width: 220px;"
                >
                <select name="exam_id" class="form-select" onchange="this.form.submit()" style="max-width: 220px;">
                    <option value="">Semua Paket Ujian</option>
                    @foreach($exams as $e)
                        <option value="{{ $e->id }}" {{ request('exam_id') == $e->id ? 'selected' : '' }}>
                            {{ $e->title }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-secondary">Filter</button>
                @if(request('search') || request('exam_id'))
                    <a href="{{ route('admin.reports.class', $class->id) }}" class="btn btn-secondary">Reset</a>
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
                            <th>Paket Ujian</th>
                            <th style="width: 140px;">Mata Pelajaran</th>
                            <th style="width: 110px;">Nilai Akhir</th>
                            <th style="width: 130px;">Status KKM</th>
                            <th style="width: 150px; text-align: right;">Waktu Dinilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $idx => $r)
                            @php
                                $passingScore = (float) ($r->exam->passing_score ?? 75.00);
                                $score = (float) $r->final_score;
                                $isPassed = ($score >= $passingScore);
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
                                    <div style="font-weight: 500;">{{ $r->exam->title ?? '-' }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $r->exam->subject->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <strong style="font-size: 1.05rem; color: var(--primary);">
                                        {{ number_format($score, 1) }}
                                    </strong>
                                </td>
                                <td>
                                    @if($isPassed)
                                        <span class="badge badge-success">LULUS (KKM {{ $passingScore }})</span>
                                    @else
                                        <span class="badge badge-danger">BELUM LULUS (KKM {{ $passingScore }})</span>
                                    @endif
                                </td>
                                <td style="text-align: right; font-size: 0.85rem; color: var(--text-secondary);">
                                    {{ $r->graded_at ? \Carbon\Carbon::parse($r->graded_at)->format('d/m/Y H:i') : '-' }}
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
                <div class="empty-icon">&#127891;</div>
                <div class="empty-title">Belum ada rekaman nilai pada kelas ini</div>
                <div class="empty-desc">Siswa pada rombel ini belum memiliki riwayat nilai dari paket ujian terpilih.</div>
            </div>
        @endif
    </div>

</div>
@endsection
