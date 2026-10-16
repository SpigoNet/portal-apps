<?php

namespace App\Modules\Pidgey\Services;

use App\Models\User;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Contracts\ManipuladorTelegram;
use App\Modules\Pidgey\Events\TelegramVinculado;
use App\Modules\Pidgey\Models\TelegramVinculo;
use App\Modules\Pidgey\Support\RespostaTelegram;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Recebe os updates do bot: vincula chats (/start CÓDIGO), exige vínculo para todo o resto e repassa
 * mensagens e botões aos módulos registrados.
 */
class TelegramRoteador
{
    public const CODIGO_TTL_MINUTOS = 10;

    /** @var array<string, ManipuladorTelegram> slug da persona => módulo dono do bot */
    private array $manipuladores = [];

    public function __construct(private readonly TelegramService $telegram) {}

    public function registrar(ManipuladorTelegram $manipulador): void
    {
        $this->manipuladores[$manipulador->persona()] = $manipulador;
    }

    public function manipulador(string $slug): ?ManipuladorTelegram
    {
        return $this->manipuladores[$slug] ?? null;
    }

    /**
     * Segredo do webhook de um bot: derivado da chave do app e do slug, sem nada para guardar.
     */
    public static function segredo(string $slug): string
    {
        return hash_hmac('sha256', 'telegram-webhook:'.$slug, (string) config('app.key'));
    }

    /**
     * Gera um código de uso único para o usuário colar no bot (/start CÓDIGO).
     */
    public function gerarCodigo(User $usuario): string
    {
        $codigo = strtoupper(bin2hex(random_bytes(4)));
        Cache::put('pidgey.telegram.codigo.'.$codigo, $usuario->id, now()->addMinutes(self::CODIGO_TTL_MINUTOS));

        return $codigo;
    }

    /**
     * @param  array<string, mixed>  $update
     */
    public function tratar(Persona $persona, array $update): void
    {
        $manipulador = $this->manipulador($persona->slug);
        $token = (string) $persona->telegram_token;

        if ($manipulador === null) {
            return;
        }

        if (isset($update['callback_query'])) {
            $this->tratarBotao($persona, $manipulador, $update['callback_query']);

            return;
        }

        $mensagem = $update['message'] ?? $update['edited_message'] ?? null;
        $chatId = $mensagem['chat']['id'] ?? null;
        $texto = $mensagem['text'] ?? null;

        if ($chatId === null || ! is_string($texto) || trim($texto) === '') {
            return;
        }

        // O bot é privado: grupos e canais não conversam com ele.
        if (($mensagem['chat']['type'] ?? 'private') !== 'private') {
            return;
        }

        $chatId = (string) $chatId;
        $texto = trim($texto);

        if (preg_match('/^\/start(?:@\w+)?(?:\s+(\S+))?$/i', $texto, $m)) {
            $this->responder($token, $chatId, $this->iniciar($manipulador, $chatId, $mensagem['from']['username'] ?? null, $m[1] ?? null));

            return;
        }

        $vinculo = TelegramVinculo::with('usuario')->where('chat_id', $chatId)->first();

        if ($vinculo === null || $vinculo->usuario === null) {
            $this->responder($token, $chatId, new RespostaTelegram('Este chat ainda não está vinculado. No portal, abra <b>Pidgey → Telegram</b>, gere um código e envie aqui: <code>/start CÓDIGO</code>.'));

            return;
        }

        if (preg_match('/^\/ajuda(?:@\w+)?$/i', $texto) || strtolower($texto) === 'ajuda') {
            $this->responder($token, $chatId, new RespostaTelegram($manipulador->ajuda()));

            return;
        }

        try {
            $resposta = $manipulador->mensagem($persona, $vinculo->usuario, $texto);
        } catch (Throwable $e) {
            Log::error('Telegram: manipulador falhou', ['persona' => $persona->slug, 'erro' => $e->getMessage()]);
            $resposta = new RespostaTelegram('Algo deu errado ao processar seu pedido. Tente de novo em instantes.');
        }

        if ($resposta !== null) {
            $this->responder($token, $chatId, $resposta);

            return;
        }

        $this->responder($token, $chatId, new RespostaTelegram('Não entendi. Envie /ajuda para ver o que eu sei fazer.'));
    }

    /**
     * @param  array<string, mixed>  $botao
     */
    private function tratarBotao(Persona $persona, ManipuladorTelegram $manipulador, array $botao): void
    {
        $token = (string) $persona->telegram_token;
        $chatId = isset($botao['message']['chat']['id']) ? (string) $botao['message']['chat']['id'] : null;
        $dados = (string) ($botao['data'] ?? '');

        $vinculo = $chatId === null ? null : TelegramVinculo::with('usuario')->where('chat_id', $chatId)->first();

        // Sempre responde o toque, senão o Telegram deixa o botão "carregando".
        $this->telegram->answerCallbackQuery($token, (string) ($botao['id'] ?? ''));

        if ($vinculo === null || $vinculo->usuario === null || $dados === '') {
            return;
        }

        try {
            $resposta = $manipulador->botao($persona, $vinculo->usuario, $dados);
        } catch (Throwable $e) {
            Log::error('Telegram: botão falhou', ['persona' => $persona->slug, 'erro' => $e->getMessage()]);
            $resposta = new RespostaTelegram('Algo deu errado ao processar o botão.');
        }

        if ($resposta !== null) {
            $this->responder($token, $chatId, $resposta);
        }
    }

    private function iniciar(ManipuladorTelegram $manipulador, string $chatId, ?string $username, ?string $codigo): RespostaTelegram
    {
        if ($codigo === null) {
            $vinculado = TelegramVinculo::where('chat_id', $chatId)->exists();

            return new RespostaTelegram($vinculado
                ? 'Tudo certo, este chat já está vinculado. Envie /ajuda para ver os comandos.'
                : 'Olá! Para usar o bot, abra <b>Pidgey → Telegram</b> no portal, gere um código e envie aqui: <code>/start CÓDIGO</code>.');
        }

        $chave = 'pidgey.telegram.codigo.'.strtoupper($codigo);
        $userId = Cache::pull($chave); // uso único

        if ($userId === null) {
            return new RespostaTelegram('Código inválido ou expirado. Gere um novo no portal.');
        }

        // Um usuário tem um chat e um chat pertence a um usuário.
        TelegramVinculo::where('chat_id', $chatId)->where('user_id', '!=', $userId)->delete();
        TelegramVinculo::updateOrCreate(['user_id' => $userId], ['chat_id' => $chatId, 'username' => $username]);
        TelegramVinculado::dispatch((int) $userId);

        return new RespostaTelegram("✅ Vinculado! Agora este chat fala com o portal.\n\n".$manipulador->ajuda());
    }

    private function responder(string $token, string $chatId, RespostaTelegram $resposta): void
    {
        $this->telegram->sendMessage($token, $chatId, $resposta->texto, $resposta->teclado());
    }
}
