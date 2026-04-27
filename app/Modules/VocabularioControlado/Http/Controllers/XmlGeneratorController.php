<?php

namespace App\Modules\VocabularioControlado\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Modules\VocabularioControlado\Models\Vocabulario;

class XmlGeneratorController extends Controller
{
    public function index()
    {
        Vocabulario::where('status', 'Aprovado')->update(['status' => 'Disponível']);

        $termos = Vocabulario::whereIn('status', ['Disponível', 'Aprovado'])
            ->orderByRaw('TRIM(palavra)')
            ->get();

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<node id=\"riccps\" label=\"\">\n    <isComposedBy>\n";

        foreach ($termos as $t) {
            $palavra = trim($t->palavra);
            $id = $t->id;
            $xml .= '        <node label="' . e($palavra) . '" id="' . $id . '"></node>' . "\n";
        }

        $xml .= "    </isComposedBy>\n</node>";

        return response($xml, 200)
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }
}
