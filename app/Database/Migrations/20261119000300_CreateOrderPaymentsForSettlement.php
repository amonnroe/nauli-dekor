<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrderPaymentsForSettlement extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('order_payments')) {
            throw new \RuntimeException('Tabel order_payments sudah ada. Periksa status migration sebelum melanjutkan.');
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'order_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'jenis_pembayaran' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'Pelunasan'],
            'metode_pembayaran' => ['type' => 'VARCHAR', 'constraint' => 50],
            'jumlah' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'bukti_pembayaran' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 50],
            'catatan' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'diverifikasi_oleh' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'diverifikasi_pada' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['order_id', 'status'], false, false, 'idx_order_payments_order_status');
        $this->forge->addKey('created_at');
        $this->forge->createTable('order_payments', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down()
    {
        // Data riwayat pembayaran tidak dihapus otomatis saat rollback.
        throw new \RuntimeException('Rollback order_payments dinonaktifkan untuk melindungi riwayat pembayaran.');
    }
}
