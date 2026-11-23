<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAiCaptionAndAudioTranscriptToPhotos extends Migration
{
    public function up()
    {
        $fields = [];

        if (!$this->db->fieldExists('ai_caption', 'tbl_photos')) {
            $fields['ai_caption'] = [
                'type' => 'TEXT',
                'null' => true,
            ];
        }
        if (!$this->db->fieldExists('audio_transcript', 'tbl_photos')) {
            $fields['audio_transcript'] = [
                'type' => 'LONGTEXT',
                'null' => true,
            ];
        }
        if (!$this->db->fieldExists('transcript_segments', 'tbl_photos')) {
            $fields['transcript_segments'] = [
                'type' => 'JSON',
                'null' => true,
            ];
        }
        if (!$this->db->fieldExists('scanned_caption', 'tbl_photos')) {
            $fields['scanned_caption'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
            ];
        }
        if (!$this->db->fieldExists('scanned_audio', 'tbl_photos')) {
            $fields['scanned_audio'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
            ];
        }
        if (!$this->db->fieldExists('embedding_model', 'tbl_photos')) {
            $fields['embedding_model'] = [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('tbl_photos', $fields);
        }

        try {
            $this->db->query('ALTER TABLE `tbl_photos` ADD FULLTEXT INDEX `ft_photos_ai_text` (`ai_caption`, `ocr_text`, `audio_transcript`)');
        } catch (\Throwable $e) {
            // Index may already exist
        }
    }

    public function down()
    {
        try {
            $this->db->query('ALTER TABLE `tbl_photos` DROP INDEX `ft_photos_ai_text`');
        } catch (\Throwable $e) {
        }

        foreach (['ai_caption', 'audio_transcript', 'transcript_segments', 'scanned_caption', 'scanned_audio', 'embedding_model'] as $col) {
            if ($this->db->fieldExists($col, 'tbl_photos')) {
                $this->forge->dropColumn('tbl_photos', $col);
            }
        }
    }
}
