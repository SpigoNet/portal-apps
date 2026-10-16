<?php

use App\Models\Package;
use App\Models\PortalApp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Grupo => [start_link => dados do app, usados só se o app ainda não existir]. */
    private function apps(): array
    {
        return [
            'Jogos' => [
                '/bingo' => [],
                '/quebra-cabeca' => [],
                '/bolao' => [
                    'title' => 'Bolão de Reuniões',
                    'description' => 'Jogo de palpites sobre reuniões.',
                    'icon' => 'images/apps/bolao.png',
                    'visibility' => 'public',
                ],
            ],
            'Ferramentas' => [
                '/vocabulario-controlado' => [
                    'title' => 'Vocabulário Controlado',
                    'description' => 'Gestão de vocabulário controlado.',
                    'icon' => 'images/apps/vocabulario.png',
                    'visibility' => 'public',
                ],
            ],
            'Pessoal' => [
                '/alfred' => [
                    'title' => 'Alfred',
                    'description' => 'Assistente pessoal: rotinas, medicações, hidratação e tarefas.',
                    'icon' => 'images/apps/alfred.png',
                    'visibility' => 'specific',
                ],
                '/yomi' => [],
            ],
        ];
    }

    /** Ícones de apps que já existem mas estão sem imagem. */
    private const ICONES = [
        '/admin' => 'images/apps/admin.png',
        '/comfy-queue' => 'images/apps/comfyqueue.png',
        '/ferramentas/whatsapp' => 'images/apps/whatsapp.png',
        '/gestor-horas' => 'images/apps/gestor-horas.png',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('packages') || ! Schema::hasTable('portal_apps')) {
            return;
        }

        foreach ($this->apps() as $nomeGrupo => $apps) {
            $grupo = Package::firstOrCreate(
                ['name' => $nomeGrupo],
                ['description' => match ($nomeGrupo) {
                    'Jogos' => 'Jogos e passatempos do portal.',
                    'Pessoal' => 'Apps de uso pessoal.',
                    default => null,
                }]
            );

            foreach ($apps as $startLink => $dados) {
                $app = PortalApp::where('start_link', $startLink)->first();

                if ($app === null && $dados !== []) {
                    PortalApp::create($dados + ['start_link' => $startLink, 'package_id' => $grupo->id]);
                } elseif ($app !== null && $app->package_id === null) {
                    $app->update(['package_id' => $grupo->id]);
                }
            }
        }

        foreach (self::ICONES as $startLink => $icone) {
            DB::table('portal_apps')
                ->where('start_link', $startLink)
                ->where(fn ($q) => $q->whereNull('icon')->orWhere('icon', 'not like', 'images/%'))
                ->update(['icon' => $icone]);
        }
    }

    public function down(): void
    {
        // Reorganização de dados: não reversível de forma segura.
    }
};
