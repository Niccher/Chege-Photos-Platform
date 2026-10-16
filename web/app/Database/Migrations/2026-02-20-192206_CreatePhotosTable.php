<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePhotosTable extends Migration
{
    /**
     * Complete field definitions for tbl_photos.
     */
    private function getFieldDefinitions(): array
    {
        return [
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'device_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'device_uuid' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'upload_source' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'webapp',
                'null'       => false,
            ],
            'filename' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'thumbnail_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'taken_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'width' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'height' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'size' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'null'       => true,
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'latitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,8',
                'null'       => true,
            ],
            'longitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '11,8',
                'null'       => true,
            ],
            'exif_data' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ocr_text' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
            ],
            'storage_driver' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'hybrid',
                'null'       => false,
            ],
            'gcp_synced' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
            ],
            'gcp_synced_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'is_archived' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'is_favorite' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'is_vault' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'vault_locked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'is_nsfw' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'nsfw_score' => [
                'type'    => 'FLOAT',
                'default' => 0.0,
            ],
            'nsfw_details' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'scanned_face' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'ignore_faces' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'scanned_tag' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'scanned_clip' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'scanned_ocr' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'scanned_nsfw' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'file_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];
    }

    public function up()
    {
        $fields = $this->getFieldDefinitions();

        if (!$this->db->tableExists('tbl_photos')) {
            $this->forge->addField($fields);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('tbl_photos', true);
        } else {
            // Self-healing: backfill any missing columns on existing tables
            $fieldsToAdd = [];
            foreach ($fields as $colName => $colDef) {
                if (!$this->db->fieldExists($colName, 'tbl_photos')) {
                    $fieldsToAdd[$colName] = $colDef;
                }
            }
            if (!empty($fieldsToAdd)) {
                $this->forge->addColumn('tbl_photos', $fieldsToAdd);
            }
        }

        // Ensure performance indexes exist
        $this->ensureIndexes();
    }

    private function ensureIndexes(): void
    {
        try {
            $indexes = $this->db->getIndexData('tbl_photos');
            $indexNames = array_column($indexes, 'name');

            $indexDefs = [
                'idx_gcp_synced'          => '(`gcp_synced`)',
                'idx_scanned_ocr'         => '(`scanned_ocr`)',
                'idx_photos_vault'        => '(`user_id`, `is_vault`)',
                'idx_photos_nsfw'         => '(`is_nsfw`)',
                'idx_scanned_nsfw'        => '(`scanned_nsfw`)',
                'idx_photos_ignore_faces' => '(`ignore_faces`)',
            ];

            foreach ($indexDefs as $idxName => $idxCols) {
                if (!in_array($idxName, $indexNames, true)) {
                    $this->db->query("CREATE INDEX `{$idxName}` ON `tbl_photos` {$idxCols}");
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Non-critical index creation notice on tbl_photos: ' . $e->getMessage());
        }
    }

    public function down()
    {
        $this->forge->dropTable('tbl_photos', true);
    }
}
