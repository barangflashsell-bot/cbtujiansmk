<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — CBT Local Server</title>
    <link rel="stylesheet" href="{{ asset('css/cbt-offline.css') }}">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">CBT</div>
                <h1 class="login-title">CBT Local Server</h1>
                <p class="login-subtitle">Masuk ke Web Admin & Guru Dashboard</p>
                <div class="offline-badge">
                    <span class="status-dot"></span>
                    <span>Offline LAN / Local Server</span>
                </div>
            </div>

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

            <form action="{{ route('login.submit') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        value="{{ old('username') }}" 
                        placeholder="Masukkan username Anda"
                        required 
                        autofocus
                    >
                    @error('username')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Masukkan password Anda"
                        required
                    >
                    @error('password')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary); cursor: pointer;">
                        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        <span>Ingat Sesi Ini</span>
                    </label>
                </div>

                <button type="submit" class="btn-primary">
                    Masuk ke Dashboard
                </button>
            </form>

            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color); text-align: center;">
                <p style="font-size: 11.5px; color: var(--text-muted); line-height: 1.5;">
                    ⚠️ Khusus Administrator dan Guru.<br>
                    Peserta ujian hanya dapat login melalui aplikasi Android.
                </p>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/cbt-offline.js') }}"></script>
</body>
</html>
