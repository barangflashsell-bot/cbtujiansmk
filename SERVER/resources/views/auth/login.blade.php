<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Administrator & Guru — CBT Server Manager</title>
    <link rel="stylesheet" href="{{ asset('css/cbt-offline.css') }}">
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
    <div class="login-page-wrap">
        <!-- FLOATING THEME SWITCHER [ ☀ Light | 🌙 Dark ] -->
        <div class="login-top-bar">
            <div class="theme-switch-pill" id="themeSwitchPill" title="Ganti Tema Tampilan">
                <button type="button" class="theme-switch-btn active" id="btnThemeLight" onclick="setAppTheme('light')">
                    <span>☀</span>
                    <span>Light</span>
                </button>
                <button type="button" class="theme-switch-btn" id="btnThemeDark" onclick="setAppTheme('dark')">
                    <span>🌙</span>
                    <span>Dark</span>
                </button>
            </div>
        </div>

        <!-- CENTERED LOGIN CARD -->
        <div class="login-card-modern">
            <!-- BRAND HEADER -->
            <div class="login-brand-header">
                <div class="login-brand-icon-wrap">
                    <svg width="48" height="48" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="loginBrandGrad" x1="0" y1="0" x2="36" y2="36" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#2563eb"/>
                                <stop offset="1" stop-color="#06b6d4"/>
                            </linearGradient>
                        </defs>
                        <rect width="36" height="36" rx="9" fill="url(#loginBrandGrad)"/>
                        <path d="M18 7L8 12V20C8 26 12.2 30.5 18 31.5C23.8 30.5 28 26 28 20V12L18 7Z" fill="#ffffff" fill-opacity="0.25" stroke="#ffffff" stroke-width="1.8"/>
                        <path d="M14 18L17 21L23 15" stroke="#ffffff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h1 class="login-title">CBT SERVER MANAGER</h1>
                <p class="login-subtitle">Masuk ke Panel Administrator & Guru Pengawas</p>
                <div class="login-badge-wrap">
                    <span class="status-badge-live online">
                        <span class="pulse-dot"></span>
                        <span>Server Offline LAN Aktif</span>
                    </span>
                </div>
            </div>

            <!-- NOTIFICATIONS -->
            @if (session('success'))
                <div class="alert alert-success">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- LOGIN FORM -->
            <form action="{{ route('login.submit') }}" method="POST">
                @csrf

                <!-- USERNAME -->
                <div class="form-group">
                    <label for="username" class="form-label">Username atau Akun</label>
                    <div class="login-input-wrap">
                        <span class="login-input-prefix">👤</span>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-control login-input-control" 
                            value="{{ old('username') }}" 
                            placeholder="Masukkan username login..."
                            required 
                            autofocus
                        >
                    </div>
                    @error('username')
                        <div style="font-size: 11.5px; color: var(--danger); margin-top: 4px; font-weight: 600;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- PASSWORD -->
                <div class="form-group">
                    <label for="password" class="form-label">Kata Sandi / Password</label>
                    <div class="login-input-wrap">
                        <span class="login-input-prefix">🔒</span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control login-input-control" 
                            placeholder="Masukkan kata sandi..."
                            required
                        >
                    </div>
                    @error('password')
                        <div style="font-size: 11.5px; color: var(--danger); margin-top: 4px; font-weight: 600;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- REMEMBER ME -->
                <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--text-secondary); cursor: pointer;">
                        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }} style="accent-color: var(--primary); width: 15px; height: 15px;">
                        <span>Ingat Sesi Login Ini</span>
                    </label>
                    <span style="font-size: 12px; color: var(--text-muted);">Port {{ request()->getPort() }}</span>
                </div>

                <!-- SUBMIT BUTTON -->
                <button type="submit" class="btn btn-primary login-submit-btn" style="justify-content: center;">
                    <span>Masuk</span>
                </button>
            </form>
        </div>
    </div>

    <script src="{{ asset('js/cbt-offline.js') }}"></script>
</body>
</html>
