<?php

namespace Tests\Unit\AI;

use App\Services\AI\Drivers\PollinationImageEditDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PollinationImageEditDriverTest extends TestCase
{
    #[Test]
    public function it_generates_an_image_using_the_image_edit_endpoint(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('references/example.png', 'reference-image');

        Http::fake([
            'https://gen.pollinations.ai/v1/images/edits' => Http::response([
                'data' => [
                    ['b64_json' => base64_encode('edited-image')],
                ],
            ], 200),
        ]);

        $driver = new PollinationImageEditDriver('nanobanana', 'test-token');

        $imageUrl = $driver->generateImage('Edit this image', [
            'reference_image_path' => 'references/example.png',
        ]);

        $this->assertNotNull($imageUrl);
        $this->assertStringContainsString('/storage/generations/pollinations-edit_', $imageUrl);

        $storedPath = ltrim((string) parse_url($imageUrl, PHP_URL_PATH), '/');
        $storedPath = preg_replace('#^storage/#', '', $storedPath);

        $this->assertNotNull($storedPath);
        $this->assertTrue(Storage::disk('public')->exists($storedPath));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://gen.pollinations.ai/v1/images/edits'
                && $request->hasHeader('Authorization', 'Bearer test-token');
        });
    }

    #[Test]
    public function it_requires_a_reference_image_for_image_edit_requests(): void
    {
        Http::fake();

        $driver = new PollinationImageEditDriver('nanobanana', 'test-token');

        $imageUrl = $driver->generateImage('Edit this image');

        $this->assertNull($imageUrl);
        Http::assertNothingSent();
    }
}
