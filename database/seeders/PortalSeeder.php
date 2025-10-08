<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\PortalApp;
use Illuminate\Support\Facades\Hash;

class PortalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Cria o usuário Administrador
        $adminUser = User::firstOrCreate(
            ['email' => 'spiriguidiberto@gmail.com'],
            [
                'name' => 'Gustavo Gomes',
                'password' => bcrypt('12345678') // Mude para uma senha segura!
            ]
        );

        // 2.1 Cria o Package pro App de Admin
        $package = Package::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Pacote de aplicativos administrativos']
        );

        // 2.2. Cria o App do Painel de Administração
        $adminApp = PortalApp::firstOrCreate(
            ['start_link' => '/admin/apps'],
            [
                'title' => 'Gerenciador do Portal',
                'description' => 'Área para cadastrar e configurar os aplicativos do portal.',
                'icon' => 'fa-solid fa-gear',
                'visibility' => 'specific',
                'package_id' => $package->id
            ]
        );

        // 3. Vincula o App de Admin ao Usuário Admin
        // O método syncWithoutDetaching evita duplicatas na tabela pivot
        $adminUser->portalApps()->syncWithoutDetaching($adminApp->id);
    }
}
