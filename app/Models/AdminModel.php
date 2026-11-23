<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    protected $returnType = 'array';
    protected $allowedFields = ['nama_lengkap', 'no_whatsapp', 'email', 'password', 'role'];
    protected $useTimestamps = true;

    /**
     * Cari admin berdasarkan ID (hanya pengguna dengan role Admin).
     */
    public function find($id = null)
    {
        $this->where('role', 'Admin');
        return parent::find($id);
    }

    /**
     * Cari admin berdasarkan nomor telepon/WhatsApp.
     */
    public function findByPhone(string $phone): ?array
    {
        return $this->where('role', 'Admin')
            ->where('no_whatsapp', $phone)
            ->first();
    }
}
