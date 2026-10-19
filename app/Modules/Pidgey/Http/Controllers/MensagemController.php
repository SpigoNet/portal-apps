<?php

namespace App\Modules\Pidgey\Http\Controllers;

use App\Models\AiModel;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Services\EnvioService;
use App\Modules\Pidgey\Services\InterpretadorPersonaService;
use App\Modules\Pidgey\Services\ResumoFinanceiroService;
use App\Modules\Pidgey\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MensagemController
{
    public function __construct(
        private TelegramService $telegram,
        private InterpretadorPersonaService $interpretador,
        private ResumoFinanceiroService $financa,
        private EnvioService $envio,
    ) {}

    public function health(): JsonResponse
    {
        return response()->json(['status' => 'ok', 'modulo' => 'pidgey']);
    }

    public function enviar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'persona' => 'required|string',
            'mensagem' => 'required|string',
            'canal' => 'sometimes|in:telegram,whatsapp,email',
            'interpretar' => 'sometimes|boolean',
            'imagem' => 'sometimes|boolean',
            'ai_model_id' => 'sometimes|nullable|exists:ai_modelos,id',
        ]);

        $persona = $this->buscarPersona($data['persona']);

        if (! $persona instanceof Persona) {
            return response()->json(['ok' => false, 'error' => 'Persona não encontrada'], 404);
        }

        $aiModel = ! empty($data['ai_model_id'])
            ? AiModel::find($data['ai_model_id'])
            : null;

        $result = $this->envio->enviar(
            $persona,
            $data['mensagem'],
            $data['canal'] ?? null,
            ! empty($data['interpretar']),
            [],
            $aiModel,
            ! empty($data['imagem'])
        );

        if (! $result['ok']) {
            return response()->json([
                'ok' => false,
                'error' => $result['error'],
                'status' => $result['status'],
            ], 502);
        }

        $payload = [
            'ok' => true,
            'canal' => 'telegram',
            'persona' => $persona->slug,
            'status' => $result['status'],
            'imagem' => $result['imagem'],
        ];

        if (! empty($data['interpretar'])) {
            $payload['interpretada'] = $result['interpretada'];
            $payload['mensagem_original'] = $data['mensagem'];
            $payload['mensagem_enviada'] = $result['mensagem_final'];
        }

        return response()->json($payload);
    }

    public function resumoFinanceiro(Request $request): JsonResponse
    {
        $data = $request->validate([
            'persona' => 'sometimes|string',
            'mes' => null,
            'ano' => 'sometimes|integer',
            'canal' => 'sometimes|in:telegram,whatsapp,email',
            'interpretar' => 'sometimes|boolean',
            'ai_model_id' => 'sometimes|nullable|exists:ai_modelos,id',
        ]);

        $slug = $data['persona'] ?? 'nami';
        $persona = $this->buscarPersona($slug);

        if (! $persona instanceof Persona) {
            return response()->json(['ok' => false, 'error' => 'Persona não encontrada'], 404);
        }

        $aiModel = ! empty($data['ai_model_id'])
            ? AiModel::find($data['ai_model_id'])
            : null;

        $mes = (int) ($data['mes'] ?? now()->month);
        $ano = (int) ($data['ano'] ?? now()->year);

        $relatorio = $this->financa->obterRelatorio($mes, $ano);

        if ($relatorio === '') {
            return response()->json([
                'ok' => false,
                'error' => 'Não foi possível obter o relatório financeiro',
            ], 502);
        }

        $saldo = $this->financa->saldoEfetivado($relatorio);
        $vermelho = $saldo !== null && $saldo < 0;

        $interpretar = $data['interpretar'] ?? true;
        $mensagemFinal = $relatorio;
        if ($interpretar) {
            $mensagemFinal = $this->interpretador->resumirFinanceiro($persona, $relatorio, $vermelho, $aiModel);
        }

        $result = $this->envio->enviar(
            $persona,
            $mensagemFinal,
            $data['canal'] ?? null,
            false,
            [],
            $aiModel
        );

        if (! $result['ok']) {
            return response()->json([
                'ok' => false,
                'error' => $result['error'],
                'status' => $result['status'],
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'canal' => 'telegram',
            'persona' => $persona->slug,
            'status' => $result['status'],
            'interpretada' => $interpretar,
            'vermelho' => $vermelho,
            'mensagem_enviada' => $mensagemFinal,
        ]);
    }

    /**
     * Busca a persona pelo slug ou, se o valor for numérico, também pelo id.
     * Comparar `id` (bigint) com texto quebra no PostgreSQL.
     */
    private function buscarPersona(string $identificador): ?Persona
    {
        return Persona::query()
            ->where('slug', $identificador)
            ->when(ctype_digit($identificador), fn ($query) => $query->orWhere('id', (int) $identificador))
            ->first();
    }
}
