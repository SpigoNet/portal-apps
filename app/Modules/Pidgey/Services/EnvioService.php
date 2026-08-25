<?php

namespace App\Modules\Pidgey\Services;

use App\Models\AiModel;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Models\Agendamento;
use App\Modules\Pidgey\Models\ConteudoDinamico;
use Illuminate\Support\Collection;

class EnvioService
{
    public function __construct(
        private TelegramService $telegram,
        private InterpretadorPersonaService $interpretador,
        private ResumoFinanceiroService $resumo,
    ) {}

    /**
     * Envia uma mensagem através da persona/canal informados.
     *
     * @param  array  $contextos  Textos adicionais injetados no system prompt da IA.
     * @return array{ok:bool,status:?int,error:?string,interpretada:bool,mensagem_final:string}
     */
    public function enviar(Persona $persona, string $mensagem, ?string $canal = null, bool $interpretar = false, array $contextos = [], ?AiModel $aiModel = null): array
    {
        $canal = $canal ?: ($persona->canal ?? 'telegram');

        if ($canal !== 'telegram') {
            return [
                'ok' => false,
                'status' => null,
                'error' => "Canal '{$canal}' ainda não suportado pelo Pidgey",
                'interpretada' => false,
                'mensagem_final' => $mensagem,
            ];
        }

        if (empty($persona->telegram_token) || empty($persona->telegram_chat_id)) {
            return [
                'ok' => false,
                'status' => null,
                'error' => 'Persona sem token/chat_id do Telegram configurado',
                'interpretada' => false,
                'mensagem_final' => $mensagem,
            ];
        }

        $mensagemFinal = $mensagem;
        $interpretada = false;

        // Se a mensagem estiver vazia mas houver conteúdo dinâmico,
        // o conteúdo passa a ser a própria mensagem a ser interpretada.
        if (trim((string) $mensagemFinal) === '' && ! empty($contextos)) {
            $mensagemFinal = implode("\n\n", $contextos);
        }

        if ($interpretar || ! empty($contextos)) {
            $mensagemFinal = $this->interpretador->interpretar($persona, $mensagemFinal, $contextos, $aiModel);
            $interpretada = $mensagemFinal !== $mensagem;
        }

        $result = $this->telegram->sendMessage(
            $persona->telegram_token,
            $persona->telegram_chat_id,
            $mensagemFinal
        );

        return [
            'ok' => $result['ok'],
            'status' => $result['status'] ?? null,
            'error' => $result['error'] ?? null,
            'interpretada' => $interpretada,
            'mensagem_final' => $mensagemFinal,
        ];
    }

    /**
     * Envia um agendamento específico (usado pelo scheduler e por "enviar agora").
     *
     * @return array{ok:bool,status:?int,error:?string,interpretada:bool,mensagem_final:string}
     */
    public function enviarAgendamento(Agendamento $agendamento): array
    {
        $persona = Persona::query()->where('slug', $agendamento->persona_slug)->first();

        if (! $persona instanceof Persona) {
            return [
                'ok' => false,
                'status' => null,
                'error' => "Persona '{$agendamento->persona_slug}' não encontrada",
                'interpretada' => false,
                'mensagem_final' => $agendamento->mensagem,
            ];
        }

        $conteudos = $agendamento->conteudosDinamicos;

        // Relatório financeiro: usa o resumo especializado da persona
        // (análise charmosa, com lógica de "no vermelho"), em vez da
        // reescrita genérica. O relatório do Mithril é buscado aqui.
        if ($agendamento->interpretar && $conteudos->contains('tipo', 'relatorio_financeiro')) {
            $relatorio = $this->resumo->obterRelatorio((int) now()->month, (int) now()->year);

            if ($relatorio !== '') {
                $saldo = $this->resumo->saldoEfetivado($relatorio);
                $vermelho = $saldo !== null && $saldo < 0;

                $mensagemFinal = $this->interpretador->resumirFinanceiro(
                    $persona,
                    $relatorio,
                    $vermelho,
                    $agendamento->aiModelEfetivo()
                );

                return $this->enviar($persona, $mensagemFinal, $agendamento->canal, false, [], $agendamento->aiModelEfetivo());
            }
        }

        $contextos = $this->resolverContextos($conteudos);

        return $this->enviar(
            $persona,
            $agendamento->mensagem,
            $agendamento->canal,
            (bool) $agendamento->interpretar,
            $contextos,
            $agendamento->aiModelEfetivo()
        );
    }

    /**
     * Converte os conteúdos dinâmicos selecionados em textos de contexto,
     * resolvendo fontes dinâmicas (ex.: relatório financeiro do Mithril).
     *
     * @param  Collection<ConteudoDinamico>  $conteudos
     * @return string[]
     */
    public function resolverContextos(Collection $conteudos): array
    {
        return $conteudos
            ->filter(fn (ConteudoDinamico $c) => $c->ativo)
            ->map(function (ConteudoDinamico $c): ?string {
                if ($c->tipo === 'relatorio_financeiro') {
                    $relatorio = $this->resumo->obterRelatorio((int) now()->month, (int) now()->year);

                    return $relatorio !== '' ? "Relatório financeiro (Mithril):\n{$relatorio}" : null;
                }

                return $c->conteudo ?: null;
            })
            ->filter()
            ->values()
            ->all();
    }
}
