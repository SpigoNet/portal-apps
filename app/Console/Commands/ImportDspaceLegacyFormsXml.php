<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ImportsVocabularies;
use App\Modules\DspaceForms\Models\SubmissionProcess;
use Illuminate\Console\Command;
use App\Modules\DspaceForms\Models\DspaceFormMap;
use App\Modules\DspaceForms\Models\DspaceValuePairsList;
use App\Modules\DspaceForms\Models\DspaceValuePair;
use App\Modules\DspaceForms\Models\DspaceForm;
use Illuminate\Support\Facades\DB;

class ImportDspaceLegacyFormsXml extends Command
{
    use ImportsVocabularies;

    protected $signature = 'dspace:import-legacy-forms {file} {--vocabulary-path= : O caminho para a pasta contendo os arquivos XML de vocabulário.}';

    protected $description = 'Importa um arquivo input-forms.xml (DSpace 6) e o converte para a estrutura do DSpace 8, incluindo vocabulários e processos de submissão.';

    public function handle()
    {
        $filePath = $this->argument('file');
        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: {$filePath}");
            return 1;
        }

        $this->info("Iniciando importação e conversão de {$filePath}...");

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $this->info("Limpando tabelas antigas...");
            DB::table('dspace_form_maps')->truncate();
            DB::table('dspace_relation_fields')->truncate();
            DB::table('dspace_form_fields')->truncate();
            DB::table('dspace_form_rows')->truncate();
            DB::table('dspace_forms')->truncate();
            DB::table('dspace_value_pairs')->truncate();
            DB::table('dspace_value_pairs_lists')->truncate();
            DB::table('submission_steps')->truncate();
            DB::table('submission_processes')->truncate();

            $xml = simplexml_load_file($filePath, "SimpleXMLElement", LIBXML_NOCDATA);
            $vocabularyPath = $this->option('vocabulary-path');
            $importedVocabularies = [];

            // 1. Importa <form-map> e cria processos de submissão
            $this->info("Importando <form-map> e criando processos de submissão...");
            if (isset($xml->{'form-map'})) {
                foreach ($xml->{'form-map'}->{'name-map'} as $map) {
                    $attributes = $map->attributes();
                    $handle = (string)$attributes['collection-handle'];
                    $formName = (string)$attributes['form-name'];

                    if (trim($handle) !== '') {
                        DspaceFormMap::updateOrCreate(
                            ['map_type' => 'handle', 'map_key' => $handle],
                            ['submission_name' => $formName]
                        );
                    }

                    // Cria o processo de submissão correspondente
                    if ($formName !== 'default' && !SubmissionProcess::where('name', $formName)->exists()) {
                        SubmissionProcess::create(['name' => $formName]);
                        $this->line(" -> Processo de submissão '{$formName}' criado.");
                    }
                }
            }

            // 2. Importa <form-definitions> e cria os passos do processo
            $this->info("Importando e convertendo <form-definitions>...");
            if(isset($xml->{'form-definitions'})) {
                foreach ($xml->{'form-definitions'}->form as $legacyFormNode) {
                    $legacyFormName = (string)$legacyFormNode['name'];
                    $process = SubmissionProcess::where('name', $legacyFormName)->first();
                    $steps = [];

                    if ($process) {
                        $steps[] = ['step_id' => 'collection', 'order' => 0];
                    }

                    foreach ($legacyFormNode->page as $pageNode) {
                        $pageNumber = (string)$pageNode['number'];
                        $newFormName = $legacyFormName . '-' . $pageNumber;

                        if ($process) {
                            $steps[] = ['step_id' => $newFormName . 'Form', 'order' => count($steps)];
                        }

                        $form = DspaceForm::create(['name' => $newFormName]);
                        $this->line(" -> Formulário '{$newFormName}' criado.");

                        $fieldOrder = 0;
                        foreach ($pageNode->field as $fieldNode) {
                            $row = $form->rows()->create(['order' => $fieldOrder]);
                            $fieldData = $this->convertLegacyField($fieldNode);
                            $fieldData['order'] = $fieldOrder++;

                            $row->fields()->create($fieldData);

                            if (!empty($fieldData['vocabulary']) && $vocabularyPath) {
                                $this->importVocabulary($fieldData['vocabulary'], $vocabularyPath, $importedVocabularies);
                            }
                        }
                    }

                    if ($process) {
                        $steps[] = ['step_id' => 'upload', 'order' => count($steps)];
                        $steps[] = ['step_id' => 'license', 'order' => count($steps)];
                        $process->steps()->createMany($steps);
                        $this->line(" -> Passos para o processo '{$legacyFormName}' foram criados.");
                    }
                }
            }

            // 3. Importa <form-value-pairs>
            $this->info("Importando <form-value-pairs>...");
            if(isset($xml->{'form-value-pairs'})) {
                foreach ($xml->{'form-value-pairs'}->{'value-pairs'} as $listNode) {
                    $attributes = $listNode->attributes();
                    $listName = (string)$attributes['value-pairs-name'];
                    $dcTerm = (string)$attributes['dc-term'];

                    $list = DspaceValuePairsList::create([
                        'name' => $listName,
                        'dc_term' => $dcTerm,
                    ]);

                    $pairOrder = 0;
                    foreach ($listNode->pair as $pairNode) {
                        $list->pairs()->create([
                            'displayed_value' => (string)$pairNode->{'displayed-value'},
                            'stored_value' => (string)$pairNode->{'stored-value'},
                            'order' => $pairOrder++,
                        ]);
                    }
                }
            }

        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->info("Importação e conversão concluídas com sucesso!");
        return 0;
    }

    private function convertLegacyField(\SimpleXMLElement $fieldNode): array
    {
        $fieldData = [
            'dc_schema' => (string)$fieldNode->{'dc-schema'},
            'dc_element' => (string)$fieldNode->{'dc-element'},
            'dc_qualifier' => (string)$fieldNode->{'dc-qualifier'} ?: null,
            'repeatable' => (string)$fieldNode->repeatable === 'true',
            'label' => (string)$fieldNode->label,
            'input_type' => (string)$fieldNode->{'input-type'},
            'hint' => (string)$fieldNode->hint,
            'required' => (string)$fieldNode->required ?: null,
            'value_pairs_name' => (string)($fieldNode->{'input-type'}['value-pairs-name']) ?: null,
            'vocabulary' => (string)$fieldNode->vocabulary ?: null,
            'vocabulary_closed' => isset($fieldNode->vocabulary['closed']) && (string)$fieldNode->vocabulary['closed'] === 'true',
        ];

        // Regra 1: Simplificar input-type
        if (in_array($fieldData['input_type'], ['name', 'twobox'])) {
            $fieldData['input_type'] = 'onebox';
        }

        // Regra 2: Converter dropdowns para vocabulários
        $metadata = "{$fieldData['dc_schema']}.{$fieldData['dc_element']}" . ($fieldData['dc_qualifier'] ? ".{$fieldData['dc_qualifier']}" : "");
        $vocabMap = [
            'dc.description.sponsorship' => 'cursos',
            'dc.publisher' => 'instituicoes',
            'dc.subject.other' => 'eixo',
        ];

        if ($fieldData['input_type'] === 'dropdown' && isset($vocabMap[$metadata])) {
            $fieldData['input_type'] = 'onebox';
            $fieldData['vocabulary'] = $vocabMap[$metadata];
            $fieldData['value_pairs_name'] = null; // Limpa a referência antiga
        }

        return $fieldData;
    }
}

