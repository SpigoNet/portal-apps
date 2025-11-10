<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tabelas de configuração DSpace a serem modificadas, com suas colunas de unicidade originais.
     */
    protected array $dspaceConfigTables = [
        'dspace_form_maps' => ['map_type', 'map_key'],
        'dspace_value_pairs_lists' => ['name'],
        'dspace_forms' => ['name'],
        'dspace_submission_processes' => ['name'], // Nome após a migração de renomeação
    ];

    /**
     * Executa as migrações (Corrigido o erro de DROP INDEX).
     */
    public function up(): void
    {
        // 1. Cria a tabela 'dspace_xml_configurations' de forma segura (Idempotência).
        if (!Schema::hasTable('dspace_xml_configurations')) {
            Schema::create('dspace_xml_configurations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Usuário dono desta configuração');
                $table->string('name')->comment('Nome da configuração (ex: Produção, Homologação, Teste)');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'name']);
            });
        }

        // 2. Insere (ou encontra) a configuração padrão para o user_id=1.
        $defaultConfigId = DB::table('dspace_xml_configurations')
            ->where('user_id', 1)
            ->where('name', 'Padrão (Inicial)')
            ->value('id');

        if (!$defaultConfigId) {
            $defaultConfigId = DB::table('dspace_xml_configurations')->insertGetId([
                'user_id' => 1, // Conforme solicitado
                'name' => 'Padrão (Inicial)',
                'description' => 'Configuração inicial criada para migrar dados existentes.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }


        // 3. Modifica as tabelas existentes
        foreach ($this->dspaceConfigTables as $tableName => $uniqueColumns) {

            // Verifica se a tabela principal existe e se a coluna de configuração NÃO existe
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'xml_configuration_id')) {
                continue;
            }

            // PASSO A: Torna a coluna nullable e remove a restrição única antiga
            Schema::table($tableName, function (Blueprint $table) use ($uniqueColumns, $tableName) {

                // CORREÇÃO DO ERRO 1091:
                // A tabela 'dspace_submission_processes' foi renomeada e o índice reteve o nome antigo.
                $indexToDrop = $uniqueColumns;
                if ($tableName === 'dspace_submission_processes' && $uniqueColumns === ['name']) {
                    $indexToDrop = 'submission_processes_name_unique';
                }

                // Remove a constraint única existente
                $table->dropUnique($indexToDrop);

                // Adiciona a chave estrangeira como NULLABLE temporariamente
                $table->foreignId('xml_configuration_id')
                    ->nullable()
                    ->constrained('dspace_xml_configurations')
                    ->onDelete('cascade')
                    ->after('id');
            });

            // PASSO B: Preenche o novo campo para todas as linhas existentes
            DB::table($tableName)->update([
                'xml_configuration_id' => $defaultConfigId
            ]);

            // PASSO C: Torna a coluna NOT NULL e adiciona a nova restrição única
            Schema::table($tableName, function (Blueprint $table) use ($uniqueColumns, $tableName) {
                // Altera a coluna para NOT NULL
                $table->foreignId('xml_configuration_id')->nullable(false)->change();

                // Adiciona a nova constraint única, que inclui o xml_configuration_id
                $newUniqueColumns = array_merge(['xml_configuration_id'], $uniqueColumns);
                $table->unique($newUniqueColumns, $tableName . '_config_unique');
            });
        }
    }

    /**
     * Reverte as migrações.
     */
    public function down(): void
    {
        // Reversão de todas as tabelas modificadas
        foreach ($this->dspaceConfigTables as $tableName => $uniqueColumns) {

            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'xml_configuration_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($uniqueColumns, $tableName) {
                    // Reverte a restrição única antiga
                    $table->dropUnique($tableName . '_config_unique');
                    // Remove a chave estrangeira
                    $table->dropForeign(['xml_configuration_id']);
                    // Remove a coluna e restaura a restrição única original
                    $table->dropColumn('xml_configuration_id');
                    $table->unique($uniqueColumns);
                });
            }
        }

        // Remove a tabela de configurações com segurança
        Schema::dropIfExists('dspace_xml_configurations');
    }
};
