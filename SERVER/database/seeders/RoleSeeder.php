<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator Sistem',
                'description' => 'Akses penuh administrasi CBT, manajemen pengguna, dan sistem.',
            ],
            [
                'name' => 'teacher',
                'display_name' => 'Guru / Pengajar',
                'description' => 'Akses manajemen bank soal, pembuatan ujian, dan penilaian.',
            ],
            [
                'name' => 'student',
                'display_name' => 'Siswa Peserta Ujian',
                'description' => 'Akses pengerjaan ujian berbasis Android client.',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
