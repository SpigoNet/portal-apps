<?php

namespace App\Modules\Cardapio\Services\Mcp;

use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\ItemCompra;
use App\Modules\Cardapio\Models\ItemEstoque;
use App\Modules\Cardapio\Models\Pessoa;
use App\Modules\Cardapio\Models\Receita;
use App\Modules\Cardapio\Models\RefeicaoPlanejada;
use App\Modules\Cardapio\Services\Assistente\ConsultasAssistente;
use App\Modules\Cardapio\Services\Assistente\ExecutorAcoes;
use App\Modules\Cardapio\Services\Assistente\ValidadorAcoes;
use App\Modules\Cardapio\Services\EstoqueService;
use App\Modules\Cardapio\Services\PlanejadorService;
use App\Modules\Cardapio\Support\TipoRefeicao;
use App\Modules\Cardapio\Support\UnidadeConversor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Ferramentas MCP do Cardápio. Leituras consultam os models/serviços; escritas passam pelo MESMO
 * validador e executor do assistente (nomes casados com o cadastro da casa, unidades, permissões),
 * então um agente externo não consegue nada que a tela não permita.
 */
class FerramentasMcp
{
    /** Ferramentas que alteram dados → ação do assistente correspondente. */
    private const ESCRITAS = [
        'adicionar_estoque', 'baixar_estoque', 'zerar_estoque', 'adicionar_restricao', 'adicionar_preferencia',
        'criar_evento', 'criar_ausencia', 'adicionar_compra', 'gerar_cardapio', 'criar_receita', 'alterar_receita',
    ];

    public function __construct(
        private readonly EstoqueService $estoque,
        private readonly ValidadorAcoes $validador,
        private readonly ExecutorAcoes $executor,
        private readonly ConsultasAssistente $consultas,
    ) {}

    public function existe(string $nome): bool
    {
        return in_array($nome, array_column($this->definicoes(), 'name'), true);
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function executar(Casa $casa, Pessoa $pessoa, string $nome, array $args): array
    {
        if (in_array($nome, self::ESCRITAS, true)) {
            return $this->escrever($casa, $pessoa, $nome, $args);
        }

        return match ($nome) {
            'listar_pessoas' => $this->listarPessoas($casa),
            'consultar_estoque' => $this->consultarEstoque($casa, $this->texto($args, 'busca')),
            'estoque_vencendo' => $this->estoqueVencendo($casa),
            'estoque_baixo' => $this->estoqueBaixo($casa),
            'ver_cardapio' => $this->verCardapio($casa, $this->texto($args, 'data')),
            'ver_compras' => $this->verCompras($casa),
            'listar_receitas' => $this->listarReceitas($casa, $this->texto($args, 'busca')),
            'detalhar_receita' => $this->detalharReceita($casa, $this->texto($args, 'nome')),
            'sugerir_receitas' => $this->sugerirReceitas($casa, $pessoa, $args),
            default => throw new InvalidArgumentException('Ferramenta desconhecida.'),
        };
    }

    // ---- escritas ----

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function escrever(Casa $casa, Pessoa $pessoa, string $tipo, array $args): array
    {
        $r = $this->validador->validar($casa, $pessoa, [['tipo' => $tipo] + $args]);

        if ($r['pendentes'] === []) {
            throw new InvalidArgumentException($r['rejeitadas'][0] ?? 'Ação não permitida.');
        }

        $acao = $r['pendentes'][0];
        $aplicado = DB::transaction(fn () => $this->executor->aplicar($casa, $pessoa, $acao));

        return ['ok' => true, 'resumo' => $acao['resumo'], 'avisos' => $acao['avisos']] + $aplicado;
    }

    // ---- leituras ----

    /**
     * @return array<string, mixed>
     */
    private function listarPessoas(Casa $casa): array
    {
        return ['pessoas' => $casa->pessoas()->where('ativo', true)->orderBy('nome')->get()
            ->map(fn (Pessoa $p) => ['id' => $p->id, 'nome' => $p->nome, 'tipo' => $p->tipo, 'tem_login' => $p->user_id !== null])->all()];
    }

    /**
     * @return array<string, mixed>
     */
    private function consultarEstoque(Casa $casa, ?string $busca): array
    {
        $consulta = $casa->itensEstoque()->with('ingrediente')->orderBy('validade');

        if ($busca !== null) {
            $consulta->whereHas('ingrediente', fn ($q) => $q->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower($busca).'%']));
        }

        $itens = $consulta->get()->groupBy('ingrediente_id')->map(function ($lotes): array {
            $ing = $lotes->first()->ingrediente;

            return [
                'ingrediente' => $ing->nome,
                'total' => UnidadeConversor::formatar((float) $lotes->sum('quantidade'), $ing->unidade_base),
                'lotes' => $lotes->map(fn (ItemEstoque $l) => $this->lote($l))->all(),
            ];
        })->sortBy('ingrediente')->values();

        return ['total_ingredientes' => $itens->count(), 'itens' => $itens->take(200)->all()];
    }

    /**
     * @return array<string, mixed>
     */
    private function estoqueVencendo(Casa $casa): array
    {
        return ['itens' => $this->estoque->vencendo($casa)->take(100)
            ->map(fn (ItemEstoque $l) => ['ingrediente' => $l->ingrediente->nome] + $this->lote($l))->all()];
    }

    /**
     * @return array<string, mixed>
     */
    private function estoqueBaixo(Casa $casa): array
    {
        return ['itens' => $this->estoque->estoqueBaixo($casa)->take(100)->map(fn (array $l) => [
            'ingrediente' => $l['ingrediente']->nome,
            'quantidade' => UnidadeConversor::formatar($l['total'], $l['ingrediente']->unidade_base),
            'minimo' => UnidadeConversor::formatar($l['minimo'], $l['ingrediente']->unidade_base),
        ])->all()];
    }

    /**
     * @return array<string, mixed>
     */
    private function lote(ItemEstoque $l): array
    {
        return [
            'quantidade' => UnidadeConversor::formatar($l->quantidade, $l->ingrediente->unidade_base),
            'local' => ItemEstoque::LOCAIS[$l->local] ?? $l->local,
            'validade' => $l->validade?->toDateString(),
            'dias_para_vencer' => $l->diasParaVencer(),
            'situacao' => $l->statusValidade(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function verCardapio(Casa $casa, ?string $data): array
    {
        $dia = $this->data($data) ?? today();
        $plano = $casa->planos()->whereDate('inicio_semana', PlanejadorService::segundaDaSemana($dia))->first();

        if ($plano === null) {
            return ['semana' => PlanejadorService::segundaDaSemana($dia)->toDateString(), 'planejada' => false, 'refeicoes' => [], 'dica' => 'Use gerar_cardapio para montar esta semana.'];
        }

        $rotulos = TipoRefeicao::rotulos();
        $ordem = array_keys($rotulos);

        $refeicoes = $plano->refeicoes()->with(['receita', 'pessoas', 'itens.receita'])->orderBy('data')->get()
            ->sortBy(fn (RefeicaoPlanejada $r) => $r->data->toDateString().'-'.str_pad((string) array_search($r->tipo_refeicao, $ordem), 2, '0', STR_PAD_LEFT))
            ->map(fn (RefeicaoPlanejada $r) => [
                'data' => $r->data->toDateString(),
                'dia_semana' => $r->data->locale('pt_BR')->dayName,
                'refeicao' => $rotulos[$r->tipo_refeicao] ?? $r->tipo_refeicao,
                'receita' => $r->receita?->nome,
                'extras' => $r->itens->map(fn ($i) => $i->receita?->nome)->filter()->values()->all(),
                'pessoas' => $r->pessoas->pluck('nome')->all(),
                'consumida' => $r->consumida(),
                'justificativa' => $r->justificativa,
            ])->values()->all();

        return ['semana' => $plano->inicio_semana->toDateString(), 'planejada' => true, 'refeicoes' => $refeicoes];
    }

    /**
     * @return array<string, mixed>
     */
    private function verCompras(Casa $casa): array
    {
        $listas = $casa->listasCompras()->where('status', 'aberta')->with(['itens.ingrediente'])->get();

        return ['listas' => $listas->map(fn ($l) => [
            'id' => $l->id,
            'titulo' => $l->titulo,
            'itens' => $l->itens->where('comprado', false)->map(fn (ItemCompra $i) => [
                'item' => $i->nome(),
                'comprar' => $i->ingrediente ? UnidadeConversor::formatar($i->quantidade_comprar, $i->ingrediente->unidade_base) : null,
                'manual' => (bool) $i->manual,
            ])->values()->all(),
        ])->all()];
    }

    /**
     * @return array<string, mixed>
     */
    private function listarReceitas(Casa $casa, ?string $busca): array
    {
        $consulta = $casa->receitas()->orderBy('nome');

        if ($busca !== null) {
            $consulta->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower($busca).'%']);
        }

        return ['receitas' => $consulta->limit(100)->get()->map(fn (Receita $r) => [
            'nome' => $r->nome,
            'porcoes' => $r->rendimento,
            'tempo_preparo_min' => $r->tempo_preparo,
            'dificuldade' => $r->dificuldade,
            'refeicoes' => $r->refeicoes_compativeis,
            'favorita' => (bool) $r->favorita,
        ])->all()];
    }

    /**
     * @return array<string, mixed>
     */
    private function detalharReceita(Casa $casa, ?string $nome): array
    {
        if ($nome === null) {
            throw new InvalidArgumentException('Informe o nome da receita.');
        }

        $receitas = $casa->receitas()->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower($nome).'%'])->with('ingredientes.ingrediente')->limit(5)->get();
        $receita = $receitas->first(fn (Receita $r) => mb_strtolower($r->nome) === mb_strtolower($nome)) ?? $receitas->first();

        if ($receita === null) {
            throw new RuntimeException("Receita “{$nome}” não encontrada.");
        }

        return [
            'id' => $receita->id,
            'nome' => $receita->nome,
            'descricao' => $receita->descricao,
            'favorita' => (bool) $receita->favorita,
            'porcoes' => $receita->rendimento,
            'tempo_preparo_min' => $receita->tempo_preparo,
            'dificuldade' => $receita->dificuldade,
            'refeicoes' => $receita->refeicoes_compativeis,
            'ingredientes' => $receita->ingredientes->map(fn ($i) => [
                'ingrediente' => $i->ingrediente?->nome,
                'quantidade' => $i->ingrediente ? UnidadeConversor::formatar((float) $i->quantidade, $i->ingrediente->unidade_base) : $i->quantidade,
                'opcional' => (bool) $i->opcional,
            ])->all(),
            'modo_preparo' => $receita->modo_preparo,
            'outras_correspondencias' => $receitas->where('id', '!=', $receita->id)->pluck('nome')->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function sugerirReceitas(Casa $casa, Pessoa $pessoa, array $args): array
    {
        $r = $this->validador->validar($casa, $pessoa, [['tipo' => 'sugerir_receitas'] + $args]);

        if ($r['consultas'] === []) {
            throw new InvalidArgumentException($r['rejeitadas'][0] ?? 'Pedido inválido.');
        }

        return ['sugestoes' => $this->consultas->responder($casa, $r['consultas'][0])];
    }

    // ---- utilidades ----

    /**
     * @param  array<string, mixed>  $args
     */
    private function texto(array $args, string $chave): ?string
    {
        $v = $args[$chave] ?? null;

        return is_string($v) && trim($v) !== '' ? trim($v) : null;
    }

    private function data(?string $valor): ?Carbon
    {
        if ($valor === null) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $valor) ?: throw new InvalidArgumentException;
        } catch (\Throwable) {
            throw new InvalidArgumentException('Data inválida: use o formato AAAA-MM-DD.');
        }
    }

    // ---- definições (tools/list) ----

    /**
     * @return array<int, array{name: string, description: string, inputSchema: array<string, mixed>, annotations: array<string, bool>}>
     */
    public function definicoes(): array
    {
        $refeicoes = array_keys(TipoRefeicao::rotulos());
        $unidades = array_keys(UnidadeConversor::rotulos());
        $str = fn (string $d): array => ['type' => 'string', 'description' => $d];
        $num = fn (string $d): array => ['type' => 'number', 'description' => $d];
        $leitura = fn (string $nome, string $desc, array $props = [], array $obrigatorios = []): array => $this->def($nome, $desc, $props, $obrigatorios, true);
        $escrita = fn (string $nome, string $desc, array $props = [], array $obrigatorios = []): array => $this->def($nome, $desc, $props, $obrigatorios, false);

        return [
            $leitura('listar_pessoas', 'Lista as pessoas da casa (nome, adulto/criança).'),
            $leitura('consultar_estoque', 'Estoque por ingrediente, com lotes, local e validade. Sem "busca" lista tudo.', ['busca' => $str('Parte do nome do alimento (ex.: "frango")')]),
            $leitura('estoque_vencendo', 'Lotes vencidos ou prestes a vencer, os mais urgentes primeiro.'),
            $leitura('estoque_baixo', 'Ingredientes abaixo da quantidade mínima configurada.'),
            $leitura('ver_cardapio', 'Cardápio da semana (refeições, receitas, quem come). Padrão: semana atual.', ['data' => $str('Qualquer dia da semana desejada, AAAA-MM-DD')]),
            $leitura('ver_compras', 'Itens ainda por comprar nas listas de compras abertas.'),
            $leitura('listar_receitas', 'Lista as receitas cadastradas.', ['busca' => $str('Parte do nome')]),
            $leitura('detalhar_receita', 'Detalhes completos de uma receita: ingredientes (com quantidades), modo de preparo, porções, tempo, dificuldade.', ['nome' => $str('Nome (ou parte) da receita')], ['nome']),
            $leitura('sugerir_receitas', 'Ranqueia receitas para uma refeição com o motor do planejador (estoque, validade, preferências, restrições).', [
                'refeicao' => ['type' => 'string', 'enum' => $refeicoes, 'description' => 'Tipo de refeição'],
                'data' => $str('AAAA-MM-DD (padrão: hoje)'),
                'ingredientes' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Ingredientes extras que se pode considerar como disponíveis'],
            ], ['refeicao']),

            $escrita('adicionar_estoque', 'Adiciona um alimento ao estoque (compra/doação). Ingrediente novo é cadastrado automaticamente.', [
                'ingrediente' => $str('Nome do alimento'), 'quantidade' => $num('Quantidade'),
                'unidade' => ['type' => 'string', 'enum' => $unidades, 'description' => 'Unidade'],
                'local' => ['type' => 'string', 'enum' => array_keys(ItemEstoque::LOCAIS), 'description' => 'Padrão: despensa'],
                'validade_dias' => ['type' => 'integer', 'description' => 'Dias até vencer'],
            ], ['ingrediente', 'quantidade', 'unidade']),
            $escrita('baixar_estoque', 'Registra o uso de uma quantidade do estoque (nunca fica negativo).', [
                'ingrediente' => $str('Nome do alimento'), 'quantidade' => $num('Quantidade usada'),
                'unidade' => ['type' => 'string', 'enum' => $unidades, 'description' => 'Unidade'],
            ], ['ingrediente', 'quantidade', 'unidade']),
            $escrita('zerar_estoque', 'Zera o estoque de um alimento ("acabou o arroz").', ['ingrediente' => $str('Nome do alimento')], ['ingrediente']),
            $escrita('adicionar_restricao', 'Registra restrição alimentar de uma pessoa.', [
                'pessoa' => $str('Nome da pessoa'), 'ingrediente' => $str('Alimento restrito'),
                'restricao' => ['type' => 'string', 'enum' => ['alergia', 'intolerancia', 'preferencia', 'objetivo'], 'description' => 'Alergia nunca é sugerida; intolerância tenta substituir'],
                'descricao' => $str('Só para restricao=objetivo, sem ingrediente'),
            ], ['pessoa', 'restricao']),
            $escrita('adicionar_preferencia', 'Registra o quanto uma pessoa gosta de um alimento.', [
                'pessoa' => $str('Nome da pessoa'), 'ingrediente' => $str('Alimento'),
                'nivel' => ['type' => 'integer', 'enum' => [5, 3, 0, -5, -10], 'description' => '5 gosta muito, 3 gosta, 0 neutro, -5 não gosta, -10 odeia'],
            ], ['pessoa', 'ingrediente', 'nivel']),
            $escrita('criar_evento', 'Cria evento na agenda: refeição fora de casa ou convidados.', [
                'evento' => ['type' => 'string', 'enum' => ['fora', 'convidados']], 'data' => $str('AAAA-MM-DD'), 'titulo' => $str('Título'),
                'refeicao' => ['type' => 'string', 'enum' => $refeicoes, 'description' => 'Omita para o dia todo'],
                'pessoas_total' => ['type' => 'integer', 'description' => 'Total à mesa (convidados)'],
                'pessoas_extras' => ['type' => 'integer', 'description' => 'Convidados além da casa (alternativa a pessoas_total)'],
            ], ['evento', 'data']),
            $escrita('criar_ausencia', 'Marca uma pessoa como ausente (sai do cálculo de refeições e compras).', [
                'pessoa' => $str('Nome da pessoa'), 'inicio' => $str('AAAA-MM-DD'), 'fim' => $str('AAAA-MM-DD'), 'motivo' => $str('Motivo (opcional)'),
            ], ['pessoa', 'inicio', 'fim']),
            $escrita('adicionar_compra', 'Adiciona item à lista de compras.', [
                'descricao' => $str('Texto livre (ex.: "Chocolate")'), 'ingrediente' => $str('Ingrediente cadastrado (opcional)'),
                'quantidade' => $num('Quantidade (opcional)'), 'unidade' => ['type' => 'string', 'enum' => $unidades],
            ]),
            $escrita('gerar_cardapio', 'Gera (ou completa) o cardápio de uma semana com o motor de planejamento e otimização.', ['semana' => $str('Qualquer dia da semana, AAAA-MM-DD')], ['semana']),
            $escrita('alterar_receita', 'Altera uma receita existente. Só os campos informados mudam; "ingredientes", se enviado, SUBSTITUI a lista inteira (use detalhar_receita antes para partir da lista atual).', [
                'receita' => $str('Nome da receita a alterar (igual ou palavra inteira do nome)'), 'novo_nome' => $str('Novo nome'),
                'porcoes' => ['type' => 'integer'], 'tempo_preparo' => ['type' => 'integer', 'description' => 'Minutos'],
                'dificuldade' => ['type' => 'string', 'enum' => ['facil', 'media', 'dificil']],
                'refeicoes' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => $refeicoes], 'description' => 'Refeições compatíveis'],
                'ingredientes' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'ingrediente' => $str('Nome'), 'quantidade' => $num('Quantidade'), 'unidade' => ['type' => 'string', 'enum' => $unidades],
                    'opcional' => ['type' => 'boolean'],
                ], 'required' => ['ingrediente', 'quantidade', 'unidade']]],
                'modo_preparo' => $str('Passo a passo'), 'descricao' => $str('Descrição'), 'favorita' => ['type' => 'boolean'],
            ], ['receita']),
            $escrita('criar_receita', 'Cria uma receita (tudo ou nada). Quantidades para o total de porções.', [
                'nome' => $str('Nome'), 'porcoes' => ['type' => 'integer'], 'tempo_preparo' => ['type' => 'integer', 'description' => 'Minutos'],
                'dificuldade' => ['type' => 'string', 'enum' => ['facil', 'media', 'dificil']],
                'refeicoes' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => $refeicoes], 'description' => 'Refeições compatíveis'],
                'ingredientes' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'ingrediente' => $str('Nome'), 'quantidade' => $num('Quantidade'), 'unidade' => ['type' => 'string', 'enum' => $unidades],
                ], 'required' => ['ingrediente', 'quantidade', 'unidade']]],
                'modo_preparo' => $str('Passo a passo'),
            ], ['nome', 'porcoes', 'ingredientes']),
        ];
    }

    /**
     * @param  array<string, mixed>  $props
     * @param  array<int, string>  $obrigatorios
     * @return array{name: string, description: string, inputSchema: array<string, mixed>, annotations: array<string, bool>}
     */
    private function def(string $nome, string $descricao, array $props, array $obrigatorios, bool $somenteLeitura): array
    {
        $schema = ['type' => 'object', 'properties' => $props === [] ? new \stdClass : $props];
        if ($obrigatorios !== []) {
            $schema['required'] = $obrigatorios;
        }

        return [
            'name' => $nome,
            'description' => $descricao,
            'inputSchema' => $schema,
            'annotations' => ['readOnlyHint' => $somenteLeitura, 'destructiveHint' => ! $somenteLeitura && in_array($nome, ['baixar_estoque', 'zerar_estoque'], true)],
        ];
    }
}
