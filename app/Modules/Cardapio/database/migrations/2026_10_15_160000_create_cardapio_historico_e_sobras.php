<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cardapio_historico_refeicoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_id')->constrained('cardapio_casas')->cascadeOnDelete();
            // Sobrevive à exclusão do plano/receita: guarda um retrato da refeição.
            $table->foreignId('refeicao_planejada_id')->nullable()->unique()->constrained('cardapio_refeicoes_planejadas')->nullOnDelete();
            $table->foreignId('receita_id')->nullable()->constrained('cardapio_receitas')->nullOnDelete();
            $table->date('data');
            $table->string('tipo_refeicao', 30);
            $table->unsignedSmallInteger('qtd_pessoas')->default(0);
            $table->timestamp('consumida_em');
            $table->unsignedTinyInteger('nota')->nullable(); // 1-5
            $table->string('feedback', 25)->nullable(); // gostamos_muito | gostamos | normal | nao_gostamos | nao_fazer_novamente
            $table->json('consumo')->nullable(); // { ingrediente_id: quantidade na unidade base } efetivamente baixada
            $table->timestamps();

            $table->index(['casa_id', 'receita_id']);
        });

        Schema::table('cardapio_ingredientes', function (Blueprint $table) {
            $table->boolean('sobra')->default(false)->after('unidade_base');
        });

        Schema::create('cardapio_sobras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_id')->constrained('cardapio_casas')->cascadeOnDelete();
            $table->foreignId('ingrediente_id')->constrained('cardapio_ingredientes')->cascadeOnDelete();
            $table->foreignId('receita_id')->nullable()->constrained('cardapio_receitas')->nullOnDelete();
            $table->foreignId('refeicao_planejada_id')->nullable()->constrained('cardapio_refeicoes_planejadas')->nullOnDelete();
            $table->decimal('quantidade', 12, 3); // unidade base
            $table->date('validade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cardapio_sobras');
        Schema::table('cardapio_ingredientes', function (Blueprint $table) {
            $table->dropColumn('sobra');
        });
        Schema::dropIfExists('cardapio_historico_refeicoes');
    }
};
