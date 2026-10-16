<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFaceEncodingsTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('tbl_face_encodings')) {
            if (!$this->db->fieldExists('emotion', 'tbl_face_encodings')) {
                $this->forge->addColumn('tbl_face_encodings', [
                    'emotion' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 50,
                        'null'       => true,
                        'after'      => 'gender',
                    ],
                ]);
            }
            $this->ensureEmotionIndex();
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'photo_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'person_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'qdrant_point_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
                'unique'     => true,
            ],
            'bbox_x' => [
                'type' => 'FLOAT',
            ],
            'bbox_y' => [
                'type' => 'FLOAT',
            ],
            'bbox_w' => [
                'type' => 'FLOAT',
            ],
            'bbox_h' => [
                'type' => 'FLOAT',
            ],
            'landmark_left_eye_x' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_left_eye_y' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_right_eye_x' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_right_eye_y' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_nose_x' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_nose_y' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_left_mouth_x' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_left_mouth_y' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_right_mouth_x' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_right_mouth_y' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'detection_score' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'model_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'face_image_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'age' => [
                'type' => 'INT',
                'null' => true,
            ],
            'gender' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'emotion' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('photo_id', 'tbl_photos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('person_id', 'tbl_people', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('tbl_face_encodings', true);
        $this->ensureEmotionIndex();
    }

    private function ensureEmotionIndex(): void
    {
        try {
            $indexes = $this->db->getIndexData('tbl_face_encodings');
            $indexNames = array_column($indexes, 'name');
            if (!in_array('idx_face_emotion', $indexNames, true)) {
                $this->db->query("CREATE INDEX `idx_face_emotion` ON `tbl_face_encodings` (`emotion`)");
            }
        } catch (\Throwable $e) {
            // Ignore non-critical index creation notice
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('tbl_face_encodings', true);
    }
}
