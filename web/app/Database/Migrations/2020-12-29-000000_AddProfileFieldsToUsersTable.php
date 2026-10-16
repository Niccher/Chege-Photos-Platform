<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProfileFieldsToUsersTable extends Migration
{
    public function up(): void
    {
        $fields = [
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'username',
            ],
            'avatar' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'after'      => 'name',
            ],
            'vault_pin_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'vault_failed_attempts' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'vault_locked_until' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'auto_vault_nsfw' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
        ];

        $usersTable = $this->db->tableExists('users') ? 'users' : ($this->db->tableExists('tbl_users') ? 'tbl_users' : null);
        if ($usersTable) {
            foreach ($fields as $colName => $colDef) {
                if (!$this->db->fieldExists($colName, $usersTable)) {
                    $this->forge->addColumn($usersTable, [$colName => $colDef]);
                }
            }
        }
    }

    public function down(): void
    {
        $usersTable = $this->db->tableExists('users') ? 'users' : ($this->db->tableExists('tbl_users') ? 'tbl_users' : null);
        if ($usersTable) {
            $cols = ['name', 'avatar', 'vault_pin_hash', 'vault_failed_attempts', 'vault_locked_until', 'auto_vault_nsfw'];
            foreach ($cols as $col) {
                if ($this->db->fieldExists($col, $usersTable)) {
                    $this->forge->dropColumn($usersTable, $col);
                }
            }
        }
    }
}
