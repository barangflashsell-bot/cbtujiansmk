<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated or invalid token',
                    'errors' => null,
                ], 401);
            }

            return redirect()->guest(route('login'));
        }

        $userRole = strtolower($user->role->name ?? '');

        // Normalize roles and support aliases (guru -> teacher, peserta -> student)
        $allowedRoles = array_map(function ($role) {
            $normalized = strtolower(trim($role));
            return match ($normalized) {
                'guru' => 'teacher',
                'peserta', 'siswa' => 'student',
                default => $normalized,
            };
        }, $roles);

        if (! in_array($userRole, $allowedRoles, true)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses resource ini.',
                    'errors' => null,
                ], 403);
            }

            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
