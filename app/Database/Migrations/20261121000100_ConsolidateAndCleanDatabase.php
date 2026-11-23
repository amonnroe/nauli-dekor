<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ConsolidateAndCleanDatabase extends Migration
{
    public function up()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        // 1. Hapus tabel-tabel lama/tidak terpakai dari skema warisan yang sudah digantikan
        $unusedTables = [
            'aturan_asosiasi_apriori',
            'detail_cross_selling',
            'detail_pesanan_produk',
            'relasi_paket_inventaris',
            'pesanan',
            'aturan_ongkos_kirim',
            'inventaris_properti',
            'paket_dekorasi',
            'produk_satuan',
        ];

        foreach ($unusedTables as $table) {
            if ($this->db->tableExists($table)) {
                $this->forge->dropTable($table, true);
            }
        }

        // 2. Pastikan kolom role ada pada tabel users
        if (! $this->db->fieldExists('role', 'users')) {
            $this->forge->addColumn('users', [
                'role' => [
                    'type'       => 'ENUM',
                    'constraint' => ['Pelanggan', 'Admin'],
                    'default'    => 'Pelanggan',
                    'after'      => 'password',
                ],
            ]);
        }

        // 3. Gabungkan data admin dari tabel admins ke tabel users (Single User Management)
        $primaryAdminUserId = null;

        if ($this->db->tableExists('admins')) {
            $admins = $this->db->table('admins')->get()->getResultArray();
            foreach ($admins as $admin) {
                $phone = preg_replace('/[^0-9]/', '', (string) ($admin['no_telepon'] ?? ''));
                if (str_starts_with($phone, '62')) {
                    $phone = '0' . substr($phone, 2);
                }

                $existingUser = $this->db->table('users')->where('no_whatsapp', $phone)->get()->getRowArray();
                if ($existingUser) {
                    $this->db->table('users')->where('user_id', $existingUser['user_id'])->update([
                        'role'       => 'Admin',
                        'password'   => $admin['password'],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    if ($primaryAdminUserId === null) {
                        $primaryAdminUserId = (int) $existingUser['user_id'];
                    }
                } else {
                    $this->db->table('users')->insert([
                        'nama_lengkap' => 'Administrator',
                        'no_whatsapp'  => $phone,
                        'email'        => null,
                        'password'     => $admin['password'],
                        'role'         => 'Admin',
                        'created_at'   => $admin['created_at'] ?? date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);
                    $newId = (int) $this->db->insertID();
                    if ($primaryAdminUserId === null) {
                        $primaryAdminUserId = $newId;
                    }
                }
            }
        }

        // Pastikan akun admin utama (081234567890 dan 0811) selalu tersedia di users dengan role Admin
        foreach (['081234567890' => 'Administrator Nauli', '0811' => 'Admin'] as $adminPhone => $adminName) {
            $existingAdmin = $this->db->table('users')->where('no_whatsapp', $adminPhone)->get()->getRowArray();
            if (! $existingAdmin) {
                $this->db->table('users')->insert([
                    'nama_lengkap' => $adminName,
                    'no_whatsapp'  => $adminPhone,
                    'email'        => $adminPhone === '081234567890' ? 'admin@naulidekor.com' : null,
                    'password'     => password_hash('admin123', PASSWORD_DEFAULT),
                    'role'         => 'Admin',
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
                $newAdminId = (int) $this->db->insertID();
                if ($primaryAdminUserId === null) {
                    $primaryAdminUserId = $newAdminId;
                }
            } else {
                $this->db->table('users')->where('user_id', $existingAdmin['user_id'])->update([
                    'role'       => 'Admin',
                    'password'   => password_hash('admin123', PASSWORD_DEFAULT),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                if ($primaryAdminUserId === null) {
                    $primaryAdminUserId = (int) $existingAdmin['user_id'];
                }
            }
        }

        // Jika ada admin lain yang sudah ada sebelumnya (seperti user_id 2 'Admin')
        if ($primaryAdminUserId === null) {
            $adminUser = $this->db->table('users')->where('role', 'Admin')->get()->getRowArray();
            if ($adminUser) {
                $primaryAdminUserId = (int) $adminUser['user_id'];
            }
        }

        // 4. Perbaiki referensi audit admin diverifikasi_oleh pada orders / order_payments jika merujuk ke ID pelanggan
        if ($primaryAdminUserId !== null) {
            $this->db->query("
                UPDATE orders o
                JOIN users u ON o.diverifikasi_oleh = u.user_id
                SET o.diverifikasi_oleh = {$primaryAdminUserId}
                WHERE u.role != 'Admin'
            ");
            if ($this->db->tableExists('order_payments')) {
                $this->db->query("
                    UPDATE order_payments op
                    JOIN users u ON op.diverifikasi_oleh = u.user_id
                    SET op.diverifikasi_oleh = {$primaryAdminUserId}
                    WHERE u.role != 'Admin'
                ");
            }
        }

        // 5. Hapus tabel admins karena sudah dilebur ke tabel users
        if ($this->db->tableExists('admins')) {
            $this->forge->dropTable('admins', true);
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        // Kembalikan tabel admins jika diperlukan rollback
        if (! $this->db->tableExists('admins')) {
            $this->forge->addField([
                'admin_id'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'no_telepon' => ['type' => 'VARCHAR', 'constraint' => 20],
                'password'   => ['type' => 'VARCHAR', 'constraint' => 255],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('admin_id', true);
            $this->forge->addUniqueKey('no_telepon');
            $this->forge->createTable('admins', true);

            // Salin kembali akun Admin dari tabel users ke admins
            $admins = $this->db->table('users')->where('role', 'Admin')->get()->getResultArray();
            foreach ($admins as $admin) {
                $this->db->table('admins')->insert([
                    'no_telepon' => $admin['no_whatsapp'],
                    'password'   => $admin['password'],
                    'created_at' => $admin['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}

