<?php

namespace App\Modules\Cardapio\Models;

use App\Modules\Cardapio\Support\TipoRefeicao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Casa extends Model
{
    protected $table = 'cardapio_casas';

    protected $fillable = ['nome', 'refeicoes_ativas', 'lancheira_itens'];

    protected function casts(): array
    {
        return ['refeicoes_ativas' => 'array'];
    }

    public function pessoas(): HasMany
    {
        return $this->hasMany(Pessoa::class, 'casa_id');
    }

    public function ingredientes(): HasMany
    {
        return $this->hasMany(Ingrediente::class, 'casa_id');
    }

    public function receitas(): HasMany
    {
        return $this->hasMany(Receita::class, 'casa_id');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class, 'casa_id');
    }

    public function ausencias(): HasMany
    {
        return $this->hasMany(Ausencia::class, 'casa_id');
    }

    public function produtosRecorrentes(): HasMany
    {
        return $this->hasMany(ProdutoRecorrente::class, 'casa_id');
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(HistoricoRefeicao::class, 'casa_id');
    }

    public function sobras(): HasMany
    {
        return $this->hasMany(Sobra::class, 'casa_id');
    }

    public function planos(): HasMany
    {
        return $this->hasMany(PlanoSemanal::class, 'casa_id');
    }

    public function listasCompras(): HasMany
    {
        return $this->hasMany(ListaCompras::class, 'casa_id');
    }

    public function itensEstoque(): HasMany
    {
        return $this->hasMany(ItemEstoque::class, 'casa_id');
    }

    public function regrasRotina(): HasMany
    {
        return $this->hasMany(RegraRotina::class, 'casa_id');
    }

    /**
     * Tipos de refeição ativos, na ordem canônica.
     *
     * @return array<int, string>
     */
    public function refeicoes(): array
    {
        $ativas = $this->refeicoes_ativas ?? TipoRefeicao::padrao();

        return array_values(array_filter(
            array_keys(TipoRefeicao::rotulos()),
            fn (string $tipo): bool => in_array($tipo, $ativas, true)
        ));
    }
}
