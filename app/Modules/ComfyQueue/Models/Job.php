<?php

namespace App\Modules\ComfyQueue\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $table = 'comfy_queue_jobs';

    protected $fillable = [
        'type',
        'params',
        'required_models',
        'input_files',
        'status',
        'prompt_id',
        'result_url',
        'output_files',
        'execution_log',
        'last_heartbeat',
        'error',
        'retry_count',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'params'          => 'array',
        'required_models' => 'array',
        'input_files'     => 'array',
        'output_files'    => 'array',
        'last_heartbeat'  => 'datetime',
        'started_at'      => 'datetime',
        'finished_at'     => 'datetime',
    ];

    protected function executionLogEntries(): Attribute
    {
        return Attribute::get(fn () => $this->decodeExecutionLog());
    }

    public function appendLog(string $message, array $context = []): void
    {
        $entries = $this->decodeExecutionLog();
        $entries[] = array_filter([
            'timestamp' => now()->toIso8601String(),
            'message' => $message,
            'context' => empty($context) ? null : $context,
        ], fn ($value) => $value !== null);

        $this->execution_log = json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function latestLogEntry(): ?array
    {
        $entries = $this->decodeExecutionLog();

        if ($entries === []) {
            return null;
        }

        return $entries[array_key_last($entries)];
    }

    private function decodeExecutionLog(): array
    {
        if (! is_string($this->execution_log) || trim($this->execution_log) === '') {
            return [];
        }

        $decoded = json_decode($this->execution_log, true);

        return is_array($decoded) ? $decoded : [];
    }
}
