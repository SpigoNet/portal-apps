<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    /**
     * Exibe a página de boas-vindas com os aplicativos públicos.
     */
    public function index()
    {
        // Busca pacotes que contenham pelo menos um aplicativo público.
        $packages = Package::whereHas('portalApps', function ($query) {
            $query->where('visibility', 'public');
        })
            ->with(['portalApps' => function ($query) {
                // Para cada pacote, carrega apenas os aplicativos que são públicos.
                $query->where('visibility', 'public')->orderBy('title');
            }])
            ->orderBy('name')
            ->get();

        return view('welcome', ['packages' => $packages]);
    }
}
