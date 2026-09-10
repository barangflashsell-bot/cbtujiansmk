<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CbtRestoreCommand extends Command
{
    protected $signature = 'cbt:restore {file? : Name of SQL backup file in storage/app/backups or absolute path} {--force : Force restore without interactive prompt}';
    protected $description = 'Restore the CBT database from a verified SQL backup file (Disaster Recovery)';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        $fileArg = $this->argument('file');

        if (! $fileArg) {
            // Find latest backup file
            $files = File::files($backupDir);
            $sqlFiles = array_filter($files, fn ($f) => $f->getExtension() === 'sql');
            if (empty($sqlFiles)) {
                $this->error("Tidak ditemukan berkas cadangan di {$backupDir}");
                return Command::FAILURE;
            }
            usort($sqlFiles, fn ($a, $b) => $b->getMTime() - $a->getMTime());
            $targetPath = $sqlFiles[0]->getRealPath();
        } else {
            if (File::exists($fileArg)) {
                $targetPath = $fileArg;
            } elseif (File::exists($backupDir . DIRECTORY_SEPARATOR . $fileArg)) {
                $targetPath = $backupDir . DIRECTORY_SEPARATOR . $fileArg;
            } else {
                $this->error("Berkas cadangan tidak ditemukan: {$fileArg}");
                return Command::FAILURE;
            }
        }

        $filename = basename($targetPath);
        $sizeBytes = filesize($targetPath);
        $checksum = hash_file('sha256', $targetPath);

        $this->warn("========================================================================");
        $this->warn(" CBT DISASTER RECOVERY RESTORE TOOL");
        $this->warn("========================================================================");
        $this->line("  Target File : {$filename}");
        $this->line("  Size        : " . number_format($sizeBytes / 1024, 2) . " KB");
        $this->line("  SHA256      : {$checksum}");
        $this->warn("PERINGATAN: Operasi ini akan menimpa seluruh tabel dengan data dari cadangan.");

        if (! $this->option('force') && ! $this->confirm('Apakah Anda yakin ingin melanjutkan pemulihan basis data?')) {
            $this->info("Pemulihan dibatalkan.");
            return Command::SUCCESS;
        }

        $this->info("Menjalankan pemulihan basis data...");

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
            DB::unprepared(File::get($targetPath));
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

            ActivityLog::create([
                'user_id' => null,
                'action' => 'CLI_RESTORE_EXECUTED',
                'module' => 'backup',
                'details' => "Pemulihan basis data CLI berhasil dari: {$filename}",
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
            ]);

            $this->info("[OK] Pemulihan basis data CBT selesai dengan sukses!");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
            $this->error("Gagal melakukan pemulihan: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
