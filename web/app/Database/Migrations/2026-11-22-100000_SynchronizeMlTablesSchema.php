<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SynchronizeMlTablesSchema extends Migration
{
    public function up(): void
    {
        // 1. Synchronize tbl_scan_jobs
        if ($this->db->tableExists('tbl_scan_jobs')) {
            $scanJobFields = [];
            if (! $this->db->fieldExists('failed', 'tbl_scan_jobs')) {
                $scanJobFields['failed'] = [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                    'after'      => 'processed_photos',
                ];
            }
            if (! $this->db->fieldExists('error_message', 'tbl_scan_jobs')) {
                $scanJobFields['error_message'] = [
                    'type'  => 'TEXT',
                    'null'  => true,
                    'after' => 'completed_at',
                ];
            }
            if (! empty($scanJobFields)) {
                $this->forge->addColumn('tbl_scan_jobs', $scanJobFields);
            }

            try {
                $this->db->query("CREATE OR REPLACE VIEW `scan_job` AS SELECT * FROM `tbl_scan_jobs`;");
                $this->db->query("CREATE OR REPLACE VIEW `scan_jobs` AS SELECT * FROM `tbl_scan_jobs`;");
            } catch (\Throwable $e) {
                // Ignore view refresh errors in isolated environments
            }
        }

        // 2. Synchronize tbl_face_annotations
        if ($this->db->tableExists('tbl_face_annotations')) {
            $annotationFields = [];
            if (! $this->db->fieldExists('annotated_by', 'tbl_face_annotations')) {
                $annotationFields['annotated_by'] = [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'action',
                ];
            }
            if (! $this->db->fieldExists('created_at', 'tbl_face_annotations')) {
                $annotationFields['created_at'] = [
                    'type'  => 'DATETIME',
                    'null'  => true,
                    'after' => 'annotated_at',
                ];
            }
            if (! $this->db->fieldExists('updated_at', 'tbl_face_annotations')) {
                $annotationFields['updated_at'] = [
                    'type'  => 'DATETIME',
                    'null'  => true,
                    'after' => 'created_at',
                ];
            }
            if (! empty($annotationFields)) {
                $this->forge->addColumn('tbl_face_annotations', $annotationFields);
            }

            try {
                $this->db->query("CREATE OR REPLACE VIEW `face_annotation` AS SELECT * FROM `tbl_face_annotations`;");
                $this->db->query("CREATE OR REPLACE VIEW `face_annotations` AS SELECT * FROM `tbl_face_annotations`;");
            } catch (\Throwable $e) {
                // Ignore view refresh errors
            }
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('tbl_scan_jobs')) {
            if ($this->db->fieldExists('failed', 'tbl_scan_jobs')) {
                $this->forge->dropColumn('tbl_scan_jobs', 'failed');
            }
            if ($this->db->fieldExists('error_message', 'tbl_scan_jobs')) {
                $this->forge->dropColumn('tbl_scan_jobs', 'error_message');
            }
        }

        if ($this->db->tableExists('tbl_face_annotations')) {
            if ($this->db->fieldExists('annotated_by', 'tbl_face_annotations')) {
                $this->forge->dropColumn('tbl_face_annotations', 'annotated_by');
            }
            if ($this->db->fieldExists('created_at', 'tbl_face_annotations')) {
                $this->forge->dropColumn('tbl_face_annotations', 'created_at');
            }
            if ($this->db->fieldExists('updated_at', 'tbl_face_annotations')) {
                $this->forge->dropColumn('tbl_face_annotations', 'updated_at');
            }
        }
    }
}
