<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // User::create([
        //     'name' => 'Admin',
        //     'email' => 'admin@gmail.com',
        //     'password' => Hash::make('password'),
        //     'username' => 'admin',
        //     'last_login' => now(),
        // ]);

        User::create([
            'name' => 'User',
            'email' => 'testing@gmail.com',
            'password' => Hash::make('password'),
            'username' => 'user',
            'last_login' => now(),
        ]);

        // User::factory(1)->create();
    }
}
