<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNauliSchema extends Migration
{
    public function up()
    {
        $tables = [
            'users' => [
                'user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'nama_lengkap' => ['type' => 'VARCHAR', 'constraint' => 150],
                'no_whatsapp' => ['type' => 'VARCHAR', 'constraint' => 20],
                'password' => ['type' => 'VARCHAR', 'constraint' => 255],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ],
            'admins' => [
                'admin_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'no_telepon' => ['type' => 'VARCHAR', 'constraint' => 20],
                'password' => ['type' => 'VARCHAR', 'constraint' => 255],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ],
            'packages' => [
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'nama_paket' => ['type' => 'VARCHAR', 'constraint' => 180],
                'kategori' => ['type' => 'VARCHAR', 'constraint' => 30],
                'jenis_paket' => ['type' => 'VARCHAR', 'constraint' => 30],
                'lokasi' => ['type' => 'VARCHAR', 'constraint' => 120],
                'deskripsi' => ['type' => 'TEXT', 'null' => true],
                'harga' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
                'gambar' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'aktif' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ],
            'extras' => [
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'nama' => ['type' => 'VARCHAR', 'constraint' => 120],
                'deskripsi' => ['type' => 'TEXT', 'null' => true],
                'harga' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
                'stok' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
                'gambar' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'aktif' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ],
            'orders' => [
                'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'kode_pesanan' => ['type' => 'VARCHAR', 'constraint' => 30],
                'user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'package_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'nama_pemesan' => ['type' => 'VARCHAR', 'constraint' => 150],
                'no_whatsapp' => ['type' => 'VARCHAR', 'constraint' => 20],
                'email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'tanggal_acara' => ['type' => 'DATE'],
                'waktu_acara' => ['type' => 'TIME', 'null' => true],
                'lokasi_acara' => ['type' => 'TEXT'],
                'metode_pembayaran' => ['type' => 'VARCHAR', 'constraint' => 20],
                'total' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
                'jumlah_dp' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'status_pembayaran' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Belum Bayar'],
                'status_pesanan' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'Pending'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ],
            'order_extras' => [
                'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'order_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'extra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'nama' => ['type' => 'VARCHAR', 'constraint' => 120],
                'harga' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
                'jumlah' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 1],
            ],
        ];

        foreach ($tables as $name => $fields) {
            if (! $this->db->tableExists($name)) {
                $this->forge->addField($fields);
                $this->forge->addKey(array_key_first($fields), true);
                if ($name === 'users') $this->forge->addUniqueKey('no_whatsapp');
                if ($name === 'admins') $this->forge->addUniqueKey('no_telepon');
                if ($name === 'orders') $this->forge->addUniqueKey('kode_pesanan');
                $this->forge->createTable($name, true);
            }
        }

        // Menjaga kompatibilitas dengan tabel users dari rancangan awal proyek.
        foreach (['nama_lengkap' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true], 'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true]] as $field => $definition) {
            if (! $this->db->fieldExists($field, 'users')) $this->forge->addColumn('users', [$field => $definition]);
        }

        $this->seedDefaults();
    }

    private function seedDefaults(): void
    {
        if ($this->db->table('admins')->countAllResults() === 0) {
            $this->db->table('admins')->insert(['no_telepon' => '081234567890', 'password' => password_hash('admin123', PASSWORD_DEFAULT), 'created_at' => date('Y-m-d H:i:s')]);
        }
        if ($this->db->table('packages')->countAllResults() === 0) {
            $venues = ['Gereja + Gedung 1 Lantai', 'Gereja + Gedung 2 Lantai', 'Hanya Gedung 1 Lantai', 'Hanya Gedung 2 Lantai', 'Hanya Gereja'];
            $types = ['biasa' => 0, 'lengkap' => 2500000, 'tambahan' => 0];
            foreach ($types as $type => $extra) foreach ($venues as $i => $venue) {
                $price = 5000000 + ($i * 1250000) + $extra;
                $this->db->table('packages')->insert(['nama_paket' => 'Paket ' . ucfirst($type) . ' - ' . $venue, 'kategori' => 'pernikahan', 'jenis_paket' => $type, 'lokasi' => $venue, 'deskripsi' => 'Dekorasi ' . strtolower($venue) . ' yang dirancang khusus untuk acara Anda.', 'harga' => $price, 'gambar' => 'dashboard-hero.png', 'aktif' => 1, 'created_at' => date('Y-m-d H:i:s')]);
            }
            foreach (['biasa', 'tambahan'] as $type) $this->db->table('packages')->insert(['nama_paket' => 'Paket Rumah Duka ' . ucfirst($type), 'kategori' => 'duka', 'jenis_paket' => $type, 'lokasi' => 'Rumah Duka', 'deskripsi' => 'Rangkaian penghormatan yang dibuat dengan penuh ketulusan.', 'harga' => $type === 'biasa' ? 3500000 : 1500000, 'gambar' => 'login-floral-wall.png', 'aktif' => 1, 'created_at' => date('Y-m-d H:i:s')]);
        }
        if ($this->db->table('extras')->countAllResults() === 0) {
            foreach ([['Photobooth', 1500000], ['Bunga Salib', 750000], ['Bouquet', 500000], ['Kaca Hias', 800000], ['Gazebo', 2000000]] as $item) $this->db->table('extras')->insert(['nama' => $item[0], 'deskripsi' => 'Tambahan dekorasi ' . $item[0] . ' untuk melengkapi acara Anda.', 'harga' => $item[1], 'stok' => 10, 'gambar' => 'login-floral-wall.png', 'aktif' => 1, 'created_at' => date('Y-m-d H:i:s')]);
        }
    }

    public function down()
    {
        foreach (['order_extras', 'orders', 'extras', 'packages', 'admins', 'users'] as $table) $this->forge->dropTable($table, true);
    }
}
