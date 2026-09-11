<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CBT Server SMK') — Web Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/cbt-offline.css') }}">
</head>
<body>
    @php
        $authUser = auth()->user();
        $userRole = strtolower($authUser->role->name ?? '');
        $initial = strtoupper(substr($authUser->name ?? $authUser->username ?? 'A', 0, 1));
        $studentCount = \App\Models\Student::count();
        $rawTitle = View::hasSection('page-title') 
            ? View::getSection('page-title') 
            : preg_replace('/\s*[-—]\s*CBT.*$/i', '', View::getSection('title', 'Dashboard'));
    @endphp

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app-layout">
        <!-- SIDEBAR HIJAU (BRAND GABUNG DENGAN MENU) -->
        <aside class="sidebar" id="appSidebar">
            <!-- 1. BRAND CBT SMK DI PALING ATAS SIDEBAR -->
            <div class="sidebar-brand-header">
                <div class="brand-crest-box">
                    <svg width="28" height="28" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="brandCrestGrad" x1="0" y1="0" x2="36" y2="36" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#2563eb"/>
                                <stop offset="1" stop-color="#06b6d4"/>
                            </linearGradient>
                        </defs>
                        <rect width="36" height="36" rx="8" fill="url(#brandCrestGrad)"/>
                        <path d="M18 7L8 12V20C8 26 12.2 30.5 18 31.5C23.8 30.5 28 26 28 20V12L18 7Z" fill="#ffffff" fill-opacity="0.25" stroke="#ffffff" stroke-width="1.8"/>
                        <path d="M14 18L17 21L23 15" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="brand-info">
                    <div class="brand-name">CBT SERVER SMK</div>
                    <div class="brand-tagline">Server Lokal Offline LAN</div>
                </div>
            </div>

            <!-- 2. DAFTAR MENU DENGAN WARNA PADA SETIAP NAMA MENU -->
            <nav class="sidebar-nav">
                @if ($userRole === 'admin')
                    <!-- 1. UTAMA -->
                    <div class="nav-section-title">Utama</div>
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box blue">📊</span>
                            <span>Dashboard</span>
                        </span>
                    </a>

                    <!-- 2. MASTER DATA -->
                    <div class="nav-section-title">Master Data</div>
                    <a href="{{ route('admin.students.index') }}" class="nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box purple">👥</span>
                            <span>Data Peserta</span>
                        </span>
                        @if ($studentCount > 0)
                            <span class="nav-badge-pill">{{ $studentCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('admin.teachers.index') }}" class="nav-link {{ request()->routeIs('admin.teachers.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box teal">👨‍🏫</span>
                            <span>Data Guru</span>
                        </span>
                    </a>
                    <a href="{{ route('admin.classes.index') }}" class="nav-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box amber">🏫</span>
                            <span>Data Kelas</span>
                        </span>
                    </a>
                    <a href="{{ route('admin.subjects.index') }}" class="nav-link {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box rose">📚</span>
                            <span>Mata Pelajaran</span>
                        </span>
                    </a>

                    <!-- 3. AKADEMIK & UJIAN -->
                    <div class="nav-section-title">Akademik & Ujian</div>
                    <a href="{{ route('admin.questions.index') }}" class="nav-link {{ request()->routeIs('admin.questions.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box orange">📝</span>
                            <span>Bank Soal</span>
                        </span>
                    </a>
                    <a href="{{ route('admin.exams.index') }}" class="nav-link {{ request()->routeIs('admin.exams.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box emerald">⏱️</span>
                            <span>Paket Ujian</span>
                        </span>
                    </a>
                    <a href="{{ route('admin.monitoring.index') }}" class="nav-link {{ request()->routeIs('admin.monitoring.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box cyan">📡</span>
                            <span>Monitoring Ujian</span>
                        </span>
                    </a>
                    <a href="{{ route('admin.results.index') }}" class="nav-link {{ request()->routeIs('admin.results.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box red">🎯</span>
                            <span>Hasil Ujian</span>
                        </span>
                    </a>
                    <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box indigo">📈</span>
                            <span>Laporan Nilai</span>
                        </span>
                    </a>

                    <!-- 4. PEMELIHARAAN SERVER -->
                    <div class="nav-section-title">Pemeliharaan Server</div>
                    <a href="{{ route('admin.backups.index') }}" class="nav-link {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box lime">💾</span>
                            <span>Backup & Restore</span>
                        </span>
                    </a>
                    <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box slate">⚙️</span>
                            <span>Pengaturan Server</span>
                        </span>
                    </a>
                    <a href="{{ route('admin.activity-logs.index') }}" class="nav-link {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box amber">📜</span>
                            <span>Log Aktivitas</span>
                        </span>
                    </a>

                @else
                    <!-- GURU MENU -->
                    <div class="nav-section-title">Utama</div>
                    <a href="{{ route('guru.dashboard') }}" class="nav-link {{ request()->routeIs('guru.dashboard') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box blue">📊</span>
                            <span>Dashboard</span>
                        </span>
                    </a>

                    <div class="nav-section-title">Akademik & Ujian</div>
                    <a href="{{ route('guru.questions.index') }}" class="nav-link {{ request()->routeIs('guru.questions.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box orange">📝</span>
                            <span>Bank Soal Saya</span>
                        </span>
                    </a>
                    <a href="{{ route('guru.exams.index') }}" class="nav-link {{ request()->routeIs('guru.exams.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box emerald">⏱️</span>
                            <span>Paket Ujian Saya</span>
                        </span>
                    </a>
                    <a href="{{ route('guru.monitoring.index') }}" class="nav-link {{ request()->routeIs('guru.monitoring.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box cyan">📡</span>
                            <span>Monitoring Peserta</span>
                        </span>
                    </a>
                    <a href="{{ route('guru.results.index') }}" class="nav-link {{ request()->routeIs('guru.results.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box red">🎯</span>
                            <span>Hasil Ujian</span>
                        </span>
                    </a>
                    <a href="{{ route('guru.reports.index') }}" class="nav-link {{ request()->routeIs('guru.reports.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box indigo">📈</span>
                            <span>Laporan Nilai</span>
                        </span>
                    </a>

                    <div class="nav-section-title">Data Siswa</div>
                    <a href="{{ route('guru.students.index') }}" class="nav-link {{ request()->routeIs('guru.students.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <span class="menu-icon-box purple">👥</span>
                            <span>Data Peserta</span>
                        </span>
                    </a>
                @endif
            </nav>
        </aside>

        <!-- MAIN WRAPPER (TOPBAR KONTEN + CONTENT BODY) -->
        <div class="main-wrapper">
            <header class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle-btn" id="sidebarToggleBtn" aria-label="Toggle Sidebar">
                        ☰
                    </button>
                    <div class="topbar-title-wrap">
                        <div class="topbar-title">
                            <span>{{ $rawTitle ?: 'Dashboard' }}</span>
                            <span class="topbar-badge">CBT SMK</span>
                        </div>
                        <div class="topbar-subtitle">CBT Server Offline &bull; Kurikulum SMK</div>
                    </div>
                </div>

                <div class="topbar-right-actions">
                    <div class="user-pill" title="Akun yang sedang aktif">
                        <div class="user-avatar-circle">{{ $initial }}</div>
                        <div style="display: flex; flex-direction: column; text-align: left;">
                            <span style="font-size: 13px; font-weight: 700; color: var(--text-primary); line-height: 1.1;">{{ $authUser->name ?? $authUser->username }}</span>
                            <span style="font-size: 11px; color: var(--text-muted); line-height: 1.1;">{{ $userRole === 'admin' ? 'Administrator Sistem' : 'Guru Pengajar' }}</span>
                        </div>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="logout-link-btn" title="Keluar dari sesi Web Dashboard">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </header>

            <main class="content-body">
                @if (session('success'))
                    <div class="alert alert-success">
                        <span>{{ session('success') }}</span>
                        <span style="cursor: pointer;" onclick="this.parentElement.remove();">&times;</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">
                        <span>{{ session('error') }}</span>
                        <span style="cursor: pointer;" onclick="this.parentElement.remove();">&times;</span>
                    </div>
                @endif

                @if (isset($errors) && $errors->any())
                    <div class="alert alert-danger">
                        <div>
                            @foreach ($errors->all() as $err)
                                <div>{{ $err }}</div>
                            @endforeach
                        </div>
                        <span style="cursor: pointer;" onclick="this.parentElement.remove();">&times;</span>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ asset('js/cbt-offline.js') }}"></script>
</body>
</html>
