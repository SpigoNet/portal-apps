<?php

namespace App\Http\Controllers;

use App\Models\PortalApp;
use Illuminate\Http\Request;

class ManifestController extends Controller
{
    public function show($id)
    {
        // Busca o App ou falha se não existir
        $app = PortalApp::findOrFail($id);

        // Define o ícone. Se o campo 'icon' estiver vazio ou for antigo, usa um padrão.
        // Assumimos que agora o campo 'icon' contém '/storage/apps/icon.png' ou similar.
        $iconPath = $app->icon;

        // Fallback simples caso o ícone ainda não tenha sido atualizado para caminho de arquivo
        if (!$iconPath || !str_contains($iconPath, '/')) {
            $iconPath = 'images/default-app-icon.png'; // Garanta que essa imagem exista em public/images
        }

        // Garante que é uma URL completa
        $iconUrl = asset($iconPath);

        $manifest = [
            "name" => $app->title,
            "short_name" => $app->pwa_short_name ?? substr($app->title, 0, 12),
            "description" => $app->description,
            "start_url" => url($app->start_link),
            "display" => $app->pwa_display ?? 'standalone',
            "background_color" => $app->pwa_background_color ?? '#1a1b26',
            "theme_color" => $app->pwa_theme_color ?? '#ccf381',
            "orientation" => $app->pwa_orientation ?? 'any',
            "scope" => $app->pwa_scope ?? dirname($app->start_link),
            "icons" => [
                [
                    "src" => $iconUrl,
                    "sizes" => "192x192", // Idealmente você teria tamanhos diferentes, mas o navegador redimensiona
                    "type" => "image/png",
                    "purpose" => "any maskable"
                ],
                [
                    "src" => $iconUrl,
                    "sizes" => "512x512",
                    "type" => "image/png"
                ]
            ]
        ];

        return response()->json($manifest)->header('Content-Type', 'application/manifest+json');
    }
}
