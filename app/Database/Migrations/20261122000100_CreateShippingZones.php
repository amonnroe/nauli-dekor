<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateShippingZones extends Migration
{
    public function up(): void
    {
        // Tabel kecamatan
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_kecamatan' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nama_kecamatan');
        $this->forge->createTable('kecamatan', true);

        // Tabel kelurahan dengan harga ongkos kirim
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'kecamatan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'nama_kelurahan' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'ongkos_kirim' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => false,
                'default'    => '0.00',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('kecamatan_id');
        $this->forge->createTable('kelurahan', true);

        // Tambah kolom ongkos kirim ke orders
        $fields = [
            'kecamatan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'tanggal_acara',
            ],
            'kelurahan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'kecamatan_id',
            ],
            'nama_kecamatan' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'kelurahan_id',
            ],
            'nama_kelurahan' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'nama_kecamatan',
            ],
            'ongkos_kirim' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => false,
                'default'    => '0.00',
                'after'      => 'nama_kelurahan',
            ],
        ];
        $this->forge->addColumn('orders', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('orders', ['kecamatan_id', 'kelurahan_id', 'nama_kecamatan', 'nama_kelurahan', 'ongkos_kirim']);
        $this->forge->dropTable('kelurahan', true);
        $this->forge->dropTable('kecamatan', true);
    }
}
