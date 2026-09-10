@extends('layouts.app')

@section('title', 'Detail Paket Ujian - CBT Guru')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">{{ $exam->title }}</h1>
        <p class="page-subtitle">
            <span class="badge badge-info">{{ $exam->subject->name ?? '-' }} ({{ $exam->subject->code ?? '-' }})</span>
            <span style="margin-left: 8px;">Durasi: <strong>{{ $exam->duration_minutes }} Menit</strong></span>
            @if($exam->token)
                <span class="badge badge-secondary" style="margin-left: 8px;">TOKEN: {{ $exam->token }}</span>
            @endif
            <span style="margin-left: 8px;">Status: 
                @if($exam->status === 'active')
                    <span class="badge badge-success">Active</span>
                @elseif($exam->status === 'published')
                    <span class="badge badge-info">Published</span>
                @elseif($exam->status === 'completed')
                    <span class="badge badge-secondary">Completed</span>
                @else
                    <span class="badge badge-warning">Draft</span>
                @endif
            </span>
        </p>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('guru.monitoring.show', $exam->id) }}" class="btn btn-primary">📡 Live Monitoring</a>
        <a href="{{ route('guru.results.index', ['exam_id' => $exam->id]) }}" class="btn btn-secondary">🎯 Rekap Hasil</a>
        <a href="{{ route('guru.exams.edit', $exam->id) }}" class="btn btn-secondary">Edit Ujian</a>
        <a href="{{ route('guru.exams.index') }}" class="btn btn-secondary">&larr; Daftar Ujian</a>
    </div>
</div>

<!-- SECTION 1: BUTIR SOAL UJIAN -->
<div class="card" style="margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--color-slate-800); margin: 0;">
                Butir Soal Ujian ({{ $examQuestions->count() }} Butir)
            </h2>
            <span style="font-size: 0.85rem; color: var(--color-slate-500);">
                Total Bobot: <strong>{{ $examQuestions->sum(fn($q) => $q->pivot->weight ?? 0) }}</strong> poin
            </span>
        </div>
        <button type="button" class="btn btn-sm btn-primary" onclick="toggleElement('attachQuestionForm')">
            + Tambah Soal dari Bank
        </button>
    </div>

    <!-- FORM ATTACH QUESTION (Collapsible) -->
    <div id="attachQuestionForm" style="display: none; padding: 16px; background: var(--color-slate-50); border: 1px solid var(--color-slate-200); border-radius: 8px; margin-bottom: 16px;">
        <h3 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 12px;">Pilih Soal dari Bank Soal Mapel: {{ $exam->subject->name ?? '-' }}</h3>
        @if($availableQuestions->count() > 0)
            <form method="POST" action="{{ route('guru.exams.questions.attach', $exam->id) }}">
                @csrf
                <div class="form-row">
                    <div class="form-group" style="flex: 3;">
                        <label class="form-label" for="question_id">Pilih Butir Pertanyaan</label>
                        <select name="question_id" id="question_id" class="form-select" required>
                            <option value="">-- Pilih Soal --</option>
                            @foreach($availableQuestions as $aq)
                                <option value="{{ $aq->id }}">
                                    [{{ strtoupper($aq->question_type) }}] {{ Str::limit(strip_tags($aq->content), 80) }} (Bobot: {{ $aq->score_weight }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label" for="weight">Bobot Poin</label>
                        <input type="number" step="0.1" min="0.1" name="weight" id="weight" class="form-control" value="2.0" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label" for="order_index">No. Urut</label>
                        <input type="number" min="1" name="order_index" id="order_index" class="form-control" value="{{ $examQuestions->count() + 1 }}">
                    </div>
                </div>
                <div style="margin-top: 12px; display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary btn-sm">Lampirkan ke Ujian</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleElement('attachQuestionForm')">Batal</button>
                </div>
            </form>
        @else
            <div style="font-size: 0.85rem; color: var(--color-slate-600);">
                Tidak ada soal baru yang tersedia di Bank Soal untuk mata pelajaran ini. Silakan tambahkan butir soal terlebih dahulu di menu Bank Soal.
            </div>
        @endif
    </div>

    <!-- QUESTIONS TABLE -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">Urut</th>
                    <th>Tipe Soal</th>
                    <th>Isi Butir Pertanyaan</th>
                    <th style="width: 80px;">Bobot</th>
                    <th style="width: 80px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($examQuestions as $eq)
                    <tr>
                        <td style="font-weight: 600;">{{ $eq->pivot->order_index }}</td>
                        <td>
                            <span class="badge badge-info">{{ $eq->question_type }}</span>
                        </td>
                        <td>
                            <div>{{ Str::limit(strip_tags($eq->content), 120) }}</div>
                            <div style="font-size: 0.8rem; color: var(--color-slate-500); margin-top: 4px;">
                                {{ $eq->options->count() }} Pilihan Jawaban
                            </div>
                        </td>
                        <td><strong>{{ $eq->pivot->weight }}</strong></td>
                        <td style="text-align: center;">
                            <form method="POST" action="{{ route('guru.exams.questions.detach', [$exam->id, $eq->id]) }}" onsubmit="return confirm('Lepaskan butir soal ini dari paket ujian?');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Lepas</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            <div class="empty-icon">&#128221;</div>
                            <div class="empty-title">Belum ada butir soal dalam paket ujian</div>
                            <div class="empty-desc">Klik tombol "+ Tambah Soal dari Bank" di atas untuk memasukkan butir soal.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- SECTION 2: PESERTA UJIAN -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--color-slate-800); margin: 0;">
                Peserta Ujian Terdaftar ({{ $participants->total() }} Siswa)
            </h2>
            <span style="font-size: 0.85rem; color: var(--color-slate-500);">
                Daftarkan siswa per kelas atau secara individual untuk memberikan akses ujian
            </span>
        </div>
        <button type="button" class="btn btn-sm btn-primary" onclick="toggleElement('enrollParticipantForm')">
            + Daftarkan Peserta
        </button>
    </div>

    <!-- FORM ENROLL PARTICIPANT (Collapsible) -->
    <div id="enrollParticipantForm" style="display: none; padding: 16px; background: var(--color-slate-50); border: 1px solid var(--color-slate-200); border-radius: 8px; margin-bottom: 16px;">
        <h3 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 12px;">Daftarkan Siswa ke Ujian Ini</h3>
        <form method="POST" action="{{ route('guru.exams.participants.add', $exam->id) }}">
            @csrf
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label class="form-label" for="class_id">Daftarkan Satu Rombel / Kelas Sekaligus</label>
                    <select name="class_id" id="class_id" class="form-select">
                        <option value="">-- Pilih Kelas untuk Mendaftarkan Seluruh Siswa --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} (Tingkat {{ $c->level }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="margin-top: 12px; display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-sm">Daftarkan Siswa Terpilih</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleElement('enrollParticipantForm')">Batal</button>
            </div>
        </form>
    </div>

    <!-- PARTICIPANTS TABLE -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>NIS</th>
                    <th>Nama Peserta</th>
                    <th>Kelas</th>
                    <th>Hak Ujian Ulang</th>
                    <th style="width: 140px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($participants as $idx => $p)
                    <tr>
                        <td>{{ $participants->firstItem() + $idx }}</td>
                        <td><span class="badge badge-info">{{ $p->student->nis ?? '-' }}</span></td>
                        <td style="font-weight: 500;">{{ $p->student->user->name ?? '-' }}</td>
                        <td><span class="badge badge-secondary">{{ $p->student->schoolClass->name ?? '-' }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('guru.exams.participants.retest', [$exam->id, $p->id]) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $p->allow_retest ? 'btn-primary' : 'btn-secondary' }}" title="Klik untuk mengubah hak ujian ulang">
                                    {{ $p->allow_retest ? 'Ya (Retest Aktif)' : 'Tidak' }}
                                </button>
                            </form>
                        </td>
                        <td style="text-align: center;">
                            <form method="POST" action="{{ route('guru.exams.participants.remove', [$exam->id, $p->id]) }}" onsubmit="return confirm('Hapus siswa ini dari pendaftaran ujian?');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div class="empty-icon">&#128101;</div>
                            <div class="empty-title">Belum ada peserta yang didaftarkan</div>
                            <div class="empty-desc">Klik tombol "+ Daftarkan Peserta" di atas untuk mendaftarkan rombel kelas ke ujian ini.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($participants->hasPages())
        <div class="pagination-wrapper">
            {{ $participants->links() }}
        </div>
    @endif
</div>

<script>
    function toggleElement(id) {
        var el = document.getElementById(id);
        if (el) {
            el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
        }
    }
</script>
@endsection
