<?php

namespace App\Modules\Cardapio\Services\Assistente;

use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\Evento;
use App\Modules\Cardapio\Models\Ingrediente;
use App\Modules\Cardapio\Models\ItemEstoque;
use App\Modules\Cardapio\Models\Pessoa;
use App\Modules\Cardapio\Models\Preferencia;
use App\Modules\Cardapio\Models\Receita;
use App\Modules\Cardapio\Models\Restricao;
use App\Modules\Cardapio\Services\AgendaSemana;
use App\Modules\Cardapio\Services\PlanejadorService;
use App\Modules\Cardapio\Support\TipoRefeicao;
use App\Modules\Cardapio\Support\UnidadeConversor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Recebe as ações propostas pela IA e só deixa passar o que é válido para ESTA casa: tipo conhecido,
 * campos no formato certo, nomes que existem (ou que o usuário poderá confirmar como novos), unidades
 * compatíveis e permissão de quem pede. Tudo vira dados normalizados (ids, unidade base, datas ISO);
 * nada do texto livre da IA chega ao banco.
 */
class ValidadorAcoes
{
    public const MAX_ACOES = 12;

    /** Ações que alteram dados: exigem confirmação. */
    public const MUTAVEIS = [
        'adicionar_estoque', 'baixar_estoque', 'zerar_estoque', 'adicionar_restricao', 'adicionar_preferencia',
        'criar_evento', 'criar_ausencia', 'adicionar_compra', 'gerar_cardapio', 'criar_receita', 'alterar_receita',
    ];

    /** Ações só de leitura: respondidas na hora. */
    public const CONSULTAS = ['sugerir_receitas', 'consultar_estoque'];

    /** @var Collection<int, Ingrediente> */
    private Collection $ingredientes;

    /** @var Collection<int, Pessoa> */
    private Collection $pessoas;

    private Casa $casa;

    private Pessoa $falante;

    /**
     * @param  array<int, array<string, mixed>>  $acoes
     * @return array{pendentes: array<int, array<string, mixed>>, consultas: array<int, array<string, mixed>>, rejeitadas: array<int, string>}
     */
    public function validar(Casa $casa, Pessoa $falante, array $acoes): array
    {
        $this->casa = $casa;
        $this->falante = $falante;
        $this->ingredientes = $casa->ingredientes()->get();
        $this->pessoas = $casa->pessoas()->where('ativo', true)->get();

        $resultado = ['pendentes' => [], 'consultas' => [], 'rejeitadas' => []];

        foreach (array_slice($acoes, 0, self::MAX_ACOES) as $i => $bruta) {
            $n = $i + 1;
            $tipo = is_string($bruta['tipo'] ?? null) ? $bruta['tipo'] : '';

            if (! in_array($tipo, array_merge(self::MUTAVEIS, self::CONSULTAS), true)) {
                $nome = mb_substr(trim(strip_tags($tipo)), 0, 40);
                $resultado['rejeitadas'][] = "Ação {$n}: o assistente não sabe fazer ".($nome !== '' ? "“{$nome}”" : 'isso').' (tipo não permitido). Ele consegue: estoque, restrições e preferências, eventos e ausências, lista de compras, criar receitas, montar o cardápio e sugerir receitas.';

                continue;
            }

            $retorno = $this->{'validar'.str_replace('_', '', ucwords($tipo, '_'))}($bruta);

            if (is_string($retorno)) {
                $resultado['rejeitadas'][] = "Ação {$n} ({$tipo}): {$retorno}";

                continue;
            }

            $alvo = in_array($tipo, self::MUTAVEIS, true) ? 'pendentes' : 'consultas';
            $resultado[$alvo][] = array_merge(['id' => 'a'.$n, 'tipo' => $tipo, 'avisos' => []], $retorno);
        }

        if (count($acoes) > self::MAX_ACOES) {
            $resultado['rejeitadas'][] = 'Pedido grande demais: só as '.self::MAX_ACOES.' primeiras ações foram consideradas.';
        }

        return $resultado;
    }

    // ---- estoque ----

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>|string
     */
    private function validarAdicionarEstoque(array $a): array|string
    {
        $nome = $this->nome($a['ingrediente'] ?? null);
        $quantidade = $this->quantidade($a['quantidade'] ?? null);
        $unidade = is_string($a['unidade'] ?? null) ? UnidadeConversor::normalizar($a['unidade']) : null;

        if ($nome === null) {
            return 'faltou o nome do alimento.';
        }
        if ($quantidade === null || $unidade === null) {
            return "informe a quantidade e a unidade de {$nome} (ex.: 2 kg).";
        }

        [$ingrediente, $erro] = $this->acharIngrediente($nome);
        if ($erro !== null) {
            return $erro;
        }

        $criar = $ingrediente === null;
        if (! $criar && ! UnidadeConversor::compativel($unidade, $ingrediente->unidade_base)) {
            return "{$ingrediente->nome} é controlado em {$this->descreverBase($ingrediente->unidade_base)}; a unidade '{$unidade}' não combina.";
        }

        $local = is_string($a['local'] ?? null) && isset(ItemEstoque::LOCAIS[$a['local']]) ? $a['local'] : 'despensa';
        $validade = $this->validade($a);
        if ($validade === false) {
            return 'validade inválida.';
        }

        $base = $criar ? UnidadeConversor::baseDe($unidade) : $ingrediente->unidade_base;
        $nomeFinal = $criar ? $nome : $ingrediente->nome;
        $texto = UnidadeConversor::formatar(UnidadeConversor::paraBase($quantidade, $unidade), $base);

        return [
            'dados' => [
                'ingrediente_id' => $ingrediente?->id, 'nome' => $nomeFinal, 'criar' => $criar,
                'quantidade' => $quantidade, 'unidade' => $unidade, 'local' => $local, 'validade' => $validade?->toDateString(),
            ],
            'resumo' => "Adicionar {$texto} de {$nomeFinal} ao estoque ({$this->rotuloLocal($local)}".($validade ? ', vence '.$validade->format('d/m') : '').')',
            'avisos' => $criar ? ["“{$nomeFinal}” ainda não existe e será cadastrado como ingrediente."] : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>|string
     */
    private function validarBaixarEstoque(array $a): array|string
    {
        $quantidade = $this->quantidade($a['quantidade'] ?? null);
        $unidade = is_string($a['unidade'] ?? null) ? UnidadeConversor::normalizar($a['unidade']) : null;
        [$ingrediente, $erro] = $this->ingredienteExistente($a['ingrediente'] ?? null);

        if ($erro !== null) {
            return $erro;
        }
        if ($quantidade === null || $unidade === null) {
            return "informe quanto de {$ingrediente->nome} foi usado (ex.: 500 g).";
        }
        if (! UnidadeConversor::compativel($unidade, $ingrediente->unidade_base)) {
            return "a unidade '{$unidade}' não combina com {$ingrediente->nome}.";
        }

        $base = UnidadeConversor::paraBase($quantidade, $unidade);

        return [
            'dados' => ['ingrediente_id' => $ingrediente->id, 'quantidade_base' => $base],
            'resumo' => 'Dar baixa em '.UnidadeConversor::formatar($base, $ingrediente->unidade_base)." de {$ingrediente->nome}",
        ];
    }

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>|string
     */
    private function validarZerarEstoque(array $a): array|string
    {
        [$ingrediente, $erro] = $this->ingredienteExistente($a['ingrediente'] ?? null);

        if ($erro !== null) {
            return $erro;
        }

        $total = (float) $this->casa->itensEstoque()->where('ingrediente_id', $ingrediente->id)->sum('quantidade');

        if ($total <= 0) {
            return "não há {$ingrediente->nome} no estoque para zerar.";
        }

        return [
            'dados' => ['ingrediente_id' => $ingrediente->id],
            'resumo' => "Zerar o estoque de {$ingrediente->nome} (hoje: ".UnidadeConversor::formatar($total, $ingrediente->unidade_base).')',
        ];
    }

    // ---- pessoas ----

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>|string
     */
    private function validarAdicionarRestricao(array $a): array|string
    {
        [$pessoa, $erro] = $this->pessoaDaAcao($a['pessoa'] ?? null);
        if ($erro !== null) {
            return $erro;
        }

        $tipo = is_string($a['restricao'] ?? null) ? $a['restricao'] : '';
        if (! isset(Restricao::TIPOS[$tipo])) {
            return 'tipo de restrição inválido (alergia, intolerância, preferência ou objetivo).';
        }

        $descricao = is_string($a['descricao'] ?? null) ? mb_substr(trim(strip_tags($a['descricao'])), 0, 255) : null;
        $ingrediente = null;

        if (! empty($a['ingrediente'])) {
            [$ingrediente, $erro] = $this->ingredienteExistente($a['ingrediente']);
            if ($erro !== null) {
                return $erro;
            }
        } elseif ($tipo !== 'objetivo' || ! $descricao) {
            return 'informe o ingrediente da restrição (ou, em objetivo, uma descrição).';
        }

        $alvo = $ingrediente?->nome ?? $descricao;

        return [
            'dados' => ['pessoa_id' => $pessoa->id, 'ingrediente_id' => $ingrediente?->id, 'tipo' => $tipo, 'descricao' => $ingrediente ? null : $descricao],
            'resumo' => "{$pessoa->nome}: ".strtolower(explode(' (', Restricao::TIPOS[$tipo])[0])." — {$alvo}",
        ];
    }

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>|string
     */
    private function validarAdicionarPreferencia(array $a): array|string
    {
        [$pessoa, $erro] = $this->pessoaDaAcao($a['pessoa'] ?? null);
        if ($erro !== null) {
            return $erro;
        }

        [$ingrediente, $erro] = $this->ingredienteExistente($a['ingrediente'] ?? null);
        if ($erro !== null) {
            return $erro;
        }

        $nivel = $a['nivel'] ?? null;
        if (! is_numeric($nivel) || ! isset(Preferencia::NIVEIS[(int) $nivel])) {
            return 'nível de preferência inválido (5, 3, 0, -5 ou -10).';
        }

        return [
            'dados' => ['pessoa_id' => $pessoa->id, 'ingrediente_id' => $ingrediente->id, 'nivel' => (int) $nivel],
            'resumo' => "{$pessoa->nome}: {$ingrediente->nome} — ".strtolower(Preferencia::NIVEIS[(int) $nivel]),
        ];
    }

    // ---- agenda ----

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>|string
     */
    private function validarCriarEvento(array $a): array|string
    {
        $tipo = is_string($a['evento'] ?? null) ? $a['evento'] : '';
        $data = $this->data($a['data'] ?? null);

        if (! isset(Evento::TIPOS[$tipo])) {
            return "o tipo do evento deve ser 'fora' ou 'convidados'.";
        }
        if ($data === null) {
            return 'data inválida ou fora do período aceito.';
        }

        $refeicao = $a['refeicao'] ?? null;
        if ($refeicao !== null && $refeicao !== '' && (! is_string($refeicao) || ! isset(TipoRefeicao::rotulos()[$refeicao]))) {
            return 'tipo de refeição inválido.';
        }
        $refeicao = $refeicao ?: null;

        $extras = 0;
        $total = null;
        if ($tipo === 'convidados') {
            if (is_numeric($a['pessoas_extras'] ?? null) && (int) $a['pessoas_extras'] >= 1) {
                $extras = (int) $a['pessoas_extras'];
            } elseif (is_numeric($a['pessoas_total'] ?? null)) {
                $total = (int) $a['pessoas_total'];
                $habituais = count(AgendaSemana::carregar($this->casa, $data, $data)->slot($data, $refeicao ?? TipoRefeicao::ALMOCO)['pessoas'] ?? []);
                $extras = $total - $habituais;

                if ($extras < 1) {
                    return "já são {$habituais} pessoas nessa refeição pela rotina; diga quantas a mais virão.";
                }
            } else {
                return 'informe quantas pessoas virão.';
            }

            if ($extras > 200) {
                return 'número de convidados fora do razoável.';
            }
        }

        $titulo = is_string($a['titulo'] ?? null) ? mb_substr(trim(strip_tags($a['titulo'])), 0, 160) : '';
        $titulo = $titulo !== '' ? $titulo : ($tipo === 'fora' ? 'Refeição fora de casa' : 'Convidados');
        $onde = $refeicao ? mb_strtolower(TipoRefeicao::rotulos()[$refeicao]) : 'o dia todo';

        return [
            'dados' => ['data' => $data->toDateString(), 'tipo' => $tipo, 'titulo' => $titulo, 'tipo_refeicao' => $refeicao, 'pessoas_extras' => $extras],
            'resumo' => ($tipo === 'fora' ? 'Fora de casa' : 'Convidados')." — {$titulo}, {$data->format('d/m')}, {$onde}".($tipo === 'convidados' ? ", +{$extras} pessoa(s)".($total ? " ({$total} à mesa)" : '') : ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>|string
     */
    private function validarCriarAusencia(array $a): array|string
    {
        $pessoa = $this->pessoa($a['pessoa'] ?? null);
        if (is_string($pessoa)) {
            return $pessoa;
        }

        $inicio = $this->data($a['inicio'] ?? null);
        $fim = isset($a['fim']) && $a['fim'] !== '' ? $this->data($a['fim']) : $inicio;

        if ($inicio === null || $fim === null) {
            return 'datas inválidas.';
        }
        if ($fim->lt($inicio)) {
            return 'a data final vem antes da inicial.';
        }
        if ($inicio->diffInDays($fim) > 366) {
            return 'período longo demais.';
        }

        $motivo = is_string($a['motivo'] ?? null) ? mb_substr(trim(strip_tags($a['motivo'])), 0, 160) : null;

        return [
            'dados' => ['pessoa_id' => $pessoa->id, 'inicio' => $inicio->toDateString(), 'fim' => $fim->toDateString(), 'motivo' => $motivo ?: null],
            'resumo' => "{$pessoa->nome} ausente de {$inicio->format('d/m')} a {$fim->format('d/m')}".($motivo ? " ({$motivo})" : ''),
        ];
    }

    // ---- compras e cardápio ----

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>|string
     */
    private function validarAdicionarCompra(array $a): array|string
    {
        $nome = $this->nome($a['ingrediente'] ?? ($a['descricao'] ?? null));
        if ($nome === null) {
            return 'faltou dizer o que comprar.';
        }

        [$ingrediente, $erro] = $this->acharIngrediente($nome);
        if ($erro !== null) {
            return $erro;
        }

        $base = null;
        $quantidade = $this->quantidade($a['quantidade'] ?? null);
        $unidade = is_string($a['unidade'] ?? null) ? UnidadeConversor::normalizar($a['unidade']) : null;

        if ($ingrediente !== null && $quantidade !== null && $unidade !== null) {
            if (! UnidadeConversor::compativel($unidade, $ingrediente->unidade_base)) {
                return "a unidade '{$unidade}' não combina com {$ingrediente->nome}.";
            }
            $base = UnidadeConversor::paraBase($quantidade, $unidade);
        }

        $texto = $ingrediente?->nome ?? $nome;

        return [
            'dados' => ['descricao' => $texto, 'ingrediente_id' => $base !== null ? $ingrediente->id : null, 'quantidade_base' => $base],
            'resumo' => 'Pôr na lista de compras: '.($base !== null ? UnidadeConversor::formatar($base, $ingrediente->unidade_base).' de ' : '').$texto,
        ];
    }

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>|string
     */
    private function validarGerarCardapio(array $a): array|string
    {
        $dia = isset($a['semana']) && $a['semana'] !== '' ? $this->data($a['semana']) : today();

        if ($dia === null) {
            return 'data da semana inválida.';
        }

        $segunda = PlanejadorService::segundaDaSemana($dia);

        return [
            'dados' => ['semana' => $segunda->toDateString()],
            'resumo' => "Montar o cardápio da semana de {$segunda->format('d/m')} a {$segunda->copy()->addDays(6)->format('d/m')}",
        ];
    }

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>|string
     */
    private function validarCriarReceita(array $a): array|string
    {
        $nome = $this->nome($a['nome'] ?? null);
        if ($nome === null) {
            return 'faltou o nome da receita.';
        }
        $nome = mb_substr($nome, 0, 160);

        if ($this->casa->receitas()->whereRaw('lower(nome) = ?', [mb_strtolower($nome)])->exists()) {
            return "já existe uma receita chamada “{$nome}”.";
        }

        $linhas = $this->linhasReceita($a['ingredientes'] ?? null);
        if (is_string($linhas)) {
            return $linhas;
        }
        ['itens' => $itens, 'novos' => $novos] = $linhas;

        $refeicoes = [];
        foreach (is_array($a['refeicoes'] ?? null) ? $a['refeicoes'] : [] as $tipo) {
            if (is_string($tipo) && isset(TipoRefeicao::rotulos()[$tipo])) {
                $refeicoes[] = $tipo;
            }
        }

        $dificuldade = is_string($a['dificuldade'] ?? null) && isset(Receita::DIFICULDADES[$a['dificuldade']]) ? $a['dificuldade'] : 'media';
        $porcoes = is_numeric($a['porcoes'] ?? null) ? max(1, min(100, (int) $a['porcoes'])) : 4;
        $tempo = is_numeric($a['tempo_preparo'] ?? null) && (int) $a['tempo_preparo'] > 0 ? min(1440, (int) $a['tempo_preparo']) : null;
        $modo = is_string($a['modo_preparo'] ?? null) ? mb_substr(trim(strip_tags($a['modo_preparo'])), 0, 10000) : null;
        $descricao = is_string($a['descricao'] ?? null) ? mb_substr(trim(strip_tags($a['descricao'])), 0, 2000) : null;

        return [
            'dados' => [
                'nome' => $nome, 'descricao' => $descricao ?: null, 'porcoes' => $porcoes, 'tempo_preparo' => $tempo,
                'dificuldade' => $dificuldade, 'refeicoes' => array_values(array_unique($refeicoes)), 'modo_preparo' => $modo ?: null,
                'ingredientes' => $itens, 'novos' => array_values($novos),
            ],
            'resumo' => "Criar a receita “{$nome}” ({$porcoes} porções, ".count($itens).' ingrediente(s)'.($tempo ? ", {$tempo} min" : '').')',
            'avisos' => $novos === [] ? [] : ['Ingredientes novos a cadastrar: '.implode(', ', array_column($novos, 'nome')).'.'],
        ];
    }

    /**
     * Valida a lista de ingredientes de uma receita (criar/alterar).
     *
     * @return array{itens: array<int, array<string, mixed>>, novos: array<string, array{nome: string, base: string}>}|string
     */
    private function linhasReceita(mixed $bruto): array|string
    {
        $linhas = is_array($bruto) ? array_slice($bruto, 0, 25) : [];
        if ($linhas === []) {
            return 'a receita precisa de ingredientes com quantidade e unidade.';
        }

        $itens = [];
        $novos = [];
        foreach ($linhas as $i => $linha) {
            $n = $i + 1;
            $nomeIng = is_array($linha) ? $this->nome($linha['ingrediente'] ?? null) : null;
            $quantidade = is_array($linha) ? $this->quantidade($linha['quantidade'] ?? null) : null;
            $unidade = is_array($linha) && is_string($linha['unidade'] ?? null) ? UnidadeConversor::normalizar($linha['unidade']) : null;

            if ($nomeIng === null || $quantidade === null || $unidade === null) {
                return "o ingrediente {$n} está incompleto (nome, quantidade e unidade).";
            }

            [$ingrediente, $erro] = $this->acharIngrediente($nomeIng);
            if ($erro !== null) {
                return $erro;
            }

            if ($ingrediente !== null && ! UnidadeConversor::compativel($unidade, $ingrediente->unidade_base)) {
                return "{$ingrediente->nome} é controlado em {$this->descreverBase($ingrediente->unidade_base)}; a unidade '{$unidade}' não combina.";
            }

            $chave = Nomes::chave($ingrediente?->nome ?? $nomeIng);
            if ($ingrediente === null) {
                // Dois usos do mesmo ingrediente novo precisam falar a mesma "língua" de unidade.
                if (isset($novos[$chave]) && $novos[$chave]['base'] !== UnidadeConversor::baseDe($unidade)) {
                    return "“{$nomeIng}” aparece com unidades incompatíveis.";
                }
                $novos[$chave] ??= ['nome' => $nomeIng, 'base' => UnidadeConversor::baseDe($unidade)];
            }

            $itens[] = [
                'ingrediente_id' => $ingrediente?->id,
                'nome' => $ingrediente?->nome ?? $nomeIng,
                'quantidade_base' => UnidadeConversor::paraBase($quantidade, $unidade),
                'opcional' => ! empty($linha['opcional']),
            ];
        }

        return ['itens' => $itens, 'novos' => $novos];
    }

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>|string
     */
    private function validarAlterarReceita(array $a): array|string
    {
        $busca = $this->nome($a['receita'] ?? null);
        if ($busca === null) {
            return 'faltou o nome da receita a alterar.';
        }

        $achado = Nomes::achar($this->casa->receitas()->get(), $busca, fn (Receita $r) => $r->nome);
        if ($achado['ambiguos'] !== []) {
            return "“{$busca}” pode ser: ".implode(', ', $achado['ambiguos']).'. Diga qual.';
        }
        $receita = $achado['item'] ?? null;
        if ($receita === null) {
            return "não encontrei a receita “{$busca}”.";
        }

        $d = ['receita_id' => $receita->id];
        $mudancas = [];

        if (array_key_exists('novo_nome', $a)) {
            $novo = $this->nome($a['novo_nome']);
            if ($novo === null) {
                return 'novo_nome inválido.';
            }
            $novo = mb_substr($novo, 0, 160);
            if ($this->casa->receitas()->whereRaw('lower(nome) = ?', [mb_strtolower($novo)])->where('id', '!=', $receita->id)->exists()) {
                return "já existe outra receita chamada “{$novo}”.";
            }
            $d['nome'] = $novo;
            $mudancas[] = 'nome';
        }
        if (array_key_exists('porcoes', $a)) {
            if (! is_numeric($a['porcoes']) || (int) $a['porcoes'] < 1) {
                return 'porcoes inválido.';
            }
            $d['porcoes'] = min(100, (int) $a['porcoes']);
            $mudancas[] = 'porções';
        }
        if (array_key_exists('tempo_preparo', $a)) {
            if (! is_numeric($a['tempo_preparo']) || (int) $a['tempo_preparo'] < 1) {
                return 'tempo_preparo inválido.';
            }
            $d['tempo_preparo'] = min(1440, (int) $a['tempo_preparo']);
            $mudancas[] = 'tempo';
        }
        if (array_key_exists('dificuldade', $a)) {
            if (! is_string($a['dificuldade']) || ! isset(Receita::DIFICULDADES[$a['dificuldade']])) {
                return 'dificuldade inválida (facil, media ou dificil).';
            }
            $d['dificuldade'] = $a['dificuldade'];
            $mudancas[] = 'dificuldade';
        }
        if (array_key_exists('refeicoes', $a)) {
            if (! is_array($a['refeicoes'])) {
                return 'refeicoes inválido.';
            }
            $tipos = array_values(array_unique(array_filter($a['refeicoes'], fn ($t) => is_string($t) && isset(TipoRefeicao::rotulos()[$t]))));
            if ($tipos === []) {
                return 'refeicoes sem nenhum tipo válido.';
            }
            $d['refeicoes'] = $tipos;
            $mudancas[] = 'refeições';
        }
        foreach (['modo_preparo' => 10000, 'descricao' => 2000] as $campo => $max) {
            if (array_key_exists($campo, $a)) {
                if (! is_string($a[$campo])) {
                    return "{$campo} inválido.";
                }
                $d[$campo] = mb_substr(trim(strip_tags($a[$campo])), 0, $max) ?: null;
                $mudancas[] = str_replace('_', ' ', $campo);
            }
        }
        if (array_key_exists('favorita', $a)) {
            $d['favorita'] = filter_var($a['favorita'], FILTER_VALIDATE_BOOLEAN);
            $mudancas[] = 'favorita';
        }

        $novos = [];
        if (array_key_exists('ingredientes', $a)) {
            $linhas = $this->linhasReceita($a['ingredientes']);
            if (is_string($linhas)) {
                return $linhas;
            }
            $d['ingredientes'] = $linhas['itens'];
            $novos = $linhas['novos'];
            $mudancas[] = 'ingredientes (lista substituída)';
        }
        $d['novos'] = array_values($novos);

        if ($mudancas === []) {
            return 'nada para alterar: informe ao menos um campo novo.';
        }

        return [
            'dados' => $d,
            'resumo' => "Alterar a receita “{$receita->nome}”: ".implode(', ', $mudancas),
            'avisos' => $novos === [] ? [] : ['Ingredientes novos a cadastrar: '.implode(', ', array_column($novos, 'nome')).'.'],
        ];
    }

    // ---- consultas ----

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>|string
     */
    private function validarSugerirReceitas(array $a): array|string
    {
        $refeicao = $a['refeicao'] ?? TipoRefeicao::JANTAR;
        if (! is_string($refeicao) || ! isset(TipoRefeicao::rotulos()[$refeicao])) {
            return 'tipo de refeição inválido.';
        }

        $data = isset($a['data']) && $a['data'] !== '' ? $this->data($a['data']) : today();
        if ($data === null) {
            return 'data inválida.';
        }

        $ids = [];
        $desconhecidos = [];
        foreach (array_slice(is_array($a['ingredientes'] ?? null) ? $a['ingredientes'] : [], 0, 20) as $nome) {
            $achado = is_string($nome) ? Nomes::achar($this->ingredientes, $nome, fn (Ingrediente $i) => $i->nome) : ['item' => null];

            if ($achado['item'] !== null) {
                $ids[] = $achado['item']->id;
            } elseif (is_string($nome) && trim($nome) !== '') {
                $desconhecidos[] = trim($nome);
            }
        }

        return [
            'dados' => ['refeicao' => $refeicao, 'data' => $data->toDateString(), 'ingredientes' => array_values(array_unique($ids)), 'desconhecidos' => $desconhecidos],
            'resumo' => 'Sugerir receitas',
        ];
    }

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>|string
     */
    private function validarConsultarEstoque(array $a): array|string
    {
        [$ingrediente, $erro] = $this->ingredienteExistente($a['ingrediente'] ?? null);

        return $erro ?? ['dados' => ['ingrediente_id' => $ingrediente->id], 'resumo' => "Consultar estoque de {$ingrediente->nome}"];
    }

    // ---- helpers ----

    private function nome(mixed $valor): ?string
    {
        if (! is_string($valor)) {
            return null;
        }

        $nome = mb_substr(trim(strip_tags($valor)), 0, 120);

        return $nome !== '' ? $nome : null;
    }

    private function quantidade(mixed $valor): ?float
    {
        if (is_string($valor)) {
            $valor = str_replace(',', '.', trim($valor));
        }

        return is_numeric($valor) && (float) $valor > 0 && (float) $valor <= 1_000_000 ? (float) $valor : null;
    }

    /**
     * Valida a data ISO e restringe a uma janela razoável (um mês atrás a mais de um ano à frente).
     */
    private function data(mixed $valor): ?Carbon
    {
        if (! is_string($valor) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return null;
        }

        $data = Carbon::createFromFormat('!Y-m-d', $valor);

        if ($data === false || $data->format('Y-m-d') !== $valor) {
            return null;
        }

        return $data->between(today()->subDays(31), today()->addDays(400)) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $a
     */
    private function validade(array $a): Carbon|false|null
    {
        if (isset($a['validade_dias']) && $a['validade_dias'] !== '') {
            return is_numeric($a['validade_dias']) && (int) $a['validade_dias'] >= 0 && (int) $a['validade_dias'] <= 3650
                ? today()->addDays((int) $a['validade_dias'])
                : false;
        }

        if (isset($a['validade']) && $a['validade'] !== '') {
            return $this->data($a['validade']) ?? false;
        }

        return null;
    }

    /**
     * @return array{0: ?Ingrediente, 1: ?string} [ingrediente (null = não existe), erro]
     */
    private function acharIngrediente(string $nome): array
    {
        $achado = Nomes::achar($this->ingredientes, $nome, fn (Ingrediente $i) => $i->nome);

        if ($achado['ambiguos'] !== []) {
            return [null, "“{$nome}” pode ser: ".implode(', ', $achado['ambiguos']).'. Diga qual.'];
        }

        return [$achado['item'], null];
    }

    /**
     * @return array{0: ?Ingrediente, 1: ?string}
     */
    private function ingredienteExistente(mixed $nome): array
    {
        $nome = $this->nome($nome);

        if ($nome === null) {
            return [null, 'faltou o nome do alimento.'];
        }

        [$ingrediente, $erro] = $this->acharIngrediente($nome);

        return $erro !== null ? [null, $erro] : ($ingrediente === null ? [null, "não encontrei “{$nome}” nos ingredientes (cadastre-o antes)."] : [$ingrediente, null]);
    }

    /**
     * @return Pessoa|string a pessoa, ou o motivo da rejeição
     */
    private function pessoa(mixed $nome): Pessoa|string
    {
        $nome = $this->nome($nome);

        if ($nome === null) {
            return 'faltou dizer de quem se trata.';
        }

        if (in_array(Nomes::chave($nome), ['eu', 'eu mesmo', 'eu mesma', 'meu', 'minha'], true)) {
            return $this->falante;
        }

        $achado = Nomes::achar($this->pessoas, $nome, fn (Pessoa $p) => $p->nome);

        return match (true) {
            $achado['item'] !== null => $achado['item'],
            $achado['ambiguos'] !== [] => "“{$nome}” pode ser: ".implode(', ', $achado['ambiguos']).'. Diga o nome.',
            default => "não encontrei a pessoa “{$nome}”. Use o nome cadastrado (ex.: ".$this->pessoas->pluck('nome')->take(3)->implode(', ').').',
        };
    }

    /**
     * Pessoa-alvo de restrição/preferência, respeitando a permissão: adulto edita qualquer um; criança, só a si.
     *
     * @return array{0: ?Pessoa, 1: ?string}
     */
    private function pessoaDaAcao(mixed $nome): array
    {
        $pessoa = $this->pessoa($nome);

        if (is_string($pessoa)) {
            return [null, $pessoa];
        }

        if ($this->falante->tipo !== 'adulto' && $pessoa->id !== $this->falante->id) {
            return [null, 'você só pode alterar o seu próprio perfil.'];
        }

        return [$pessoa, null];
    }

    private function descreverBase(string $base): string
    {
        return match ($base) {
            'g' => 'peso (g/kg)', 'ml' => 'volume (ml/L)', default => 'unidades',
        };
    }

    private function rotuloLocal(string $local): string
    {
        return mb_strtolower(ItemEstoque::LOCAIS[$local]);
    }
}
