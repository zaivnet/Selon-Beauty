<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\BackupRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackupService
{
    protected array $applicationTables = [
        'users',
        'employees',
        'job_titles',
        'attendance_locations',
        'shifts',
        'work_schedules',
        'attendance_records',
        'leave_requests',
        'overtime_requests',
        'attendance_corrections',
        'notifications',
        'audit_logs',
        'app_settings',
        'holidays',
        'backup_records',
    ];

    /**
     * Detect hosting backup engine capability (mysqldump vs logical export).
     */
    public function detectEngine(): string
    {
        if (function_exists('exec')) {
            $disabled = explode(',', ini_get('disable_functions'));
            if (! in_array('exec', array_map('trim', $disabled), true)) {
                $returnCode = 127;
                @exec('mysqldump --version 2>&1', $output, $returnCode);
                if ($returnCode === 0) {
                    return 'mysqldump';
                }
            }
        }

        return 'logical_export';
    }

    /**
     * Create a new database or full application backup.
     */
    public function createBackup(string $type = 'database', ?User $user = null, bool $isPreRestore = false): BackupRecord
    {
        if (! in_array($type, ['database', 'full'], true)) {
            throw new \InvalidArgumentException('Jenis backup tidak valid.');
        }

        $uuid = (string) Str::uuid();
        $shortUuid = strtoupper(substr($uuid, 0, 6));
        $timestamp = Carbon::now('Asia/Jakarta')->format('Y-m-d-His');
        $filename = "selon-backup-{$type}-{$timestamp}-{$shortUuid}.zip";
        $relativeStoragePath = "private/backups/{$filename}";

        $backupRecord = BackupRecord::create([
            'backup_uuid' => $uuid,
            'type' => $type,
            'file_path' => $relativeStoragePath,
            'file_size' => 0,
            'checksum' => null,
            'status' => 'creating',
            'created_by' => $user?->id,
            'is_pre_restore' => $isPreRestore,
            'metadata' => [
                'engine' => $this->detectEngine(),
                'format_version' => '1.0',
            ],
        ]);

        try {
            $tempDir = storage_path('app/private/temp/'.$uuid);
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // 1. Export Database Data
            $recordCounts = $this->exportDatabaseData($tempDir);

            // 2. Export Files if FULL backup
            $mediaCategories = [];
            if ($type === 'full') {
                $mediaCategories = $this->exportApplicationFiles($tempDir);
            }

            // 3. Create Manifest
            $manifestData = [
                'backup_format_version' => '1.0',
                'app_version' => '1.0.0',
                'created_at' => Carbon::now('Asia/Jakarta')->toIso8601String(),
                'created_by' => $user ? ['id' => $user->id, 'name' => $user->name, 'email' => $user->email] : 'System Cron',
                'database_driver' => DB::connection()->getDriverName(),
                'backup_type' => $type,
                'is_pre_restore' => $isPreRestore,
                'engine_used' => $this->detectEngine(),
                'schema_version' => $this->schemaVersion(),
                'database_file' => 'database/dump.json',
                'included_components' => array_keys($mediaCategories),
                'record_counts' => $recordCounts,
                'file_counts' => array_map(fn (array $category) => $category['file_count'], $mediaCategories),
                'media_categories' => $mediaCategories,
                'media_total_size' => array_sum(array_column($mediaCategories, 'total_size')),
            ];

            file_put_contents($tempDir.'/backup-manifest.json', json_encode($manifestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // 4. Generate Checksums
            $checksums = $this->generateDirectoryChecksums($tempDir);
            file_put_contents($tempDir.'/checksums.json', json_encode($checksums, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // 5. Create Archive File in private storage
            $zipPath = storage_path('app/'.$relativeStoragePath);
            $zipDir = dirname($zipPath);
            if (! is_dir($zipDir)) {
                mkdir($zipDir, 0755, true);
            }

            $this->zipDirectory($tempDir, $zipPath);

            // Cleanup temp directory
            $this->deleteDirectory($tempDir);

            $fileSize = filesize($zipPath);
            $zipChecksum = hash_file('sha256', $zipPath);

            $backupRecord->update([
                'file_size' => $fileSize,
                'checksum' => $zipChecksum,
                'status' => 'completed',
                'metadata' => array_merge($backupRecord->metadata ?? [], [
                    'manifest' => $manifestData,
                ]),
            ]);

            AuditLog::log('backup.created', $backupRecord, null, $backupRecord->toArray(), $user);

            return $backupRecord;
        } catch (\Throwable $e) {
            $backupRecord->update(['status' => 'failed']);
            Log::error('Backup creation failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            throw new \RuntimeException('Gagal membuat backup: '.$e->getMessage(), 0, $e);
        } finally {
            if (isset($tempDir) && is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Restore application data & database from a BackupRecord.
     */
    public function restoreBackup(BackupRecord $record, string $password, User $actor): void
    {
        if ($actor->role !== 'superadmin') {
            throw new \InvalidArgumentException('Akses ditolak. Restore backup hanya dapat dilakukan oleh Superadmin.');
        }

        if (! Hash::check($password, $actor->password)) {
            throw new \InvalidArgumentException('Password konfirmasi Superadmin tidak sesuai.');
        }

        if ($record->status !== 'completed') {
            throw new \InvalidArgumentException('File backup ini belum selesai atau berstatus gagal/dihapus.');
        }

        $zipPath = storage_path('app/'.$record->file_path);
        if (! file_exists($zipPath)) {
            throw new \InvalidArgumentException('Physical file backup tidak ditemukan pada storage.');
        }

        // Verify SHA-256 integrity
        if ($record->checksum && hash_file('sha256', $zipPath) !== $record->checksum) {
            throw new \InvalidArgumentException('Integritas file backup (Checksum SHA-256) tidak cocok. File kemungkinan rusak.');
        }

        // 1. Create Pre-Restore Safety Backup
        AuditLog::log('restore.started', $record, null, ['record_id' => $record->id], $actor);

        $safetyBackup = $this->createBackup('full', $actor, isPreRestore: true);
        if ($safetyBackup->status !== 'completed') {
            throw new \RuntimeException('Gagal membuat Pre-Restore Safety Backup. Proses restore dibatalkan secara aman.');
        }

        $tempExtractDir = storage_path('app/private/temp/restore_'.Str::uuid());
        if (! is_dir($tempExtractDir)) {
            mkdir($tempExtractDir, 0755, true);
        }

        try {
            // 2. Extract and Check Zip Slip Protection
            $this->extractZipWithProtection($zipPath, $tempExtractDir);

            // 3. Verify Manifest & Checksums
            $manifestFile = $tempExtractDir.'/backup-manifest.json';
            if (! file_exists($manifestFile)) {
                throw new \InvalidArgumentException('Manifest backup (backup-manifest.json) tidak ditemukan.');
            }

            $manifest = json_decode(file_get_contents($manifestFile), true);
            if (! isset($manifest['backup_format_version']) || $manifest['backup_format_version'] !== '1.0') {
                throw new \InvalidArgumentException('Versi format backup tidak kompatibel.');
            }

            $checksumsFile = $tempExtractDir.'/checksums.json';
            if (file_exists($checksumsFile)) {
                $checksums = json_decode(file_get_contents($checksumsFile), true);
                foreach ($checksums as $relFile => $expectedHash) {
                    $targetPath = $tempExtractDir.'/'.$relFile;
                    if (file_exists($targetPath)) {
                        if (hash_file('sha256', $targetPath) !== $expectedHash) {
                            throw new \InvalidArgumentException("Integritas file '{$relFile}' gagal diverifikasi (Checksum mismatch).");
                        }
                    }
                }
            }

            // 4. Restore Database Data
            $this->importDatabaseData($tempExtractDir);

            // 5. Restore Files if Full Backup
            if (is_dir($tempExtractDir.'/files')) {
                $this->importApplicationFiles($tempExtractDir.'/files');
            }

            // Cleanup temp
            $this->deleteDirectory($tempExtractDir);

            AuditLog::log('restore.completed', $record, null, [
                'restored_from' => $record->id,
                'pre_restore_backup_id' => $safetyBackup->id,
            ], $actor);
        } catch (\Throwable $e) {
            $this->deleteDirectory($tempExtractDir);
            AuditLog::log('restore.failed', $record, null, ['error' => $e->getMessage()], $actor);
            throw new \RuntimeException('Gagal melakukan restore: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete physical backup file and update record status.
     */
    public function deleteBackup(BackupRecord $record, User $actor): void
    {
        if (! in_array($actor->role, ['superadmin', 'owner'], true)) {
            throw new \InvalidArgumentException('Hanya Superadmin dan Owner yang dapat menghapus data backup.');
        }

        $fullPath = storage_path('app/'.$record->file_path);
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }

        $before = $record->toArray();
        $record->update(['status' => 'deleted']);

        AuditLog::log('backup.deleted', $record, $before, $record->toArray(), $actor);
    }

    /**
     * Clean up excess backups based on retention count policy.
     */
    public function applyRetentionPolicy(): int
    {
        $retentionCount = (int) AppSetting::get('backup_scheduled_retention_count', 14);
        $retentionCount = max(3, min(100, $retentionCount));

        // Get completed non-pre-restore backups sorted by newest
        $backups = BackupRecord::where('status', 'completed')
            ->where('is_pre_restore', false)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($backups->count() <= $retentionCount) {
            return 0;
        }

        $excess = $backups->slice($retentionCount);
        $deletedCount = 0;

        foreach ($excess as $backup) {
            $fullPath = storage_path('app/'.$backup->file_path);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
            $backup->update(['status' => 'deleted']);
            $deletedCount++;
        }

        return $deletedCount;
    }

    // ==========================================
    // PRIVATE INTERNAL HELPERS
    // ==========================================

    protected function exportDatabaseData(string $tempDir): array
    {
        $dbDir = $tempDir.'/database';
        mkdir($dbDir, 0755, true);

        $dumpData = [];
        $recordCounts = [];

        foreach ($this->applicationTables as $table) {
            if (Schema::hasTable($table)) {
                $rows = DB::table($table)->get()->map(function ($row) {
                    return (array) $row;
                })->toArray();

                $dumpData[$table] = $rows;
                $recordCounts[$table] = count($rows);
            }
        }

        file_put_contents($dbDir.'/dump.json', json_encode($dumpData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $recordCounts;
    }

    protected function exportApplicationFiles(string $tempDir): array
    {
        $filesDir = $tempDir.'/files';
        mkdir($filesDir, 0755, true);

        return [
            'attendance_selfies' => $this->copyStorageDirectoryToArchive('local', 'attendance', $filesDir.'/attendance'),
            'leave_attachments' => $this->copyStorageDirectoryToArchive('local', 'leave-attachments', $filesDir.'/leave-attachments'),
            'branding' => $this->copyStorageDirectoryToArchive('public', 'branding', $filesDir.'/branding'),
        ];
    }

    protected function importDatabaseData(string $tempDir): void
    {
        $dumpFile = $tempDir.'/database/dump.json';
        if (! file_exists($dumpFile)) {
            throw new \InvalidArgumentException('Database dump file (database/dump.json) tidak ditemukan dalam archive.');
        }

        $dumpData = json_decode(file_get_contents($dumpFile), true);
        if (! is_array($dumpData)) {
            throw new \InvalidArgumentException('Format database dump tidak valid.');
        }

        // Capture existing pre-restore safety backups to preserve them
        $preRestoreRecords = Schema::hasTable('backup_records')
            ? DB::table('backup_records')->where('is_pre_restore', true)->get()->map(fn ($r) => (array) $r)->toArray()
            : [];

        // Perform transactional database import with FK checks disabled controls
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        try {
            foreach ($this->applicationTables as $table) {
                if (isset($dumpData[$table]) && Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                    $rows = $dumpData[$table];
                    if (! empty($rows)) {
                        foreach (array_chunk($rows, 100) as $chunk) {
                            DB::table($table)->insert($chunk);
                        }
                    }
                }
            }

            // Re-insert pre-restore safety backup records so they are never lost
            if (! empty($preRestoreRecords)) {
                foreach ($preRestoreRecords as $pRec) {
                    DB::table('backup_records')->updateOrInsert(['id' => $pRec['id']], $pRec);
                }
            }
        } finally {
            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    protected function importApplicationFiles(string $filesDir): void
    {
        // Current format: private attendance media. Keep legacy "selfies" readable.
        if (is_dir($filesDir.'/attendance')) {
            $this->copyArchiveDirectoryToStorage($filesDir.'/attendance', 'local', 'attendance');
        } elseif (is_dir($filesDir.'/selfies')) {
            $this->copyArchiveDirectoryToStorage($filesDir.'/selfies', 'local', 'attendance');
        }

        if (is_dir($filesDir.'/leave-attachments')) {
            $this->copyArchiveDirectoryToStorage($filesDir.'/leave-attachments', 'local', 'leave-attachments');
        }

        if (is_dir($filesDir.'/branding')) {
            $this->copyArchiveDirectoryToStorage($filesDir.'/branding', 'public', 'branding');
        }
    }

    protected function schemaVersion(): ?string
    {
        if (! Schema::hasTable('migrations')) {
            return null;
        }

        return DB::table('migrations')->orderByDesc('id')->value('migration');
    }

    protected function copyStorageDirectoryToArchive(string $disk, string $source, string $destination): array
    {
        $storage = Storage::disk($disk);
        $files = $storage->allFiles($source);
        $totalSize = 0;

        foreach ($files as $file) {
            $relative = ltrim(substr($file, strlen($source)), '/\\');
            $target = $destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $targetDirectory = dirname($target);

            if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0755, true) && ! is_dir($targetDirectory)) {
                throw new \RuntimeException("Gagal membuat direktori media backup: {$targetDirectory}");
            }

            $stream = $storage->readStream($file);
            if ($stream === false) {
                throw new \RuntimeException("Gagal membaca media backup dari disk {$disk}: {$file}");
            }

            $targetStream = fopen($target, 'wb');
            if ($targetStream === false) {
                fclose($stream);
                throw new \RuntimeException("Gagal menulis media ke archive: {$file}");
            }

            try {
                if (stream_copy_to_stream($stream, $targetStream) === false) {
                    throw new \RuntimeException("Gagal menyalin media ke archive: {$file}");
                }
            } finally {
                fclose($stream);
                fclose($targetStream);
            }

            $totalSize += (int) $storage->size($file);
        }

        return [
            'disk' => $disk,
            'source' => $source,
            'archive_path' => 'files/'.str_replace('\\', '/', basename($destination)),
            'file_count' => count($files),
            'total_size' => $totalSize,
        ];
    }

    protected function copyArchiveDirectoryToStorage(string $source, string $disk, string $destination): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($source) + 1));
            $stream = fopen($file->getPathname(), 'rb');
            if ($stream === false) {
                throw new \RuntimeException("Gagal membaca media restore: {$relative}");
            }

            try {
                if (! Storage::disk($disk)->writeStream(trim($destination.'/'.$relative, '/'), $stream)) {
                    throw new \RuntimeException("Gagal memulihkan media ke disk {$disk}: {$relative}");
                }
            } finally {
                fclose($stream);
            }
        }
    }

    protected function generateDirectoryChecksums(string $dir, string $baseDir = ''): array
    {
        $checksums = [];
        $baseDir = $baseDir ?: $dir;

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === 'checksums.json') {
                continue;
            }

            $path = $dir.'/'.$item;
            $relPath = ltrim(str_replace($baseDir, '', $path), '/\\');

            if (is_dir($path)) {
                $checksums = array_merge($checksums, $this->generateDirectoryChecksums($path, $baseDir));
            } else {
                $checksums[$relPath] = hash_file('sha256', $path);
            }
        }

        return $checksums;
    }

    protected function zipDirectory(string $sourceDir, string $outZipPath): void
    {
        if (class_exists('\ZipArchive')) {
            $zip = new \ZipArchive;
            if ($zip->open($outZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $file) {
                    if (! $file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen(realpath($sourceDir)) + 1);
                        $relativePath = str_replace('\\', '/', $relativePath);
                        $zip->addFile($filePath, $relativePath);
                    }
                }

                $zip->close();

                return;
            }
        }

        // Pure PHP JSON/base64 Fallback Zip Archive Generator (Shared Hosting without ZipArchive extension)
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $archiveData = [];
        foreach ($files as $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(realpath($sourceDir)) + 1);
                $relativePath = str_replace('\\', '/', $relativePath);
                $archiveData[$relativePath] = base64_encode(file_get_contents($filePath));
            }
        }

        file_put_contents($outZipPath, json_encode($archiveData));
    }

    protected function extractZipWithProtection(string $zipPath, string $extractTo): void
    {
        if (class_exists('\ZipArchive')) {
            $zip = new \ZipArchive;
            if ($zip->open($zipPath) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);

                    // Zip Slip / Path Traversal Protection
                    if (str_contains($filename, '../') || str_contains($filename, '..\\') || str_starts_with($filename, '/')) {
                        $zip->close();
                        throw new \InvalidArgumentException("Zip Slip path traversal terdeteksi dalam file backup: {$filename}");
                    }

                    $targetPath = $extractTo.'/'.$filename;
                    if (str_ends_with($filename, '/')) {
                        if (! is_dir($targetPath)) {
                            mkdir($targetPath, 0755, true);
                        }
                    } else {
                        $dirName = dirname($targetPath);
                        if (! is_dir($dirName)) {
                            mkdir($dirName, 0755, true);
                        }
                        copy('zip://'.$zipPath.'#'.$filename, $targetPath);
                    }
                }
                $zip->close();

                return;
            }
        }

        // Fallback JSON Base64 Archive Extraction
        $content = file_get_contents($zipPath);
        $archiveData = json_decode($content, true);

        if (is_array($archiveData)) {
            foreach ($archiveData as $filename => $b64Content) {
                if (str_contains($filename, '../') || str_contains($filename, '..\\') || str_starts_with($filename, '/')) {
                    throw new \InvalidArgumentException("Zip Slip path traversal terdeteksi dalam file backup: {$filename}");
                }

                $targetPath = $extractTo.'/'.$filename;
                $dirName = dirname($targetPath);
                if (! is_dir($dirName)) {
                    mkdir($dirName, 0755, true);
                }
                file_put_contents($targetPath, base64_decode($b64Content));
            }
        } else {
            throw new \RuntimeException('Gagal mengekstrak file archive backup.');
        }
    }

    protected function copyDirectory(string $src, string $dst): int
    {
        if (! is_dir($src)) {
            return 0;
        }

        if (! is_dir($dst)) {
            mkdir($dst, 0755, true);
        }

        $count = 0;
        $items = scandir($src);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $srcPath = $src.'/'.$item;
            $dstPath = $dst.'/'.$item;
            if (is_dir($srcPath)) {
                $count += $this->copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
                $count++;
            }
        }

        return $count;
    }

    protected function deleteDirectory(string $dir): bool
    {
        if (! is_dir($dir)) {
            return false;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.'/'.$item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        return rmdir($dir);
    }
}
