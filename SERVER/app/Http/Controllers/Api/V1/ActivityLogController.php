<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ActivityLogController extends ApiController
{
    /**
     * Display a paginated list of activity logs with safe whitelisted filters.
     * Restricted strictly to Administrator.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        // Only Administrator can inspect audit logs
        if ($userRole !== 'admin') {
            return $this->errorResponse('Akses ditolak. Hanya Administrator yang dapat melihat log aktivitas sistem', null, 403);
        }

        // Validate query parameters strictly
        $validator = Validator::make($request->query(), [
            'module' => ['nullable', 'string', 'max:32'],
            'action' => ['nullable', 'string', 'max:64'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Parameter filter log aktivitas tidak valid', $validator->errors(), 422);
        }

        $query = ActivityLog::with(['user:id,name,username,role_id'])
            ->latest('id');

        if ($request->filled('module')) {
            $query->where('module', $request->query('module'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        $perPage = (int) $request->query('per_page', 15);
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'module' => $log->module,
                'ip_address' => $log->ip_address,
                'details' => $log->details,
                'created_at' => $log->created_at?->toIso8601String(),
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'username' => $log->user->username,
                ] : null,
            ];
        });

        return $this->successResponse([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Log aktivitas sistem berhasil diambil');
    }

    /**
     * Record an integrity/anti-cheat event sent from client during exam session.
     * Actor is strictly authenticated user (anti-spoofing).
     */
    public function recordEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:WINDOW_FOCUS_LOST,APP_BACKGROUNDED,DEVICE_LOCKED,SCREEN_UNPINNED'],
            'details' => ['nullable', 'string', 'max:255'],
        ]);

        // Actor MUST come strictly from authenticated user context, never from body input
        $actorId = $request->user()->id;

        $log = ActivityLog::create([
            'user_id' => $actorId,
            'action' => $validated['action'],
            'module' => 'ANTI_CHEAT',
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'details' => $validated['details'] ?? null,
            'created_at' => now(),
        ]);

        return $this->successResponse([
            'id' => $log->id,
            'action' => $log->action,
            'module' => $log->module,
            'actor_id' => $actorId,
            'created_at' => $log->created_at?->toIso8601String(),
        ], 'Event integritas berhasil dicatat', 201);
    }
}
