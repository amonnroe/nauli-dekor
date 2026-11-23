<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymentProofAndVerificationToOrders extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('orders')) {
            throw new \RuntimeException('Tabel orders belum tersedia. Jalankan migration skema utama terlebih dahulu.');
        }

        $rows = $this->db->query(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'"
        )->getResultArray();
        $existing = array_column($rows, 'COLUMN_NAME');

        if (! in_array('bukti_pembayaran', $existing, true)) {
            $this->forge->addColumn('orders', [
                'bukti_pembayaran' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'kanal_pembayaran',
                ],
            ]);
        }

        if (! in_array('bukti_diupload_pada', $existing, true)) {
            $this->forge->addColumn('orders', [
                'bukti_diupload_pada' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'bukti_pembayaran',
                ],
            ]);
        }

        if (! in_array('catatan_verifikasi', $existing, true)) {
            $this->forge->addColumn('orders', [
                'catatan_verifikasi' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 500,
                    'null'       => true,
                    'after'      => 'bukti_diupload_pada',
                ],
            ]);
        }

        if (! in_array('diverifikasi_oleh', $existing, true)) {
            $this->forge->addColumn('orders', [
                'diverifikasi_oleh' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'catatan_verifikasi',
                ],
            ]);
        }

        if (! in_array('diverifikasi_pada', $existing, true)) {
            $this->forge->addColumn('orders', [
                'diverifikasi_pada' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'diverifikasi_oleh',
                ],
            ]);
        }
    }

    public function down()
    {
        $rows = $this->db->query(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'"
        )->getResultArray();
        $existing = array_column($rows, 'COLUMN_NAME');
        $drop = [];
        foreach (['diverifikasi_pada', 'diverifikasi_oleh', 'catatan_verifikasi', 'bukti_diupload_pada', 'bukti_pembayaran'] as $field) {
            if (in_array($field, $existing, true)) {
                $drop[] = $field;
            }
        }
        if ($drop !== []) {
            $this->forge->dropColumn('orders', $drop);
        }
    }
}
