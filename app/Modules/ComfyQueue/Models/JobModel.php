<?php

namespace App\Modules\ComfyQueue\Models;

use Illuminate\Database\Eloquent\Model;

class JobModel extends Model
{
    protected $table = 'comfy_queue_job_models';

    protected $fillable = [
        'nome',
        'json',
    ];

    protected $casts = [
        'json' => 'array',
    ];

    public function getJsonDecodedAttribute(): array
    {
        return is_array($this->json) ? $this->json : json_decode($this->json, true) ?? [];
    }

    public function getVariaveisAttribute(): array
    {
        $json = $this->json_decoded;
        $variaveis = [];

        $this->extractVariaveis($json, '', $variaveis);

        return $variaveis;
    }

    private function extractVariaveis($data, string $prefix, array &$variaveis): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $newPrefix = $prefix ? "{$prefix}.{$key}" : $key;
                $this->extractVariaveis($value, $newPrefix, $variaveis);
            }
        } elseif (is_string($data) && preg_match('/^__(.+)__$/', $data, $matches)) {
            $variaveis[] = $matches[1];
        }
    }

    public function processarJsonComValores(array $valores): array
    {
        $json = $this->json_decoded;

        return $this->replaceVariaveis($json, $valores);
    }

    private function replaceVariaveis($data, array $valores)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->replaceVariaveis($value, $valores);
            }
            return $data;
        } elseif (is_string($data)) {
            foreach ($valores as $varName => $varValue) {
                $data = str_replace("__{$varName}__", (string) $varValue, $data);
            }
            return $data;
        }

        return $data;
    }
}