<?php
namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PortalApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Inicia a query para buscar os aplicativos visíveis
        $visibleAppsQuery = PortalApp::query();

        // Apps públicos são visíveis para todos
        $visibleAppsQuery->where('visibility', 'public');

        // Se o usuário estiver logado, adiciona os privados e os específicos dele
        if ($user) {
            $visibleAppsQuery->orWhere('visibility', 'private');
            $visibleAppsQuery->orWhereHas('users', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // Obtém a coleção de apps visíveis
        $apps = $visibleAppsQuery->get();

        // Busca apenas os pacotes que contêm algum dos apps visíveis
        $packages = Package::whereHas('portalApps', function ($q) use ($apps) {
            $q->whereIn('id', $apps->pluck('id'));
        })->orderBy('name')->get();

        // Anexa a cada pacote a lista de seus apps que são visíveis
        foreach ($packages as $package) {
            $package->visible_apps = $apps->where('package_id', $package->id);
        }

        return view('dashboard', ['packages' => $packages]);
    }
}
