<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: CreateOfficialSettingsTable
 *
 * This migration creates the `settings` table with the EXACT schema
 * expected by the CodeIgniter Settings library (including the `context` column).
 */
class CreateOfficialSettingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'class' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 31,
                'default'    => 'string',
            ],
            'context' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['class', 'key', 'context']);
        $this->forge->createTable('settings', true);
    }

    public function down()
    {
        $this->forge->dropTable('settings', true);
    }
}
