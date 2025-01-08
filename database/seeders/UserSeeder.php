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
                'email'      => 'kareem@example.com',
                'password'   => Hash::make('123456789'),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name'       => 'Waad',
                'email'      => 'waad@example.com',
                'password'   => Hash::make('123456789'),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name'       => 'Andria',
                'email'      => 'andria@example.com',
                'password'   => Hash::make('123456789'),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name'       => 'Franklin',
                'email'      => 'franklin@example.com',
                'password'   => Hash::make('123456789'),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name'       => 'Alexas',
                'email'      => 'alexas@example.com',
                'password'   => Hash::make('123456789'),
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
