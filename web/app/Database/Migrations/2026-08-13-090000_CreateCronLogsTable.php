<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCronLogsTable extends Migration
{
    public function up(): void
    {
        if (!$this->db->tableExists('sys_cron_logs')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'job_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 191,
                    'null'       => false,
                ],
                'command' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 191,
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => false,
                    'default'    => 'success',
                ],
                'output' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'error_message' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'duration_seconds' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,4',
                    'null'       => true,
                    'default'    => 0.0000,
                ],
                'execution_time_ms' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'default'    => 0,
                ],
                'run_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('job_name');
            $this->forge->addKey('status');
            $this->forge->addKey('run_at');
            $this->forge->addKey('created_at');
            $this->forge->createTable('sys_cron_logs', true);
        } else {
            $fieldsToAdd = [];
            if (!$this->db->fieldExists('job_name', 'sys_cron_logs')) {
                $fieldsToAdd['job_name'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 191,
                    'null'       => true,
                    'after'      => 'id',
                ];
            }
            if (!$this->db->fieldExists('command', 'sys_cron_logs')) {
                $fieldsToAdd['command'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 191,
                    'null'       => true,
                    'after'      => 'job_name',
                ];
            }
            if (!$this->db->fieldExists('duration_seconds', 'sys_cron_logs')) {
                $fieldsToAdd['duration_seconds'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,4',
                    'null'       => true,
                    'default'    => 0.0000,
                ];
            }
            if (!$this->db->fieldExists('execution_time_ms', 'sys_cron_logs')) {
                $fieldsToAdd['execution_time_ms'] = [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'default'    => 0,
                ];
            }
            if (!$this->db->fieldExists('run_at', 'sys_cron_logs')) {
                $fieldsToAdd['run_at'] = [
                    'type' => 'DATETIME',
                    'null' => true,
                ];
            }
            if (!$this->db->fieldExists('created_at', 'sys_cron_logs')) {
                $fieldsToAdd['created_at'] = [
                    'type' => 'DATETIME',
                    'null' => true,
                ];
            }
            if (!empty($fieldsToAdd)) {
                $this->forge->addColumn('sys_cron_logs', $fieldsToAdd);
            }
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('sys_cron_logs', true);
    }
}
