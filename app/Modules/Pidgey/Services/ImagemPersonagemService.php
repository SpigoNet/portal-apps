<?php

namespace App\Modules\Pidgey\Services;

use App\Models\AiModel;
use App\Modules\Admin\Services\AiProviderService;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Models\ParametroImagem;
use Illuminate\Support\Facades\Log;

/**
 * Gera a imagem do personagem para acompanhar uma mensagem: a aparência é fixa (perfil da persona)
 * e a expressão/sentimento vêm da própria mensagem.
 */
class ImagemPersonagemService
{
    public function __construct(
        private InterpretadorPersonaService $interpretador,
        private AiProviderService $provedores,
    ) {}

    public function podeGerar(Persona $persona): bool
    {
        return trim((string) $persona->aparencia) !== '' || ! empty($persona->imagem_referencia);
    }

    /**
     * Tenta os modelos de imagem do portal em ordem e devolve a primeira imagem gerada.
     *
     * @return string|null URL (ou caminho no disco `public`) da imagem gerada; null se não houver como gerar.
     */
    public function gerar(Persona $persona, string $mensagem, ?AiModel $modeloTexto = null): ?string
    {
        if (! $this->podeGerar($persona)) {
            return null;
        }

        $modelos = $this->modelosCandidatos((bool) $persona->imagem_referencia);

        if ($modelos === []) {
            Log::warning('Pidgey: nenhum modelo de imagem ativo para gerar o personagem', ['persona_id' => $persona->id]);

            return null;
        }

        $expressao = $this->interpretador->descreverExpressao($persona, $mensagem, $modeloTexto);

        foreach ($modelos as $modelo) {
            $imagem = $this->gerarCom($modelo, $persona, $expressao);

            if ($imagem !== null) {
                return $imagem;
            }

            Log::warning('Pidgey: modelo de imagem falhou, tentando o próximo', ['persona_id' => $persona->id, 'modelo' => $modelo->nome]);
        }

        return null;
    }

    /**
     * Padrão texto→imagem e padrão imagem→imagem do portal, sem repetir. Com imagem de referência,
     * o imagem→imagem vem primeiro (é o único que usa a referência).
     *
     * @return AiModel[]
     */
    private function modelosCandidatos(bool $comReferencia): array
    {
        $textoImagem = $this->provedores->getModelForUserEntity(null, 'text', 'image');
        $imagemImagem = $this->provedores->getImageToImageProvider();

        $ordem = $comReferencia ? [$imagemImagem, $textoImagem] : [$textoImagem, $imagemImagem];

        return collect($ordem)->filter()->unique('id')->values()->all();
    }

    private function gerarCom(AiModel $modelo, Persona $persona, string $expressao): ?string
    {
        $driver = $this->provedores->createImageDriver($modelo);

        if ($driver === null) {
            return null;
        }

        $opcoes = ParametroImagem::para($modelo);

        if ($persona->imagem_referencia && $this->provedores->supportsImageToImageProvider($modelo)) {
            $opcoes['reference_image_path'] = $persona->imagem_referencia;
        }

        try {
            return $driver->generateImage($this->montarPrompt($persona, $expressao, isset($opcoes['reference_image_path'])), $opcoes) ?: null;
        } catch (\Throwable $e) {
            Log::warning('Pidgey: falha ao gerar imagem do personagem', ['persona_id' => $persona->id, 'modelo' => $modelo->nome, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function montarPrompt(Persona $persona, string $expressao, bool $comReferencia = false): string
    {
        $partes = [];

        $partes[] = $comReferencia
            ? "Keep the exact same character from the reference image ({$persona->name}): same face, hair, outfit and art style."
            : "Character illustration of {$persona->name}.";

        if (trim((string) $persona->aparencia) !== '') {
            $partes[] = 'Character design (keep consistent): '.trim((string) $persona->aparencia);
        }

        $partes[] = 'Expression, emotion and pose: '.(trim($expressao) !== '' ? trim($expressao) : 'friendly, natural expression');
        $partes[] = 'Single character, upper body, clean background. No text, no letters, no speech bubbles, no watermark.';

        return implode("\n", $partes);
    }
}
