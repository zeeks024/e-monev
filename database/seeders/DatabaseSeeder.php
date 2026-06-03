<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Panggil AdminSeeder di sini
        $this->call([
            AdminSeeder::class,
            DinasSeeder::class,
            KuesionerSeeder::class,
        ]);

        // membiarkan factory ini untuk testing
        // User::factory(10)->create();
    }
}