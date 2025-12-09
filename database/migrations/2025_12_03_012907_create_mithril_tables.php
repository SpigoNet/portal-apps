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
        // Desabilita verificação de chave estrangeira temporariamente para criar as tabelas
        Schema::disableForeignKeyConstraints();

        // 1. Tabela Contas
        Schema::create('mithril_contas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Separação por usuário
            $table->string('nome', 100);
            $table->enum('tipo', ['normal', 'credito'])->default('normal');
            $table->decimal('saldo_inicial', 10, 2)->default(0.00);
            $table->integer('dia_fechamento')->nullable(); // Para cartão
            $table->integer('dia_vencimento')->nullable(); // Para cartão
            $table->foreignId('conta_debito_id')->nullable()->constrained('mithril_contas')->nullOnDelete();
            $table->timestamps();

            // Garante que o nome da conta seja único APENAS dentro do escopo do usuário
            $table->unique(['user_id', 'nome']);
        });

        // 2. Tabela Classificações (Categorias)
        Schema::create('mithril_classificacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nome', 100);
            $table->timestamps();

            $table->unique(['user_id', 'nome']);
        });

        // 3. Tabela Pré-Transações (Recorrentes/Parceladas)
        Schema::create('mithril_pre_transacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('descricao', 255);
            $table->decimal('valor_parcela', 10, 2);
            $table->foreignId('conta_id')->nullable()->constrained('mithril_contas');
            $table->integer('dia_vencimento');
            $table->enum('tipo', ['parcelada', 'recorrente']);
            $table->integer('total_parcelas')->nullable();
            $table->integer('parcela_atual')->default(0);
            $table->date('data_inicio');
            $table->boolean('ativa')->default(true);
            $table->date('data_ultima_acao')->nullable();
            $table->timestamps();
        });

        // 4. Tabela Transações (Efetivadas)
        Schema::create('mithril_transacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('descricao', 255);
            $table->decimal('valor', 10, 2);
            $table->foreignId('conta_id')->constrained('mithril_contas');
            $table->foreignId('pre_transacao_id')->nullable()->constrained('mithril_pre_transacoes')->nullOnDelete();
            $table->date('data_efetiva');
            $table->timestamps();
        });

        // 5. Tabela Faturas (Cabeçalho de fechamento de fatura)
        Schema::create('mithril_faturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('conta_id')->constrained('mithril_contas'); // Conta do cartão
            $table->integer('mes');
            $table->integer('ano');
            $table->decimal('valor_total', 10, 2);
            $table->date('data_pagamento');
            $table->foreignId('conta_pagamento_id')->constrained('mithril_contas'); // Conta onde foi debitado
            $table->timestamps();
        });

        // 6. Tabela Itens da Fatura do Cartão
        Schema::create('mithril_cartao_fatura_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('conta_id')->constrained('mithril_contas');
            $table->string('descricao', 255);
            $table->string('descricao_detalhada', 255)->nullable();
            $table->foreignId('classificacao_id')->nullable()->constrained('mithril_classificacoes')->nullOnDelete();
            $table->decimal('valor', 10, 2);
            $table->date('data_compra');
            $table->foreignId('fatura_id')->nullable()->constrained('mithril_faturas');
            $table->timestamps();
        });

        // 7. Tabela Regras de Descrição (Para auto-classificação)
        Schema::create('mithril_regras_descricao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('descricao_original', 255);
            $table->string('descricao_detalhada', 255);
            $table->foreignId('classificacao_id')->constrained('mithril_classificacoes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'descricao_original']);
        });

        // 8. Tabela Saldos de Fechamento (Histórico mensal)
        Schema::create('mithril_saldos_fechamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('conta_id')->constrained('mithril_contas')->cascadeOnDelete();
            $table->integer('ano');
            $table->integer('mes');
            $table->decimal('saldo_final', 10, 2);
            $table->timestamp('data_fechamento')->useCurrent();

            $table->unique(['conta_id', 'ano', 'mes']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('mithril_saldos_fechamento');
        Schema::dropIfExists('mithril_regras_descricao');
        Schema::dropIfExists('mithril_cartao_fatura_itens');
        Schema::dropIfExists('mithril_faturas');
        Schema::dropIfExists('mithril_transacoes');
        Schema::dropIfExists('mithril_pre_transacoes');
        Schema::dropIfExists('mithril_classificacoes');
        Schema::dropIfExists('mithril_contas');
        Schema::enableForeignKeyConstraints();
    }
};
