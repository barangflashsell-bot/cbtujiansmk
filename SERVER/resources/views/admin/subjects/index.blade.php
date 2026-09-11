@extends('layouts.app')

@section('title', 'Mata Pelajaran — CBT Local Server')
@section('page-title', 'Mata Pelajaran')

@section('content')
<div style="display: flex; flex-direction: column; gap: 20px;">
    <!-- ACTION BAR -->
    <div class="action-bar">
        <div class="filter-group">
            <form action="{{ route('admin.subjects.index') }}" method="GET" style="display: flex; gap: 8px;">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    class="form-control" 
                    placeholder="Cari kode / nama mapel..."
                    style="max-width: 260px;"
                >
                <button type="submit" class="btn btn-secondary">Cari</button>
                @if(request('search'))
                    <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>

        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('createSubjectCard').style.display = 'block'; window.scrollTo({top: document.getElementById('createSubjectCard').offsetTop - 80, behavior: 'smooth'});">
                <span>+</span> Tambah Mapel Baru
            </button>
        </div>
    </div>

    <!-- CREATE FORM CARD -->
    <div class="card" id="createSubjectCard" style="display: none; border-color: var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="card-title" style="margin-bottom: 0;">Tambah Mata Pelajaran Baru</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('createSubjectCard').style.display = 'none';">&times; Batal</button>
        </div>

        <form action="{{ route('admin.subjects.store') }}" method="POST">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kode Mapel *</label>
                    <input type="text" name="code" class="form-control" placeholder="Contoh: MAT, BIND, IPA" style="text-transform: uppercase;" required>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Nama Mata Pelajaran *</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Matematika, Bahasa Indonesia" required>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px;">
                <button type="submit" class="btn btn-primary">Simpan Mata Pelajaran</button>
            </div>
        </form>
    </div>

    <!-- SUBJECTS TABLE -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Kode</th>
                        <th>Nama Mata Pelajaran</th>
                        <th>Total Soal</th>
                        <th>Total Ujian</th>
                        <th>Status</th>
                        <th style="width: 220px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subjects as $idx => $item)
                        <tr>
                            <td>{{ $subjects->firstItem() + $idx }}</td>
                            <td>
                                <span class="badge badge-primary" style="font-size: 12px;">{{ $item->code }}</span>
                            </td>
                            <td>
                                <strong style="font-size: 14px;">{{ $item->name }}</strong>
                            </td>
                            <td>
                                <a href="{{ route('admin.questions.index', ['subject_id' => $item->id]) }}" class="badge badge-info" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="Lihat semua soal {{ $item->name }}">
                                    <span>📖</span> {{ $item->questions_count }} Butir &rarr;
                                </a>
                            </td>
                            <td>{{ $item->exams_count }} Paket</td>
                            <td>
                                <span class="badge {{ $item->status === 'active' ? 'badge-success' : 'badge-neutral' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-btns" style="justify-content: center;">
                                    <a href="{{ route('admin.questions.create', ['subject_id' => $item->id]) }}" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 4px; font-weight: 700;" title="Buat soal baru untuk {{ $item->name }}">
                                        <span>📝</span> Buat Soal
                                    </a>
                                    <button 
                                        type="button" 
                                        class="btn btn-secondary btn-sm"
                                        onclick="editSubject('{{ $item->id }}', '{{ addslashes($item->code) }}', '{{ addslashes($item->name) }}', '{{ $item->status }}')"
                                    >
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.subjects.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus mapel {{ addslashes($item->name) }}?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-state-icon">📚</div>
                                    <p>Belum ada data mata pelajaran yang terdaftar.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subjects->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 12.5px; color: var(--text-muted);">
                    Menampilkan {{ $subjects->firstItem() }} - {{ $subjects->lastItem() }} dari {{ $subjects->total() }} mapel
                </div>
                <div style="display: flex; gap: 6px;">
                    @if ($subjects->onFirstPage())
                        <span class="btn btn-secondary btn-sm" style="opacity: 0.5; cursor: not-allowed;">Prev</span>
                    @else
                        <a href="{{ $subjects->previousPageUrl() }}" class="btn btn-secondary btn-sm">Prev</a>
                    @endif

                    @if ($subjects->hasMorePages())
                        <a href="{{ $subjects->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                    @else
                        <span class="btn btn-secondary btn-sm" style="opacity: 0.5; cursor: not-allowed;">Next</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- EDIT FORM CARD -->
    <div class="card" id="editSubjectCard" style="display: none; border-color: var(--primary); margin-top: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="card-title" style="margin-bottom: 0;">Edit Mata Pelajaran</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('editSubjectCard').style.display = 'none';">&times; Tutup</button>
        </div>

        <form id="editSubjectForm" method="POST">
            @csrf
            @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kode Mapel *</label>
                    <input type="text" id="edit_code" name="code" class="form-control" style="text-transform: uppercase;" required>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Nama Mata Pelajaran *</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px;">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    </div>
</div>

<script>
function editSubject(id, code, name, status) {
    const card = document.getElementById('editSubjectCard');
    const form = document.getElementById('editSubjectForm');
    form.action = '/admin/subjects/' + id;
    document.getElementById('edit_code').value = code;
    document.getElementById('edit_name').value = name;
    card.style.display = 'block';
    window.scrollTo({top: card.offsetTop - 80, behavior: 'smooth'});
}
</script>
@endsection
