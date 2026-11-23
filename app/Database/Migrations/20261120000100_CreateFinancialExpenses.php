<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinancialExpenses extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('financial_expenses')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'order_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'jenis_biaya' => ['type' => 'VARCHAR', 'constraint' => 20],
            'kategori' => ['type' => 'VARCHAR', 'constraint' => 80],
            'deskripsi' => ['type' => 'VARCHAR', 'constraint' => 255],
            'jumlah' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'tanggal_biaya' => ['type' => 'DATE'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Aktif'],
            'dicatat_oleh' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'dibatalkan_oleh' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'alasan_pembatalan' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tanggal_biaya', 'status'], false, false, 'idx_fin_exp_date_status');
        $this->forge->addKey(['jenis_biaya', 'status'], false, false, 'idx_fin_exp_type_status');
        $this->forge->addKey('order_id');
        // Tidak memakai ON DELETE CASCADE agar catatan biaya tidak terhapus ketika
        // data pesanan lama diarsipkan. Order ID tetap disimpan sebagai referensi.
        $this->forge->createTable('financial_expenses', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down()
    {
        // Catatan harga perolehan merupakan data akuntansi; rollback otomatis dilarang.
        throw new \RuntimeException('Rollback financial_expenses dinonaktifkan agar catatan biaya tidak terhapus.');
    }
}
