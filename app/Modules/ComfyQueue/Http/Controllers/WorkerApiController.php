<?php

namespace App\Modules\ComfyQueue\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ComfyQueue\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkerApiController extends Controller
{
    private function authorized(Request $request): bool
    {
        $key = env('COMFYQUEUE_API_KEY');

        if (! $key) {
            return true; // sem chave configurada: acesso livre (desenvolvimento)
        }

        return $request->header('X-Api-Key') === $key;
    }

    /**
     * Retorna todos os modelos necessários para os jobs pendentes (sem duplicatas por URL).
     */
    public function pendingModels(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $models = Job::where('status', 'pending')
            ->whereNotNull('required_models')
            ->get()
            ->flatMap(fn ($job) => $job->required_models ?? [])
            ->unique('url')
            ->values();

        return response()->json($models);
    }

    /**
     * Reivindica o próximo job pendente e retorna seus dados para o executor.
     */
    public function nextJob(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $job = DB::transaction(function () {
            $job = Job::where('status', 'pending')
                ->lockForUpdate()
                ->oldest()
                ->first();

            if (! $job) {
                return null;
            }

            $job->status = 'processing';
            $job->started_at = now();
            $job->appendLog('Job reivindicado pelo worker Colab');
            $job->save();

            return $job->fresh();
        });

        if (! $job) {
            return response()->json(['message' => 'Nenhum job pendente'], 204);
        }

        return response()->json([
            'id'              => $job->id,
            'type'            => $job->type,
            'params'          => $job->params,
            'required_models' => $job->required_models ?? [],
        ]);
    }

    /**
     * Marca o job como concluído com sucesso.
     * Body: { "output_files": [...], "prompt_id": "..." }
     */
    public function done(Request $request, int $id): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $job = Job::findOrFail($id);

        $job->status       = 'done';
        $job->finished_at  = now();
        $job->output_files = $request->input('output_files', []);
        $job->prompt_id    = $request->input('prompt_id');
        $job->error        = null;
        $job->appendLog('Job concluído com sucesso');
        $job->save();

        return response()->json(['message' => 'Job marcado como concluído']);
    }

    /**
     * Marca o job como falhou.
     * Body: { "error": "mensagem de erro" }
     */
    public function fail(Request $request, int $id): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $job = Job::findOrFail($id);
        $error = $request->input('error', 'Erro desconhecido');

        $job->status      = 'error';
        $job->finished_at = now();
        $job->error       = $error;
        $job->retry_count = $job->retry_count + 1;
        $job->appendLog('Job falhou: ' . $error);
        $job->save();

        return response()->json(['message' => 'Job marcado como falhou']);
    }
}
