<?php

namespace App\Commands;

use App\Models\PhotoModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ReindexSiglip extends BaseCommand
{
    protected $group       = 'ML';
    protected $name        = 'ml:reindex-siglip';
    protected $description = 'Re-queues all library photos and videos for SigLIP 768-d embeddings, Florence-2 dense captions/OCR, and Whisper Large-v3-Turbo video transcription.';
    protected $usage       = 'ml:reindex-siglip [--limit=500]';

    public function run(array $params)
    {
        helper('ml');
        $limit = 500;
        foreach ($params as $p) {
            if (str_starts_with($p, '--limit=')) {
                $limit = max(1, (int) substr($p, 8));
            }
        }

        $mlUrl = get_ml_url();
        $apiKey = get_ml_api_key();
        $photoModel = new PhotoModel();

        $photos = $photoModel
            ->groupStart()
                ->where('embedding_model !=', 'google/siglip-base-patch16-224')
                ->orWhere('embedding_model IS NULL', null, false)
                ->orWhere('scanned_caption', 0)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->findAll($limit);

        if (empty($photos)) {
            CLI::write('All media items are already indexed with SigLIP 768-d & Florence-2.', 'green');
            return EXIT_SUCCESS;
        }

        CLI::write(sprintf('Queueing %d media item(s) to %s ...', count($photos), $mlUrl), 'cyan');
        $client = service('curlrequest', ['connect_timeout' => 5, 'timeout' => 15, 'http_errors' => false]);
        $queued = 0;

        foreach ($photos as $photo) {
            $mime = (string) ($photo['mime_type'] ?? '');
            $isVideo = str_starts_with($mime, 'video/');
            try {
                $resp = $client->post(rtrim($mlUrl, '/') . '/api/v1/faces/encode', [
                    'headers'     => ['X-API-KEY' => $apiKey],
                    'form_params' => [
                        'photo_id'     => (int) $photo['id'],
                        'async_task'   => 1,
                        'scan_faces'   => 0,
                        'scan_tags'    => 0,
                        'scan_clip'    => $isVideo ? 0 : 1,
                        'scan_nsfw'    => 0,
                        'scan_caption' => $isVideo ? 0 : 1,
                        'scan_audio'   => $isVideo ? 1 : 0,
                    ],
                ]);
                if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) {
                    $queued++;
                }
            } catch (\Throwable $e) {
                CLI::write("Failed queueing media #{$photo['id']}: " . $e->getMessage(), 'yellow');
            }
        }

        CLI::write("✓ Successfully queued {$queued} / " . count($photos) . " items for SigLIP / Florence-2 / Whisper re-indexing.", 'green');
        return EXIT_SUCCESS;
    }
}
