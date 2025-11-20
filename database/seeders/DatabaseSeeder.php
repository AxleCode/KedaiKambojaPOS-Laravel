<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test Kasir',
            'username' => 'kasir',
            'role' => 0,
            'password' => bcrypt('password'),
        ]);
       
        User::factory()->create([
            'name' => 'Test Admin',
            'username' => 'admin',
            'role' => 1,
            'password' => bcrypt('password'),
        ]);
    }
}
