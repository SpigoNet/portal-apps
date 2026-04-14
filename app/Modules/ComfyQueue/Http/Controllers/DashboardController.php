<?php

namespace App\Modules\ComfyQueue\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ComfyQueue\Models\Job;
use App\Modules\ComfyQueue\Models\JobModel;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Job::query()->orderBy('created_at', 'desc');

        $status = (string) $request->query('status', '');
        if ($status !== '') {
            $query->where('status', $status);
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                if (ctype_digit($search)) {
                    $inner->orWhere('id', (int) $search);
                }

                $inner->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('prompt_id', 'like', '%' . $search . '%')
                    ->orWhere('error', 'like', '%' . $search . '%');
            });
        }

        $jobs = $query->paginate(15)->withQueryString();

        return view('ComfyQueue::index', compact('jobs'));
    }

    public function create()
    {
        return view('ComfyQueue::create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'            => 'required|string',
            'params'          => 'required|string',
            'required_models' => 'nullable|string',
        ]);

        $params = json_decode($validated['params'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['params' => 'Workflow deve ser um JSON válido.'])->withInput();
        }

        $requiredModels = null;
        if (! empty($validated['required_models'])) {
            $requiredModels = json_decode($validated['required_models'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['required_models' => 'Lista de modelos deve ser um JSON válido.'])->withInput();
            }
        }

        Job::create([
            'type'            => $validated['type'],
            'params'          => $params,
            'required_models' => $requiredModels,
            'status'          => 'pending',
        ]);

        return redirect()->route('comfy-queue.index')->with('success', 'Job criado com sucesso.');
    }

    public function edit(int $id)
    {
        $job = Job::findOrFail($id);

        return view('ComfyQueue::edit', compact('job'));
    }

    public function update(Request $request, int $id)
    {
        $job = Job::findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|string|max:120',
            'status' => 'required|in:pending,processing,done,error',
            'params' => 'required|string',
            'required_models' => 'nullable|string',
            'error' => 'nullable|string|max:5000',
            'prompt_id' => 'nullable|string|max:255',
        ]);

        $params = json_decode($validated['params'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['params' => 'Workflow deve ser um JSON válido.'])->withInput();
        }

        $requiredModels = null;
        if (! empty($validated['required_models'])) {
            $requiredModels = json_decode($validated['required_models'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['required_models' => 'Lista de modelos deve ser um JSON válido.'])->withInput();
            }
        }

        $beforeStatus = $job->status;

        $job->type = $validated['type'];
        $job->status = $validated['status'];
        $job->params = $params;
        $job->required_models = $requiredModels;
        $job->error = $validated['error'] ?: null;
        $job->prompt_id = $validated['prompt_id'] ?: null;
        $job->appendLog('Job editado manualmente', [
            'status_before' => $beforeStatus,
            'status_after' => $job->status,
        ]);
        $job->save();

        return redirect()->route('comfy-queue.index')->with('success', "Job #{$job->id} atualizado com sucesso.");
    }

    public function requeue(int $id)
    {
        $job = Job::findOrFail($id);

        $job->status = 'pending';
        $job->started_at = null;
        $job->finished_at = null;
        $job->last_heartbeat = null;
        $job->error = null;
        $job->prompt_id = null;
        $job->result_url = null;
        $job->output_files = [];
        $job->appendLog('Job reenfileirado manualmente');
        $job->save();

        return redirect()->route('comfy-queue.index')->with('success', "Job #{$job->id} reenfileirado.");
    }

    public function duplicate(int $id)
    {
        $job = Job::findOrFail($id);

        Job::create([
            'type' => $job->type,
            'params' => $job->params,
            'required_models' => $job->required_models,
            'status' => 'pending',
        ]);

        return redirect()->route('comfy-queue.index')->with('success', "Job #{$job->id} duplicado e reenfileirado.");
    }

    public function destroy(int $id)
    {
        $job = Job::findOrFail($id);
        $job->delete();

        return redirect()->route('comfy-queue.index')->with('success', "Job #{$id} removido.");
    }

    public function assistant()
    {
        $modelos = JobModel::orderBy('nome')->get();

        return view('ComfyQueue::assistant', compact('modelos'));
    }

    public function assistantStore(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:manual,modelo',
            'modelo_id' => 'required_if:tipo,modelo|nullable|exists:comfy_queue_job_models,id',
            'prompt' => 'nullable|string|required_if:tipo,manual',
            'negative_prompt' => 'nullable|string',
            'workflow_json' => 'nullable|string|required_if:tipo,manual',
        ]);

        $workflowJson = null;
        $requiredModels = null;

        if ($validated['tipo'] === 'modelo') {
            $modelo = JobModel::findOrFail($validated['modelo_id']);
            $variaveis = is_array($modelo->variaveis) ? $modelo->variaveis : [];
            $valores = [];
            $variaveisInput = $request->validate([
                'variaveis' => 'nullable|array',
                'variaveis.*.nome' => 'required|string',
                'variaveis.*.valor' => 'required|string',
            ]);

            $valoresPorNome = [];
            foreach (($variaveisInput['variaveis'] ?? []) as $item) {
                $nome = (string) ($item['nome'] ?? '');
                $valor = (string) ($item['valor'] ?? '');
                if ($nome !== '') {
                    $valoresPorNome[$nome] = $valor;
                }
            }

            foreach ($variaveis as $var) {
                $valores[$var] = $valoresPorNome[$var] ?? '';
            }

            $workflowJson = $modelo->processarJsonComValores($valores);
            $requiredModels = $this->extractModelsFromWorkflow($workflowJson);
        } else {
            $workflowJson = json_decode($validated['workflow_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['workflow_json' => 'Workflow deve ser um JSON válido.'])->withInput();
            }

            $workflowJson = $this->updateWorkflowWithPrompts($workflowJson, $validated['prompt'], $validated['negative_prompt'] ?? '');
            $requiredModels = $this->extractModelsFromWorkflow($workflowJson);
        }

        Job::create([
            'type' => 'prompt',
            'params' => $workflowJson,
            'required_models' => $requiredModels,
            'status' => 'pending',
        ]);

        return redirect()->route('comfy-queue.index')->with('success', 'Job criado com sucesso.');
    }

    private function updateWorkflowWithPrompts(array $workflow, string $positivePrompt, string $negativePrompt): array
    {
        foreach ($workflow as &$node) {
            if (isset($node['class_type']) && isset($node['inputs']['text'])) {
                if (empty($node['inputs']['text']) || stripos($node['inputs']['text'], 'positive') !== false) {
                    $node['inputs']['text'] = $positivePrompt;
                }
            }
            if (isset($node['class_type']) && isset($node['inputs']['negative'])) {
                $node['inputs']['negative'] = [[7, 0]];
            }
        }

        if (isset($workflow['7'])) {
            $workflow['7']['inputs']['text'] = $negativePrompt ?: 'text, watermark';
        }

        return $workflow;
    }

    private function extractModelsFromWorkflow(array $workflow): ?array
    {
        $models = [];

        foreach ($workflow as $node) {
            if (isset($node['class_type']) && $node['class_type'] === 'CheckpointLoaderSimple') {
                if (isset($node['inputs']['ckpt_name'])) {
                    $models[] = [
                        'name' => $node['inputs']['ckpt_name'],
                        'dest' => 'models/checkpoints',
                        'url' => '',
                    ];
                }
            }
        }

        return empty($models) ? null : $models;
    }
}
