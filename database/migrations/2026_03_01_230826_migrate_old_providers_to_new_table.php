<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $oldProviders = DB::table('mundos_de_mim_providers')->get();

        foreach ($oldProviders as $old) {
            $inputType = 'image';
            $outputType = 'image';

            if ($old->driver === 'airforce') {
                $outputType = 'image';
            }

            DB::table('ai_providers')->insert([
                'name' => $old->name,
                'driver' => $old->driver,
                'input_type' => $inputType,
                'output_type' => $outputType,
                'base_url' => $old->base_url,
                'api_key' => $old->api_key,
                'is_active' => $old->is_active,
                'created_at' => $old->created_at,
                'updated_at' => $old->updated_at,
            ]);
        }

        echo 'Migrated '.count($oldProviders)." providers\n";
    }

    public function down(): void
    {
        DB::table('ai_providers')->whereIn('driver', ['pollination', 'airforce'])->delete();
    }
};
