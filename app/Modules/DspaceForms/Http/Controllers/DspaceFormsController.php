<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DspaceForms\Models\DspaceForm;
use App\Modules\DspaceForms\Models\DspaceFormField;
use App\Modules\DspaceForms\Models\DspaceFormMap;
use App\Modules\DspaceForms\Models\DspaceValuePairsList;
use App\Modules\DspaceForms\Models\SubmissionProcess;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use ZipArchive;


class DspaceFormsController extends Controller
{
    /**
     * Tela principal (Dashboard do Módulo).
     */
    public function index()
    {
        $stats = [
            // Manter a contagem de Formulários
            'forms_count' => DspaceForm::count(),
            // Manter a contagem de Vocabulários (inclui listas de valor)
            'vocabularies_count' => DspaceValuePairsList::count(),
            // Adicionar a contagem de Vínculos (Mapeamentos)
            'maps_count' => DspaceFormMap::count(),
            // Opcional: A contagem de processos de submissão pode ser útil, mas o usuário pediu "Vínculos"
            // 'submission_processes_count' => SubmissionProcess::count(),
        ];
        return view('DspaceForms::index', compact('stats'));
    }

    /**
     * Gera e exporta um arquivo ZIP com todas as configurações.
     */
    public function exportAllAsZip()
    {
        $zip = new ZipArchive();
        $zipFileName = 'dspace_config_' . date('Y-m-d_His') . '.zip';
        $zipPath = storage_path('app/' . $zipFileName);

        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            return back()->with('error', 'Não foi possível criar o arquivo ZIP.');
        }

        // 1. Gerar e adicionar submission-forms.xml
        $submissionFormsContent = $this->generateSubmissionFormsXmlContent();
        $zip->addFromString('submission-forms.xml', $submissionFormsContent);

        // 2. Gerar e adicionar item-submission.xml
        $itemSubmissionContent = $this->generateItemSubmissionXmlContent();
        $zip->addFromString('item-submission.xml', $itemSubmissionContent);
        // Adiciona um DTD placeholder para item-submission
        if (Storage::disk('local')->exists('dspace_templates/item-submission-dtd-placeholder.dtd')) {
            $zip->addFile(storage_path('app/dspace_templates/item-submission-dtd-placeholder.dtd'), 'item-submission.dtd');
        }

        // 3. Adicionar vocabulários e seus XSDs
        $zip->addEmptyDir('controlled-vocabularies');

        // ** NOVO FILTRO **: Obtém os nomes dos vocabulários que estão em uso
        $usedVocabularies = DspaceFormField::whereNotNull('vocabulary')
            ->distinct()
            ->pluck('vocabulary')
            ->toArray();

        // Filtro para exportar apenas os vocabulários usados E não 'riccps'
        $vocabularies = DspaceValuePairsList::with('pairs')
            ->whereIn('name', $usedVocabularies)
            ->where('name', '!=', 'riccps')
            ->get();

        // ** CORREÇÃO **: Carrega o conteúdo XSD do modelo uma única vez
        // pegado do arquivo 'storage/app/dspace_templates/vocabulary.xsd'

        $xsdContent = Storage::disk('local')->exists('dspace_templates/vocabulary.xsd')
            ? Storage::disk('local')->get('dspace_templates/vocabulary.xsd')
            : '';

        if (empty($xsdContent) && $vocabularies->isNotEmpty()) {
            // Se houver vocabulários, mas o modelo XSD não for encontrado, emite um aviso.
            // Para evitar um erro de ZIP vazio, continua, mas os XSDs ficarão faltando.
            // Idealmente, o arquivo 'dspace_templates/vocabulary.xsd' deve existir.
        }

        foreach ($vocabularies as $vocabulary) {
            $vocabularyXmlContent = $this->generateVocabularyXmlContent($vocabulary);
            $zip->addFromString('controlled-vocabularies/' . $vocabulary->name . '.xml', $vocabularyXmlContent);

            // ** CORREÇÃO **: Adiciona um arquivo XSD separado para cada vocabulário
            if ($xsdContent) {
                $zip->addFromString('controlled-vocabularies/' . $vocabulary->name . '.xsd', $xsdContent);
            }
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    private function generateItemSubmissionXmlContent(): string
    {
        $maps = DspaceFormMap::all();
        $processes = SubmissionProcess::with('steps')->get();

        $xml = new \SimpleXMLElement('<item-submission />');

        $submissionMap = $xml->addChild('submission-map');
        foreach($maps as $map) {
            $mapNode = $submissionMap->addChild('name-map');
            if ($map->map_type === 'handle') {
                $mapNode->addAttribute('collection-handle', $map->map_key);
            } elseif ($map->map_type === 'entity-type') {
                $mapNode->addAttribute('collection-entity-type', $map->map_key);
            }
            $mapNode->addAttribute('submission-name', $map->submission_name);
        }

        $submissionDefs = $xml->addChild('submission-definitions');
        foreach($processes as $process) {
            $processNode = $submissionDefs->addChild('submission-process');
            $processNode->addAttribute('name', $process->name);
            foreach($process->steps as $step) {
                $stepNode = $processNode->addChild('step');
                $stepNode->addAttribute('id', $step->step_id);
            }
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        $implementation = new \DOMImplementation();
        $dtd = $implementation->createDocumentType('item-submission', '', 'item-submission.dtd');
        $dom->insertBefore($dtd, $dom->documentElement);

        return $dom->saveXML();
    }

    private function generateSubmissionFormsXmlContent(): string
    {
        $forms = DspaceForm::with('rows.fields', 'rows.relationFields')->get();

        // FILTRO APLICADO: Apenas as listas que não são 'riccps' para submission-forms.xml,
        // pois elas seriam exportadas como vocabulários controlados separadamente.
        $valueLists = DspaceValuePairsList::whereNotNull('dc_term')
            ->where('name', '!=', 'riccps')
            ->get();

        $xml = new \SimpleXMLElement('<input-forms/>');
        $formDefs = $xml->addChild('form-definitions');
        foreach ($forms as $form) {
            $formNode = $formDefs->addChild('form');
            $formNode->addAttribute('name', $form->name);
            foreach ($form->rows as $row) {
                $rowNode = $formNode->addChild('row');
                foreach ($row->fields as $field) {
                    $fieldNode = $rowNode->addChild('field');
                    // Adiciona os elementos do field...
                    $fieldNode->addChild('dc-schema', $field->dc_schema);
                    $fieldNode->addChild('dc-element', $field->dc_element);
                    if ($field->dc_qualifier) $fieldNode->addChild('dc-qualifier', $field->dc_qualifier);
                    $fieldNode->addChild('repeatable', $field->repeatable ? 'true' : 'false');
                    $fieldNode->addChild('label', htmlspecialchars($field->label));
                    $inputType = $fieldNode->addChild('input-type', $field->input_type);
                    if ($field->value_pairs_name) {
                        $inputType->addAttribute('value-pairs-name', $field->value_pairs_name);
                    }
                    $fieldNode->addChild('hint', htmlspecialchars($field->hint));
                    if ($field->required) $fieldNode->addChild('required', htmlspecialchars($field->required));
                    if ($field->vocabulary) {
                        $vocabNode = $fieldNode->addChild('vocabulary', $field->vocabulary);
                        if ($field->vocabulary_closed) {
                            $vocabNode->addAttribute('closed', 'true');
                        }
                    }
                }
                // Adicionar lógica para relationFields se necessário...
            }
        }

        $valuePairs = $xml->addChild('form-value-pairs');
        foreach ($valueLists as $list) {
            $listNode = $valuePairs->addChild('value-pairs');
            $listNode->addAttribute('value-pairs-name', $list->name);
            $listNode->addAttribute('dc-term', $list->dc_term);
            foreach ($list->pairs()->orderBy('order')->get() as $pair) {
                $pairNode = $listNode->addChild('pair');
                $pairNode->addChild('displayed-value', htmlspecialchars($pair->displayed_value));
                $pairNode->addChild('stored-value', htmlspecialchars($pair->stored_value));
            }
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        $implementation = new \DOMImplementation();
        $dtd = $implementation->createDocumentType('input-forms', '', 'submission-forms.dtd');
        $dom->insertBefore($dtd, $dom->documentElement);

        return $dom->saveXML();
    }

    private function generateVocabularyXmlContent(DspaceValuePairsList $list): string
    {
        $xml = new \SimpleXMLElement('<node />');
        $xml->addAttribute('id', $list->name);
        $xml->addAttribute('label', '');
        $isComposedBy = $xml->addChild('isComposedBy');

        foreach ($list->pairs()->orderBy('order')->get() as $pair) {
            $node = $isComposedBy->addChild('node');
            $id = !empty($pair->stored_value) ? $pair->stored_value : str_replace(' ', '-', strtolower($pair->displayed_value));
            $node->addAttribute('id', $id);
            $node->addAttribute('label', htmlspecialchars($pair->displayed_value));
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        return $dom->saveXML();
    }
}

