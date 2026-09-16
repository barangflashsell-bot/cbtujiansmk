@extends('layouts.app')

@section('title', 'Detail Paket Ujian - CBT Administrator')

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
        <button type="button" class="btn btn-primary" onclick="openScheduleModal()" style="background: #10b981; border-color: #059669;">
            ⚙️ Setting Waktu Ujian
        </button>
        <a href="{{ route('admin.exams.export', $exam->id) }}" class="btn btn-secondary" style="background: #3b82f6; color: #fff; border-color: #2563eb;">
            📥 Export
        </a>
        <a href="{{ route('admin.monitoring.show', $exam->id) }}" class="btn btn-primary">📡 Live Monitoring</a>
        <a href="{{ route('admin.results.index', ['exam_id' => $exam->id]) }}" class="btn btn-secondary">🎯 Rekap Hasil</a>
        <a href="{{ route('admin.exams.edit', $exam->id) }}" class="btn btn-secondary">Edit</a>
        <a href="{{ route('admin.exams.index') }}" class="btn btn-secondary">&larr; Ruang Ujian</a>
    </div>
</div>

@if($exam->start_window)
    <div style="background: #ecfdf5; border: 1px solid #6ee7b7; border-left: 5px solid #10b981; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 22px;">🗓️</span>
            <div>
                <strong style="color: #065f46; font-size: 14px;">Status Jadwal Aktif</strong>
                <div style="color: #047857; font-size: 13.5px; margin-top: 2px;">
                    Selesai, ruang ujian sudah dibuat dan bisa dikerjakan mulai <strong>{{ \Carbon\Carbon::parse($exam->start_window)->format('d-m-Y') }} Pukul {{ \Carbon\Carbon::parse($exam->start_window)->format('H:i') }} (GMT+07:00)</strong>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-secondary" onclick="openScheduleModal()">Ubah Jadwal</button>
    </div>
@endif

<!-- INFORMASI KREDENSIAL LOGIN SISWA (NOMOR 8 PANDUAN) -->
<div class="card" style="margin-bottom: 24px; border-left: 4px solid #6366f1; background: linear-gradient(to right, #f8fafc, #ffffff);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 18px;">🔑</span>
                <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #1e293b;">Kredensial Login Siswa &amp; Kode Kelas Ujian</h3>
            </div>
            <div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                Berikan NIS/NIM, Password, dan Kode Kelas Ujian berikut kepada siswa calon peserta ujian:
            </div>
            <div style="display: flex; gap: 16px; margin-top: 10px; flex-wrap: wrap; font-size: 13px;">
                <span style="background: #f1f5f9; padding: 4px 10px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    Username/Login: <strong>NIS masing-masing</strong>
                </span>
                <span style="background: #f1f5f9; padding: 4px 10px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    Password Default: <strong>12345678</strong>
                </span>
                <span style="background: #eef2ff; color: #4338ca; padding: 4px 12px; border-radius: 6px; border: 1px solid #c7d2fe; font-weight: 700; letter-spacing: 1px;">
                    Kode Kelas Ujian: {{ $exam->token ?? 'RU-'.substr(md5($exam->id), 0, 5) }}
                </span>
            </div>
        </div>
        <button type="button" class="btn btn-secondary" onclick="copyExamCredentials('{{ $exam->token ?? 'RU-'.substr(md5($exam->id), 0, 5) }}', '{{ addslashes($exam->title) }}')" style="font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
            <span>📋</span> Salin Format Informasi Siswa
        </button>
    </div>
</div>
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
            <form method="POST" action="{{ route('admin.exams.questions.attach', $exam->id) }}">
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
                            <form method="POST" action="{{ route('admin.exams.questions.detach', [$exam->id, $eq->id]) }}" onsubmit="return confirm('Lepaskan butir soal ini dari paket ujian?');" style="display:inline;">
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
                            <div class="empty-desc">Klik tombol "+ Tambah Soal dari Bank" di atas untuk memasukkan soal ke ujian ini.</div>
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
        <form method="POST" action="{{ route('admin.exams.participants.add', $exam->id) }}">
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
                            <form method="POST" action="{{ route('admin.exams.participants.retest', [$exam->id, $p->id]) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $p->allow_retest ? 'btn-primary' : 'btn-secondary' }}" title="Klik untuk mengubah hak ujian ulang">
                                    {{ $p->allow_retest ? 'Ya (Retest Aktif)' : 'Tidak' }}
                                </button>
                            </form>
                        </td>
                        <td style="text-align: center;">
                            <form method="POST" action="{{ route('admin.exams.participants.remove', [$exam->id, $p->id]) }}" onsubmit="return confirm('Hapus siswa ini dari pendaftaran ujian?');" style="display:inline;">
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

<!-- MODAL SETTING WAKTU UJIAN (NOMOR 7 PANDUAN) -->
<div class="modal-overlay" id="scheduleModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); z-index: 9999; align-items: center; justify-content: center;">
    <div class="modal-content-card" style="background: #ffffff; border-radius: 12px; max-width: 480px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden;">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">⚙️</span>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">Setting Waktu Ujian</h3>
            </div>
            <button type="button" onclick="closeScheduleModal()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.exams.set-schedule', $exam->id) }}">
            @csrf
            <div style="padding: 24px;">
                <p style="margin: 0 0 16px 0; font-size: 13.5px; color: #64748b; line-height: 1.5;">
                    Pilih Tanggal dan jam kapan ruang ujian bisa mulai dikerjakan oleh siswa. Ruang ujian akan langsung aktif pada waktu yang ditentukan.
                </p>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                        Tanggal dan Jam Mulai (GMT+07:00) *
                    </label>
                    <input type="datetime-local" name="start_window" class="form-control" value="{{ $exam->start_window ? \Carbon\Carbon::parse($exam->start_window)->format('Y-m-d\TH:i') : date('Y-m-d\TH:i') }}" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;">
                </div>
                <div style="background: #f1f5f9; padding: 12px; border-radius: 6px; font-size: 12.5px; color: #475569; margin-bottom: 20px;">
                    💡 Durasi ujian ini adalah <strong>{{ $exam->duration_minutes }} menit</strong>. Timer pengerjaan siswa akan otomatis menyesuaikan durasi tersebut.
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="closeScheduleModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background: #10b981; border-color: #059669; font-weight: 700;">
                        Save / Simpan Waktu
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleElement(id) {
        var el = document.getElementById(id);
        if (el) {
            el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
        }
    }

    function openScheduleModal() {
        var modal = document.getElementById('scheduleModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function closeScheduleModal() {
        var modal = document.getElementById('scheduleModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function copyExamCredentials(token, title) {
        var text = "INFORMASI UJIAN ONLINE CBT\n" +
                   "Ruang Ujian: " + title + "\n" +
                   "Login Siswa: Gunakan NIS masing-masing\n" +
                   "Password: 12345678\n" +
                   "Kode Kelas Ujian: " + token + "\n" +
                   "Harap masuk tepat waktu.";
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                alert("Kredensial dan Kode Ujian (" + token + ") berhasil disalin ke clipboard!");
            });
        } else {
            alert(text);
        }
    }

    document.addEventListener('click', function(e) {
        var m = document.getElementById('scheduleModal');
        if (e.target === m) closeScheduleModal();
    });
</script>
@endsection
