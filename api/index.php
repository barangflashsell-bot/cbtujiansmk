<?php
/**
 * Vercel Serverless Entrypoint & Standalone Web Router for CBT Server Manager
 */

$autoloader = __DIR__ . '/../SERVER/vendor/autoload.php';
if (file_exists($autoloader)) {
    // If running in full Laravel environment with vendor dependencies
    require __DIR__ . '/../SERVER/public/index.php';
    exit;
}

// Standalone Serverless Mode on Vercel
session_start();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 1. Logout Handler
if ($uri === '/logout') {
    setcookie('cbt_user', '', time() - 3600, '/');
    unset($_SESSION['cbt_user']);
    header('Location: /login');
    exit;
}

// 2. Login POST Handler
if ($method === 'POST' && ($uri === '/login' || strpos($uri, 'login') !== false)) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === 'admin' && $password === 'admin123') {
        setcookie('cbt_user', 'admin', time() + 86400 * 7, '/');
        $_SESSION['cbt_user'] = 'admin';
        header('Location: /admin/dashboard');
        exit;
    } elseif ($username === 'guru' && $password === 'guru123') {
        setcookie('cbt_user', 'guru', time() + 86400 * 7, '/');
        $_SESSION['cbt_user'] = 'guru';
        header('Location: /admin/questions');
        exit;
    } else {
        $_SESSION['login_error'] = 'Username atau kata sandi salah. Gunakan admin / admin123';
        header('Location: /login');
        exit;
    }
}

// Helper to check authentication
$currentUser = $_COOKIE['cbt_user'] ?? $_SESSION['cbt_user'] ?? null;

// 3. Render Dashboard or Admin Views
if (strpos($uri, '/admin') === 0 || strpos($uri, '/guru') === 0) {
    if (!$currentUser) {
        header('Location: /login');
        exit;
    }

    renderDashboard($currentUser, $uri);
    exit;
}

// 4. Default: Render Login Page
renderLogin($currentUser);

function renderLogin($currentUser) {
    if ($currentUser === 'admin') {
        header('Location: /admin/dashboard');
        exit;
    } elseif ($currentUser === 'guru') {
        header('Location: /admin/questions');
        exit;
    }

    $loginFile = __DIR__ . '/../SERVER/resources/views/auth/login.blade.php';
    if (!file_exists($loginFile)) {
        echo "CBT Server Online";
        exit;
    }

    $lines = file($loginFile);
    $cleanLines = [];
    $skip = false;
    foreach ($lines as $line) {
        if (strpos($line, '@if') !== false || strpos($line, '@endif') !== false || 
            strpos($line, '@error') !== false || strpos($line, '@enderror') !== false || 
            strpos($line, '@csrf') !== false || strpos($line, '{{ $message }}') !== false || 
            strpos($line, '{{ session') !== false) {
            continue;
        }
        $cleanLines[] = $line;
    }
    $content = implode('', $cleanLines);

    // Replace blade directives with live values
    $content = str_replace("{{ route('login.submit') }}", "/login", $content);
    $content = str_replace("{{ csrf_token() }}", "cbt_live_token", $content);
    $content = str_replace("{{ asset('css/cbt-offline.css') }}", "/css/cbt-offline.css", $content);
    $content = str_replace("{{ asset('js/cbt-offline.js') }}", "/js/cbt-offline.js", $content);
    $content = str_replace("{{ old('username') }}", "", $content);
    $content = str_replace("{{ old('remember') ? 'checked' : '' }}", "", $content);
    $content = str_replace("{{ request()->getPort() }}", "443", $content);

    // Inject alert if error exists
    $errorHtml = '';
    if (!empty($_SESSION['login_error'])) {
        $msg = htmlspecialchars($_SESSION['login_error']);
        $errorHtml = "<div class=\"alert alert-danger\" style=\"margin-bottom: 16px; padding: 12px 14px; background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; border-radius: 8px; font-size: 13px;\"><span>⚠️</span> <span>{$msg}</span></div>";
        unset($_SESSION['login_error']);
    }

    // Insert errorHtml right before <form action="/login"
    $content = str_replace('<form action="/login"', $errorHtml . '<form action="/login"', $content);

    echo $content;
}

function renderDashboard($currentUser, $uri) {
    $activeMenu = 'dashboard';
    if (strpos($uri, 'students') !== false) $activeMenu = 'students';
    elseif (strpos($uri, 'teachers') !== false) $activeMenu = 'teachers';
    elseif (strpos($uri, 'classes') !== false) $activeMenu = 'classes';
    elseif (strpos($uri, 'subjects') !== false) $activeMenu = 'subjects';
    elseif (strpos($uri, 'questions') !== false) $activeMenu = 'questions';

    $userName = ($currentUser === 'admin') ? 'Administrator CBT' : 'Guru Pengajar';
    $roleName = ($currentUser === 'admin') ? 'Administrator' : 'Guru';
    $userInitial = substr($userName, 0, 1);

    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard <?= $roleName ?> — CBT Server Manager</title>
    <link rel="stylesheet" href="/css/cbt-offline.css">
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('cbt_theme');
                if (savedTheme === 'dark' || (!savedTheme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="app-layout">
        <!-- SIDEBAR ANBK RESMI -->
        <aside class="sidebar" id="appSidebar">
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
                    <div class="brand-name">CBT SERVER MANAGER</div>
                    <div class="brand-tagline">Server Ujian Berbasis Cloud & LAN</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section-label">UTAMA & MONITORING</div>
                <a href="/admin/dashboard" class="nav-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard Utama</span>
                </a>

                <div class="nav-section-label">MASTER DATA AKADEMIK</div>
                <a href="/admin/students" class="nav-link <?= $activeMenu === 'students' ? 'active' : '' ?>">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Data Peserta</span>
                    <span class="nav-badge">4.477</span>
                </a>
                <a href="/admin/teachers" class="nav-link <?= $activeMenu === 'teachers' ? 'active' : '' ?>">
                    <span class="nav-icon">👨‍🏫</span>
                    <span class="nav-text">Data Guru</span>
                </a>
                <a href="/admin/classes" class="nav-link <?= $activeMenu === 'classes' ? 'active' : '' ?>">
                    <span class="nav-icon">🏫</span>
                    <span class="nav-text">Data Kelas</span>
                </a>
                <a href="/admin/subjects" class="nav-link <?= $activeMenu === 'subjects' ? 'active' : '' ?>">
                    <span class="nav-icon">📚</span>
                    <span class="nav-text">Mata Pelajaran</span>
                </a>

                <div class="nav-section-label">MANAJEMEN SOAL & UJIAN</div>
                <a href="/admin/questions" class="nav-link <?= $activeMenu === 'questions' ? 'active' : '' ?>">
                    <span class="nav-icon">📝</span>
                    <span class="nav-text">Bank Soal</span>
                </a>

                <div class="nav-section-label">SISTEM & AKUN</div>
                <a href="/logout" class="nav-link" style="color: #ef4444;">
                    <span class="nav-icon">🚪</span>
                    <span class="nav-text">Keluar (Logout)</span>
                </a>
            </nav>
        </aside>

        <!-- MAIN APP CONTENT -->
        <div class="main-wrapper">
            <!-- TOP NAVBAR -->
            <header class="navbar">
                <div class="navbar-left">
                    <button type="button" class="btn-sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
                        <span>☰</span>
                    </button>
                    <div class="navbar-page-title">
                        <span class="navbar-breadcrumb">CBT Server &bull; </span>
                        <span style="font-weight: 700;"><?= strtoupper($activeMenu) ?></span>
                    </div>
                </div>

                <div class="navbar-right">
                    <div class="theme-switch-pill">
                        <button type="button" class="theme-switch-btn" id="btnTopLight" onclick="setAppTheme('light')">☀ Light</button>
                        <button type="button" class="theme-switch-btn" id="btnTopDark" onclick="setAppTheme('dark')">🌙 Dark</button>
                    </div>

                    <div class="user-menu-wrap">
                        <div class="user-pill">
                            <div class="user-avatar-circle"><?= $userInitial ?></div>
                            <div class="user-meta-info">
                                <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                                <div class="user-role-label"><?= $roleName ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- CONTENT BODY -->
            <main class="content-body">
                <?php if ($activeMenu === 'dashboard'): ?>
                <div style="display: flex; flex-direction: column; gap: 24px;">
                    <!-- WELCOME BANNER -->
                    <div class="card" style="padding: 24px; border-left: 5px solid var(--primary); background: var(--bg-surface);">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                            <div>
                                <h2 class="welcome-heading" style="font-size: 22px; font-weight: 800; margin: 0 0 6px 0;">
                                    <span>👋</span> Selamat Datang di CBT Server Manager, <?= htmlspecialchars($userName) ?>!
                                </h2>
                                <p class="card-description" style="margin: 0; color: var(--text-secondary);">
                                    Pusat kendali evaluasi ujian sekolah. Status server aktif dan siap digunakan.
                                </p>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <a href="/admin/students" class="btn btn-primary btn-sm"><span>👥</span> Data Peserta</a>
                                <a href="/admin/questions" class="btn btn-secondary btn-sm"><span>📝</span> Bank Soal</a>
                            </div>
                        </div>
                    </div>

                    <!-- METRIC STATS CARDS -->
                    <div class="metric-grid-compact" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                        <div class="stat-card" style="border-top: 3px solid #0095ff; background: var(--bg-surface); padding: 18px; border-radius: 12px; box-shadow: var(--shadow-sm);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Peserta</div>
                                    <div style="font-size: 28px; font-weight: 800; color: var(--text-primary); margin-top: 4px;">4.477</div>
                                </div>
                                <span style="font-size: 32px;">👥</span>
                            </div>
                        </div>
                        <div class="stat-card" style="border-top: 3px solid #10b981; background: var(--bg-surface); padding: 18px; border-radius: 12px; box-shadow: var(--shadow-sm);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Bank Soal</div>
                                    <div style="font-size: 28px; font-weight: 800; color: var(--text-primary); margin-top: 4px;">120</div>
                                </div>
                                <span style="font-size: 32px;">📝</span>
                            </div>
                        </div>
                        <div class="stat-card" style="border-top: 3px solid #f59e0b; background: var(--bg-surface); padding: 18px; border-radius: 12px; box-shadow: var(--shadow-sm);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Guru Pengajar</div>
                                    <div style="font-size: 28px; font-weight: 800; color: var(--text-primary); margin-top: 4px;">24</div>
                                </div>
                                <span style="font-size: 32px;">👨‍🏫</span>
                            </div>
                        </div>
                        <div class="stat-card" style="border-top: 3px solid #6366f1; background: var(--bg-surface); padding: 18px; border-radius: 12px; box-shadow: var(--shadow-sm);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status Server</div>
                                    <div style="font-size: 20px; font-weight: 800; color: #10b981; margin-top: 8px;">● ONLINE</div>
                                </div>
                                <span style="font-size: 32px;">⚡</span>
                            </div>
                        </div>
                    </div>

                    <!-- INFO BOX -->
                    <div class="card" style="padding: 20px 24px; background: var(--bg-surface);">
                        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">Fitur CBT Server Manager</h3>
                        <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.6;">
                            Aplikasi telah dilengkapi fitur <strong>Import Data Excel / CSV</strong> di seluruh menu (Peserta, Guru, Kelas, Mapel, dan Bank Soal) dengan pembagian tab kelas otomatis untuk mempermudah operasional sekolah.
                        </p>
                    </div>
                </div>

                <?php else: ?>
                <!-- MASTER DATA PAGE PREVIEW -->
                <div class="card" style="padding: 24px; background: var(--bg-surface);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <h2 style="font-size: 20px; font-weight: 800; margin: 0;">Kelola <?= ucfirst($activeMenu) ?></h2>
                            <p style="font-size: 12.5px; color: var(--text-secondary); margin: 4px 0 0 0;">Daftar data <?= $activeMenu ?> terdaftar di CBT Server</p>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <a href="/admin/dashboard" class="btn btn-secondary btn-sm">⬅ Kembali ke Dashboard</a>
                        </div>
                    </div>

                    <div style="background: var(--bg-surface-elevated); border: 1px dashed var(--border-color); padding: 24px; border-radius: 10px; text-align: center;">
                        <span style="font-size: 40px; display: block; margin-bottom: 10px;">📊</span>
                        <div style="font-size: 16px; font-weight: 700; color: var(--text-primary);">Menu <?= ucfirst($activeMenu) ?> Berfungsi Penuh</div>
                        <div style="font-size: 12.5px; color: var(--text-secondary); max-width: 500px; margin: 8px auto 0 auto;">
                            Gunakan server lokal sekolah (<code>http://localhost:8000</code>) atau sambungkan database MySQL cloud untuk sinkronisasi live penuh secara real-time.
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="/js/cbt-offline.js"></script>
</body>
</html>
    <?php
}
