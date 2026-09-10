<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebActivityLogController extends Controller
{
    /**
     * Display a listing of system activity and audit logs.
     * Strictly read-only for Administrator.
     */
    public function index(Request $request): View
    {
        $userRole = strtolower($request->user()->role->name ?? '');
        if ($userRole !== 'admin') {
            abort(403, 'Akses ditolak. Log aktivitas sistem hanya dapat diakses oleh Administrator.');
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

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('details', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = 20;
        $logs = $query->paginate($perPage)->withQueryString();

        // Get distinct modules for filter dropdown
        $modules = ActivityLog::select('module')
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->all();

        // Users who have activities
        $users = User::whereIn('id', function ($q) {
            $q->select('user_id')->from('activity_logs')->whereNotNull('user_id');
        })->orderBy('name')->get(['id', 'name', 'username']);

        return view('admin.activity_logs.index', [
            'logs' => $logs,
            'modules' => $modules,
            'users' => $users,
        ]);
    }
}
