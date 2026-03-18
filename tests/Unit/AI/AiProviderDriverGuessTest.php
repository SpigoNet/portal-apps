<?php

namespace Tests\Unit\AI;

use App\Models\AiProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiProviderDriverGuessTest extends TestCase
{
    #[Test]
    public function it_detects_pollinations_image_edit_as_a_separate_driver(): void
    {
        $driver = AiProvider::guessDriver(
            null,
            'Pollinations Image Edit',
            'https://gen.pollinations.ai/v1/images/edits',
            'https://gen.pollinations.ai'
        );

        $this->assertSame('pollination_image_edit', $driver);
    }

    #[Test]
    public function it_keeps_classic_pollination_detection_unchanged(): void
    {
        $driver = AiProvider::guessDriver(
            null,
            'Pollinations',
            'https://gen.pollinations.ai/image/models',
            'https://gen.pollinations.ai'
        );

        $this->assertSame('pollination', $driver);
    }
}
