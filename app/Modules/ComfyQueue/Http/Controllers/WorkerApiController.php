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

        $outputFiles = $this->normalizeOutputFiles($request);
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
