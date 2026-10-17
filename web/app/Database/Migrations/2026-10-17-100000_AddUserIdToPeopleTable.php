<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserIdToPeopleTable extends Migration
{
    public function up(): void
    {
        $fields = [
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'id',
            ],
        ];

        if (! $this->db->fieldExists('user_id', 'tbl_people')) {
            $this->forge->addColumn('tbl_people', $fields);
            
            // Add index for fast multi-tenant filtering
            try {
                $this->db->query("ALTER TABLE `tbl_people` ADD INDEX `idx_tbl_people_user_id` (`user_id`);");
            } catch (\Throwable $e) {
                // Index already exists
            }
        }

        // Refresh views to include user_id column
        if ($this->db->tableExists('tbl_people')) {
            $this->db->query("CREATE OR REPLACE VIEW `person` AS SELECT * FROM `tbl_people`;");
            $this->db->query("CREATE OR REPLACE VIEW `people` AS SELECT * FROM `tbl_people`;");
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('user_id', 'tbl_people')) {
            $this->forge->dropColumn('tbl_people', 'user_id');
        }

        if ($this->db->tableExists('tbl_people')) {
            $this->db->query("CREATE OR REPLACE VIEW `person` AS SELECT * FROM `tbl_people`;");
            $this->db->query("CREATE OR REPLACE VIEW `people` AS SELECT * FROM `tbl_people`;");
        }
    }
}
