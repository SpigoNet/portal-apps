<?php

namespace App\Modules\Pidgey\Services;

use App\Models\AiModel;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Models\Agendamento;
use App\Modules\Pidgey\Models\ConteudoDinamico;
use App\Modules\Pidgey\Models\TelegramVinculo;
use Illuminate\Support\Collection;

class EnvioService
{
    public function __construct(
        private TelegramService $telegram,
        private InterpretadorPersonaService $interpretador,
        private ResumoFinanceiroService $resumo,
        private ProvedoresConteudo $provedores,
        private ImagemPersonagemService $imagens,
    ) {}

    /**
     * Envia uma mensagem através da persona/canal informados.
     *
     * @param  array  $contextos  Textos adicionais injetados no system prompt da IA.
     * @param  bool  $comImagem  Envia junto uma imagem do personagem com a expressão da mensagem.
     * @return array{ok:bool,status:?int,error:?string,interpretada:bool,mensagem_final:string,imagem:?string}
     */
    public function enviar(Persona $persona, string $mensagem, ?string $canal = null, bool $interpretar = false, array $contextos = [], ?AiModel $aiModel = null, bool $comImagem = false): array
    {
        $canal = $canal ?: ($persona->canal ?? 'telegram');

        if ($canal !== 'telegram') {
            return [
                'ok' => false,
                'status' => null,
                'error' => "Canal '{$canal}' ainda não suportado pelo Pidgey",
                'interpretada' => false,
                'mensagem_final' => $mensagem,
                'imagem' => null,
            ];
        }

        if (empty($persona->telegram_token) || empty($persona->telegram_chat_id)) {
            return [
                'ok' => false,
                'status' => null,
                'error' => 'Persona sem token/chat_id do Telegram configurado',
                'interpretada' => false,
                'mensagem_final' => $mensagem,
                'imagem' => null,
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

        $result = $this->entregar($persona, (string) $persona->telegram_chat_id, $mensagemFinal, $comImagem, $aiModel);

        return [...$result, 'interpretada' => $interpretada, 'mensagem_final' => $mensagemFinal];
    }

    /**
     * Entrega o texto pelo bot da persona: como legenda de uma imagem do personagem, quando pedido e
     * possível, ou como mensagem de texto. Se a imagem não puder ser gerada ou enviada, manda só o texto.
     *
     * @return array{ok:bool,status:?int,error:?string,imagem:?string}
     */
    private function entregar(Persona $persona, string $chatId, string $texto, bool $comImagem, ?AiModel $aiModel): array
    {
        $token = (string) $persona->telegram_token;
        $imagem = $comImagem && $this->imagens->podeGerar($persona)
            ? $this->imagens->gerar($persona, $texto, $aiModel)
            : null;

        if ($imagem !== null) {
            $envio = $this->telegram->sendPhoto($token, $chatId, $imagem, $texto);

            if ($envio['ok']) {
                return ['ok' => true, 'status' => $envio['status'] ?? null, 'error' => null, 'imagem' => $imagem];
            }
        }

        $envio = $this->telegram->sendMessage($token, $chatId, $texto);

        return ['ok' => $envio['ok'], 'status' => $envio['status'] ?? null, 'error' => $envio['error'] ?? null, 'imagem' => null];
    }

    /**
     * Envia um agendamento específico (usado pelo scheduler e por "enviar agora").
     *
     * @return array{ok:bool,status:?int,error:?string,interpretada:bool,mensagem_final:string,imagem:?string}
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
                'imagem' => null,
            ];
        }

        $conteudos = $agendamento->conteudosDinamicos;

        // Conteúdo fornecido por um módulo (ex.: resumo do Cardápio): mensagem pronta, sem IA,
        // enviada ao chat vinculado do dono do agendamento.
        foreach ($conteudos->where('ativo', true) as $conteudo) {
            $provedor = $this->provedores->para((string) $conteudo->tipo);

            if ($provedor !== null) {
                return $this->enviarDeProvedor($agendamento, $persona, $provedor->mensagem($agendamento, $persona));
            }
        }

        $contextos = $this->resolverContextos($conteudos);

        return $this->enviar(
            $persona,
            $agendamento->mensagem,
            $agendamento->canal,
            (bool) $agendamento->interpretar,
            $contextos,
            $agendamento->aiModelEfetivo(),
            (bool) $agendamento->enviar_imagem
        );
    }

    /**
     * @return array{ok:bool,status:?int,error:?string,interpretada:bool,mensagem_final:string,imagem:?string}
     */
    private function enviarDeProvedor(Agendamento $agendamento, Persona $persona, ?string $texto): array
    {
        $resultado = ['ok' => true, 'status' => null, 'error' => null, 'interpretada' => false, 'mensagem_final' => (string) $texto, 'imagem' => null];

        // Nada a dizer hoje: não é falha, o agendamento segue para a próxima execução.
        if ($texto === null || trim($texto) === '') {
            return $resultado;
        }

        // Chat vinculado do dono; sem vínculo, o chat configurado na própria persona.
        $chatId = TelegramVinculo::where('user_id', $agendamento->user_id)->value('chat_id') ?: $persona->telegram_chat_id;

        if (empty($persona->telegram_token) || empty($chatId)) {
            return [...$resultado, 'ok' => false, 'error' => 'Persona sem token do Telegram ou sem chat para enviar (vincule o Telegram)'];
        }

        return [...$resultado, ...$this->entregar($persona, (string) $chatId, $texto, (bool) $agendamento->enviar_imagem, $agendamento->aiModel)];
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
