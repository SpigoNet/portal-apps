<?php

namespace App\Services;

use App\Modules\DspaceForms\Models\DspaceForm;
use App\Modules\DspaceForms\Models\DspaceFormMap;
use App\Modules\DspaceForms\Models\DspaceValuePairsList;
use App\Modules\DspaceForms\Models\DspaceXmlConfiguration;
use App\Modules\DspaceForms\Models\SubmissionProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DspaceLegacyFormsImporter
{
    private array $importedVocabularies = [];

    public function import(DspaceXmlConfiguration $config, string $filePath, ?string $vocabularyPath = null): void
    {
        $configId = $config->id;

        DB::transaction(function () use ($configId, $filePath, $vocabularyPath) {
            $this->clearConfigData($configId);

            $xml = $this->loadXmlFile($filePath);

            if (isset($xml->{'form-map'})) {
                $this->importFormMap($configId, $xml);
            }

            if (isset($xml->{'form-definitions'})) {
                $this->importFormDefinitions($configId, $xml, $vocabularyPath);
            }

            if (isset($xml->{'form-value-pairs'})) {
                $this->importFormValuePairs($configId, $xml);
            }
        });
    }

    private function clearConfigData(int $configId): void
    {
        DspaceFormMap::where('xml_configuration_id', $configId)->delete();
        SubmissionProcess::where('xml_configuration_id', $configId)->delete();
        DspaceForm::where('xml_configuration_id', $configId)->delete();
        DspaceValuePairsList::where('xml_configuration_id', $configId)->delete();
    }

    private function importFormMap(int $configId, \SimpleXMLElement $xml): void
    {
        foreach ($xml->{'form-map'}->{'name-map'} as $map) {
            $attributes = $map->attributes();
            $handle = (string) $attributes['collection-handle'];
            $formName = (string) $attributes['form-name'];

            if (trim($handle) !== '') {
                $m = new DspaceFormMap;
                $m->xml_configuration_id = $configId;
                $m->map_type = 'handle';
                $m->map_key = $handle;
                $m->submission_name = $formName;
                $m->save();
            }

            if ($formName !== 'default') {
                $p = SubmissionProcess::where('xml_configuration_id', $configId)
                    ->where('name', $formName)
                    ->first();
                if (! $p) {
                    $p = new SubmissionProcess;
                    $p->xml_configuration_id = $configId;
                    $p->name = $formName;
                    $p->save();
                }
            }
        }
    }

    private function importFormDefinitions(int $configId, \SimpleXMLElement $xml, ?string $vocabularyPath): void
    {
        foreach ($xml->{'form-definitions'}->form as $legacyFormNode) {
            $legacyFormName = (string) $legacyFormNode['name'];
            $process = SubmissionProcess::where('xml_configuration_id', $configId)
                ->where('name', $legacyFormName)
                ->first();
            $steps = [];

            if ($process) {
                $steps[] = ['step_id' => 'collection', 'order' => 0];
            }

            foreach ($legacyFormNode->page as $pageNode) {
                $pageNumber = (string) $pageNode['number'];
                $newFormName = $legacyFormName.'-'.$pageNumber;

                if ($process) {
                    $steps[] = ['step_id' => $newFormName.'Form', 'order' => count($steps)];
                }

                $form = new DspaceForm;
                $form->xml_configuration_id = $configId;
                $form->name = $newFormName;
                $form->save();

                $fieldOrder = 0;
                foreach ($pageNode->field as $fieldNode) {
                    $row = $form->rows()->create(['order' => $fieldOrder]);
                    $fieldData = $this->convertLegacyField($fieldNode);
                    $fieldData['order'] = $fieldOrder++;
                    $row->fields()->create($fieldData);

                    if (! empty($fieldData['vocabulary']) && $vocabularyPath) {
                        $this->importVocabulary($configId, $fieldData['vocabulary'], $vocabularyPath);
                    }
                }
            }

            if ($process) {
                $steps[] = ['step_id' => 'upload', 'order' => count($steps)];
                $steps[] = ['step_id' => 'license', 'order' => count($steps)];
                $process->steps()->createMany($steps);
            }
        }
    }

    private function importFormValuePairs(int $configId, \SimpleXMLElement $xml): void
    {
        foreach ($xml->{'form-value-pairs'}->{'value-pairs'} as $listNode) {
            $attributes = $listNode->attributes();
            $listName = (string) $attributes['value-pairs-name'];
            $dcTerm = (string) $attributes['dc-term'];

            $list = new DspaceValuePairsList;
            $list->xml_configuration_id = $configId;
            $list->name = $listName;
            $list->dc_term = $dcTerm;
            $list->save();

            $pairOrder = 0;
            foreach ($listNode->pair as $pairNode) {
                $list->pairs()->create([
                    'displayed_value' => (string) $pairNode->{'displayed-value'},
                    'stored_value' => (string) $pairNode->{'stored-value'},
                    'order' => $pairOrder++,
                ]);
            }
        }
    }

    private function importVocabulary(int $configId, string $name, string $vocabularyPath): void
    {
        if (empty($name) || in_array($name, $this->importedVocabularies)) {
            return;
        }

        $filePath = rtrim($vocabularyPath, '/').'/'.$name.'.xml';
        if (! file_exists($filePath)) {
            return;
        }

        $list = DspaceValuePairsList::where('xml_configuration_id', $configId)
            ->where('name', $name)
            ->first();

        if (! $list) {
            $list = new DspaceValuePairsList;
            $list->xml_configuration_id = $configId;
            $list->name = $name;
            $list->dc_term = $name;
            $list->save();
        }

        $list->pairs()->delete();

        $xml = $this->loadXmlFile($filePath);
        $order = 0;
        $this->processNode($xml, $order, $list->id);

        $this->importedVocabularies[] = $name;
    }

    private function processNode(\SimpleXMLElement $node, int &$order, int $listId, string $prefix = ''): void
    {
        if (isset($node->isComposedBy)) {
            $currentLabel = (string) $node['label'];
            $newPrefix = $prefix.($currentLabel ? html_entity_decode($currentLabel).' > ' : '');

            foreach ($node->isComposedBy->node as $childNode) {
                if (! isset($childNode->isComposedBy)) {
                    DB::table('dspace_value_pairs')->insert([
                        'list_id' => $listId,
                        'displayed_value' => $newPrefix.html_entity_decode((string) $childNode['label']),
                        'stored_value' => (string) $childNode['label'],
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

    private function loadXmlFile(string $filePath): \SimpleXMLElement
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        $content = preg_replace('/<!DOCTYPE[^>]*>/', '', $content);

        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $message = 'Failed to parse XML: '.implode('; ', array_map(fn ($e) => trim($e->message), $errors));
            Log::error($message, ['file' => $filePath]);
            throw new \RuntimeException($message);
        }

        return $xml;
    }

    private function convertLegacyField(\SimpleXMLElement $fieldNode): array
    {
        $fieldData = [
            'dc_schema' => (string) $fieldNode->{'dc-schema'},
            'dc_element' => (string) $fieldNode->{'dc-element'},
            'dc_qualifier' => (string) $fieldNode->{'dc-qualifier'} ?: null,
            'repeatable' => (string) $fieldNode->repeatable === 'true',
            'label' => (string) $fieldNode->label,
            'input_type' => (string) $fieldNode->{'input-type'},
            'hint' => (string) $fieldNode->hint,
            'required' => (string) $fieldNode->required ?: null,
            'value_pairs_name' => (string) ($fieldNode->{'input-type'}['value-pairs-name']) ?: null,
            'vocabulary' => (string) $fieldNode->vocabulary ?: null,
            'vocabulary_closed' => isset($fieldNode->vocabulary['closed']) && (string) $fieldNode->vocabulary['closed'] === 'true',
        ];

        if (in_array($fieldData['input_type'], ['name', 'twobox'])) {
            $fieldData['input_type'] = 'onebox';
        }

        $metadata = "{$fieldData['dc_schema']}.{$fieldData['dc_element']}".($fieldData['dc_qualifier'] ? ".{$fieldData['dc_qualifier']}" : '');
        $vocabMap = [
            'dc.description.sponsorship' => 'cursos',
            'dc.publisher' => 'instituicoes',
            'dc.subject.other' => 'eixo',
        ];

        if ($fieldData['input_type'] === 'dropdown' && isset($vocabMap[$metadata])) {
            $fieldData['input_type'] = 'onebox';
            $fieldData['vocabulary'] = $vocabMap[$metadata];
            $fieldData['value_pairs_name'] = null;
        }

        return $fieldData;
    }
}
