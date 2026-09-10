<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Nilai Ujian - {{ $exam->title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #111;
            font-size: 12px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0 0 6px;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 14px;
            margin: 0 0 6px;
            font-weight: normal;
        }
        .info-table {
            width: 100%;
            margin-bottom: 16px;
        }
        .info-table td {
            padding: 4px 8px;
            font-size: 12px;
        }
        .stats-box {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            background: #f4f4f4;
            padding: 12px;
            border-radius: 4px;
        }
        .stat-item {
            flex: 1;
            text-align: center;
        }
        .stat-item .val {
            font-size: 16px;
            font-weight: bold;
        }
        .stat-item .lbl {
            font-size: 10px;
            color: #555;
            text-transform: uppercase;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
            font-size: 11px;
        }
        table.data-table th {
            background-color: #eee;
            font-weight: bold;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .sig-box {
            text-align: center;
            width: 200px;
        }
        .sig-line {
            margin-top: 60px;
            border-bottom: 1px solid #000;
        }
        .no-print {
            margin-bottom: 20px;
            background: #e0f2fe;
            padding: 10px 16px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            background: #2563eb;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <span>Pratinjau Dokumen Cetak / PDF Laporan Nilai CBT</span>
        <div>
            <button class="btn" onclick="window.print()">Cetak / Simpan ke PDF</button>
            <button class="btn" style="background: #64748b; margin-left: 8px;" onclick="window.close()">Tutup</button>
        </div>
    </div>

    <div class="header">
        <h1>LAPORAN HASIL NILAI UJIAN BERBASIS KOMPUTER (CBT)</h1>
        <h2>{{ $exam->title }}</h2>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 15%;"><strong>Mata Pelajaran</strong></td>
            <td style="width: 35%;">: {{ $exam->subject->name ?? '-' }}</td>
            <td style="width: 15%;"><strong>KKM</strong></td>
            <td style="width: 35%;">: {{ number_format($statistics['passing_score'], 1) }}</td>
        </tr>
        <tr>
            <td><strong>Total Soal</strong></td>
            <td>: {{ $exam->exam_questions_count }} Butir Soal</td>
            <td><strong>Tanggal Cetak</strong></td>
            <td>: {{ $printedAt->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Penguji / Pembuat</strong></td>
            <td>: {{ $exam->creator->name ?? 'Administrator' }}</td>
            <td><strong>Total Peserta</strong></td>
            <td>: {{ $statistics['total_graded'] }} Siswa (Lulus: {{ $statistics['passed_count'] }} / {{ $statistics['pass_percentage'] }}%)</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th>NIS</th>
                <th>Nama Peserta</th>
                <th>Kelas</th>
                <th style="width: 45px;">Benar</th>
                <th style="width: 45px;">Salah</th>
                <th style="width: 45px;">Kosong</th>
                <th style="width: 70px;">Nilai Akhir</th>
                <th style="width: 80px;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($results as $index => $r)
                @php
                    $score = (float) $r->final_score;
                    $isPassed = ($score >= $statistics['passing_score']);
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $r->student->nis ?? '-' }}</td>
                    <td>{{ $r->student->user->name ?? '-' }}</td>
                    <td class="text-center">{{ $r->student->schoolClass->name ?? '-' }}</td>
                    <td class="text-center">{{ $r->correct_count }}</td>
                    <td class="text-center">{{ $r->wrong_count }}</td>
                    <td class="text-center">{{ $r->unanswered_count }}</td>
                    <td class="text-center" style="font-weight: bold;">{{ number_format($score, 2) }}</td>
                    <td class="text-center" style="font-weight: bold; color: {{ $isPassed ? '#166534' : '#991b1b' }};">
                        {{ $isPassed ? 'LULUS' : 'TIDAK LULUS' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Belum ada hasil nilai siswa.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div class="sig-box">
            Mengetahui,<br>Kepala Sekolah
            <div class="sig-line"></div>
        </div>
        <div class="sig-box">
            Guru Pengampu Mata Pelajaran,
            <div class="sig-line"></div>
            {{ $exam->creator->name ?? 'Guru CBT' }}
        </div>
    </div>
</body>
</html>
