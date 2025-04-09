<?php

namespace Database\Seeders;

use App\Models\Roles;
use App\Models\User;
use Exception;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Roles::where('name', 'admin')->first();
        if (!$adminRole) {
            throw new Exception('Role "admin" not found in database.');
        }

        User::create([
            'name' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('adminpass'),
            'role_id' => $adminRole->id
        ]);
    }
}
