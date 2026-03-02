<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mundos_de_mim_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->string('name');
            $table->unsignedBigInteger('ai_provider_id')->nullable();
            $table->timestamps();
        });

        $settings = [
            ['setting_key' => 'image_to_image', 'name' => 'Imagem → Imagem'],
            ['setting_key' => 'text_to_image', 'name' => 'Texto → Imagem'],
            ['setting_key' => 'image_to_video', 'name' => 'Imagem → Vídeo'],
        ];

        foreach ($settings as $setting) {
            \DB::table('mundos_de_mim_ai_settings')->insert($setting);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mundos_de_mim_ai_settings');
    }
};
