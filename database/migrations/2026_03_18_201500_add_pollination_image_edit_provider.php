<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_provedores') || ! Schema::hasTable('ai_modelos')) {
            return;
        }

        $now = now();
        $provider = DB::table('ai_provedores')
            ->where('driver', 'pollination_image_edit')
            ->first();

        $providerPayload = [
            'nome' => 'Pollinations Image Edit',
            'driver' => 'pollination_image_edit',
            'url_json_modelos' => 'https://gen.pollinations.ai/image/models',
            'base_url' => 'https://gen.pollinations.ai',
            'default_input_types' => json_encode(['image'], JSON_UNESCAPED_UNICODE),
            'default_output_types' => json_encode(['image'], JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'updated_at' => $now,
        ];

        if ($provider) {
            DB::table('ai_provedores')
                ->where('id', $provider->id)
                ->update($providerPayload);
            $providerId = $provider->id;
        } else {
            $providerId = DB::table('ai_provedores')->insertGetId([
                ...$providerPayload,
                'created_at' => $now,
            ]);
        }

        $model = DB::table('ai_modelos')
            ->where('ai_provedor_id', $providerId)
            ->where('modelo_id_externo', 'nanobanana')
            ->first();

        $modelPayload = [
            'ai_provedor_id' => $providerId,
            'modelo_id_externo' => 'nanobanana',
            'nome' => 'Pollinations Image Edit',
            'descricao' => 'Edicao de imagem via endpoint /v1/images/edits do Pollinations.',
            'input_types' => json_encode(['image'], JSON_UNESCAPED_UNICODE),
            'output_types' => json_encode(['image'], JSON_UNESCAPED_UNICODE),
            'pricing' => json_encode(['price' => '0.00'], JSON_UNESCAPED_UNICODE),
            'raw_data' => json_encode([
                'endpoint' => 'https://gen.pollinations.ai/v1/images/edits',
                'provider_type' => 'openai-compatible-image-edit',
            ], JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'updated_at' => $now,
        ];

        if ($model) {
            DB::table('ai_modelos')
                ->where('id', $model->id)
                ->update($modelPayload);
            $modelId = $model->id;
        } else {
            $modelId = DB::table('ai_modelos')->insertGetId([
                ...$modelPayload,
                'created_at' => $now,
            ]);
        }

        if (Schema::hasTable('ai_modelos_padrao')) {
            $currentDefault = DB::table('ai_modelos_padrao')
                ->join('ai_modelos', 'ai_modelos.id', '=', 'ai_modelos_padrao.ai_modelo_id')
                ->join('ai_provedores', 'ai_provedores.id', '=', 'ai_modelos.ai_provedor_id')
                ->where('ai_modelos_padrao.input_type', 'image')
                ->where('ai_modelos_padrao.output_type', 'image')
                ->select('ai_provedores.driver')
                ->first();

            if (! $currentDefault || $currentDefault->driver === 'pollination') {
                DB::table('ai_modelos_padrao')->updateOrInsert(
                    ['input_type' => 'image', 'output_type' => 'image'],
                    ['ai_modelo_id' => $modelId, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_provedores') || ! Schema::hasTable('ai_modelos')) {
            return;
        }

        $provider = DB::table('ai_provedores')
            ->where('driver', 'pollination_image_edit')
            ->first();

        if (! $provider) {
            return;
        }

        $modelIds = DB::table('ai_modelos')
            ->where('ai_provedor_id', $provider->id)
            ->pluck('id');

        if (Schema::hasTable('ai_modelos_padrao') && $modelIds->isNotEmpty()) {
            DB::table('ai_modelos_padrao')
                ->where('input_type', 'image')
                ->where('output_type', 'image')
                ->whereIn('ai_modelo_id', $modelIds)
                ->delete();
        }

        DB::table('ai_modelos')->where('ai_provedor_id', $provider->id)->delete();
        DB::table('ai_provedores')->where('id', $provider->id)->delete();
    }
};
