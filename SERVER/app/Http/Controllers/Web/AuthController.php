<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the web login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            $role = strtolower(Auth::user()->role->name ?? '');

            if ($role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if (in_array($role, ['teacher', 'guru'], true)) {
                return redirect()->route('guru.dashboard');
            }

            Auth::logout();

            return redirect()->route('login')->with('error', 'Peserta ujian hanya dapat mengikuti ujian melalui aplikasi Android.');
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming web login request.
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $user = User::with('role')->where('username', $validated['username'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Username atau password salah.',
            ]);
        }

        if (! $user->is_active) {
            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Akun pengguna berstatus nonaktif. Silakan hubungi Administrator.',
            ]);
        }

        $roleName = strtolower($user->role->name ?? '');

        // Reject student from web admin/guru login
        if (in_array($roleName, ['student', 'peserta', 'siswa'], true)) {
            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Akun peserta tidak diizinkan login ke Web Admin/Guru. Peserta ujian wajib menggunakan aplikasi Android.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($roleName === 'admin') {
            return redirect()->intended(route('admin.dashboard'))->with('success', 'Selamat datang, Administrator.');
        }

        if (in_array($roleName, ['teacher', 'guru'], true)) {
            return redirect()->intended(route('guru.dashboard'))->with('success', 'Selamat datang, Bapak/Ibu Guru.');
        }

        Auth::logout();

        return redirect()->route('login')->with('error', 'Role pengguna tidak dikenali untuk akses Web Dashboard.');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }
}
