<?php

namespace App\Http\Controllers;

use App\Models\PortalApp;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ManifestController extends Controller
{
    private const TIPOS = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
    ];

    public function show(int|string $id): JsonResponse
    {
        $app = PortalApp::findOrFail($id);

        $startLink = '/'.ltrim($app->start_link, '/');
        $arquivoIcone = $app->icon_file ?? 'apple-touch-icon.png';
        $tipo = self::TIPOS[strtolower(pathinfo($arquivoIcone, PATHINFO_EXTENSION))] ?? 'image/png';

        $icones = [
            ['src' => asset($arquivoIcone), 'sizes' => '192x192', 'type' => $tipo, 'purpose' => 'any'],
            ['src' => asset($arquivoIcone), 'sizes' => '512x512', 'type' => $tipo, 'purpose' => 'any'],
        ];

        if ($app->maskable_icon_file !== null) {
            $icones[] = ['src' => asset($app->maskable_icon_file), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'];
        }

        $display = $app->pwa_display ?: 'fullscreen';

        $manifest = [
            'id' => $startLink,
            'name' => $app->title,
            'short_name' => $app->pwa_short_name ?: Str::limit($app->title, 12, ''),
            'description' => $app->description,
            'lang' => 'pt-BR',
            'start_url' => $startLink,
            'scope' => $app->pwa_scope ?: $startLink,
            'display' => $display,
            // Se o navegador não suportar tela cheia, cai para standalone e depois para o display padrão.
            'display_override' => array_values(array_unique([$display, 'fullscreen', 'standalone'])),
            'background_color' => $app->pwa_background_color ?: '#1a1b26',
            'theme_color' => $app->pwa_theme_color ?: '#ccf381',
            'orientation' => $app->pwa_orientation ?: 'any',
            'icons' => $icones,
        ];

        return response()->json($manifest, 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
