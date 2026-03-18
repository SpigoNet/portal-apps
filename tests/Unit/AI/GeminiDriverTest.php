<?php

namespace Tests\Unit\AI;

use App\Services\AI\Drivers\GeminiDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GeminiDriverTest extends TestCase
{
    #[Test]
    public function it_generates_an_image_from_prompt_and_reference_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('references/cat.png', 'cat-image');

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:streamGenerateContent?key=test-key' => Http::response([
                [
                    'candidates' => [[
                        'content' => [
                            'parts' => [
                                ['text' => 'Image saved'],
                            ],
                        ],
                    ]],
                ],
                [
                    'candidates' => [[
                        'content' => [
                            'parts' => [
                                ['inlineData' => [
                                    'mimeType' => 'image/png',
                                    'data' => base64_encode('generated-image'),
                                ]],
                            ],
                        ],
                    ]],
                ],
            ], 200),
        ]);

        $driver = new GeminiDriver('test-key', 'gemini-2.5-flash');

        $imageUrl = $driver->generateImage('Create a picture of my cat eating a nano-banana', [
            'reference_image_path' => 'references/cat.png',
        ]);

        $this->assertNotNull($imageUrl);
        $this->assertStringContainsString('/storage/generations/gemini_', $imageUrl);

        $storedPath = preg_replace('#^storage/#', '', ltrim((string) parse_url($imageUrl, PHP_URL_PATH), '/'));
        $this->assertNotNull($storedPath);
        $this->assertTrue(Storage::disk('public')->exists($storedPath));

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:streamGenerateContent?key=test-key'
                && data_get($payload, 'generationConfig.responseModalities') === ['IMAGE', 'TEXT'];
        });
    }
}
