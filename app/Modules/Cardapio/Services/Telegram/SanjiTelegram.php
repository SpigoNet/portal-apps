<?php

namespace App\Modules\Cardapio\Services\Telegram;

use App\Models\User;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Cardapio\Models\Pessoa;
use App\Modules\Cardapio\Services\Assistente\AssistenteService;
use App\Modules\Cardapio\Services\Assistente\IaIndisponivelException;
use App\Modules\Pidgey\Contracts\ManipuladorTelegram;
use App\Modules\Pidgey\Support\RespostaTelegram;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Bot do Cardápio (persona Sanji). Comandos mostram dados do sistema; texto livre vai ao assistente,
 * que só PROPÕE: nada é alterado sem o toque em "Aplicar".
 */
class SanjiTelegram implements ManipuladorTelegram
{
    public const PERSONA = 'sanji';

    private const CACHE = 'cardapio.telegram.proposta.';

    private const CACHE_MINUTOS = 30;

    private const LIMITE_MENSAGEM = 3800;

    public function __construct(
        private readonly AssistenteService $assistente,
        private readonly ResumoCasa $resumo,
        private readonly VozPersona $voz,
    ) {}

    public function persona(): string
    {
        return self::PERSONA;
    }

    public function ajuda(): string
    {
        return "🍳 <b>Cardápio</b>\n"
            ."/hoje — o que tem hoje\n"
            ."/amanha — o que tem amanhã\n"
            ."/vencendo — o que está vencendo\n"
            ."/estoque — alertas de estoque (ou <code>/estoque arroz</code>)\n"
            ."/compras — o que falta comprar\n\n"
            .'Ou escreva do jeito que falaria: “comprei 2 kg de frango”, “acabou o arroz”, “o que dá pra fazer com batata?”. Eu proponho e você confirma.';
    }

    public function mensagem(Persona $persona, User $usuario, string $texto): ?RespostaTelegram
    {
        $pessoa = $this->pessoa($usuario);

        if ($pessoa === null) {
            return new RespostaTelegram('Você ainda não faz parte de nenhuma casa no Cardápio. Crie ou entre em uma pelo portal.');
        }

        if (str_starts_with($texto, '/')) {
            return $this->comando($persona, $pessoa, $texto);
        }

        return $this->assistente($persona, $pessoa, $usuario, $texto);
    }

    public function botao(Persona $persona, User $usuario, string $dados): ?RespostaTelegram
    {
        if (! preg_match('/^cd:(ap|no):([A-Za-z0-9]{16})$/', $dados, $m)) {
            return null;
        }

        $pessoa = $this->pessoa($usuario);
        $proposta = Cache::pull(self::CACHE.$m[2]); // uso único: tocar duas vezes não aplica duas vezes

        if ($pessoa === null || ! is_array($proposta) || $proposta['pessoa_id'] !== $pessoa->id) {
            return new RespostaTelegram('Esta proposta expirou. Peça de novo.');
        }

        if ($m[1] === 'no') {
            return new RespostaTelegram('Tudo bem, descartei.');
        }

        $resultados = $this->assistente->aplicar($pessoa->casa, $pessoa, $proposta['pendentes'], array_column($proposta['pendentes'], 'id'));

        $linhas = array_map(fn (array $r): string => ($r['ok'] ? '✔ ' : '✖ ').e($r['texto']), $resultados);

        return new RespostaTelegram($linhas === [] ? 'Nada para aplicar.' : implode("\n", $linhas));
    }

    private function comando(Persona $persona, Pessoa $pessoa, string $texto): RespostaTelegram
    {
        [$cmd, $arg] = array_pad(explode(' ', $texto, 2), 2, '');
        $cmd = strtolower(explode('@', $cmd)[0]);
        $arg = trim($arg);
        $casa = $pessoa->casa;

        switch ($cmd) {
            case '/hoje':
            case '/amanha':
                $dia = $cmd === '/hoje' ? today() : today()->addDay();
                $nome = $cmd === '/hoje' ? 'hoje' : 'amanhã';
                $linhas = $this->resumo->refeicoes($casa, $dia);

                if ($linhas === []) {
                    return new RespostaTelegram("Não há nada planejado para {$nome}. Monte a semana no portal ou me peça: “monte o cardápio da semana”.");
                }

                return new RespostaTelegram($this->voz->falar($persona, "Aqui está o cardápio de {$nome}:")."\n\n".implode("\n", $linhas));

            case '/vencendo':
                $linhas = $this->resumo->vencendo($casa);

                return new RespostaTelegram($linhas === [] ? 'Nada vencendo por enquanto. ✅' : "⚠ <b>Vencendo ou vencido</b>\n".implode("\n", $linhas));

            case '/estoque':
                if ($arg !== '') {
                    $linhas = $this->resumo->procurarEstoque($casa, $arg);

                    return new RespostaTelegram($linhas === [] ? 'Não encontrei “'.e($arg).'” no estoque.' : implode("\n", $linhas));
                }

                $baixo = $this->resumo->estoqueBaixo($casa);

                return new RespostaTelegram($baixo === [] ? 'Nenhum alimento abaixo do mínimo. ✅' : "📉 <b>Estoque baixo</b>\n".implode("\n", $baixo));

            case '/compras':
                $linhas = $this->resumo->compras($casa);

                return new RespostaTelegram($linhas === [] ? 'Nada para comprar agora. ✅' : "🛒 <b>Falta comprar</b>\n".implode("\n", $linhas));
        }

        return new RespostaTelegram('Esse comando eu não conheço. Envie /ajuda.');
    }

    private function assistente(Persona $persona, Pessoa $pessoa, User $usuario, string $texto): RespostaTelegram
    {
        try {
            $proposta = $this->assistente->interpretar($pessoa->casa, $pessoa, $usuario, $texto);
        } catch (IaIndisponivelException $e) {
            return new RespostaTelegram(e($e->getMessage()));
        }

        $partes = [];

        if ($proposta['mensagem']) {
            $partes[] = e($proposta['mensagem']);
        }

        foreach ($proposta['respostas'] as $linhas) {
            $partes[] = e(implode("\n", $linhas));
        }

        foreach ($proposta['rejeitadas'] as $motivo) {
            $partes[] = '⚠ '.e($motivo);
        }

        $botoes = [];

        if ($proposta['pendentes'] !== []) {
            $token = Str::random(16);
            Cache::put(self::CACHE.$token, ['pessoa_id' => $pessoa->id, 'pendentes' => $proposta['pendentes']], now()->addMinutes(self::CACHE_MINUTOS));

            $partes[] = "<b>Posso fazer isto?</b>\n".implode("\n", array_map(fn (array $a): string => '• '.e($a['resumo']), $proposta['pendentes']));
            $botoes = [[['✅ Aplicar', 'cd:ap:'.$token], ['❌ Descartar', 'cd:no:'.$token]]];
        }

        if ($partes === []) {
            $partes[] = 'Não encontrei nada para fazer com isso. Tente de outro jeito ou envie /ajuda.';
        }

        return new RespostaTelegram(Str::limit(implode("\n\n", $partes), self::LIMITE_MENSAGEM, '…'), $botoes);
    }

    private function pessoa(User $usuario): ?Pessoa
    {
        return Pessoa::with('casa')->where('user_id', $usuario->id)->where('ativo', true)->first();
    }
}
