<?php

namespace App\Modules\Cardapio\Services\Assistente;

use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\Pessoa;
use App\Modules\Cardapio\Models\Receita;
use App\Modules\Cardapio\Services\AgendaService;
use App\Modules\Cardapio\Services\ComprasService;
use App\Modules\Cardapio\Services\EstoqueService;
use App\Modules\Cardapio\Services\PlanejadorService;
use App\Modules\Cardapio\Support\UnidadeConversor;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Aplica uma ação JÁ validada e confirmada, sempre pelos serviços determinísticos do módulo.
 * Reconfere o essencial (a casa, as permissões, o que ainda existe) porque entre a proposta e a
 * confirmação o estado pode ter mudado.
 */
class ExecutorAcoes
{
    public function __construct(
        private readonly EstoqueService $estoque,
        private readonly AgendaService $agenda,
        private readonly PlanejadorService $planejador,
        private readonly ComprasService $compras,
    ) {}

    /**
     * @param  array<string, mixed>  $acao  validada
     * @return array{texto: string, plano_id?: int, receita_id?: int}
     *
     * @throws RuntimeException com mensagem segura para o usuário
     */
    public function aplicar(Casa $casa, Pessoa $falante, array $acao): array
    {
        $d = $acao['dados'];

        return match ($acao['tipo']) {
            'adicionar_estoque' => $this->adicionarEstoque($casa, $d),
            'baixar_estoque' => $this->baixarEstoque($casa, $d),
            'zerar_estoque' => $this->zerarEstoque($casa, $d),
            'adicionar_restricao' => $this->adicionarRestricao($casa, $falante, $d),
            'adicionar_preferencia' => $this->adicionarPreferencia($casa, $falante, $d),
            'criar_evento' => $this->criarEvento($casa, $d),
            'criar_ausencia' => $this->criarAusencia($casa, $d),
            'adicionar_compra' => $this->adicionarCompra($casa, $d),
            'gerar_cardapio' => $this->gerarCardapio($casa, $d),
            'criar_receita' => $this->criarReceita($casa, $d),
            'alterar_receita' => $this->alterarReceita($casa, $d),
            default => throw new RuntimeException('Ação desconhecida.'),
        };
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{texto: string}
     */
    private function adicionarEstoque(Casa $casa, array $d): array
    {
        $ingrediente = $d['ingrediente_id'] ? $casa->ingredientes()->find($d['ingrediente_id']) : null;

        if ($ingrediente === null) {
            if (! $d['criar']) {
                throw new RuntimeException('o ingrediente não existe mais.');
            }

            $ingrediente = $casa->ingredientes()->firstOrCreate(
                ['nome' => $d['nome']],
                ['unidade_base' => UnidadeConversor::baseDe($d['unidade']), 'sobra' => false]
            );
        }

        if (! UnidadeConversor::compativel($d['unidade'], $ingrediente->unidade_base)) {
            throw new RuntimeException("a unidade não combina com {$ingrediente->nome}.");
        }

        $this->estoque->adicionar($casa, $ingrediente, $d['quantidade'], $d['unidade'], ['local' => $d['local'], 'validade' => $d['validade']]);

        return ['texto' => 'Estoque de '.$ingrediente->nome.' agora: '.UnidadeConversor::formatar($this->estoque->total($casa, $ingrediente), $ingrediente->unidade_base).'.'];
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{texto: string}
     */
    private function baixarEstoque(Casa $casa, array $d): array
    {
        $ingrediente = $casa->ingredientes()->find($d['ingrediente_id']) ?? throw new RuntimeException('o ingrediente não existe mais.');
        $consumido = $this->estoque->baixar($casa, $ingrediente, $d['quantidade_base'], limitar: true);
        $pedido = UnidadeConversor::formatar($d['quantidade_base'], $ingrediente->unidade_base);

        return ['texto' => $consumido + 0.0005 < $d['quantidade_base']
            ? 'Só havia '.UnidadeConversor::formatar($consumido, $ingrediente->unidade_base)." de {$ingrediente->nome} (você pediu {$pedido}); o estoque zerou."
            : "Baixa de {$pedido} de {$ingrediente->nome}. Restam ".UnidadeConversor::formatar($this->estoque->total($casa, $ingrediente), $ingrediente->unidade_base).'.'];
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{texto: string}
     */
    private function zerarEstoque(Casa $casa, array $d): array
    {
        $ingrediente = $casa->ingredientes()->find($d['ingrediente_id']) ?? throw new RuntimeException('o ingrediente não existe mais.');
        $removido = $this->estoque->zerar($casa, $ingrediente);

        return ['texto' => "Estoque de {$ingrediente->nome} zerado (".UnidadeConversor::formatar($removido, $ingrediente->unidade_base).' removidos).'];
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{texto: string}
     */
    private function adicionarRestricao(Casa $casa, Pessoa $falante, array $d): array
    {
        $pessoa = $this->pessoaEditavel($casa, $falante, $d['pessoa_id']);

        if ($d['ingrediente_id'] && ! $casa->ingredientes()->whereKey($d['ingrediente_id'])->exists()) {
            throw new RuntimeException('o ingrediente não existe mais.');
        }

        $pessoa->restricoes()->firstOrCreate(
            ['ingrediente_id' => $d['ingrediente_id'], 'tipo' => $d['tipo']],
            ['descricao' => $d['descricao']]
        );

        return ['texto' => "Restrição registrada para {$pessoa->nome}. O planejador já a considera nas próximas sugestões."];
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{texto: string}
     */
    private function adicionarPreferencia(Casa $casa, Pessoa $falante, array $d): array
    {
        $pessoa = $this->pessoaEditavel($casa, $falante, $d['pessoa_id']);
        $ingrediente = $casa->ingredientes()->find($d['ingrediente_id']) ?? throw new RuntimeException('o ingrediente não existe mais.');

        $pessoa->preferencias()->updateOrCreate(['ingrediente_id' => $ingrediente->id], ['nivel' => $d['nivel']]);

        return ['texto' => "Preferência de {$pessoa->nome} por {$ingrediente->nome} atualizada."];
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{texto: string}
     */
    private function criarEvento(Casa $casa, array $d): array
    {
        $evento = $this->agenda->criarEvento($casa, $d);

        return ['texto' => "Evento “{$evento->titulo}” criado; as semanas planejadas e a lista de compras foram ajustadas."];
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{texto: string}
     */
    private function criarAusencia(Casa $casa, array $d): array
    {
        $pessoa = $casa->pessoas()->find($d['pessoa_id']) ?? throw new RuntimeException('a pessoa não existe mais.');
        $this->agenda->criarAusencia($casa, $pessoa, $d);

        return ['texto' => "{$pessoa->nome} fora do cálculo das refeições no período."];
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{texto: string}
     */
    private function adicionarCompra(Casa $casa, array $d): array
    {
        $lista = $casa->listasCompras()->where('status', 'aberta')->latest('id')->first()
            ?? $casa->listasCompras()->create(['titulo' => 'Compras avulsas']);

        if ($d['ingrediente_id'] && $d['quantidade_base'] !== null && $casa->ingredientes()->whereKey($d['ingrediente_id'])->exists()) {
            $lista->itens()->create(['ingrediente_id' => $d['ingrediente_id'], 'quantidade_comprar' => $d['quantidade_base'], 'manual' => true]);
        } else {
            $lista->itens()->create(['descricao' => $d['descricao'], 'manual' => true]);
        }

        return ['texto' => "“{$d['descricao']}” entrou na lista “".($lista->titulo ?? 'Lista #'.$lista->id).'”.'];
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{texto: string, plano_id: int}
     */
    private function gerarCardapio(Casa $casa, array $d): array
    {
        $plano = $this->planejador->criarSemana($casa, Carbon::parse($d['semana']));
        $r = $this->planejador->gerar($plano);

        if ($plano->listaCompras()->where('status', 'aberta')->exists()) {
            $this->compras->gerarParaPlano($plano);
        }

        $texto = "Cardápio da semana de {$plano->inicio_semana->format('d/m')}: {$r['preenchidas']} refeição(ões) planejada(s)";

        return ['texto' => $texto.($r['sem_opcao'] > 0 ? "; {$r['sem_opcao']} sem receita compatível." : '.'), 'plano_id' => $plano->id];
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{texto: string, receita_id: int}
     */
    private function criarReceita(Casa $casa, array $d): array
    {
        if ($casa->receitas()->whereRaw('lower(nome) = ?', [mb_strtolower($d['nome'])])->exists()) {
            throw new RuntimeException('já existe uma receita com esse nome.');
        }

        $receita = $casa->receitas()->create([
            'nome' => $d['nome'], 'descricao' => $d['descricao'], 'rendimento' => $d['porcoes'], 'tempo_preparo' => $d['tempo_preparo'],
            'dificuldade' => $d['dificuldade'], 'refeicoes_compativeis' => $d['refeicoes'], 'modo_preparo' => $d['modo_preparo'],
        ]);

        $criados = $this->gravarIngredientes($casa, $receita, $d);

        return ['texto' => "Receita “{$receita->nome}” criada".($criados > 0 ? " ({$criados} ingrediente(s) novo(s) cadastrado(s))" : '').'. Você pode ajustar os detalhes em Receitas.', 'receita_id' => $receita->id];
    }

    /**
     * Grava as linhas de ingredientes (já validadas), criando os ingredientes novos.
     *
     * @param  array<string, mixed>  $d
     */
    private function gravarIngredientes(Casa $casa, Receita $receita, array $d): int
    {
        $bases = [];
        foreach ($d['novos'] as $novo) {
            $bases[Nomes::chave($novo['nome'])] = $novo;
        }

        $criados = 0;
        foreach ($d['ingredientes'] as $linha) {
            $ingrediente = $linha['ingrediente_id'] ? $casa->ingredientes()->find($linha['ingrediente_id']) : null;

            if ($ingrediente === null) {
                $novo = $bases[Nomes::chave($linha['nome'])] ?? null;

                if ($novo === null) {
                    throw new RuntimeException("o ingrediente {$linha['nome']} não existe mais.");
                }

                $existia = $casa->ingredientes()->where('nome', $novo['nome'])->exists();
                $ingrediente = $casa->ingredientes()->firstOrCreate(['nome' => $novo['nome']], ['unidade_base' => $novo['base'], 'sobra' => false]);
                $criados += $existia ? 0 : 1;
            }

            $receita->ingredientes()->create(['ingrediente_id' => $ingrediente->id, 'quantidade' => $linha['quantidade_base'], 'opcional' => $linha['opcional']]);
        }

        return $criados;
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{texto: string, receita_id: int}
     */
    private function alterarReceita(Casa $casa, array $d): array
    {
        $receita = $casa->receitas()->find($d['receita_id']) ?? throw new RuntimeException('a receita não existe mais.');

        if (isset($d['nome']) && $casa->receitas()->whereRaw('lower(nome) = ?', [mb_strtolower($d['nome'])])->where('id', '!=', $receita->id)->exists()) {
            throw new RuntimeException('já existe outra receita com esse nome.');
        }

        $campos = ['nome' => 'nome', 'descricao' => 'descricao', 'porcoes' => 'rendimento', 'tempo_preparo' => 'tempo_preparo',
            'dificuldade' => 'dificuldade', 'refeicoes' => 'refeicoes_compativeis', 'modo_preparo' => 'modo_preparo', 'favorita' => 'favorita'];

        foreach ($campos as $chave => $coluna) {
            if (array_key_exists($chave, $d)) {
                $receita->{$coluna} = $d[$chave];
            }
        }
        $receita->save();

        $criados = 0;
        if (array_key_exists('ingredientes', $d)) {
            $receita->ingredientes()->delete();
            $criados = $this->gravarIngredientes($casa, $receita, $d);
        }

        return ['texto' => "Receita “{$receita->nome}” atualizada".($criados > 0 ? " ({$criados} ingrediente(s) novo(s) cadastrado(s))" : '').'.', 'receita_id' => $receita->id];
    }

    private function pessoaEditavel(Casa $casa, Pessoa $falante, int $pessoaId): Pessoa
    {
        $pessoa = $casa->pessoas()->find($pessoaId) ?? throw new RuntimeException('a pessoa não existe mais.');

        if ($falante->tipo !== 'adulto' && $pessoa->id !== $falante->id) {
            throw new RuntimeException('você só pode alterar o seu próprio perfil.');
        }

        return $pessoa;
    }
}
