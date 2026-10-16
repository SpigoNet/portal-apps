<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cardapio_planos_semanais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_id')->constrained('cardapio_casas')->cascadeOnDelete();
            $table->date('inicio_semana'); // segunda-feira
            $table->string('status', 15)->default('rascunho'); // rascunho | ativo | concluido
            $table->timestamps();

            $table->unique(['casa_id', 'inicio_semana']);
        });

        Schema::create('cardapio_refeicoes_planejadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plano_id')->constrained('cardapio_planos_semanais')->cascadeOnDelete();
            $table->date('data');
            $table->string('tipo_refeicao', 30);
            $table->foreignId('receita_id')->nullable()->constrained('cardapio_receitas')->nullOnDelete();
            $table->unsignedSmallInteger('qtd_pessoas')->default(0);
            $table->text('justificativa')->nullable();
            $table->timestamp('consumida_em')->nullable();
            $table->timestamps();

            $table->unique(['plano_id', 'data', 'tipo_refeicao']);
        });

        Schema::create('cardapio_refeicao_planejada_pessoa', function (Blueprint $table) {
            $table->foreignId('refeicao_planejada_id')->constrained('cardapio_refeicoes_planejadas')->cascadeOnDelete();
            $table->foreignId('pessoa_id')->constrained('cardapio_pessoas')->cascadeOnDelete();

            $table->primary(['refeicao_planejada_id', 'pessoa_id']);
        });

        Schema::create('cardapio_listas_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_id')->constrained('cardapio_casas')->cascadeOnDelete();
            $table->foreignId('plano_id')->nullable()->constrained('cardapio_planos_semanais')->nullOnDelete();
            $table->string('titulo')->nullable();
            $table->string('status', 15)->default('aberta'); // aberta | concluida
            $table->timestamps();
        });

        Schema::create('cardapio_itens_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lista_id')->constrained('cardapio_listas_compras')->cascadeOnDelete();
            $table->foreignId('ingrediente_id')->nullable()->constrained('cardapio_ingredientes')->cascadeOnDelete();
            $table->string('descricao')->nullable(); // item manual sem ingrediente
            // Quantidades na unidade base do ingrediente.
            $table->decimal('quantidade_necessaria', 12, 3)->default(0);
            $table->decimal('quantidade_estoque', 12, 3)->default(0);
            $table->decimal('quantidade_comprar', 12, 3)->default(0);
            $table->boolean('comprado')->default(false);
            $table->boolean('manual')->default(false);
            $table->boolean('adicionado_ao_estoque')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cardapio_itens_compra');
        Schema::dropIfExists('cardapio_listas_compras');
        Schema::dropIfExists('cardapio_refeicao_planejada_pessoa');
        Schema::dropIfExists('cardapio_refeicoes_planejadas');
        Schema::dropIfExists('cardapio_planos_semanais');
    }
};
