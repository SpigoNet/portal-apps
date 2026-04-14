<?php

namespace App\Modules\ComfyQueue\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ComfyQueue\Models\Job;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WorkerApiController extends Controller
{
    private function appendUploadedFileToJob(Job $job, array $fileMeta): void
    {
        $existing = is_array($job->output_files) ? $job->output_files : [];
        $existing[] = $fileMeta;
        $job->output_files = array_values($existing);
        $job->save();
    }

    private function normalizeOutputFiles(Request $request): array
    {
        $outputFiles = $request->input('output_files', []);

        if (is_array($outputFiles)) {
            return $outputFiles;
        }

        if (! is_string($outputFiles) || trim($outputFiles) === '') {
            return [];
        }

        $decoded = json_decode($outputFiles, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function storeUploadedOutputs(Request $request, Job $job): array
    {
        $uploaded = $request->file('outputs', []);
        if ($uploaded === [] || $uploaded === null) {
            $uploaded = $request->file('outputs[]', []);
        }

        if ($uploaded instanceof UploadedFile) {
            $uploaded = [$uploaded];
        }

        if (! is_array($uploaded) || $uploaded === []) {
            return [];
        }

        $stored = [];

        foreach ($uploaded as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store("comfy-queue/jobs/{$job->id}", 'public');

            $stored[] = [
                'original_name' => $file->getClientOriginalName(),
                'filename' => basename($path),
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'type' => 'uploaded',
            ];
        }

        return $stored;
    }

    private function storeBase64Outputs(Request $request, Job $job): array
    {
        $uploadedFiles = $request->input('uploaded_files', []);

        if (! is_array($uploadedFiles) || $uploadedFiles === []) {
            return [];
        }

        $stored = [];

        foreach ($uploadedFiles as $fileData) {
            if (! is_array($fileData) || empty($fileData['content']) || empty($fileData['filename'])) {
                Log::warning('ComfyQueue: entrada de arquivo base64 inválida ignorada', ['job_id' => $job->id, 'data' => is_array($fileData) ? array_keys($fileData) : gettype($fileData)]);
                continue;
            }

            $content = base64_decode($fileData['content'], true);
            if ($content === false) {
                Log::warning('ComfyQueue: falha ao decodificar base64 do arquivo', ['job_id' => $job->id, 'filename' => $fileData['filename']]);
                continue;
            }

            $originalName = basename($fileData['filename']);
            $mime = $fileData['mime'] ?? 'application/octet-stream';
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) ?: 'bin';
            $storedFilename = uniqid('', true) . '.' . $extension;
            $path = "comfy-queue/jobs/{$job->id}/{$storedFilename}";

            Storage::disk('public')->put($path, $content);

            $stored[] = [
                'original_name' => $originalName,
                'filename' => $storedFilename,
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'mime' => $mime,
                'size' => strlen($content),
                'type' => 'uploaded',
            ];
        }

        return $stored;
    }

    /**
     * Recebe upload em partes pequenas para contornar bloqueio do ModSecurity.
     */
    public function uploadChunk(Request $request, int $id): JsonResponse
    {
        $job = Job::findOrFail($id);

        $uploadId = (string) $request->input('u', $request->input('upload_id', ''));
        $filename = basename((string) $request->input('f', $request->input('filename', '')));
        $mime = (string) $request->input('m', $request->input('mime', 'application/octet-stream'));
        $chunkIndex = (int) $request->input('i', $request->input('chunk_index', -1));
        $totalChunks = (int) $request->input('t', $request->input('total_chunks', 0));
        $chunkHex = (string) $request->input('h', '');
        $chunkBase64Url = (string) $request->input('c', '');
        $chunkBase64 = (string) $request->input('chunk', '');

        if ($uploadId === '' || $filename === '' || $chunkIndex < 0 || $totalChunks <= 0) {
            return response()->json(['message' => 'Payload de chunk inválido'], 422);
        }

        $chunkData = null;

        if ($chunkHex !== '') {
            $chunkData = @hex2bin($chunkHex);
            if ($chunkData === false) {
                return response()->json(['message' => 'Chunk hex inválido'], 422);
            }
        } elseif ($chunkBase64Url !== '') {
            $normalized = strtr($chunkBase64Url, '-_', '+/');
            $padding = strlen($normalized) % 4;
            if ($padding > 0) {
                $normalized .= str_repeat('=', 4 - $padding);
            }
            $chunkData = base64_decode($normalized, true);
            if ($chunkData === false) {
                return response()->json(['message' => 'Chunk base64url inválido'], 422);
            }
        } elseif ($chunkBase64 !== '') {
            $chunkData = base64_decode($chunkBase64, true);
            if ($chunkData === false) {
                return response()->json(['message' => 'Chunk base64 inválido'], 422);
            }
        } else {
            return response()->json(['message' => 'Chunk ausente'], 422);
        }

        $tmpDir = storage_path("app/comfy-queue/chunks/{$job->id}");
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $safeUploadId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $uploadId) ?: uniqid('upload_', true);
        $tmpPath = "{$tmpDir}/{$safeUploadId}.part";

        if ($chunkIndex === 0 && file_exists($tmpPath)) {
            @unlink($tmpPath);
        }

        $handle = fopen($tmpPath, 'ab');
        if ($handle === false) {
            return response()->json(['message' => 'Falha ao abrir arquivo temporário'], 500);
        }

        fwrite($handle, $chunkData);
        fclose($handle);

        if ($chunkIndex + 1 < $totalChunks) {
            return response()->json([
                'message' => 'Chunk recebido',
                'chunk_index' => $chunkIndex,
                'total_chunks' => $totalChunks,
                'completed' => false,
            ]);
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'bin';
        $storedFilename = uniqid('', true) . '.' . $extension;
        $path = "comfy-queue/jobs/{$job->id}/{$storedFilename}";
        $content = file_get_contents($tmpPath);
        if ($content === false) {
            return response()->json(['message' => 'Falha ao finalizar upload'], 500);
        }

        Storage::disk('public')->put($path, $content);
        @unlink($tmpPath);

        $fileMeta = [
            'original_name' => $filename,
            'filename' => $storedFilename,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'mime' => $mime,
            'size' => strlen($content),
            'type' => 'uploaded',
        ];

        $this->appendUploadedFileToJob($job, $fileMeta);

        return response()->json([
            'message' => 'Upload concluído',
            'completed' => true,
            'file' => $fileMeta,
        ]);
    }

    /**
     * Retorna todos os modelos necessários para os jobs pendentes (sem duplicatas por URL).
     */
    public function pendingModels(Request $request): JsonResponse
    {
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
            $job->finished_at = null;
            $job->error = null;
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
        $job = Job::findOrFail($id);

        $existingOutputFiles = is_array($job->output_files) ? $job->output_files : [];
        $outputFiles = $this->normalizeOutputFiles($request);
        if ($existingOutputFiles !== []) {
            $outputFiles = array_values(array_merge($existingOutputFiles, $outputFiles));
        }

        $storedUploads = $this->storeUploadedOutputs($request, $job);
        $base64Uploads = $this->storeBase64Outputs($request, $job);
        $allUploads = array_merge($storedUploads, $base64Uploads);
        if ($allUploads !== []) {
            $outputFiles = array_values(array_merge($outputFiles, $allUploads));
        }

        $job->status       = 'done';
        $job->finished_at  = now();
        $job->output_files = $outputFiles;
        $job->prompt_id    = $request->input('prompt_id');
        $job->error        = null;
        $job->appendLog('Job concluído com sucesso', [
            'outputs' => count($outputFiles),
            'uploaded_files' => count($allUploads),
        ]);
        $job->save();

        return response()->json(['message' => 'Job marcado como concluído']);
    }

    /**
     * Cria um novo job via assistente (endpoint público de API).
     * Query params: prompt (obrigatório), negative_prompt (opcional), workflow (obrigatório, JSON).
     */
    public function assistantCreate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt'          => 'required|string',
            'negative_prompt' => 'nullable|string',
            'workflow'        => 'required|string',
        ]);

        $workflowJson = json_decode($validated['workflow'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['message' => 'O parâmetro workflow deve ser um JSON válido.'], 422);
        }

        $workflowJson = $this->applyPromptsToWorkflow(
            $workflowJson,
            $validated['prompt'],
            $validated['negative_prompt'] ?? ''
        );

        $job = Job::create([
            'type'            => 'prompt',
            'params'          => $workflowJson,
            'required_models' => $this->extractModelsFromWorkflow($workflowJson),
            'status'          => 'pending',
        ]);

        return response()->json([
            'message' => 'Job criado com sucesso.',
            'job_id'  => $job->id,
            'status'  => $job->status,
        ], 201);
    }

    private function applyPromptsToWorkflow(array $workflow, string $positivePrompt, string $negativePrompt): array
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
                        'url'  => '',
                    ];
                }
            }
        }

        return empty($models) ? null : $models;
    }

    /**
     * Marca o job como falhou.
     * Body: { "error": "mensagem de erro" }
     */
    public function fail(Request $request, int $id): JsonResponse
    {
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
