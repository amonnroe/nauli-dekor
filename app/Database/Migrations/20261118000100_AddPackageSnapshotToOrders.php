<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPackageSnapshotToOrders extends Migration
{
    public function up()
    {
        // Migration ini dijalankan satu kali oleh CI4.
        // Tambahkan snapshot nama dan harga paket saat transaksi dibuat.
        $this->forge->addColumn('orders', [
            'package_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 180,
                'null'       => true,
                'after'      => 'package_id',
            ],
            'package_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
                'after'      => 'package_name',
            ],
        ]);
    }

    public function down()
    {
        // Rollback menghapus kedua kolom snapshot. Jangan jalankan rollback
        // setelah pesanan produksi memakai kolom ini tanpa backup terlebih dahulu.
        $this->forge->dropColumn('orders', ['package_name', 'package_price']);
    }
}
