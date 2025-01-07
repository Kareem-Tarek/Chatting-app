<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name'       => 'Kareem',
                'email'      => 'kareemtarekpk@gmail.com',
                'password'   => Hash::make('123456789'),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name'       => 'Andria',
                'email'      => 'andria@gmail.com',
                'password'   => Hash::make('123456789'),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name'       => 'Franklin',
                'email'      => 'franklin@gmail.com',
                'password'   => Hash::make('123456789'),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name'       => 'Alexas',
                'email'      => 'alexas@gmail.com',
                'password'   => Hash::make('123456789'),
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
