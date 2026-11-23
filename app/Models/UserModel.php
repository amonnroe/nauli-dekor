<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    protected $allowedFields = ['nama_lengkap', 'no_whatsapp', 'email', 'password', 'role'];
    protected $useTimestamps = true;
    protected $returnType = 'array';

    /**
     * Mengambil semua pelanggan (role Pelanggan).
     */
    public function getCustomers(): array
    {
        return $this->where('role', 'Pelanggan')->findAll();
    }

    /**
     * Mengambil semua admin (role Admin).
     */
    public function getAdmins(): array
    {
        return $this->where('role', 'Admin')->findAll();
    }
}
