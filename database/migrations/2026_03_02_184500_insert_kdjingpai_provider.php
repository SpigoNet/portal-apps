<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        // 1. Inserir no sistema Mundos de Mim (Novo sistema de provedores do Mundos de Mim)
        $gatewayTable = 'mundos_de_mim_providers';
        $modelTable = 'mundos_de_mim_ai_providers';

        if (!Schema::hasTable($gatewayTable)) {
            // Fallback para o caso de a tabela não existir por algum motivo, mas deveria
            return;
        }

        $gateway = DB::table($gatewayTable)->where('driver', 'kdjingpai')->first();

        if (!$gateway) {
            $gatewayId = DB::table($gatewayTable)->insertGetId([
                'name' => 'Kdjingpai',
                'driver' => 'kdjingpai',
                'base_url' => 'https://img.kdjingpai.com/',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $gatewayId = $gateway->id;
        }

        $mundosProvider = DB::table($modelTable)->where('model', 'kdjingpai-default')->first();
        if (!$mundosProvider) {
            DB::table($modelTable)->insert([
                'provider_id' => $gatewayId,
                'name' => 'Direct Image Generator',
                'driver' => 'kdjingpai',
                'model' => 'kdjingpai-default',
                'description' => 'Geração de imagem direta via URL Kdjingpai',
                'supports_image_input' => false,
                'supports_video_output' => false,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Inserir no Novo Sistema (Admin centralizado)
        $provedor = DB::table('ai_provedores')->where('nome', 'Kdjingpai')->first();
        if (!$provedor) {
            $provedorId = DB::table('ai_provedores')->insertGetId([
                'nome' => 'Kdjingpai',
                'url_json_modelos' => null,
                'default_input_types' => json_encode(['text']),
                'default_output_types' => json_encode(['image']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $provedorId = $provedor->id;
        }

        $modelo = DB::table('ai_modelos')
            ->where('ai_provedor_id', $provedorId)
            ->where('modelo_id_externo', 'kdjingpai-image')
            ->first();

        if (!$modelo) {
            DB::table('ai_modelos')->insert([
                'ai_provedor_id' => $provedorId,
                'modelo_id_externo' => 'kdjingpai-image',
                'nome' => 'Kdjingpai Image',
                'descricao' => 'Geração de imagem via URL kdjingpai.com',
                'input_types' => json_encode(['text']),
                'output_types' => json_encode(['image']),
                'pricing' => json_encode(['price' => "0.00"]),
                'raw_data' => json_encode(['info' => 'Direct URL Driver']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        $gatewayTable = 'mundos_de_mim_providers';
        $modelTable = 'mundos_de_mim_ai_providers';

        if (Schema::hasTable($gatewayTable)) {
            $gateway = DB::table($gatewayTable)->where('driver', 'kdjingpai')->first();
            if ($gateway) {
                DB::table($modelTable)->where('provider_id', $gateway->id)->delete();
                DB::table($gatewayTable)->where('id', $gateway->id)->delete();
            }
        }

        $provedor = DB::table('ai_provedores')->where('nome', 'Kdjingpai')->first();
        if ($provedor) {
            DB::table('ai_modelos')->where('ai_provedor_id', $provedor->id)->delete();
            DB::table('ai_provedores')->where('id', $provedor->id)->delete();
        }
    }
};
