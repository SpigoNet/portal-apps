<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DspaceForms\Models\DspaceForm;
use App\Modules\DspaceForms\Models\DspaceFormField;
use App\Modules\DspaceForms\Models\DspaceFormMap;
use App\Modules\DspaceForms\Models\DspaceFormRow; // NOVO: Para duplicação
use App\Modules\DspaceForms\Models\DspaceValuePair; // NOVO: Para duplicação
use App\Modules\DspaceForms\Models\DspaceRelationField; // NOVO: Para duplicação
use App\Modules\DspaceForms\Models\DspaceValuePairsList;
use App\Modules\DspaceForms\Models\DspaceXmlConfiguration; // NOVO
use App\Modules\DspaceForms\Models\SubmissionProcess;
use App\Modules\DspaceForms\Models\SubmissionStep;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth; // NOVO
use Illuminate\Http\Request; // NOVO
use ZipArchive;


class DspaceFormsController extends Controller
{
    /**
     * Tela principal (Dashboard do Módulo) - Agora lida com seleção ou dashboard da config.
     */
    public function index(Request $request)
    {
        $configId = $request->get('config_id');
        $allConfigurations = DspaceXmlConfiguration::where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        // Se houver configurações disponíveis, mas nenhuma selecionada, redireciona para a seleção.
        if (!$configId && $allConfigurations->isNotEmpty() && $allConfigurations->count() > 1) {
            return view('DspaceForms::selection', compact('allConfigurations')); // Usa a view de seleção se houver várias
        }

        // Tenta usar a primeira configuração se não houver ID, mas houver apenas uma, ou se houver a configuração Padrão
        if (!$configId && $allConfigurations->isNotEmpty()) {
            $configId = $allConfigurations->first()->id;
        }

        // Se uma configuração foi selecionada, mostra o dashboard filtrado por ela.
        if ($configId) {
            $config = DspaceXmlConfiguration::findOrFail($configId);

            // Garante que o usuário tem acesso a essa configuração
            if ($config->user_id !== Auth::id()) {
                abort(403, 'Acesso não autorizado à configuração.');
            }

            // Lógica do Dashboard Original, AGORA FILTRADA
            $stats = [
                'config_id' => $config->id,
                'config_name' => $config->name,
                // Manter a contagem de Formulários
                'forms_count' => DspaceForm::where('xml_configuration_id', $configId)->count(),
                // Manter a contagem de Vocabulários (inclui listas de valor)
                'vocabularies_count' => DspaceValuePairsList::where('xml_configuration_id', $configId)->count(),
                // Adicionar a contagem de Vínculos (Mapeamentos)
                'maps_count' => DspaceFormMap::where('xml_configuration_id', $configId)->count(),
            ];

            // Passa os stats, a configuração atual e TODAS as configs para a view de dashboard.
            return view('DspaceForms::index', compact('stats', 'allConfigurations', 'config'));
        }

        // Se não houver configurações e nenhuma selecionada, mostra a tela de seleção vazia ou criação.
        return view('DspaceForms::selection', compact('allConfigurations'));
    }
    /**
     * Exibe o formulário para criar uma nova configuração.
     */
    public function create()
    {
        return view('DspaceForms::configurations.create');
    }

    /**
     * Salva uma nova configuração.
     */
    public function store(Request $request)
    {
        $request->validate([
            // Garante que o nome seja único para o usuário logado
            'name' => 'required|string|max:255|unique:dspace_xml_configurations,name,NULL,id,user_id,' . Auth::id(),
            'description' => 'nullable|string',
        ], [
            'name.unique' => 'Você já possui uma configuração com este nome.',
        ]);

        DspaceXmlConfiguration::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('dspace-forms.index')
            ->with('success', 'Configuração criada com sucesso.');
    }

    /**
     * Duplica uma configuração existente e todos os seus dados dependentes.
     */
    public function duplicate(DspaceXmlConfiguration $configuration)
    {
        if ($configuration->user_id !== Auth::id()) {
            return back()->with('error', 'Acesso não autorizado para duplicar esta configuração.');
        }

        // 1. Cria a nova configuração (Clone)
        $newConfig = $configuration->replicate();
        $newConfig->name = $configuration->name . ' (Cópia ' . now()->format('YmdHis') . ')';
        $newConfig->created_at = now();
        $newConfig->updated_at = now();
        $newConfig->save();

        $newId = $newConfig->id;

        // 2. Duplicação em Cascata

        // Duplicar Mapeamentos (dspace_form_maps)
        $configuration->formMaps()->each(function ($map) use ($newId) {
            $newMap = $map->replicate();
            $newMap->xml_configuration_id = $newId;
            $newMap->save();
        });

        // Duplicar Listas de Valores (dspace_value_pairs_lists) e seus Pares (dspace_value_pairs)
        $configuration->valuePairsLists()->with('pairs')->each(function ($list) use ($newId) {
            $newList = $list->replicate();
            $newList->xml_configuration_id = $newId;
            $newList->save();

            // Duplicar os pares de valor internos
            foreach ($list->pairs as $pair) {
                $newPair = $pair->replicate();
                $newPair->list_id = $newList->id; // Vincula ao novo ID da lista
                $newPair->save();
            }
        });

        // Duplicar Formulários (dspace_forms) e todas as suas dependências
        $configuration->forms()->with('rows.fields', 'rows.relationFields')->each(function ($form) use ($newId) {
            $newForm = $form->replicate();
            $newForm->xml_configuration_id = $newId;
            $newForm->save();
            $newFormId = $newForm->id;

            // Duplicar Rows
            foreach ($form->rows as $row) {
                $newRow = $row->replicate();
                $newRow->form_id = $newFormId;
                $newRow->save();
                $newRowId = $newRow->id;

                // Duplicar Fields
                foreach ($row->fields as $field) {
                    $newField = $field->replicate();
                    $newField->row_id = $newRowId;
                    $newField->save();
                }

                // Duplicar Relation Fields
                foreach ($row->relationFields as $rField) {
                    $newRField = $rField->replicate();
                    $newRField->row_id = $newRowId;
                    $newRField->save();
                }
            }
        });

        // Duplicar Processos de Submissão (dspace_submission_processes) e seus Passos
        $configuration->submissionProcesses()->with('steps')->each(function ($process) use ($newId) {
            $newProcess = $process->replicate();
            $newProcess->xml_configuration_id = $newId;
            $newProcess->save();
            $newProcessId = $newProcess->id;

            // Duplicar os passos
            foreach ($process->steps as $step) {
                $newStep = $step->replicate();
                $newStep->submission_process_id = $newProcessId;
                $newStep->save();
            }
        });

        return redirect()->route('dspace-forms.index')
            ->with('success', "Configuração '{$configuration->name}' duplicada para '{$newConfig->name}'.");
    }

    // --- MÉTODOS DE EXPORTAÇÃO (FILTRADOS) ---

    /**
     * Gera e exporta um arquivo ZIP com todas as configurações.
     */
    public function exportAllAsZip(Request $request, $configId)
    {
        // Garante que o ID da configuração seja válido e pertença ao usuário logado
        $config = DspaceXmlConfiguration::findOrFail($configId);
        if ($config->user_id !== Auth::id()) {
            abort(403, 'Acesso não autorizado.');
        }

        $zip = new ZipArchive();
        $zipFileName = 'dspace_config_' . $config->name . '_' . date('Y-m-d_His') . '.zip';
        $zipPath = storage_path('app/' . $zipFileName);

        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            return back()->with('error', 'Não foi possível criar o arquivo ZIP.');
        }

        // 1. Gerar e adicionar submission-forms.xml
        $submissionFormsContent = $this->generateSubmissionFormsXmlContent($configId);
        $zip->addFromString('submission-forms.xml', $submissionFormsContent);

        // 2. Gerar e adicionar item-submission.xml
        $itemSubmissionContent = $this->generateItemSubmissionXmlContent($configId);
        $zip->addFromString('item-submission.xml', $itemSubmissionContent);
        // Adiciona um DTD placeholder para item-submission
        if (Storage::disk('local')->exists('dspace_templates/item-submission-dtd-placeholder.dtd')) {
            $zip->addFile(storage_path('app/dspace_templates/item-submission-dtd-placeholder.dtd'), 'item-submission.dtd');
        }

        // 3. Adicionar vocabulários e seus XSDs
        $zip->addEmptyDir('controlled-vocabularies');

        // Obtém os nomes dos vocabulários que estão em uso NA CONFIGURAÇÃO ATUAL
        $usedVocabularies = DspaceForm::where('xml_configuration_id', $configId)
            ->join('dspace_form_rows', 'dspace_forms.id', '=', 'dspace_form_rows.form_id')
            ->join('dspace_form_fields', 'dspace_form_rows.id', '=', 'dspace_form_fields.row_id')
            ->whereNotNull('dspace_form_fields.vocabulary')
            ->distinct()
            ->pluck('dspace_form_fields.vocabulary')
            ->toArray();

        // Filtro para exportar apenas os vocabulários usados E não 'riccps' E DA CONFIGURAÇÃO ATUAL
        $vocabularies = DspaceValuePairsList::with('pairs')
            ->where('xml_configuration_id', $configId)
            ->whereIn('name', $usedVocabularies)
            ->where('name', '!=', 'riccps')
            ->get();

        // Carrega o conteúdo XSD do modelo uma única vez
        $xsdContent = Storage::disk('local')->exists('dspace_templates/vocabulary.xsd')
            ? Storage::disk('local')->get('dspace_templates/vocabulary.xsd')
            : '';


        foreach ($vocabularies as $vocabulary) {
            $vocabularyXmlContent = $this->generateVocabularyXmlContent($vocabulary);
            $zip->addFromString('controlled-vocabularies/' . $vocabulary->name . '.xml', $vocabularyXmlContent);

            // Adiciona um arquivo XSD separado para cada vocabulário
            if ($xsdContent) {
                $zip->addFromString('controlled-vocabularies/' . $vocabulary->name . '.xsd', $xsdContent);
            }
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    private function generateItemSubmissionXmlContent($configId): string
    {
        $maps = DspaceFormMap::where('xml_configuration_id', $configId)->get();
        // Assumindo que SubmissionProcess tem o escopo 'xml_configuration_id'
        $processes = SubmissionProcess::where('xml_configuration_id', $configId)->with('steps')->get();

        $stepIds = SubmissionStep::whereHas('process', function ($query) use ($configId) {
            // Garante que os steps estão vinculados a processos desta configuração
            $query->where('xml_configuration_id', $configId);
        })->distinct()->pluck('step_id');

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

        $stepDefs = $xml->addChild('step-definitions');
        foreach ($stepIds as $stepId) {
            $stepDefNode = $stepDefs->addChild('step-definition');
            $stepDefNode->addAttribute('id', $stepId);

            // Adiciona conteúdo baseado no ID do step, conforme os exemplos
            switch ($stepId) {
                case 'collection':
                    $stepDefNode->addChild('heading');
                    $stepDefNode->addChild('processing-class', 'org.dspace.app.rest.submit.step.CollectionStep');
                    $stepDefNode->addChild('type', 'collection');
                    break;

                case 'upload':
                    $stepDefNode->addChild('heading', 'submit.progressbar.upload');
                    $stepDefNode->addChild('processing-class', 'org.dspace.app.rest.submit.step.UploadStep');
                    $stepDefNode->addChild('type', 'upload');
                    break;

                case 'license':
                    $stepDefNode->addChild('heading', 'submit.progressbar.license');
                    $stepDefNode->addChild('processing-class', 'org.dspace.app.rest.submit.step.LicenseStep');
                    $stepDefNode->addChild('type', 'license');
                    $scopeNode = $stepDefNode->addChild('scope', 'submission');
                    $scopeNode->addAttribute('visibilityOutside', 'read-only');
                    break;

                // Caso padrão para formulários dinâmicos (ex: artigopageone, tccpagetwo, etc.)
                default:
                    $stepDefNode->addAttribute('mandatory', 'true');

                    // Lógica para heading (stepone vs steptwo)
                    if (str_ends_with(strtolower($stepId), 'two') || str_ends_with(strtolower($stepId), '2')) {
                        $stepDefNode->addChild('heading', 'submit.progressbar.describe.steptwo');
                    } else {
                        // Fallback para todos os outros (pageone, personStep, etc.)
                        $stepDefNode->addChild('heading', 'submit.progressbar.describe.stepone');
                    }

                    $stepDefNode->addChild('processing-class', 'org.dspace.app.rest.submit.step.DescribeStep');
                    $stepDefNode->addChild('type', 'submission-form');
                    break;
            }
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

        // Expandir tags vazias
        $this->expandEmptyTags($dom->documentElement);

        $implementation = new \DOMImplementation();
        $dtd = $implementation->createDocumentType('item-submission', '', 'item-submission.dtd');
        $dom->insertBefore($dtd, $dom->documentElement);

        return $dom->saveXML();
    }

    private function generateSubmissionFormsXmlContent($configId): string
    {
        $forms = DspaceForm::where('xml_configuration_id', $configId)->with('rows.fields', 'rows.relationFields')->get();

        // 1. Descobrir quais listas de valores estão REALMENTE em uso NA CONFIGURAÇÃO.
        $usedListNames = DspaceForm::where('xml_configuration_id', $configId)
            ->join('dspace_form_rows', 'dspace_forms.id', '=', 'dspace_form_rows.form_id')
            ->join('dspace_form_fields', 'dspace_form_rows.id', '=', 'dspace_form_fields.row_id')
            ->whereNotNull('dspace_form_fields.value_pairs_name')
            ->where('dspace_form_fields.value_pairs_name', '!=', '')
            ->distinct()
            ->pluck('dspace_form_fields.value_pairs_name')
            ->all();

        // 2. Buscar APENAS as listas que estão em uso E pertencem a esta configuração.
        $valueLists = DspaceValuePairsList::with('pairs')
            ->where('xml_configuration_id', $configId)
            ->whereIn('name', $usedListNames)
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
                    if ($field->required) $fieldNode->addChild('required', htmlspecialchars($field->required ?? ''));
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
                $storedValue = htmlspecialchars($pair->stored_value ?? '', ENT_QUOTES, 'UTF-8');
                $pairNode->addChild('stored-value', $storedValue !== '' ? $storedValue : '');
            }
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        // Expandir tags vazias
        $this->expandEmptyTags($dom->documentElement);

        $implementation = new \DOMImplementation();
        $dtd = $implementation->createDocumentType('input-forms', '', 'submission-forms.dtd');
        $dom->insertBefore($dtd, $dom->documentElement);

        return $dom->saveXML();
    }

    // Os métodos generateVocabularyXmlContent e expandEmptyTags permanecem inalterados
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

        $this->expandEmptyTags($dom->documentElement);

        return $dom->saveXML();
    }

    private function expandEmptyTags(\DOMElement $element): void
    {
        if ($element->childNodes->length === 0) {
            $element->appendChild($element->ownerDocument->createTextNode(''));
        } else {
            foreach ($element->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $this->expandEmptyTags($child);
                }
            }
        }
    }
}
