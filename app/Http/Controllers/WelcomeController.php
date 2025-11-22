<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PortalApp; // Adicionado
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Adicionado

class WelcomeController extends Controller
{
    /**
     * Exibe a página unificada (Home/Dashboard).
     */
    public function index()
    {
        $user = Auth::user();

        // Inicia a query de Apps
        $query = PortalApp::query();

        if ($user) {
            // USUÁRIO LOGADO: Públicos + Privados + Atribuídos
            $query->where(function ($q) use ($user) {
                $q->where('visibility', 'public')
                    ->orWhere('visibility', 'private')
                    ->orWhereHas('users', function ($subQ) use ($user) {
                        $subQ->where('user_id', $user->id);
                    });
            });
        } else {
            // VISITANTE: Apenas Públicos
            $query->where('visibility', 'public');
        }

        // Busca os apps permitidos
        $apps = $query->get();

        // Busca os pacotes que contêm esses apps e organiza
        $packages = Package::whereHas('portalApps', function ($q) use ($apps) {
            $q->whereIn('id', $apps->pluck('id'));
        })->orderBy('name')->get();

        // Vincula os apps filtrados aos seus respectivos pacotes para exibição na view
        foreach ($packages as $package) {
            $package->visible_apps = $apps->where('package_id', $package->id);
        }

        return view('welcome', ['packages' => $packages]);
    }
}
