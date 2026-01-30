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

        // Query base para apps visíveis
        $visibleAppsQuery = PortalApp::query();

        // Regra de visibilidade
        $visibleAppsQuery->where('visibility', 'public');

        if ($user) {
            $visibleAppsQuery->orWhere('visibility', 'private');
            $visibleAppsQuery->orWhereHas('users', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $apps = $visibleAppsQuery->get();

        // Carrega pacotes que tenham apps visíveis
        $packages = Package::whereHas('portalApps', function ($q) use ($apps) {
            $q->whereIn('id', $apps->pluck('id'));
        })
            ->orderBy('id', 'asc') // Ou 'name', conforme preferir
            ->get();

        // Vincula os apps ao pacote na memória para a View
        foreach ($packages as $package) {
            $package->visible_apps = $apps->where('package_id', $package->id);
        }

        return view('welcome', ['packages' => $packages]);
    }
}
