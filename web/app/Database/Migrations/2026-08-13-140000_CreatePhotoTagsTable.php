<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePhotoTagsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tbl_photo_tags')) {
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
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'tag' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'confidence' => [
                'type' => 'FLOAT',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('photo_id', 'tbl_photos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addKey('tag');
        $this->forge->createTable('tbl_photo_tags', true);
    }

    public function down()
    {
        $this->forge->dropTable('tbl_photo_tags');
    }
}
