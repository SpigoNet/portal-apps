<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // inserto ou update no usuário admin
        User::updateOrCreate(
            ['email' => 'spiriguidiberto@gmail.com'],
            [
                'name' => 'Gustavo Gomes',
                'password' => bcrypt('12345678') // Mude para uma senha segura!
            ]
        );

        $this->call([
            PortalSeeder::class,
        ]);
    }
}
