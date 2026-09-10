<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CbtBackupCommand extends Command
{
    protected $signature = 'cbt:backup {--type=manual : Backup type (manual, pre_exam, post_exam)}';
    protected $description = 'Create a secure database backup snapshot of the CBT system';

    public function handle(): int
    {
        $type = $this->option('type');
        $backupDir = storage_path('app/backups');

        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = date('Ymd_His');
        $randomSuffix = bin2hex(random_bytes(4));
        $filename = "cbt_backup_{$type}_{$timestamp}_{$randomSuffix}.sql";
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $this->info("Membuat snapshot cadangan basis data CBT ({$type})...");

        try {
            $handle = fopen($filePath, 'w');
            if (! $handle) {
                $this->error("Gagal membuka berkas tujuan penulisan: {$filePath}");
                return Command::FAILURE;
            }

            fwrite($handle, "-- ========================================================\n");
            fwrite($handle, "-- CBT System Database Backup Snapshot\n");
            fwrite($handle, "-- Generated at: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- ========================================================\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n\n");

            $tables = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
            $totalRecords = 0;

            foreach ($tables as $tableRow) {
                $table = array_values((array) $tableRow)[0];
                $createTable = DB::select("SHOW CREATE TABLE `{$table}`");
                if (! empty($createTable)) {
                    $createRow = (array) $createTable[0];
                    $createSql = $createRow['Create Table'] ?? array_values($createRow)[1] ?? null;
                    if ($createSql) {
                        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                        fwrite($handle, $createSql . ";\n\n");
                    }
                }

                $count = DB::table($table)->count();
                $totalRecords += $count;

                if ($count > 0) {
                    DB::table($table)->orderBy(DB::raw('1'))->chunk(200, function ($rows) use ($handle, $table) {
                        foreach ($rows as $row) {
                            $rowArray = (array) $row;
                            $columns = array_keys($rowArray);
                            $escapedCols = array_map(fn ($c) => "`{$c}`", $columns);
                            $escapedVals = array_map(function ($val) {
                                if (is_null($val)) return 'NULL';
                                if (is_numeric($val) && ! is_string($val)) return $val;
                                return DB::getPdo()->quote((string) $val);
                            }, array_values($rowArray));

                            fwrite($handle, sprintf(
                                "INSERT INTO `%s` (%s) VALUES (%s);\n",
                                $table,
                                implode(', ', $escapedCols),
                                implode(', ', $escapedVals)
                            ));
                        }
                    });
                    fwrite($handle, "\n");
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fclose($handle);

            $sizeBytes = filesize($filePath);
            $checksum = hash_file('sha256', $filePath);

            ActivityLog::create([
                'user_id' => null,
                'action' => 'CLI_BACKUP_CREATED',
                'module' => 'backup',
                'details' => "Backup CLI berhasil dibuat: {$filename} ({$sizeBytes} bytes)",
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
            ]);

            $this->info("[OK] Backup berhasil dibuat!");
            $this->line("  Berkas   : {$filename}");
            $this->line("  Ukuran   : " . number_format($sizeBytes / 1024, 2) . " KB");
            $this->line("  Total Data: " . number_format($totalRecords) . " records");
            $this->line("  SHA256   : {$checksum}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Gagal membuat backup: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
