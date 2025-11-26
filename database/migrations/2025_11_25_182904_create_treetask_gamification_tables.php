<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Tabela de Recompensas (Gamification)
        Schema::create('treetask_gamification_rewards', function (Blueprint $table) {
            $table->id('id_reward');
            $table->string('nome');
            $table->enum('tipo', ['moeda', 'cosmetico', 'reforco']);
            $table->decimal('chance', 5, 2); // 0 a 100.00
            $table->integer('moeda_ganha')->default(0);
            $table->text('descricao')->nullable();
            $table->timestamps();
        });

        // 2. Tabela de Avatares/Pets (Gamification Não Punitiva)
        Schema::create('treetask_user_avatars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nome_pet')->default('Pino');
            $table->string('tipo_pet')->default('Raposa');
            $table->integer('nivel')->default(1);
            $table->integer('energia')->default(100);
            $table->timestamp('ultimo_checkin')->nullable();
            $table->timestamps();
        });

        // 3. Tabela de Configurações de Acessibilidade (Neuro-Inclusiva)
        Schema::create('treetask_user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // UX Acessível
            $table->enum('focus_mode_sound_type', ['brown_noise', 'pink_noise', 'lofi', 'none'])->default('brown_noise');
            $table->boolean('use_analog_timer')->default(true); // Timers visuais
            $table->enum('palette_style', ['minimal', 'high_contrast', 'muted'])->default('muted');
            $table->boolean('enable_haptic_feedback')->default(true);
            $table->boolean('enable_streak_repair')->default(true); // Evitar "all-or-nothing" thinking

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('treetask_user_settings');
        Schema::dropIfExists('treetask_user_avatars');
        Schema::dropIfExists('treetask_gamification_rewards');
    }
};
