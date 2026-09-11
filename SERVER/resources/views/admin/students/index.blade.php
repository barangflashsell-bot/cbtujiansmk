@extends('layouts.app')

@section('title', 'Manajemen Peserta — CBT Local Server')
@section('page-title', 'Manajemen Peserta Ujian')

@section('content')
<div style="display: flex; flex-direction: column; gap: 16px;">
    <!-- 1. MENU PEMBAGIAN KELAS (TABS NAVIGASI CEPAT PER-KELAS) -->
    <div class="card" style="padding: 16px 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 17px;">🏷️</span>
                <strong style="font-size: 14.5px; color: var(--text-primary);">Menu Pembagian Kelas</strong>
                <span style="font-size: 11.5px; color: var(--text-muted);">(Klik nama kelas untuk filter instan)</span>
            </div>
            <div style="font-size: 12px; color: var(--text-secondary); font-weight: 600;">
                Total: <span style="color: var(--primary);">{{ $totalStudentsCount ?? $students->total() }} Peserta</span> &bull; {{ $classes->count() }} Rombel
            </div>
        </div>

        <div class="class-division-tabs">
            <a 
                href="{{ route('admin.students.index', request()->except('class_id', 'page')) }}" 
                class="class-tab-pill {{ empty($selectedClassId) ? 'active' : '' }}"
                title="Tampilkan seluruh siswa dari semua kelas"
            >
                <span>Semua Kelas</span>
                <span class="class-tab-badge">{{ $totalStudentsCount ?? $students->total() }}</span>
            </a>

            @foreach ($classes as $c)
                <a 
                    href="{{ route('admin.students.index', array_merge(request()->except('page'), ['class_id' => $c->id])) }}" 
                    class="class-tab-pill {{ (string)$selectedClassId === (string)$c->id ? 'active' : '' }}"
                    title="Filter hanya kelas {{ $c->name }}"
                >
                    <span>{{ $c->name }}</span>
                    <span class="class-tab-badge">{{ $c->students_count ?? 0 }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- 2. ACTION BAR (SEARCH & BUTTONS) -->
    <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div class="filter-group">
            <form action="{{ route('admin.students.index') }}" method="GET" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    class="form-control" 
                    placeholder="Cari nama, username, NIS..."
                    style="max-width: 240px;"
                >

                <select name="class_id" class="form-select" onchange="this.form.submit()" style="max-width: 180px;">
                    <option value="">Semua Kelas</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-secondary">Filter</button>
                @if(request('search') || request('class_id'))
                    <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>

        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7;">
                <span>📊</span> Import Data Excel
            </button>

            <button type="button" class="btn btn-primary" onclick="document.getElementById('createStudentCard').style.display = 'block'; window.scrollTo({top: document.getElementById('createStudentCard').offsetTop - 80, behavior: 'smooth'});">
                <span>+</span> Tambah Peserta Baru
            </button>
        </div>
    </div>

    <!-- CREATE FORM CARD -->
    <div class="card" id="createStudentCard" style="display: none; border-color: var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="card-title" style="margin-bottom: 0;">Tambah Akun & Data Peserta Baru</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('createStudentCard').style.display = 'none';">&times; Batal</button>
        </div>

        <form action="{{ route('admin.students.store') }}" method="POST">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Username / Nomor Peserta *</label>
                    <input type="text" name="username" class="form-control" placeholder="Contoh: peserta01" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Lengkap Siswa *</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Ahmad Dhani" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password Login *</label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Kelas / Rombel *</label>
                    <select name="class_id" class="form-select" style="width: 100%;" required>
                        <option value="">Pilih Kelas</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->level }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">NIS *</label>
                    <input type="text" name="nis" class="form-control" placeholder="Nomor Induk Siswa" required>
                </div>

                <div class="form-group">
                    <label class="form-label">NISN (Opsional)</label>
                    <input type="text" name="nisn" class="form-control" placeholder="10 Digit NISN">
                </div>

                <div class="form-group">
                    <label class="form-label">Jenis Kelamin *</label>
                    <select name="gender" class="form-select" style="width: 100%;" required>
                        <option value="L">Laki-laki (L)</option>
                        <option value="P">Perempuan (P)</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px;">
                <button type="submit" class="btn btn-primary">Simpan Data Peserta</button>
            </div>
        </form>
    </div>

    <!-- STUDENTS TABLE -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Nama Peserta</th>
                        <th>Username</th>
                        <th>Kelas</th>
                        <th>NIS / NISN</th>
                        <th>JK</th>
                        <th>Status</th>
                        <th style="width: 140px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $idx => $item)
                        <tr>
                            <td>{{ $students->firstItem() + $idx }}</td>
                            <td>
                                <strong style="font-size: 14px;">{{ $item->user?->name ?? 'Peserta' }}</strong>
                            </td>
                            <td>
                                <code>{{ $item->user?->username ?? '-' }}</code>
                            </td>
                            <td>
                                <span class="badge badge-primary">{{ $item->schoolClass?->name ?? '-' }}</span>
                            </td>
                            <td>
                                <div>{{ $item->nis }}</div>
                                @if($item->nisn)
                                    <div style="font-size: 11px; color: var(--text-muted);">NISN: {{ $item->nisn }}</div>
                                @endif
                            </td>
                            <td>{{ $item->gender }}</td>
                            <td>
                                <span class="badge {{ $item->user?->is_active ? 'badge-success' : 'badge-danger' }}">
                                    {{ $item->user?->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-btns">
                                    <button 
                                        type="button" 
                                        class="btn btn-secondary btn-sm"
                                        onclick="editStudent('{{ $item->id }}', '{{ addslashes($item->user?->name ?? '') }}', '{{ $item->class_id }}', '{{ addslashes($item->nis) }}', '{{ addslashes($item->nisn ?? '') }}', '{{ $item->gender }}', '{{ $item->user?->is_active ? 1 : 0 }}')"
                                    >
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.students.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus data peserta {{ addslashes($item->user?->name ?? '') }}?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-state-icon">👥</div>
                                    <p>Belum ada data peserta yang terdaftar.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($students->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 12.5px; color: var(--text-muted);">
                    Menampilkan {{ $students->firstItem() }} - {{ $students->lastItem() }} dari {{ $students->total() }} peserta
                </div>
                <div style="display: flex; gap: 6px;">
                    @if ($students->onFirstPage())
                        <span class="btn btn-secondary btn-sm" style="opacity: 0.5; cursor: not-allowed;">Prev</span>
                    @else
                        <a href="{{ $students->previousPageUrl() }}" class="btn btn-secondary btn-sm">Prev</a>
                    @endif

                    @if ($students->hasMorePages())
                        <a href="{{ $students->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                    @else
                        <span class="btn btn-secondary btn-sm" style="opacity: 0.5; cursor: not-allowed;">Next</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- EDIT FORM CARD -->
    <div class="card" id="editStudentCard" style="display: none; border-color: var(--primary); margin-top: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="card-title" style="margin-bottom: 0;">Edit Data Peserta</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('editStudentCard').style.display = 'none';">&times; Tutup</button>
        </div>

        <form id="editStudentForm" method="POST">
            @csrf
            @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap Siswa *</label>
                    <input type="text" id="edit_student_name" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Reset Password (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" class="form-control" placeholder="Biarkan kosong jika tetap">
                </div>

                <div class="form-group">
                    <label class="form-label">Kelas / Rombel *</label>
                    <select id="edit_student_class_id" name="class_id" class="form-select" style="width: 100%;" required>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">NIS *</label>
                    <input type="text" id="edit_student_nis" name="nis" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">NISN</label>
                    <input type="text" id="edit_student_nisn" name="nisn" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Jenis Kelamin *</label>
                    <select id="edit_student_gender" name="gender" class="form-select" style="width: 100%;" required>
                        <option value="L">Laki-laki (L)</option>
                        <option value="P">Perempuan (P)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status Akun</label>
                    <select id="edit_student_status" name="is_active" class="form-select" style="width: 100%;">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px;">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <!-- 3. MODAL IMPORT EXCEL / CSV -->
    <div class="modal-overlay" id="importStudentModal">
        <div class="modal-content-card" style="max-width: 540px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 20px;">📊</span>
                    <h3 class="modal-title" style="margin: 0;">Import Data Peserta dari Excel</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeImportModal()">&times;</button>
            </div>

            <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 12px; line-height: 1.45;">
                        Unggah file daftar siswa dalam format <strong>Excel (.xlsx)</strong> atau <strong>CSV (.csv)</strong>. Sistem akan otomatis mendaftarkan akun login CBT dan mengelompokkan ke kelas terkait.
                    </p>

                    <!-- DOWNLOAD TEMPLATE BUTTON -->
                    <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                        <div>
                            <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">Belum punya formatnya?</div>
                            <div style="font-size: 11px; color: var(--text-muted);">Unduh template standar berisi contoh baris</div>
                        </div>
                        <a href="{{ route('admin.students.template') }}" class="btn btn-secondary btn-sm" style="color: #0284c7; border-color: #bae6fd; text-decoration: none;">
                            <span>📥</span> Unduh Template
                        </a>
                    </div>

                    <!-- FILE INPUT -->
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Pilih File Excel / CSV *</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required style="padding: 7px 12px;">
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Mendukung file .xlsx, .xls, dan .csv (Maksimal 10MB)</div>
                    </div>

                    <!-- DEFAULT CLASS (OPTIONAL FALLBACK) -->
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Kelas Target Default (Opsional)</label>
                        <select name="default_class_id" class="form-select">
                            <option value="">-- Otomatis Sesuai Kolom "Kelas" di File --</option>
                            @foreach ($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->level }})</option>
                            @endforeach
                        </select>
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Jika di file Excel sudah terdapat kolom "Kelas", sistem akan menggunakannya secara otomatis.</div>
                    </div>

                    <!-- UPDATE EXISTING OPTION -->
                    <div style="margin-bottom: 16px; display: flex; align-items: flex-start; gap: 8px;">
                        <input type="checkbox" id="update_existing" name="update_existing" value="1" style="margin-top: 2px;">
                        <label for="update_existing" style="font-size: 12.5px; color: var(--text-primary); cursor: pointer; line-height: 1.35;">
                            <strong>Perbarui data jika NIS / Username sudah ada</strong><br>
                            <span style="font-size: 11px; color: var(--text-muted);">Jika tidak dicentang, data siswa yang sudah terdaftar akan dilewati secara aman.</span>
                        </label>
                    </div>

                    <!-- FORMAT PREVIEW TABLE -->
                    <div style="background: var(--bg-surface-elevated); border: 1px dashed var(--border-color); border-radius: 6px; padding: 10px 12px; font-size: 11px; color: var(--text-secondary);">
                        <strong style="display: block; margin-bottom: 4px; color: var(--text-primary);">Format Kolom File yang Dikenali:</strong>
                        <code>Nama Lengkap | Username | Password | Kelas | NIS | NISN | Jenis Kelamin</code>
                        <div style="margin-top: 4px; font-size: 10.5px; color: var(--text-muted);">
                            &bull; Jenis kelamin isi dengan <code>L</code> atau <code>P</code>.<br>
                            &bull; Jika password dikosongkan, otomatis menggunakan nomor NIS.
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #0095ff; border-color: #0095ff;">
                        <span>📤</span> Upload & Mulai Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editStudent(id, name, classId, nis, nisn, gender, isActive) {
    const card = document.getElementById('editStudentCard');
    const form = document.getElementById('editStudentForm');
    form.action = '/admin/students/' + id;
    document.getElementById('edit_student_name').value = name;
    document.getElementById('edit_student_class_id').value = classId;
    document.getElementById('edit_student_nis').value = nis;
    document.getElementById('edit_student_nisn').value = nisn;
    document.getElementById('edit_student_gender').value = gender;
    document.getElementById('edit_student_status').value = isActive;
    card.style.display = 'block';
    window.scrollTo({top: card.offsetTop - 80, behavior: 'smooth'});
}

function openImportModal() {
    const modal = document.getElementById('importStudentModal');
    if (modal) {
        modal.classList.add('active');
    }
}

function closeImportModal() {
    const modal = document.getElementById('importStudentModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Close modal if backdrop clicked
document.addEventListener('click', function(e) {
    const modal = document.getElementById('importStudentModal');
    if (modal && e.target === modal) {
        closeImportModal();
    }
});
</script>
@endsection
