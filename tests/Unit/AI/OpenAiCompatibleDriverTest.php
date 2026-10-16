<?php

namespace Tests\Unit\AI;

use App\Models\AiProvider;
use App\Services\AI\Drivers\OpenAiCompatibleDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OpenAiCompatibleDriverTest extends TestCase
{
    #[Test]
    public function it_generates_text_through_chat_completions(): void
    {
        Http::fake(['gateway.test/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Olá!']]],
        ])]);

        $driver = new OpenAiCompatibleDriver('gpt-6-sol', 'segredo', 'http://gateway.test/v1');

        $this->assertSame('Olá!', $driver->generateText([['role' => 'user', 'content' => 'Oi']]));

        Http::assertSent(fn ($request) => $request->url() === 'http://gateway.test/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer segredo')
            && $request['model'] === 'gpt-6-sol');
    }

    #[Test]
    public function it_generates_an_image_and_stores_it_on_the_public_disk(): void
    {
        Storage::fake('public');
        Http::fake(['gateway.test/v1/images/generations' => Http::response([
            'data' => [['b64_json' => base64_encode('png-bytes')]],
        ])]);

        $url = (new OpenAiCompatibleDriver(null, null, 'http://gateway.test'))->generateImage('Um gato');

        $this->assertNotNull($url);
        $files = Storage::disk('public')->allFiles('generations');
        $this->assertCount(1, $files);
        $this->assertSame('png-bytes', Storage::disk('public')->get($files[0]));
    }

    #[Test]
    public function it_edits_the_reference_image_through_the_edits_endpoint(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('fotos/eu.png', 'foto');
        Http::fake(['gateway.test/v1/images/edits' => Http::response([
            'data' => [['b64_json' => base64_encode('editada')]],
        ])]);

        $url = (new OpenAiCompatibleDriver(null, null, 'http://gateway.test'))
            ->generateImage('Sofá verde', ['reference_image_path' => 'fotos/eu.png']);

        $this->assertNotNull($url);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/images/edits') && $request->isMultipart());
    }

    #[Test]
    public function it_forwards_image_parameters_to_the_gateway(): void
    {
        Storage::fake('public');
        Http::fake(['gateway.test/v1/images/generations' => Http::response([
            'data' => [['b64_json' => base64_encode('jpg-bytes')]],
        ])]);

        $url = (new OpenAiCompatibleDriver(null, null, 'http://gateway.test'))->generateImage('Um gato', [
            'quality' => 'high', 'background' => 'opaque', 'output_format' => 'jpeg',
            'output_compression' => 0, 'style' => 'natural', 'size' => '1024x1536', 'ignorado' => 'x',
        ]);

        $this->assertStringEndsWith('.jpg', $url);
        Http::assertSent(fn ($request) => $request['quality'] === 'high'
            && $request['background'] === 'opaque'
            && $request['output_format'] === 'jpeg'
            && $request['output_compression'] === 0
            && $request['style'] === 'natural'
            && $request['size'] === '1024x1536'
            && ! isset($request['ignorado']));
    }

    #[Test]
    public function it_returns_null_when_the_gateway_fails(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'falhou']], 502)]);

        $this->assertNull((new OpenAiCompatibleDriver(null, null, 'http://gateway.test'))->generateImage('x'));
    }

    #[Test]
    public function it_guesses_the_openai_driver_from_provider_metadata(): void
    {
        $this->assertSame('openai', AiProvider::guessDriver(null, 'Codex Gateway'));
        $this->assertSame('openai', AiProvider::guessDriver(null, null, null, 'http://spigo-codex-gateway:8000'));
    }
}
