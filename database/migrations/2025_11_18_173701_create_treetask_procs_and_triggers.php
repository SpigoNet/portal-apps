<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // =========================================================
        // 1. STORED PROCEDURE: ATUALIZA STATUS DA FASE
        // (Usando a lógica corrigida que inclui 'Aguardando resposta')
        // =========================================================
        DB::unprepared("
            CREATE OR REPLACE PROCEDURE SP_treetask_atualiza_status_fase(IN fase_id_param bigint)
            BEGIN
                DECLARE total_tarefas INT DEFAULT 0;
                DECLARE tarefas_concluidas INT DEFAULT 0;
                DECLARE tarefas_ativas INT DEFAULT 0;
                DECLARE tarefas_em_progresso_ou_bloqueio INT DEFAULT 0;
                DECLARE novo_status VARCHAR(255);

                SELECT COUNT(*) INTO total_tarefas
                FROM treetask_tarefas
                WHERE id_fase = fase_id_param;

                IF total_tarefas > 0 THEN
                    -- Contagem de tarefas concluídas
                    SELECT COUNT(CASE WHEN status = 'Concluído' THEN 1 END)
                    INTO tarefas_concluidas
                    FROM treetask_tarefas
                    WHERE id_fase = fase_id_param;

                    SET tarefas_ativas = total_tarefas - tarefas_concluidas;

                    -- Contagem de tarefas Em Andamento ou Bloqueadas
                    SELECT COUNT(*)
                    INTO tarefas_em_progresso_ou_bloqueio
                    FROM treetask_tarefas
                    WHERE id_fase = fase_id_param
                      AND status IN ('Em Andamento', 'Aguardando resposta');

                    IF tarefas_ativas = 0 THEN
                        SET novo_status = 'Concluído';
                    ELSEIF tarefas_em_progresso_ou_bloqueio > 0 THEN
                        SET novo_status = 'Em Andamento';
                    ELSE
                        SET novo_status = 'A Fazer';
                    END IF;
                ELSE
                    SET novo_status = 'A Fazer';
                END IF;

                -- Atualiza a tabela de Fases
                UPDATE treetask_fases
                SET status = novo_status
                WHERE id_fase = fase_id_param;
            END
        ");


        // =========================================================
        // 2. STORED PROCEDURE: ATUALIZA STATUS DO PROJETO
        // =========================================================
        DB::unprepared("
            CREATE OR REPLACE PROCEDURE SP_treetask_atualiza_status_projeto(IN projeto_id_param bigint)
            BEGIN
                DECLARE total_fases INT DEFAULT 0;
                DECLARE fases_ativas INT DEFAULT 0;
                DECLARE fases_em_execucao INT DEFAULT 0;
                DECLARE novo_status VARCHAR(255);

                SELECT COUNT(*) INTO total_fases
                FROM treetask_fases
                WHERE id_projeto = projeto_id_param;

                IF total_fases > 0 THEN
                    -- Contagem de fases que não estão 'Concluído'
                    SELECT COUNT(*)
                    INTO fases_ativas
                    FROM treetask_fases
                    WHERE id_projeto = projeto_id_param
                      AND status != 'Concluído';

                    -- Contagem de fases ativamente em execução
                    SELECT COUNT(*)
                    INTO fases_em_execucao
                    FROM treetask_fases
                    WHERE id_projeto = projeto_id_param
                      AND status = 'Em Andamento';

                    IF fases_ativas = 0 THEN
                        SET novo_status = 'Concluído';
                    ELSEIF fases_em_execucao > 0 THEN
                        SET novo_status = 'Em Execução';
                    ELSE
                        SET novo_status = 'Planejamento';
                    END IF;
                ELSE
                    SET novo_status = 'Planejamento';
                END IF;

                -- Atualiza a tabela de Projetos
                UPDATE treetask_projetos
                SET status = novo_status
                WHERE id_projeto = projeto_id_param;
            END
        ");


        // =========================================================
        // 3. TRIGGERS EM TREETASK_TAREFAS (Atualizam o Status da Fase)
        // =========================================================
        DB::unprepared("
            CREATE TRIGGER TRG_treetask_tarefa_ai AFTER INSERT ON treetask_tarefas
            FOR EACH ROW CALL SP_treetask_atualiza_status_fase(NEW.id_fase);
        ");

        DB::unprepared("
            CREATE TRIGGER TRG_treetask_tarefa_au AFTER UPDATE ON treetask_tarefas
            FOR EACH ROW
            BEGIN
                IF OLD.id_fase != NEW.id_fase THEN
                    CALL SP_treetask_atualiza_status_fase(OLD.id_fase);
                END IF;
                CALL SP_treetask_atualiza_status_fase(NEW.id_fase);
            END
        ");

        DB::unprepared("
            CREATE TRIGGER TRG_treetask_tarefa_ad AFTER DELETE ON treetask_tarefas
            FOR EACH ROW CALL SP_treetask_atualiza_status_fase(OLD.id_fase);
        ");


        // =========================================================
        // 4. TRIGGERS EM TREETASK_FASES (Atualizam o Status do Projeto)
        // =========================================================
        DB::unprepared("
            CREATE TRIGGER TRG_treetask_fase_ai AFTER INSERT ON treetask_fases
            FOR EACH ROW CALL SP_treetask_atualiza_status_projeto(NEW.id_projeto);
        ");

        DB::unprepared("
            CREATE TRIGGER TRG_treetask_fase_au AFTER UPDATE ON treetask_fases
            FOR EACH ROW
            BEGIN
                IF OLD.id_projeto != NEW.id_projeto THEN
                    CALL SP_treetask_atualiza_status_projeto(OLD.id_projeto);
                END IF;
                -- Chama se o status da fase mudou
                IF OLD.status != NEW.status THEN
                    CALL SP_treetask_atualiza_status_projeto(NEW.id_projeto);
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER TRG_treetask_fase_ad AFTER DELETE ON treetask_fases
            FOR EACH ROW CALL SP_treetask_atualiza_status_projeto(OLD.id_projeto);
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // =========================================================
        // 1. DROP TRIGGERS
        // =========================================================
        DB::unprepared('DROP TRIGGER IF EXISTS TRG_treetask_tarefa_ai');
        DB::unprepared('DROP TRIGGER IF EXISTS TRG_treetask_tarefa_au');
        DB::unprepared('DROP TRIGGER IF EXISTS TRG_treetask_tarefa_ad');

        DB::unprepared('DROP TRIGGER IF EXISTS TRG_treetask_fase_ai  ');
        DB::unprepared('DROP TRIGGER IF EXISTS TRG_treetask_fase_au  ');
        DB::unprepared('DROP TRIGGER IF EXISTS TRG_treetask_fase_ad  ');

        // =========================================================
        // 2. DROP STORED PROCEDURES
        // =========================================================
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_treetask_atualiza_status_fase');
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_treetask_atualiza_status_projeto');
    }
};
