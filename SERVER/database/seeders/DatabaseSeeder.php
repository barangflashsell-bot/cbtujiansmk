<?php

namespace Database\Seeders;

use App\Models\Classes;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with minimal development accounts.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        $adminRole = Role::where('name', 'admin')->first();
        $teacherRole = Role::where('name', 'teacher')->first();
        $studentRole = Role::where('name', 'student')->first();

        // 1. Seed Development Admin User
        if ($adminRole) {
            User::firstOrCreate(
                ['username' => 'admin'],
                [
                    'role_id' => $adminRole->id,
                    'name' => 'Administrator CBT',
                    'email' => 'admin@cbt.local',
                    'password' => Hash::make('admin123'),
                    'is_active' => true,
                ]
            );
        }

        // 2. Seed Development Teacher (Guru) User & Profile
        if ($teacherRole) {
            $guruUser = User::firstOrCreate(
                ['username' => 'guru'],
                [
                    'role_id' => $teacherRole->id,
                    'name' => 'Guru Pengajar',
                    'email' => 'guru@cbt.local',
                    'password' => Hash::make('guru123'),
                    'is_active' => true,
                ]
            );

            Teacher::firstOrCreate(
                ['user_id' => $guruUser->id],
                [
                    'nip' => '197501012000011001',
                    'phone' => '08123456789',
                ]
            );
        }

        // 3. Seed Development Student (Peserta) User & Profile
        if ($studentRole) {
            $defaultClass = Classes::firstOrCreate(
                ['name' => '9A'],
                [
                    'level' => '9',
                    'academic_year' => '2026/2027',
                    'status' => 'active',
                ]
            );

            $pesertaUser = User::firstOrCreate(
                ['username' => 'peserta'],
                [
                    'role_id' => $studentRole->id,
                    'name' => 'Siswa Peserta Ujian',
                    'email' => 'peserta@cbt.local',
                    'password' => Hash::make('peserta123'),
                    'is_active' => true,
                ]
            );

            Student::firstOrCreate(
                ['user_id' => $pesertaUser->id],
                [
                    'class_id' => $defaultClass->id,
                    'nis' => 'NIS-DEV-001',
                    'nisn' => '0081234567',
                    'gender' => 'L',
                ]
            );
        }
    }
}
