<?php

namespace Tests\Unit\MundosDeMim;

use App\Modules\MundosDeMim\Models\UserAttribute;
use PHPUnit\Framework\TestCase;

class UserAttributeProfileTest extends TestCase
{
    public function test_it_builds_prompt_sections_from_rich_profile(): void
    {
        $attribute = new UserAttribute([
            'visual_profile' => "  Pele morena clara,\n olhos castanhos e cabelo cacheado escuro.  ",
            'personality_vibe' => 'Calma, criativa e acolhedora.',
            'style_and_wardrobe' => 'Vestidos fluidos, tons terrosos e acessórios discretos.',
            'favorite_settings' => 'Florestas com neblina e cafés aconchegantes.',
            'avoid_in_generations' => 'Neon exagerado e terror.',
        ]);

        $sections = $attribute->promptContextSections();

        $this->assertTrue($attribute->hasCompleteProfile());
        $this->assertSame('Pele morena clara, olhos castanhos e cabelo cacheado escuro.', $sections['perfil visual']);
        $this->assertSame('Calma, criativa e acolhedora.', $sections['jeito e energia']);
        $this->assertSame('Vestidos fluidos, tons terrosos e acessórios discretos.', $sections['estilo e roupas']);
        $this->assertSame('Florestas com neblina e cafés aconchegantes.', $sections['cenarios favoritos']);
        $this->assertSame('Neon exagerado e terror.', $attribute->avoidPromptContext());
    }

    public function test_it_falls_back_to_legacy_visual_summary_when_visual_profile_is_missing(): void
    {
        $attribute = new UserAttribute([
            'body_type' => 'atlético',
            'eye_color' => 'castanhos',
            'hair_type' => 'cacheado preto',
            'height' => 180,
            'weight' => 78.5,
        ]);

        $sections = $attribute->promptContextSections();

        $this->assertTrue($attribute->hasCompleteProfile());
        $this->assertArrayHasKey('perfil visual', $sections);
        $this->assertStringContainsString('olhos castanhos', $sections['perfil visual']);
        $this->assertStringContainsString('cabelo cacheado preto', $sections['perfil visual']);
        $this->assertStringContainsString('tipo fisico atlético', $sections['perfil visual']);
        $this->assertStringContainsString('altura aproximada de 180 cm', $sections['perfil visual']);
        $this->assertStringContainsString('peso aproximado de 78.5 kg', $sections['perfil visual']);
    }
}
