<?php

namespace App\Console\Commands;

use App\Modules\DspaceForms\Models\DspaceFormMap;
use App\Modules\DspaceForms\Models\SubmissionProcess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportItemSubmissionXml extends Command
{
    protected $signature = 'dspace:import-submission-process {file}';
    protected $description = 'Importa um arquivo item-submission.xml (DSpace 7/8) para o banco de dados.';

    public function handle()
    {
        $filePath = $this->argument('file');
        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: {$filePath}");
            return 1;
        }

        $this->info("Iniciando importação de {$filePath}...");

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $this->info("Limpando tabelas de processos de submissão e mapas...");
            DB::table('submission_steps')->truncate();
            DB::table('submission_processes')->truncate();
            DB::table('dspace_form_maps')->truncate();

            $xml = simplexml_load_file($filePath);

            // Importar submission-map (similar ao form-map legado)
            if (isset($xml->{'submission-map'})) {
                $this->info('Importando <submission-map>...');
                foreach ($xml->{'submission-map'}->{'name-map'} as $map) {
                    $attributes = $map->attributes();
                    if (isset($attributes['collection-handle'])) {
                        DspaceFormMap::create([
                            'map_type' => 'handle',
                            'map_key' => (string)$attributes['collection-handle'],
                            'submission_name' => (string)$attributes['submission-name'],
                        ]);
                    } elseif (isset($attributes['collection-entity-type'])) {
                        DspaceFormMap::create([
                            'map_type' => 'entity-type',
                            'map_key' => (string)$attributes['collection-entity-type'],
                            'submission_name' => (string)$attributes['submission-name'],
                        ]);
                    } else {
                        $this->warn("Ignorando 'name-map' sem 'collection-handle' ou 'collection-entity-type'.");
                    }
                }
            }

            // Importar submission-definitions
            if (isset($xml->{'submission-definitions'})) {
                $this->info('Importando <submission-definitions>...');
                foreach ($xml->{'submission-definitions'}->{'submission-process'} as $processNode) {
                    $processName = (string)$processNode['name'];
                    $process = SubmissionProcess::create(['name' => $processName]);

                    $order = 0;
                    foreach ($processNode->step as $stepNode) {
                        $process->steps()->create([
                            'step_id' => (string)$stepNode['id'],
                            'order' => $order++,
                        ]);
                    }
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->info('Importação do processo de submissão concluída.');
        return 0;
    }
}

