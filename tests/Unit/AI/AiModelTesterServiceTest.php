<?php

namespace Tests\Unit\AI;

use App\Models\AiModel;
use App\Models\AiModelDefault;
use App\Models\AiProvider;
use App\Modules\Admin\Services\AiModelTesterService;
use App\Modules\Admin\Services\AiProviderService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiModelTesterServiceTest extends TestCase
{
    private function padrao(string $input, string $output): AiModelDefault
    {
        $provider = new AiProvider([
            'nome' => 'Gateway',
            'driver' => 'openai',
            'base_url' => 'http://gateway.test',
            'is_active' => true,
        ]);
        $model = new AiModel([
            'nome' => 'gpt',
            'modelo_id_externo' => 'gpt-test',
            'is_active' => true,
        ]);
        $model->setRelation('provedor', $provider);

        $padrao = new AiModelDefault(['input_type' => $input, 'output_type' => $output]);
        $padrao->setRelation('modelo', $model);

        return $padrao;
    }

    #[Test]
    public function it_reports_ok_when_text_model_answers(): void
    {
        Http::fake(['gateway.test/*' => Http::response(['choices' => [['message' => ['content' => 'OK']]]])]);

        $resultado = (new AiModelTesterService(new AiProviderService))->testar($this->padrao('text', 'text'));

        $this->assertSame('ok', $resultado['status']);
    }

    #[Test]
    public function it_reports_error_with_log_detail_when_text_model_fails(): void
    {
        Http::fake(['gateway.test/*' => Http::response('boom', 500)]);

        $resultado = (new AiModelTesterService(new AiProviderService))->testar($this->padrao('text', 'text'));

        $this->assertSame('erro', $resultado['status']);
        $this->assertStringContainsString('500', (string) $resultado['detalhe']);
    }

    #[Test]
    public function it_skips_types_without_automatic_test(): void
    {
        $resultado = (new AiModelTesterService(new AiProviderService))->testar($this->padrao('image', 'text'));

        $this->assertSame('ignorado', $resultado['status']);
    }

    #[Test]
    public function ollama_driver_does_not_duplicate_v1_when_base_url_already_has_it(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'OK']]]])]);

        $resposta = (new \App\Services\AI\Drivers\OllamaDriver('sonnet', null, 'http://gateway.test:8000/v1'))
            ->generateText([['role' => 'user', 'content' => 'oi']]);

        $this->assertSame('OK', $resposta);
        Http::assertSent(fn ($request) => $request->url() === 'http://gateway.test:8000/v1/chat/completions');
    }
}
