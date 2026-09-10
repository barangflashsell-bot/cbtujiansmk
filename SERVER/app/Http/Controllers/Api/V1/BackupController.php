<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends ApiController
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (! File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Display a list of all database backup snapshots.
     */
    public function index(Request $request): JsonResponse
    {
        if (strtolower($request->user()->role->name ?? '') !== 'admin') {
            return $this->errorResponse('Akses ditolak. Fitur backup hanya dapat diakses oleh Administrator', null, 403);
        }

        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'sql') {
                $filename = $file->getFilename();
                $sizeBytes = $file->getSize();
                $createdAt = date('c', $file->getMTime());

                $backups[] = [
                    'filename' => $filename,
                    'size_bytes' => $sizeBytes,
                    'size_human' => $this->formatBytes($sizeBytes),
                    'created_at' => $createdAt,
                    'checksum_sha256' => hash_file('sha256', $file->getRealPath()),
                ];
            }
        }

        usort($backups, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return $this->successResponse([
            'total_backups' => count($backups),
            'items' => $backups,
        ], 'Daftar berkas cadangan database berhasil diambil');
    }

    /**
     * Generate a new database backup snapshot.
     */
    public function create(Request $request): JsonResponse
    {
        if (strtolower($request->user()->role->name ?? '') !== 'admin') {
            return $this->errorResponse('Akses ditolak. Fitur backup hanya dapat diakses oleh Administrator', null, 403);
        }

        $validated = $request->validate([
            'type' => 'nullable|string|in:manual,pre_exam,post_exam',
        ]);

        $type = $validated['type'] ?? 'manual';
        $timestamp = date('Ymd_His');
        $randomSuffix = bin2hex(random_bytes(4));
        $filename = "cbt_backup_{$type}_{$timestamp}_{$randomSuffix}.sql";
        $filePath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        // Perform safe database dump
        $dumpResult = $this->generateSqlDump($filePath);

        if (! $dumpResult['success']) {
            return $this->errorResponse('Gagal membuat berkas cadangan database: ' . $dumpResult['error'], null, 500);
        }

        $sizeBytes = filesize($filePath);

        return $this->successResponse([
            'filename' => $filename,
            'type' => $type,
            'size_bytes' => $sizeBytes,
            'size_human' => $this->formatBytes($sizeBytes),
            'tables_count' => $dumpResult['tables_count'],
            'total_records' => $dumpResult['records_count'],
            'checksum_sha256' => hash_file('sha256', $filePath),
            'created_at' => date('c'),
        ], 'Cadangan database berhasil dibuat', 201);
    }

    /**
     * Download a specific backup snapshot.
     */
    public function download(Request $request, string $filename): BinaryFileResponse|JsonResponse
    {
        if (strtolower($request->user()->role->name ?? '') !== 'admin') {
            return $this->errorResponse('Akses ditolak. Fitur backup hanya dapat diakses oleh Administrator', null, 403);
        }

        // Strict filename validation against path traversal
        if (! preg_match('/^[a-zA-Z0-9_\-]+\.sql$/', $filename)) {
            return $this->errorResponse('Nama berkas tidak valid atau terdeteksi path traversal', null, 400);
        }

        $filePath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        if (! File::exists($filePath)) {
            return $this->errorResponse('Berkas cadangan tidak ditemukan', null, 404);
        }

        $realPath = realpath($filePath);
        $realBackupDir = realpath($this->backupDir);
        if ($realPath === false || ! str_starts_with($realPath, $realBackupDir)) {
            return $this->errorResponse('Akses berkas tidak diizinkan', null, 403);
        }

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    /**
     * Delete an existing backup snapshot.
     */
    public function destroy(Request $request, string $filename): JsonResponse
    {
        if (strtolower($request->user()->role->name ?? '') !== 'admin') {
            return $this->errorResponse('Akses ditolak. Fitur backup hanya dapat diakses oleh Administrator', null, 403);
        }

        if (! preg_match('/^[a-zA-Z0-9_\-]+\.sql$/', $filename)) {
            return $this->errorResponse('Nama berkas tidak valid atau terdeteksi path traversal', null, 400);
        }

        $filePath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        if (! File::exists($filePath)) {
            return $this->errorResponse('Berkas cadangan tidak ditemukan', null, 404);
        }

        $realPath = realpath($filePath);
        $realBackupDir = realpath($this->backupDir);
        if ($realPath === false || ! str_starts_with($realPath, $realBackupDir)) {
            return $this->errorResponse('Akses berkas tidak diizinkan', null, 403);
        }

        File::delete($filePath);

        return $this->successResponse([
            'filename' => $filename,
        ], 'Berkas cadangan berhasil dihapus');
    }

    /**
     * Safely export database DDL and DML to an SQL file using pure PDO queries.
     * Guaranteed zero shell injection and zero external credential exposure.
     */
    protected function generateSqlDump(string $filePath): array
    {
        try {
            $handle = fopen($filePath, 'w');
            if (! $handle) {
                return ['success' => false, 'error' => 'Gagal membuka berkas tujuan untuk penulisan'];
            }

            fwrite($handle, "-- ========================================================\n");
            fwrite($handle, "-- CBT System Database Backup Snapshot\n");
            fwrite($handle, "-- Target: Local CBT Server (Windows Application)\n");
            fwrite($handle, "-- Generated at: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- ========================================================\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
            fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

            $tables = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
            $tableNames = [];
            foreach ($tables as $table) {
                $values = array_values((array) $table);
                if (! empty($values[0])) {
                    $tableNames[] = $values[0];
                }
            }

            $totalRecords = 0;

            foreach ($tableNames as $table) {
                // Table schema (DDL)
                $createTableResult = DB::select("SHOW CREATE TABLE `{$table}`");
                if (! empty($createTableResult)) {
                    $createRow = (array) $createTableResult[0];
                    $createSql = $createRow['Create Table'] ?? array_values($createRow)[1] ?? null;

                    if ($createSql) {
                        fwrite($handle, "-- --------------------------------------------------------\n");
                        fwrite($handle, "-- Table structure for table `{$table}`\n");
                        fwrite($handle, "-- --------------------------------------------------------\n");
                        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                        fwrite($handle, $createSql . ";\n\n");
                    }
                }

                // Table data (DML)
                $count = DB::table($table)->count();
                $totalRecords += $count;

                if ($count > 0) {
                    fwrite($handle, "-- Dumping data for table `{$table}` ({$count} records)\n");

                    DB::table($table)->orderBy(DB::raw('1'))->chunk(200, function ($rows) use ($handle, $table) {
                        foreach ($rows as $row) {
                            $rowArray = (array) $row;
                            $columns = array_keys($rowArray);
                            $escapedColumns = array_map(fn ($col) => "`{$col}`", $columns);

                            $escapedValues = array_map(function ($val) {
                                if (is_null($val)) {
                                    return 'NULL';
                                }
                                if (is_numeric($val) && ! is_string($val)) {
                                    return $val;
                                }
                                return DB::getPdo()->quote((string) $val);
                            }, array_values($rowArray));

                            $sql = sprintf(
                                "INSERT INTO `%s` (%s) VALUES (%s);\n",
                                $table,
                                implode(', ', $escapedColumns),
                                implode(', ', $escapedValues)
                            );
                            fwrite($handle, $sql);
                        }
                    });

                    fwrite($handle, "\n");
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fwrite($handle, "-- End of backup snapshot\n");
            fclose($handle);

            return [
                'success' => true,
                'tables_count' => count($tableNames),
                'records_count' => $totalRecords,
            ];
        } catch (\Throwable $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
