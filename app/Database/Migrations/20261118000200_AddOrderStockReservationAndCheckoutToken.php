<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrderStockReservationAndCheckoutToken extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('orders')) {
            throw new \RuntimeException('Tabel orders belum tersedia. Jalankan migration skema utama terlebih dahulu.');
        }

        $columns = $this->db->query(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME IN ('stock_reserved', 'checkout_token')"
        )->getResultArray();
        $existingColumns = array_column($columns, 'COLUMN_NAME');

        if (! in_array('stock_reserved', $existingColumns, true)) {
            $this->forge->addColumn('orders', [
                'stock_reserved' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'unsigned'   => true,
                    'default'    => 0,
                    'null'       => false,
                    'after'      => 'status_pesanan',
                ],
            ]);
        }

        if (! in_array('checkout_token', $existingColumns, true)) {
            $this->forge->addColumn('orders', [
                'checkout_token' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                    'after'      => 'stock_reserved',
                ],
            ]);
        }

        $index = $this->db->query(
            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND INDEX_NAME = 'uq_orders_checkout_token' LIMIT 1"
        )->getRowArray();
        if ($index === null) {
            $this->db->query('CREATE UNIQUE INDEX uq_orders_checkout_token ON orders (checkout_token)');
        }
    }

    public function down()
    {
        $index = $this->db->query(
            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND INDEX_NAME = 'uq_orders_checkout_token' LIMIT 1"
        )->getRowArray();
        if ($index !== null) {
            $this->db->query('DROP INDEX uq_orders_checkout_token ON orders');
        }

        $columns = $this->db->query(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME IN ('stock_reserved', 'checkout_token')"
        )->getResultArray();
        $existingColumns = array_column($columns, 'COLUMN_NAME');
        $drop = [];
        foreach (['checkout_token', 'stock_reserved'] as $field) {
            if (in_array($field, $existingColumns, true)) {
                $drop[] = $field;
            }
        }
        if ($drop !== []) {
            $this->forge->dropColumn('orders', $drop);
        }
    }
}
