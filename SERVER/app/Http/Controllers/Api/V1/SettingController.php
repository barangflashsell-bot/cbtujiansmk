<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class SettingController extends ApiController
{
    protected string $settingsPath;

    public function __construct()
    {
        $this->settingsPath = storage_path('app/settings.json');
    }

    /**
     * Whitelist of allowed setting keys and their validation rules.
     */
    protected function allowedSettingsRules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:255'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'academic_year' => ['required', 'string', 'max:32'],
            'app_name' => ['required', 'string', 'max:100'],
            'server_port' => ['required', 'integer', 'between:80,65535'],
            'auto_token_release' => ['required', 'boolean'],
            'token_refresh_minutes' => ['required', 'integer', 'between:5,180'],
            'allow_student_review' => ['required', 'boolean'],
            'logo_url' => ['nullable', 'string', 'max:255'],
        ];
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
     * Display current application settings.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        // Students are strictly forbidden from viewing administrative settings
        if (in_array($userRole, ['student', 'peserta'], true)) {
            return $this->errorResponse('Akses ditolak. Peserta tidak memiliki izin untuk mengakses pengaturan sistem', null, 403);
        }

        $settings = $this->loadSettings();

        return $this->successResponse($settings, 'Pengaturan sistem berhasil diambil');
    }

    /**
     * Update application settings.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        // Only Administrator can update settings
        if ($userRole !== 'admin') {
            return $this->errorResponse('Akses ditolak. Hanya Administrator yang dapat mengubah pengaturan sistem', null, 403);
        }

        $rules = $this->allowedSettingsRules();
        $allowedKeys = array_keys($rules);

        // Reject unknown or arbitrary keys
        $inputKeys = array_keys($request->all());
        $unknownKeys = array_diff($inputKeys, $allowedKeys);

        if (! empty($unknownKeys)) {
            return $this->errorResponse('Terdapat kunci pengaturan yang tidak diizinkan atau tidak dikenal', [
                'unknown_keys' => array_values($unknownKeys),
            ], 422);
        }

        // Validate only fields present in request
        $fieldsToValidate = array_intersect_key($rules, $request->all());
        if (empty($fieldsToValidate)) {
            return $this->errorResponse('Tidak ada data pengaturan yang dikirim untuk diperbarui', null, 422);
        }

        $validator = Validator::make($request->all(), $fieldsToValidate);
        if ($validator->fails()) {
            return $this->errorResponse('Validasi pengaturan gagal', $validator->errors(), 422);
        }

        $validated = $validator->validated();

        $currentSettings = $this->loadSettings();
        $updatedSettings = array_merge($currentSettings, $validated);

        $this->saveSettings($updatedSettings);

        // Audit logging in activity_logs
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

        return $this->successResponse($updatedSettings, 'Pengaturan sistem berhasil diperbarui');
    }
}
