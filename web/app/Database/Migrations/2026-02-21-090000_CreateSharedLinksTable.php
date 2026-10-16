<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSharedLinksTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('tbl_shared_links')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'photo_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                ],
                'access_token' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'unique'     => true,
                ],
                'password_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'expires_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('photo_id', 'tbl_photos', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('tbl_shared_links', true);
        } else {
            if (!$this->db->fieldExists('password_hash', 'tbl_shared_links')) {
                $this->forge->addColumn('tbl_shared_links', [
                    'password_hash' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => true,
                        'default'    => null,
                        'after'      => 'access_token',
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('tbl_shared_links');
    }
}
