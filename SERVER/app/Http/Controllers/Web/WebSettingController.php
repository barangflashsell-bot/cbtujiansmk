<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class WebSettingController extends Controller
{
    protected string $settingsPath;

    public function __construct()
    {
        $this->settingsPath = storage_path('app/settings.json');
    }

    /**
     * Default application settings.
     */
    protected function defaultSettings(): array
    {
        return [
            'school_name' => 'CBT School',
            'school_address' => 'Jl. Pendidikan No. 1',
            'academic_year' => '2025/2026',
            'app_name' => 'CBT Local Server',
            'server_port' => 8000,
            'auto_token_release' => false,
            'token_refresh_minutes' => 15,
            'allow_student_review' => false,
            'logo_url' => null,
        ];
    }

    /**
     * Load settings from local storage with fallback to defaults.
     */
    protected function loadSettings(): array
    {
        if (File::exists($this->settingsPath)) {
            $content = File::get($this->settingsPath);
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return array_merge($this->defaultSettings(), $decoded);
            }
        }

        return $this->defaultSettings();
    }

    /**
     * Save settings array to storage file.
     */
    protected function saveSettings(array $settings): void
    {
        $dir = dirname($this->settingsPath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($this->settingsPath, json_encode($settings, JSON_PRETTY_PRINT));
    }

    /**
     * Display settings page.
     */
    public function index(Request $request): View
    {
        $userRole = strtolower($request->user()->role->name ?? '');
        if ($userRole !== 'admin') {
            abort(403, 'Akses ditolak. Fitur pengaturan sistem hanya dapat diakses oleh Administrator.');
        }

        $settings = $this->loadSettings();

        return view('admin.settings.index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update application settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');
        if ($userRole !== 'admin') {
            abort(403, 'Akses ditolak. Fitur pengaturan sistem hanya dapat diakses oleh Administrator.');
        }

        // Prepare request data with boolean conversions for HTML checkboxes
        $data = $request->all();
        $data['auto_token_release'] = $request->boolean('auto_token_release');
        $data['allow_student_review'] = $request->boolean('allow_student_review');

        // Whitelist validation
        $validated = validator($data, [
            'school_name' => ['required', 'string', 'max:255'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'academic_year' => ['required', 'string', 'max:32'],
            'app_name' => ['required', 'string', 'max:100'],
            'server_port' => ['required', 'integer', 'between:80,65535'],
            'auto_token_release' => ['required', 'boolean'],
            'token_refresh_minutes' => ['required', 'integer', 'between:5,180'],
            'allow_student_review' => ['required', 'boolean'],
            'logo_url' => ['nullable', 'string', 'max:255'],
        ], [
            'school_name.required' => 'Nama sekolah wajib diisi.',
            'academic_year.required' => 'Tahun ajaran wajib diisi.',
            'app_name.required' => 'Nama aplikasi wajib diisi.',
            'server_port.required' => 'Port server lokal wajib diisi.',
            'server_port.between' => 'Port server harus antara 80 dan 65535.',
            'token_refresh_minutes.between' => 'Interval refresh token harus antara 5 dan 180 menit.',
        ])->validate();

        $currentSettings = $this->loadSettings();
        $updatedSettings = array_merge($currentSettings, $validated);

        $this->saveSettings($updatedSettings);

        // Record audit log in activity_logs
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'UPDATE_SETTINGS',
            'module' => 'SETTINGS',
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'details' => json_encode([
                'updated_fields' => array_keys($validated),
            ]),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan sistem berhasil disimpan.');
    }

    /**
     * Download Android CBT Peserta APK installer.
     */
    public function downloadApk()
    {
        $candidatePaths = [
            public_path('downloads/cbt-peserta-v1.0.apk'),
            base_path('../ANDROID/build/app/outputs/flutter-apk/app-release.apk'),
            base_path('../ANDROID/build/app/outputs/apk/release/app-release.apk'),
        ];

        foreach ($candidatePaths as $path) {
            if (File::exists($path) && filesize($path) > 1000) {
                return response()->download($path, 'cbt-peserta-v1.0.apk', [
                    'Content-Type' => 'application/vnd.android.package-archive',
                    'Content-Disposition' => 'attachment; filename="cbt-peserta-v1.0.apk"',
                ]);
            }
        }

        abort(404, 'Berkas APK Android CBT belum tersedia atau sedang dikompilasi.');
    }
}
