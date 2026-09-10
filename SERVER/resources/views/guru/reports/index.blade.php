@extends('layouts.app')

@section('title', 'Laporan & Analisis Akademik — CBT Guru')
@section('page-title', 'Laporan & Analisis Akademik Guru')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- TOP HEADER -->
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
            Rekapitulasi & Analisis Hasil Ujian Saya
        </h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">
            Laporan statistik nilai, rekapitulasi kelas, dan analisis butir soal khusus untuk paket ujian yang Anda buat.
        </p>
    </div>

    <!-- SECTION 1: LAPORAN PER PAKET UJIAN -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
            <h3 class="card-title" style="margin-bottom: 0;">Laporan Statistik Paket Ujian</h3>
            
            <form method="GET" action="{{ route('guru.reports.index') }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <input 
                    type="text" 
                    name="search" 
                    class="form-control" 
                    placeholder="Cari judul ujian..." 
                    value="{{ request('search') }}"
                    style="max-width: 220px;"
                >
                <select name="subject_id" class="form-select" onchange="this.form.submit()" style="max-width: 180px;">
                    <option value="">Semua Mapel</option>
                    @foreach($subjects as $s)
                        <option value="{{ $s->id }}" {{ request('subject_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-secondary">Filter</button>
                @if(request('search') || request('subject_id'))
                    <a href="{{ route('guru.reports.index') }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>

        @if($exams->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Paket Ujian & Mata Pelajaran</th>
                            <th style="width: 130px;">Jumlah Soal</th>
                            <th style="width: 140px;">Peserta Terdaftar</th>
                            <th style="width: 120px;">Sesi Attempt</th>
                            <th style="width: 100px;">KKM</th>
                            <th style="width: 260px; text-align: right;">Aksi Laporan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exams as $idx => $exam)
                            <tr>
                                <td>{{ $exams->firstItem() + $idx }}</td>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-primary);">{{ $exam->title }}</div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                        Mapel: {{ $exam->subject->name ?? '-' }}
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $exam->exam_questions_count }}</strong> Soal
                                </td>
                                <td>
                                    <strong>{{ $exam->participants_count }}</strong> Siswa
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $exam->attempts_count }} Sesi</span>
                                </td>
                                <td>
                                    <span class="badge badge-secondary">{{ number_format((float) ($exam->passing_score ?? 75), 1) }}</span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                        <a href="{{ route('guru.reports.exam', $exam->id) }}" class="btn btn-sm btn-primary" title="Lihat Rekapitulasi Statistik & Nilai Siswa">
                                            📊 Statistik Nilai
                                        </a>
                                        <a href="{{ route('guru.reports.item-analysis', $exam->id) }}" class="btn btn-sm btn-secondary" title="Analisis Tingkat Kesukaran Soal">
                                            🔍 Analisis Soal
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($exams->hasPages())
                <div class="pagination-wrapper" style="margin-top: 16px;">
                    {{ $exams->links() }}
                </div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-icon">&#128202;</div>
                <div class="empty-title">Belum ada paket ujian Anda yang ditemukan</div>
                <div class="empty-desc">Buat paket ujian baru terlebih dahulu pada menu Ujian untuk melihat laporan akademik.</div>
            </div>
        @endif
    </div>

    <!-- SECTION 2: LAPORAN PER KELAS / ROMBEL -->
    <div class="card">
        <h3 class="card-title" style="margin-bottom: 12px;">Laporan Rekapitulasi Nilai per Rombel / Kelas</h3>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">
            Pilih rombel/kelas di bawah ini untuk melihat perolehan skor siswa pada paket ujian yang Anda buat:
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
            @forelse($classes as $c)
                <a href="{{ route('guru.reports.class', $c->id) }}" style="text-decoration: none; color: inherit; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; transition: border-color 0.15s ease, transform 0.15s ease;" onmouseover="this.style.borderColor='var(--primary)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.borderColor='var(--border-color)'; this.style.transform='none';">
                    <div>
                        <div style="font-weight: 700; font-size: 1rem; color: var(--text-primary);">{{ $c->name }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">Tingkat: {{ $c->level ?? '-' }}</div>
                    </div>
                    <span style="font-size: 1.2rem; color: var(--primary);">&#8594;</span>
                </a>
            @empty
                <div style="font-size: 0.85rem; color: var(--text-muted);">Belum ada rombel kelas terdaftar.</div>
            @endforelse
        </div>
    </div>

</div>
@endsection
