<?php

namespace App\Modules\Engenhocopolis\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class EngenhocopolisController extends Controller
{
    /**
     * O jogo roda inteiro no navegador (Alpine + SVG); progresso e montagens ficam no localStorage.
     */
    public function index(): View
    {
        return view('Engenhocopolis::index');
    }
}
