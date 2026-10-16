<?php

namespace Tests\Unit;

use App\Modules\Cardapio\Support\UnidadeConversor;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CardapioUnidadeConversorTest extends TestCase
{
    public function test_converte_para_a_unidade_base(): void
    {
        $this->assertSame(2000.0, UnidadeConversor::paraBase(2, 'kg'));
        $this->assertSame(1500.0, UnidadeConversor::paraBase(1.5, 'l'));
        $this->assertSame(12.0, UnidadeConversor::paraBase(1, 'duzia'));
        $this->assertSame(3.0, UnidadeConversor::paraBase(3, 'pacote'));
    }

    public function test_compatibilidade_com_a_base(): void
    {
        $this->assertTrue(UnidadeConversor::compativel('kg', 'g'));
        $this->assertFalse(UnidadeConversor::compativel('l', 'g'));
        $this->assertFalse(UnidadeConversor::compativel('xyz', 'g'));
    }

    public function test_formata_de_forma_legivel(): void
    {
        $this->assertSame('1,5 kg', UnidadeConversor::formatar(1500, 'g'));
        $this->assertSame('500 g', UnidadeConversor::formatar(500, 'g'));
        $this->assertSame('2 L', UnidadeConversor::formatar(2000, 'ml'));
        $this->assertSame('6 unidade', UnidadeConversor::formatar(6, 'un'));
    }

    public function test_unidade_desconhecida_falha(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UnidadeConversor::paraBase(1, 'xyz');
    }
}
