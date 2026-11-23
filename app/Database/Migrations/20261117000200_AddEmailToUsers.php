<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddEmailToUsers extends Migration { public function up(){ if(!$this->db->fieldExists('email','users')) $this->forge->addColumn('users',['email'=>['type'=>'VARCHAR','constraint'=>150,'null'=>true,'after'=>'no_whatsapp']]); } public function down(){ if($this->db->fieldExists('email','users')) $this->forge->dropColumn('users','email'); } }
