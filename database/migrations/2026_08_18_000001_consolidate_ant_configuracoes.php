<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consolida eventuais linhas duplicadas em ant_configuracoes em um único registro.
     * A linha canônica é a que possui a lista de admins (para não perder acesso),
     * ou a de menor id. Campos não vazios das linhas removidas são mesclados.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ant_configuracoes')) {
            return;
        }

        $rows = DB::table('ant_configuracoes')->orderBy('id')->get();

        if ($rows->count() <= 1) {
            return;
        }

        $canonical = $rows->first(fn ($r) => ! empty($r->admins)) ?? $rows->first();
        $others = $rows->reject(fn ($r) => $r->id === $canonical->id);

        $fields = ['semestre_atual', 'admins', 'prompt_agente', 'ia_driver', 'ia_url', 'ia_key'];
        $data = (array) $canonical;

        foreach ($others as $other) {
            foreach ($fields as $field) {
                if (empty($data[$field]) && ! empty($other->{$field})) {
                    $data[$field] = $other->{$field};
                }
            }
        }

        DB::table('ant_configuracoes')->where('id', $canonical->id)->update([
            'semestre_atual' => $data['semestre_atual'] ?? null,
            'admins' => $data['admins'] ?? null,
            'prompt_agente' => $data['prompt_agente'] ?? null,
            'ia_driver' => $data['ia_driver'] ?? null,
            'ia_url' => $data['ia_url'] ?? null,
            'ia_key' => $data['ia_key'] ?? null,
            'updated_at' => now(),
        ]);

        DB::table('ant_configuracoes')->whereIn('id', $others->pluck('id')->all())->delete();
    }

    public function down(): void
    {
        // Migração de consolidação de dados: não é possível reconstruir as linhas removidas.
    }
};
