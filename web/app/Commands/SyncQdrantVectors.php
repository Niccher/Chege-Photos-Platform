<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncQdrantVectors extends BaseCommand
{
    protected $group       = 'ML';
    protected $name        = 'qdrant:sync-vectors';
    protected $description = 'Reconciles face encodings and vector indexes between MySQL and Qdrant.';
    protected $usage       = 'qdrant:sync-vectors';

    public function run(array $params)
    {
        $startTime = microtime(true);
        $db = \Config\Database::connect();

        CLI::write('Checking face encodings and relational photo integrity...', 'cyan');

        // 1. Delete orphaned face encodings where parent photo was deleted
        $orphaned = $db->table('tbl_face_encodings fe')
            ->select('fe.id')
            ->join('tbl_photos p', 'p.id = fe.photo_id', 'left')
            ->where('p.id IS NULL')
            ->get()
            ->getResultArray();

        $orphanedCount = 0;
        if (!empty($orphaned)) {
            $ids = array_column($orphaned, 'id');
            $db->table('tbl_face_encodings')->whereIn('id', $ids)->delete();
            $orphanedCount = count($ids);
            CLI::write("Pruned {$orphanedCount} orphaned face encoding record(s) missing parent photos.", 'yellow');
        }

        // 2. Trigger Qdrant reconciliation via ML microservice
        CLI::write('Triggering vector reconciliation on ML microservice & Qdrant...', 'cyan');
        $mlPurgedPhotoVectors = 0;
        $mlPurgedFaceVectors = 0;
        $mlScannedPhotoVectors = 0;
        $mlScannedFaceVectors = 0;

        try {
            helper('ml');
            $client = service('curlrequest', [
                'connect_timeout' => 5,
                'timeout'         => 180,
                'headers'         => [
                    'X-API-KEY' => get_ml_api_key(),
                ]
            ]);

            $response = $client->post(get_ml_url() . '/api/v1/maintenance/reconcile-vectors');
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                $mlPurgedPhotoVectors  = $data['photo_embeddings']['orphans_deleted'] ?? 0;
                $mlPurgedFaceVectors   = $data['face_embeddings']['orphans_deleted'] ?? 0;
                $mlScannedPhotoVectors = $data['photo_embeddings']['total_scanned'] ?? 0;
                $mlScannedFaceVectors  = $data['face_embeddings']['total_scanned'] ?? 0;

                CLI::write("Qdrant Audit: Scanned {$mlScannedPhotoVectors} photo vectors (purged {$mlPurgedPhotoVectors} orphans), scanned {$mlScannedFaceVectors} face vectors (purged {$mlPurgedFaceVectors} orphans).", 'green');
            }
        } catch (\Throwable $t) {
            CLI::write('Notice: ML reconcile-vectors call encountered an error: ' . $t->getMessage(), 'yellow');
        }

        // 3. Check unassigned face count
        $totalFaces = $db->table('tbl_face_encodings')->countAllResults();
        $unassignedFaces = $db->table('tbl_face_encodings')->where('person_id IS NULL')->countAllResults();
        $totalPersons = $db->table('tbl_people')->countAllResults();

        $duration = round(microtime(true) - $startTime, 3);
        $outputMsg = sprintf(
            "Vector audit completed in %ss. Faces: %d (Unassigned: %d), Persons: %d. Purged DB orphans: %d, Purged Qdrant photo vectors: %d, Purged Qdrant face vectors: %d.",
            $duration,
            $totalFaces,
            $unassignedFaces,
            $totalPersons,
            $orphanedCount,
            $mlPurgedPhotoVectors,
            $mlPurgedFaceVectors
        );

        $db->table('sys_cron_logs')->insert([
            'job_name'         => 'qdrant:sync-vectors',
            'status'           => 'success',
            'output'           => $outputMsg,
            'duration_seconds' => $duration,
            'run_at'           => date('Y-m-d H:i:s'),
        ]);

        CLI::write($outputMsg, 'green');
        return EXIT_SUCCESS;
    }
}
