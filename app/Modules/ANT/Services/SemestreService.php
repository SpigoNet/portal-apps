<?php

namespace App\Modules\ANT\Services;

use App\Models\User;
use App\Modules\ANT\Models\AntConfiguracao;
use Illuminate\Support\Facades\DB;

class SemestreService
{
    /**
     * Retorna a configuração canônica.
     * Prioriza a linha que contém a lista de admins (evitando perder acesso)
     * e garante determinismo no PostgreSQL (first() sem ORDER BY é arbitrário).
     */
    private static function config(): ?AntConfiguracao
    {
        return AntConfiguracao::whereNotNull('admins')
            ->where('admins', '<>', '')
            ->orderBy('id')
            ->first()
            ?? AntConfiguracao::orderBy('id')->first();
    }

    /**
     * Retorna o semestre corrente configurado no banco.
     */
    public static function getCurrent(): string
    {
        $config = self::config();

        return $config->semestre_atual ?? date('Y').'/'.(date('m') > 6 ? '2' : '1');
    }

    /**
     * Normaliza o formato do semestre para o padrão correto (ano/semestre).
     * Aceita hífen ou ponto como separador (ex: 2026-1, 2026.1) e converte para 2026/1.
     */
    public static function normalize(string $semestre): string
    {
        return preg_replace('/[-\.]/', '/', trim($semestre));
    }

    /**
     * Retorna o semestre para o usuário logado.
     * - Admin: sempre o semestre corrente do banco.
     * - Professor: usa session se disponível, senão o corrente.
     * - Aluno: sempre o semestre corrente do banco.
     */
    public static function getForUser(?User $user = null): string
    {
        $user = $user ?? auth()->user();

        if ($user && $user->isProfessor()) {
            return session('ant_semestre_professor', self::getCurrent());
        }

        return self::getCurrent();
    }

    /**
     * Atualiza o semestre corrente no banco.
     */
    public static function setCurrent(string $semestre): void
    {
        $config = self::config();

        if ($config) {
            // Atualiza todas as linhas para manter consistência (evita linhas duplicadas divergentes)
            AntConfiguracao::query()->update(['semestre_atual' => $semestre]);
        } else {
            $config = AntConfiguracao::create(['semestre_atual' => $semestre]);
        }
    }

    /**
     * Retorna lista de semestres disponíveis (distinct dos dados existentes).
     */
    public static function getAvailable(): array
    {
        $tabelas = [
            'ant_trabalhos',
            'ant_pesos',
            'ant_aluno_materia',
            'ant_professor_materia',
            'ant_materiais',
        ];

        $semestres = collect();

        foreach ($tabelas as $tabela) {
            $semestres = $semestres->merge(
                DB::table($tabela)->distinct()->pluck('semestre')->filter()
            );
        }

        $current = self::getCurrent();
        $semestres = $semestres->push($current)->unique()->sort()->values()->all();

        return $semestres;
    }

    /**
     * Retorna a configuração (AntConfiguracao).
     */
    public static function getConfig(): ?AntConfiguracao
    {
        return self::config();
    }
}
