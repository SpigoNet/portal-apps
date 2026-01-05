<?php

namespace App\Modules\MundosDeMim\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MundosDeMim\Models\UserAttribute;
use App\Modules\MundosDeMim\Models\RelatedPerson;
use App\Modules\MundosDeMim\Models\DailyGeneration; // <--- Novo
use App\Modules\MundosDeMim\Models\Theme;           // <--- Novo
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Dados existentes
        $hasBiometrics = UserAttribute::where('user_id', $userId)->exists();
        $relatedCount = RelatedPerson::where('user_id', $userId)->count();
        $activeRelated = RelatedPerson::where('user_id', $userId)->where('is_active', true)->count();

        // Novos dados para os cards
        $artCount = DailyGeneration::where('user_id', $userId)->count();

        // Conta temas sazonais ativos hoje (Ex: Natal, Halloween)
        $activeSeasonal = Theme::where('is_seasonal', true)
            ->whereDate('starts_at', '<=', now())
            ->whereDate('ends_at', '>=', now())
            ->count();

        $stats = [
            'biometria_ok' => $hasBiometrics,
            'total_pessoas' => $relatedCount,
            'pessoas_ativas' => $activeRelated,
            'total_artes' => $artCount,      // <--- Novo
            'temas_sazonais' => $activeSeasonal // <--- Novo
        ];

        return view('MundosDeMim::index', compact('stats'));
    }
}
