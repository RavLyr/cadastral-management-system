<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'username'   => 'admin',
                'password'   => Hash::make('rahasia123'),
                'full_name'  => 'Admin Sistem',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username'   => 'adminDesa1',
                'password'   => Hash::make('rahasia123'),
                'full_name'  => 'Admin Desa 1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username'   => 'adminDesa2',
                'password'   => Hash::make('rahasia123'),
                'full_name'  => 'Admin Desa 2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);
    }
}
