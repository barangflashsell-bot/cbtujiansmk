@extends('layouts.app')

@section('title', 'Analisis Butir Soal: ' . $exam->title . ' — CBT Guru')
@section('page-title', 'Analisis Butir Soal Guru')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- TOP HEADER -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                Analisis Butir Soal: {{ $exam->title }}
            </h2>
            <div style="font-size: 0.85rem; color: var(--text-secondary); display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <span class="badge badge-info">{{ $exam->subject->name ?? '-' }}</span>
                <span>Total Butir: <strong>{{ count($items) }}</strong> Soal</span>
                <span>Total Sesi Diperiksa: <strong>{{ $totalAttempts }}</strong> Attempt</span>
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('guru.reports.exam', $exam->id) }}" class="btn btn-primary btn-sm">
                📊 Laporan Statistik Nilai
            </a>
            <a href="{{ route('guru.reports.index') }}" class="btn btn-secondary btn-sm">
                &larr; Kembali ke Daftar Laporan
            </a>
        </div>
    </div>

    <!-- CLASSIFICATION LEGEND CARD -->
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-primary);">
            Standar Klasifikasi Tingkat Kesukaran (Difficulty Index):
        </div>
        <div style="display: flex; gap: 12px; font-size: 0.8rem; align-items: center;">
            <span class="badge badge-success">Mudah: &ge; 70.0%</span>
            <span class="badge badge-warning">Sedang: 30.0% &ndash; 69.9%</span>
            <span class="badge badge-danger">Sukar: &lt; 30.0%</span>
        </div>
    </div>

    <!-- ITEM ANALYSIS TABLE -->
    <div class="card">
        <h3 class="card-title" style="margin-bottom: 16px;">Tabel Indeks Kesukaran Soal</h3>

        @if(count($items) > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Pratinjau Butir Pertanyaan</th>
                            <th style="width: 120px;">Tipe Soal</th>
                            <th style="width: 80px;">Bobot</th>
                            <th style="width: 90px;">Terjawab</th>
                            <th style="width: 80px;">Benar</th>
                            <th style="width: 80px;">Salah</th>
                            <th style="width: 130px;">Indeks Kesukaran</th>
                            <th style="width: 110px; text-align: right;">Klasifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $idx => $item)
                            <tr>
                                <td>{{ $item['order_index'] ?? ($idx + 1) }}</td>
                                <td>
                                    <div style="font-size: 0.9rem; color: var(--text-primary);">
                                        {{ $item['content_preview'] ?: 'Soal #' . $item['question_id'] }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-secondary">{{ $item['question_type'] }}</span>
                                </td>
                                <td>
                                    <strong>{{ $item['weight'] }}</strong>
                                </td>
                                <td>{{ $item['total_answered'] }}</td>
                                <td>
                                    <span style="color: var(--success); font-weight: 600;">{{ $item['correct_count'] }}</span>
                                </td>
                                <td>
                                    <span style="color: var(--danger); font-weight: 600;">{{ $item['wrong_count'] }}</span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="flex: 1; background: var(--bg-main); border-radius: 4px; height: 8px; overflow: hidden; border: 1px solid var(--border-color);">
                                            <div style="background: {{ $item['classification'] === 'mudah' ? 'var(--success)' : ($item['classification'] === 'sedang' ? 'var(--warning)' : 'var(--danger)') }}; height: 100%; width: {{ min(100, $item['difficulty_index']) }}%;"></div>
                                        </div>
                                        <strong style="font-size: 0.85rem; width: 45px; text-align: right;">
                                            {{ number_format($item['difficulty_index'], 1) }}%
                                        </strong>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    @if($item['classification'] === 'mudah')
                                        <span class="badge badge-success">Mudah</span>
                                    @elseif($item['classification'] === 'sedang')
                                        <span class="badge badge-warning">Sedang</span>
                                    @else
                                        <span class="badge badge-danger">Sukar</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <div class="empty-icon">&#128269;</div>
                <div class="empty-title">Belum ada butir soal pada ujian ini</div>
                <div class="empty-desc">Lampirkan butir soal dari Bank Soal ke dalam paket ujian ini untuk melihat analisis kesukaran.</div>
            </div>
        @endif
    </div>

</div>
@endsection
