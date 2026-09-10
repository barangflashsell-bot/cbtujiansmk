@extends('layouts.app')

@section('title', 'Daftar Siswa - CBT Guru')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Daftar Siswa</h1>
        <p class="page-subtitle">Informasi peserta ujian dan data kelas untuk referensi pengajar</p>
    </div>
</div>

<div class="card">
    <form method="GET" action="{{ route('guru.students.index') }}" class="search-filter-bar">
        <div class="search-input-group">
            <span class="search-icon">&#128269;</span>
            <input type="text" name="search" class="form-control" placeholder="Cari NIS, NISN, atau nama siswa..." value="{{ request('search') }}">
        </div>
        <div class="filter-select-group">
            <select name="class_id" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->class_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        @if(request('search') || request('class_id'))
            <a href="{{ route('guru.students.index') }}" class="btn btn-secondary">Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>NIS / NISN</th>
                    <th>Nama Lengkap</th>
                    <th>Kelas</th>
                    <th>Jenis Kelamin</th>
                    <th>Status Akun</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $idx => $s)
                    <tr>
                        <td>{{ $students->firstItem() + $idx }}</td>
                        <td>
                            <span class="badge badge-info">{{ $s->nis }}</span>
                            @if($s->nisn)
                                <div style="font-size: 0.8rem; color: var(--color-slate-500); margin-top: 4px;">NISN: {{ $s->nisn }}</div>
                            @endif
                        </td>
                        <td style="font-weight: 500;">
                            {{ $s->name }}
                            @if($s->user)
                                <div style="font-size: 0.8rem; color: var(--color-slate-500); margin-top: 4px;">{{ $s->user->email }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-secondary">{{ $s->schoolClass->class_name ?? '-' }}</span>
                        </td>
                        <td>
                            @if($s->gender === 'L')
                                Laki-laki
                            @elseif($s->gender === 'P')
                                Perempuan
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($s->user && $s->user->status === 'active')
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-warning">Nonaktif</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div class="empty-icon">&#128101;</div>
                            <div class="empty-title">Tidak ada data siswa ditemukan</div>
                            <div class="empty-desc">Data siswa yang terdaftar akan ditampilkan pada tabel ini.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($students->hasPages())
        <div class="pagination-wrapper">
            {{ $students->links() }}
        </div>
    @endif
</div>
@endsection
