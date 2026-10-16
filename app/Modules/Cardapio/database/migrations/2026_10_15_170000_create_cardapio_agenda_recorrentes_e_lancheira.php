<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cardapio_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_id')->constrained('cardapio_casas')->cascadeOnDelete();
            $table->date('data');
            $table->string('tipo_refeicao', 30)->nullable(); // null = o dia todo
            $table->string('tipo', 15); // fora (refeição não precisa ser planejada) | convidados (mais gente à mesa)
            $table->string('titulo');
            $table->unsignedSmallInteger('pessoas_extras')->default(0);
            $table->timestamps();

            $table->index(['casa_id', 'data']);
        });

        Schema::create('cardapio_ausencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_id')->constrained('cardapio_casas')->cascadeOnDelete();
            $table->foreignId('pessoa_id')->constrained('cardapio_pessoas')->cascadeOnDelete();
            $table->date('inicio');
            $table->date('fim');
            $table->string('motivo')->nullable();
            $table->timestamps();

            $table->index(['casa_id', 'inicio', 'fim']);
        });

        Schema::create('cardapio_produtos_recorrentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_id')->constrained('cardapio_casas')->cascadeOnDelete();
            // Com ingrediente: entra no cálculo de déficit (quantidade desejada na unidade base).
            // Sem ingrediente: item livre (papel higiênico, detergente…).
            $table->foreignId('ingrediente_id')->nullable()->constrained('cardapio_ingredientes')->cascadeOnDelete();
            $table->string('descricao')->nullable();
            $table->decimal('quantidade', 12, 3)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('cardapio_refeicao_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refeicao_planejada_id')->constrained('cardapio_refeicoes_planejadas')->cascadeOnDelete();
            $table->foreignId('receita_id')->constrained('cardapio_receitas')->cascadeOnDelete();
            $table->json('substituicoes')->nullable();
            $table->timestamps();
        });

        Schema::table('cardapio_refeicoes_planejadas', function (Blueprint $table) {
            $table->unsignedSmallInteger('pessoas_extras')->default(0)->after('qtd_pessoas');
            $table->boolean('pessoas_manual')->default(false)->after('pessoas_extras');
            $table->string('origem', 10)->nullable()->after('justificativa'); // sugerida | manual
        });

        Schema::table('cardapio_receitas', function (Blueprint $table) {
            $table->unsignedInteger('escolhas')->default(0)->after('frequencia'); // escolhida manualmente
            $table->unsignedInteger('rejeicoes')->default(0)->after('escolhas'); // sugestão trocada por outra
        });

        Schema::table('cardapio_casas', function (Blueprint $table) {
            $table->unsignedTinyInteger('lancheira_itens')->default(3)->after('refeicoes_ativas');
        });
    }

    public function down(): void
    {
        Schema::table('cardapio_casas', fn (Blueprint $t) => $t->dropColumn('lancheira_itens'));
        Schema::table('cardapio_receitas', fn (Blueprint $t) => $t->dropColumn(['escolhas', 'rejeicoes']));
        Schema::table('cardapio_refeicoes_planejadas', fn (Blueprint $t) => $t->dropColumn(['pessoas_extras', 'pessoas_manual', 'origem']));
        Schema::dropIfExists('cardapio_refeicao_itens');
        Schema::dropIfExists('cardapio_produtos_recorrentes');
        Schema::dropIfExists('cardapio_ausencias');
        Schema::dropIfExists('cardapio_eventos');
    }
};
