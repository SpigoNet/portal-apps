<?php

namespace App\Modules\VocabularioControlado\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\VocabularioControlado\Models\Vocabulario;
use Illuminate\View\View;

class PdfController extends Controller
{
    /**
     * Exibe uma página de impressão do vocabulário completo.
     * O usuário pode imprimir ou usar "Salvar como PDF" do browser.
     */
    public function index(): View
    {
        $termos = Vocabulario::whereIn('status', ['Disponível', 'Aprovado'])
            ->orderByRaw('TRIM(palavra)')
            ->get();

        return view('VocabularioControlado::pdf.index', compact('termos'));
    }
}
