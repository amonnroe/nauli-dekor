<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymentChannelToOrders extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('orders')) {
            throw new \RuntimeException('Tabel orders belum tersedia. Jalankan migration skema utama terlebih dahulu.');
        }

        if (! $this->db->fieldExists('kanal_pembayaran', 'orders')) {
            $this->forge->addColumn('orders', [
                'kanal_pembayaran' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                    'after'      => 'metode_pembayaran',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('orders') && $this->db->fieldExists('kanal_pembayaran', 'orders')) {
            $this->forge->dropColumn('orders', 'kanal_pembayaran');
        }
    }
}
