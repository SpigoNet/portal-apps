<?php

namespace App\Console\Commands\Concerns;

use App\Modules\DspaceForms\Models\DspaceValuePairsList;
use Illuminate\Support\Facades\DB;

trait ImportsVocabularies
{
    /**
     * Importa um arquivo XML de vocabulário específico.
     *
     * @param string $name Nome do vocabulário (ex: "cursos")
     * @param string|null $vocabularyPath Caminho para a pasta de vocabulários
     * @param array &$importedVocabularies Array para rastrear vocabulários já importados
     * @return bool
     */
    protected function importVocabulary(string $name, ?string $vocabularyPath, array &$importedVocabularies): bool
    {
        if (empty($name) || empty($vocabularyPath) || in_array($name, $importedVocabularies)) {
            return false;
        }

        $filePath = rtrim($vocabularyPath, '/') . '/' . $name . '.xml';

        if (!file_exists($filePath)) {
            $this->warn(" -> Arquivo de vocabulário '{$filePath}' não encontrado. Pulando importação para '{$name}'.");
            return false;
        }

        $this->line(" -> Importando vocabulário '{$name}' de '{$filePath}'...");

        $list = DspaceValuePairsList::firstOrCreate(
            ['name' => $name],
            ['dc_term' => $name] // Usa o nome como dc_term por padrão
        );

        $list->pairs()->delete();

        $xml = simplexml_load_file($filePath);
        $order = 0;
        $this->processNode($xml, $order, $list->id);

        $this->line("    -> '{$name}' importado com {$order} itens.");
        $importedVocabularies[] = $name; // Marca como importado
        return true;
    }

    /**
     * Processa recursivamente cada nó do XML para extrair os pares de valores.
     */
    private function processNode(\SimpleXMLElement $node, int &$order, int $listId, string $prefix = ''): void
    {
        if (isset($node->isComposedBy)) {
            $currentLabel = (string)$node['label'];
            $newPrefix = $prefix . ($currentLabel ? html_entity_decode($currentLabel) . ' > ' : '');

            foreach ($node->isComposedBy->node as $childNode) {
                if (!isset($childNode->isComposedBy)) {
                    DB::table('dspace_value_pairs')->insert([
                        'list_id' => $listId,
                        'displayed_value' => $newPrefix . html_entity_decode((string)$childNode['label']),
                        'stored_value' => (string)$childNode['label'],
                        'order' => $order++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $this->processNode($childNode, $order, $listId, $newPrefix);
                }
            }
        }
    }
}
