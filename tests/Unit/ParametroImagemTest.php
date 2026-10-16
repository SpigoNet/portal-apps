<?php

namespace Tests\Unit;

use App\Modules\MundosDeMim\Models\ParametroImagem;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ParametroImagemTest extends TestCase
{
    #[Test]
    public function it_keeps_only_valid_known_parameters(): void
    {
        $r = ParametroImagem::sanitizar([
            'quality' => 'high', 'style' => 'invalido', 'output_format' => 'jpg', 'size' => '1024X1536',
            'output_compression' => '80', 'background' => '', 'desconhecido' => 'x',
        ]);

        $this->assertSame([
            'quality' => 'high', 'output_format' => 'jpeg', 'size' => '1024x1536', 'output_compression' => 80,
        ], $r);
    }

    #[Test]
    public function it_rejects_out_of_range_values_and_transparent_jpeg(): void
    {
        $this->assertSame([], ParametroImagem::sanitizar(['size' => '99999x10', 'output_compression' => 150]));
        $this->assertSame(
            ['output_format' => 'jpeg'],
            ParametroImagem::sanitizar(['background' => 'transparent', 'output_format' => 'jpeg'])
        );
        $this->assertSame(['background' => 'transparent'], ParametroImagem::sanitizar(['background' => 'transparent']));
    }
}
