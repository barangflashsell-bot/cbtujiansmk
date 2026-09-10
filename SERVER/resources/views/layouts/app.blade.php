<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CBT Local Server') — Web Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/cbt-offline.css') }}">
</head>
<body>
    @php
        $authUser = auth()->user();
        $userRole = strtolower($authUser->role->name ?? '');
        $initial = strtoupper(substr($authUser->name ?? $authUser->username ?? 'A', 0, 1));
        $studentCount = \App\Models\Student::count();
    @endphp

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- GLOBAL TOPBAR (Exact layout from screenshot) -->
    <header class="global-topbar">
        <div class="topbar-left-brand">
            <button class="menu-toggle-btn" id="sidebarToggleBtn" aria-label="Toggle Sidebar" style="margin-right: 8px;">
                ☰
            </button>
            <div class="brand-crest-box">
                <svg width="28" height="28" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="36" height="36" rx="8" fill="#1e293b"/>
                    <path d="M18 6L8 11V19C8 25 12.2 29.8 18 31C23.8 29.8 28 25 28 19V11L18 6Z" fill="#0284c7" stroke="#ffffff" stroke-width="1.5"/>
                    <path d="M18 12V25M13 17H23" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="brand-info">
                <div class="brand-name">Rivendell High School</div>
                <div class="brand-tagline">All-in-One School Management Platform</div>
            </div>
        </div>

        <div class="topbar-right-actions">
            <div class="user-pill">
                <div class="user-avatar-circle">{{ $initial }}</div>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted);"><polyline points="6 9 12 15 18 9"></polyline></svg>
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

    <div class="app-layout">
        <!-- SIDEBAR (Clean White Minimalist matching screenshot) -->
        <aside class="sidebar" id="appSidebar">
            <nav class="sidebar-nav">
                @if ($userRole === 'admin')
                    <!-- Dashboard -->
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') && !request()->has('view') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            <span>Dashboard</span>
                        </span>
                    </a>

                    <!-- Create Report -->
                    <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            <span>Create Report</span>
                        </span>
                    </a>

                    <!-- Add Student -->
                    <a href="{{ route('admin.students.index') }}#add" class="nav-link">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                            <span>Add Student</span>
                        </span>
                    </a>

                    <!-- All Students -->
                    <a href="{{ route('admin.students.index') }}" class="nav-link {{ request()->routeIs('admin.students.index') || request()->routeIs('admin.students.edit') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <span>All Students</span>
                        </span>
                        @if ($studentCount > 0)
                            <span class="nav-badge-pill">{{ $studentCount }}</span>
                        @endif
                    </a>

                    <!-- Manage Teachers -->
                    <a href="{{ route('admin.teachers.index') }}" class="nav-link {{ request()->routeIs('admin.teachers.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>Manage Teachers</span>
                        </span>
                    </a>

                    <!-- View Attendance (Monitoring) -->
                    <a href="{{ route('admin.monitoring.index') }}" class="nav-link {{ request()->routeIs('admin.monitoring.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            <span>View Attendance</span>
                        </span>
                    </a>

                    <!-- CBT System Section (Dropdown styled group) -->
                    <div class="nav-group-header">
                        <span class="nav-group-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                            <span>CBT System</span>
                        </span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                    </div>

                    <div class="nav-sub-group">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link nav-sub-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <span class="nav-link-content">
                                <svg class="nav-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                                <span>CBT Analytics</span>
                            </span>
                        </a>
                        <a href="{{ route('admin.questions.index') }}" class="nav-link nav-sub-link {{ request()->routeIs('admin.questions.*') ? 'active' : '' }}">
                            <span class="nav-link-content">
                                <svg class="nav-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                                <span>Question Bank</span>
                            </span>
                        </a>
                        <a href="{{ route('admin.exams.index') }}" class="nav-link nav-sub-link {{ request()->routeIs('admin.exams.*') ? 'active' : '' }}">
                            <span class="nav-link-content">
                                <svg class="nav-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                <span>Exam Management</span>
                            </span>
                        </a>
                        <a href="{{ route('admin.results.index') }}" class="nav-link nav-sub-link {{ request()->routeIs('admin.results.*') ? 'active' : '' }}">
                            <span class="nav-link-content">
                                <svg class="nav-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                                <span>Exam Results</span>
                            </span>
                        </a>
                    </div>

                    <div style="height: 1px; background-color: var(--border-color); margin: 8px 12px;"></div>

                    <!-- Classes & Subjects -->
                    <a href="{{ route('admin.classes.index') }}" class="nav-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M3 7v1a3 3 0 0 0 6 0V7m0 1a3 3 0 0 0 6 0V7m0 1a3 3 0 0 0 6 0V7H3l2-4h14l2 4"></path></svg>
                            <span>Classes</span>
                        </span>
                    </a>
                    <a href="{{ route('admin.subjects.index') }}" class="nav-link {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                            <span>Subjects</span>
                        </span>
                    </a>

                    <!-- School Accounting / Profile / Settings -->
                    <a href="{{ route('admin.backups.index') }}" class="nav-link {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                            <span>Backup & Restore</span>
                        </span>
                    </a>

                    <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                            <span>Settings</span>
                        </span>
                    </a>

                    <a href="{{ route('admin.activity-logs.index') }}" class="nav-link {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            <span>Activity Logs</span>
                        </span>
                    </a>

                @else
                    <!-- GURU MENU -->
                    <a href="{{ route('guru.dashboard') }}" class="nav-link {{ request()->routeIs('guru.dashboard') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            <span>Dashboard</span>
                        </span>
                    </a>

                    <div class="nav-group-header">
                        <span class="nav-group-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                            <span>CBT System</span>
                        </span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                    </div>

                    <div class="nav-sub-group">
                        <a href="{{ route('guru.questions.index') }}" class="nav-link nav-sub-link {{ request()->routeIs('guru.questions.*') ? 'active' : '' }}">
                            <span class="nav-link-content">
                                <svg class="nav-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                                <span>Question Bank</span>
                            </span>
                        </a>
                        <a href="{{ route('guru.exams.index') }}" class="nav-link nav-sub-link {{ request()->routeIs('guru.exams.*') ? 'active' : '' }}">
                            <span class="nav-link-content">
                                <svg class="nav-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                <span>Exam Management</span>
                            </span>
                        </a>
                        <a href="{{ route('guru.results.index') }}" class="nav-link nav-sub-link {{ request()->routeIs('guru.results.*') ? 'active' : '' }}">
                            <span class="nav-link-content">
                                <svg class="nav-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                                <span>Exam Results</span>
                            </span>
                        </a>
                    </div>

                    <a href="{{ route('guru.students.index') }}" class="nav-link {{ request()->routeIs('guru.students.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                            <span>All Students</span>
                        </span>
                    </a>

                    <a href="{{ route('guru.monitoring.index') }}" class="nav-link {{ request()->routeIs('guru.monitoring.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            <span>View Attendance</span>
                        </span>
                    </a>

                    <a href="{{ route('guru.reports.index') }}" class="nav-link {{ request()->routeIs('guru.reports.*') ? 'active' : '' }}">
                        <span class="nav-link-content">
                            <svg class="nav-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            <span>Create Report</span>
                        </span>
                    </a>
                @endif
            </nav>
        </aside>

        <!-- MAIN CONTENT WRAPPER -->
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

    <script src="{{ asset('js/cbt-offline.js') }}"></script>
</body>
</html>
