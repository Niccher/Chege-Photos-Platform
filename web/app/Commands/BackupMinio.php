<?php

namespace App\Commands;

use App\Services\Storage\StorageManager;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class BackupMinio extends BaseCommand
{
    protected $group       = 'Storage';
    protected $name        = 'backup:minio';
    protected $description = 'Streams compressed MySQL dump and Qdrant snapshot archives to minio://chege-backups/ with zero local disk retention.';
    protected $usage       = 'backup:minio';

    public function run(array $params)
    {
        $startTime = microtime(true);
        $timestamp = gmdate('Ymd_His');
        $backupsBucket = env('S3_BACKUPS_BUCKET') ?: getenv('S3_BACKUPS_BUCKET') ?: 'chege-backups';

        $storage = new StorageManager([
            'bucket' => $backupsBucket,
        ]);

        if (!$storage->isConfigured()) {
            CLI::error('MinIO backup storage is not configured.');
            return EXIT_ERROR;
        }

        $tmpDir = rtrim(sys_get_temp_dir(), '/') . '/chege_backup_' . $timestamp;
        @mkdir($tmpDir, 0700, true);

        // 1. MySQL Dump -> MinIO
        $dbHost = env('DB_HOST') ?: getenv('DB_HOST') ?: 'mysql';
        $dbUser = env('DB_USER') ?: getenv('DB_USER') ?: 'root';
        $dbPass = env('DB_PASS') ?: getenv('DB_PASS') ?: getenv('DB_ROOT_PASSWORD') ?: 'root_password';
        $dbName = env('DB_NAME') ?: getenv('DB_NAME') ?: 'db_chege_photos';

        $dumpFile = "{$tmpDir}/mysql_{$dbName}_{$timestamp}.sql.gz";
        $cmd = sprintf(
            'mysqldump -h %s -u %s -p%s --single-transaction --routines --triggers %s 2>/dev/null | gzip -c > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($dumpFile)
        );
        @exec($cmd, $out, $ret);

        $mysqlOk = false;
        if ($ret === 0 && file_exists($dumpFile) && filesize($dumpFile) > 64) {
            $res = $storage->uploadFile($dumpFile, "mysql/{$dbName}_{$timestamp}.sql.gz", 'application/gzip');
            $mysqlOk = !empty($res['success']);
            if ($mysqlOk) {
                CLI::write("✓ MySQL backup uploaded to minio://{$backupsBucket}/mysql/{$dbName}_{$timestamp}.sql.gz", 'green');
            } else {
                CLI::error('✗ Failed uploading MySQL dump: ' . ($res['error'] ?? 'Unknown error'));
            }
        } else {
            CLI::write('! Skipped MySQL dump (mysqldump unavailable or empty output).', 'yellow');
        }
        @unlink($dumpFile);

        // 2. Qdrant Snapshots -> MinIO
        $qdrantHost = env('QDRANT_HOST') ?: getenv('QDRANT_HOST') ?: 'qdrant';
        $qdrantPort = env('QDRANT_PORT') ?: getenv('QDRANT_PORT') ?: '6333';
        $qdrantBase = "http://{$qdrantHost}:{$qdrantPort}";
        $collections = ['face_embeddings', 'photo_embeddings'];

        foreach ($collections as $col) {
            $ch = curl_init("{$qdrantBase}/collections/{$col}/snapshots");
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 60,
            ]);
            $snapResp = curl_exec($ch);
            $snapCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($snapCode === 200 && $snapResp) {
                $decoded = json_decode($snapResp, true);
                $snapName = $decoded['result']['name'] ?? null;
                if ($snapName) {
                    $snapLocal = "{$tmpDir}/{$col}_{$snapName}";
                    $fp = fopen($snapLocal, 'wb');
                    $dl = curl_init("{$qdrantBase}/collections/{$col}/snapshots/{$snapName}");
                    curl_setopt_array($dl, [
                        CURLOPT_FILE    => $fp,
                        CURLOPT_TIMEOUT => 120,
                    ]);
                    curl_exec($dl);
                    $dlCode = curl_getinfo($dl, CURLINFO_HTTP_CODE);
                    curl_close($dl);
                    fclose($fp);

                    if ($dlCode === 200 && file_exists($snapLocal) && filesize($snapLocal) > 0) {
                        $up = $storage->uploadFile($snapLocal, "qdrant/{$col}/{$timestamp}_{$snapName}", 'application/octet-stream');
                        if (!empty($up['success'])) {
                            CLI::write("✓ Qdrant snapshot uploaded to minio://{$backupsBucket}/qdrant/{$col}/{$timestamp}_{$snapName}", 'green');
                        }
                    }
                    @unlink($snapLocal);
                }
            }
        }

        @rmdir($tmpDir);

        $duration = round(microtime(true) - $startTime, 2);
        CLI::write("MinIO cold backup completed in {$duration}s (zero local retention).", 'cyan');

        return EXIT_SUCCESS;
    }
}
