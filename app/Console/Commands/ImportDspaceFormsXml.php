<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ImportsVocabularies;
use Illuminate\Console\Command;
use App\Modules\DspaceForms\Models\DspaceValuePairsList;
use App\Modules\DspaceForms\Models\DspaceValuePair;
use App\Modules\DspaceForms\Models\DspaceForm;
use Illuminate\Support\Facades\DB;

class ImportDspaceFormsXml extends Command
{
    use ImportsVocabularies;

    protected $signature = 'dspace:import-forms {file} {--vocabulary-path= : O caminho para a pasta contendo os arquivos XML de vocabulário.}';
    protected $description = 'Importa um arquivo submission-forms.xml (DSpace 7/8) e seus vocabulários associados.';

    public function handle()
    {
        $filePath = $this->argument('file');
        $vocabularyPath = $this->option('vocabulary-path');
        $importedVocabularies = [];

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado em: {$filePath}");
            return 1;
        }
        $this->info("Iniciando importação de {$filePath}...");

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            $this->info("Limpando tabelas antigas...");
            // Limpeza de todas as tabelas relacionadas
            DB::table('dspace_form_maps')->truncate();
            DB::table('dspace_value_pairs')->truncate();
            DB::table('dspace_value_pairs_lists')->truncate();
            DB::table('dspace_relation_fields')->truncate();
            DB::table('dspace_form_fields')->truncate();
            DB::table('dspace_form_rows')->truncate();
            DB::table('dspace_forms')->truncate();
            $this->info("Tabelas limpas.");

            $xml = simplexml_load_file($filePath);

            // 1. Importa <form-definitions>
            $this->info("Importando <form-definitions>...");
            foreach ($xml->{'form-definitions'}->form as $formNode) {
                $form = DspaceForm::create(['name' => (string)$formNode['name']]);
                $rowOrder = 0;
                foreach ($formNode->row as $rowNode) {
                    $row = $form->rows()->create(['order' => $rowOrder++]);
                    $fieldOrder = 0;
                    foreach ($rowNode->children() as $childNode) {
                        if ($childNode->getName() === 'field') {
                            $field = $row->fields()->create([
                                'dc_schema' => (string)$childNode->{'dc-schema'},
                                'dc_element' => (string)$childNode->{'dc-element'},
                                'dc_qualifier' => (string)$childNode->{'dc-qualifier'} ?: null,
                                'repeatable' => (string)$childNode->repeatable === 'true' || (string)$childNode->repeatable === 'Sim',
                                'label' => (string)$childNode->label,
                                'input_type' => (string)$childNode->{'input-type'},
                                'hint' => (string)$childNode->hint,
                                'required' => (string)$childNode->required ?: null,
                                'style' => (string)$childNode->style ?: null,
                                'vocabulary' => (string)$childNode->vocabulary ?: null,
                                'vocabulary_closed' => isset($childNode->vocabulary['closed']) && (string)$childNode->vocabulary['closed'] === 'true',
                                'value_pairs_name' => isset($childNode->{'input-type'}['value-pairs-name']) ? (string)$childNode->{'input-type'}['value-pairs-name'] : null,
                                'order' => $fieldOrder++,
                            ]);

                            // Tenta importar o vocabulário associado, se houver
                            if ($field->vocabulary) {
                                $this->importVocabulary($field->vocabulary, $vocabularyPath, $importedVocabularies);
                            }
                        }
                        // Adicionar lógica para 'relation-field' se necessário...
                    }
                }
            }
            $this->info(count($xml->{'form-definitions'}->form) . " formulários importados.");

            // 2. Importa <form-value-pairs>
            $this->info("Importando <form-value-pairs>...");
            if (isset($xml->{'form-value-pairs'})) {
                foreach ($xml->{'form-value-pairs'}->{'value-pairs'} as $list) {
                    $listName = (string)$list['value-pairs-name'];
                    if (in_array($listName, $importedVocabularies)) continue; // Pula se já foi importado como vocabulário

                    $newList = DspaceValuePairsList::create([
                        'name' => $listName,
                        'dc_term' => (string) $list['dc-term'],
                    ]);
                    $order = 0;
                    foreach ($list->pair as $pair) {
                        $newList->pairs()->create([
                            'displayed_value' => (string) $pair->{'displayed-value'},
                            'stored_value' => (string) $pair->{'stored-value'},
                            'order' => $order++,
                        ]);
                    }
                }
                $this->info(count($xml->{'form-value-pairs'}->{'value-pairs'}) . " listas de valores importadas.");
            }

        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
        $this->info("Importação concluída com sucesso!");
        return 0;
    }
}

