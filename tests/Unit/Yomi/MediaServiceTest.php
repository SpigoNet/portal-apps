<?php

namespace Tests\Unit\Yomi;

use App\Modules\Yomi\Exceptions\MediaDownloadException;
use App\Modules\Yomi\Models\Midia;
use App\Modules\Yomi\Services\MediaService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Unit\Yomi\Concerns\RefreshYomiDatabase;

class MediaServiceTest extends TestCase
{
    use RefreshYomiDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshYomiDatabase();

        Storage::fake('local');
        config(['yomi.media.disk' => 'local']);
        config(['yomi.media.timeout' => 5]);
        config(['yomi.media.max_size_mb' => 20]);

        $this->mediaService = app(MediaService::class);
    }

    public function test_download_salva_arquivo_e_metadados(): void
    {
        Http::fake([
            'cdn.example.com/*' => Http::response(base64_decode(self::PNG_1X1), 200, ['Content-Type' => 'image/png']),
        ]);

        $midia = $this->midia('https://cdn.example.com/berserk.jpg');

        $result = $this->mediaService->download($midia->id);

        $this->assertSame('baixada', $result->status);
        $this->assertNotNull($result->storage_path);
        $this->assertSame('image/png', $result->mime_type);
        $this->assertSame(1, $result->width);
        $this->assertSame(1, $result->height);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $result->checksum);
        Storage::disk('local')->assertExists($result->storage_path);
    }

    public function test_mime_invalido_e_rejeitado(): void
    {
        Http::fake([
            'cdn.example.com/*' => Http::response('<html>malware</html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $midia = $this->midia('https://cdn.example.com/invalid.txt');

        $this->expectException(MediaDownloadException::class);

        try {
            $this->mediaService->download($midia->id);
        } finally {
            $this->assertSame('falha', $midia->fresh()->status);
            $this->assertStringContainsString('MIME', (string) $midia->fresh()->error_message);
            Storage::disk('local')->assertDirectoryEmpty('yomi');
        }
    }

    public function test_arquivo_acima_do_limite_e_rejeitado(): void
    {
        config(['yomi.media.max_size_mb' => 1]);

        Http::fake([
            'cdn.example.com/*' => Http::response(str_repeat('x', 1024 * 1024 + 1), 200, ['Content-Type' => 'image/png']),
        ]);

        $midia = $this->midia('https://cdn.example.com/huge.png');

        $this->expectException(MediaDownloadException::class);

        try {
            $this->mediaService->download($midia->id);
        } finally {
            $this->assertSame('falha', $midia->fresh()->status);
            $this->assertStringContainsString('limite', (string) $midia->fresh()->error_message);
        }
    }

    public function test_timeout_de_conexao_marca_como_falha(): void
    {
        Http::fake([
            'cdn.example.com/*' => fn () => throw new ConnectException(
                'Connection timed out',
                new Request('GET', 'https://cdn.example.com/berserk.jpg'),
            ),
        ]);

        $midia = $this->midia('https://cdn.example.com/berserk.jpg');

        $this->expectException(MediaDownloadException::class);

        try {
            $this->mediaService->download($midia->id);
        } finally {
            $this->assertSame('falha', $midia->fresh()->status);
        }
    }

    public function test_download_e_idempotente_e_preserva_midia_existente(): void
    {
        Http::fake([
            'cdn.example.com/*' => Http::response(base64_decode(self::PNG_1X1), 200, ['Content-Type' => 'image/png']),
        ]);

        $midia = $this->midia('https://cdn.example.com/berserk.jpg');
        $this->mediaService->download($midia->id);
        $storagePath = $midia->fresh()->storage_path;

        $this->mediaService->download($midia->id);

        $fresh = $midia->fresh();
        $this->assertSame('baixada', $fresh->status);
        $this->assertSame($storagePath, $fresh->storage_path);
        Storage::disk('local')->assertExists($storagePath);
    }

    public function test_falha_de_uma_midia_nao_afeta_outra_ja_baixada(): void
    {
        Http::fake([
            'cdn.example.com/sucesso.png' => Http::response(base64_decode(self::PNG_1X1), 200, ['Content-Type' => 'image/png']),
            'cdn.example.com/falha.png' => Http::response([], 500),
        ]);

        $ok = $this->midia('https://cdn.example.com/sucesso.png');
        $this->mediaService->download($ok->id);

        $failed = $this->midia('https://cdn.example.com/falha.png');

        try {
            $this->mediaService->download($failed->id);
            $this->fail('Deveria lançar MediaDownloadException');
        } catch (MediaDownloadException) {
            // esperado
        }

        $this->assertSame('baixada', $ok->fresh()->status);
        $this->assertSame('falha', $failed->fresh()->status);
        Storage::disk('local')->assertExists($ok->fresh()->storage_path);
    }

    public function test_checksum_deduplica_arquivos_identicos(): void
    {
        Http::fake([
            'cdn.example.com/a.png' => Http::response(base64_decode(self::PNG_1X1), 200, ['Content-Type' => 'image/png']),
            'cdn.example.com/b.png' => Http::response(base64_decode(self::PNG_1X1), 200, ['Content-Type' => 'image/png']),
        ]);

        $a = $this->midia('https://cdn.example.com/a.png');
        $b = $this->midia('https://cdn.example.com/b.png');

        $this->mediaService->download($a->id);
        $this->mediaService->download($b->id);

        $this->assertSame($a->fresh()->storage_path, $b->fresh()->storage_path);
        $this->assertSame($a->fresh()->checksum, $b->fresh()->checksum);
    }

    private function midia(string $url): Midia
    {
        return $this->mediaService->registerExternal('manga', 1, 'capa', $url);
    }
}
