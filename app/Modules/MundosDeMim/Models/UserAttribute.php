<?php

namespace App\Modules\MundosDeMim\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserAttribute extends Model
{
    protected $table = 'mundos_de_mim_user_attributes';

    protected $fillable = [
        'user_id',
        'photo_path',
        'visual_profile',
        'gender_identity',
        'notification_preference',
        'height',     // float
        'weight',     // float
        'body_type',  // string
        'eye_color',  // string
        'hair_type',  // string
        'personality_vibe',
        'interests_and_symbols',
        'style_and_wardrobe',
        'favorite_settings',
        'identity_details',
        'avoid_in_generations',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hasCompleteProfile(): bool
    {
        if ($this->filledText($this->visual_profile) && $this->filledText($this->gender_identity)) {
            return true;
        }

        return $this->filledText($this->body_type)
            && $this->filledText($this->eye_color)
            && $this->filledText($this->hair_type);
    }

    public function promptContextSections(): array
    {
        $sections = [
            'genero com que se identifica' => $this->cleanPromptText($this->gender_identity),
            'perfil visual' => $this->cleanPromptText($this->visual_profile),
            'jeito e energia' => $this->cleanPromptText($this->personality_vibe),
            'gostos e interesses' => $this->cleanPromptText($this->interests_and_symbols),
            'estilo e roupas' => $this->cleanPromptText($this->style_and_wardrobe),
            'cenarios favoritos' => $this->cleanPromptText($this->favorite_settings),
            'detalhes de identidade' => $this->cleanPromptText($this->identity_details),
        ];

        if (! $sections['perfil visual']) {
            $sections['perfil visual'] = $this->cleanPromptText($this->legacyVisualSummary());
        }

        return array_filter($sections);
    }

    public function avoidPromptContext(): ?string
    {
        return $this->cleanPromptText($this->avoid_in_generations);
    }

    public function buildImageGenerationPrompt(?string $userPrompt): string
    {
        $request = $this->cleanPromptText($userPrompt)
            ?? 'Crie uma nova imagem personalizada inspirada nesta pessoa.';

        $parts = [
            'Use a imagem de referencia anexada para preservar a mesma pessoa, mantendo rosto, cabelo, expressao e tracos unicos.',
            'Pedido principal: '.$request,
        ];

        $profileDetails = [];
        foreach ($this->promptContextSections(150) as $label => $content) {
            $profileDetails[] = "{$label}: {$content}";
        }

        if ($avoid = $this->avoidPromptContext(120)) {
            $profileDetails[] = "evitar: {$avoid}";
        }

        if ($profileDetails !== []) {
            $parts[] = 'Perfil do usuario: '.implode('; ', $profileDetails).'.';
        }

        $parts[] = 'Resultado final com alta qualidade, aparencia coerente com o perfil, composicao bem resolvida e identidade preservada.';

        return trim(implode(' ', $parts));
    }

    protected function legacyVisualSummary(): ?string
    {
        $parts = [];

        if ($this->filledText($this->eye_color)) {
            $parts[] = 'olhos '.$this->eye_color;
        }

        if ($this->filledText($this->hair_type)) {
            $parts[] = 'cabelo '.$this->hair_type;
        }

        if ($this->filledText($this->body_type)) {
            $parts[] = 'tipo fisico '.$this->body_type;
        }

        if ($this->height) {
            $parts[] = 'altura aproximada de '.rtrim(rtrim(number_format((float) $this->height, 1, '.', ''), '0'), '.').' cm';
        }

        if ($this->weight) {
            $parts[] = 'peso aproximado de '.rtrim(rtrim(number_format((float) $this->weight, 1, '.', ''), '0'), '.').' kg';
        }

        return empty($parts) ? null : implode(', ', $parts);
    }

    protected function cleanPromptText(?string $value): ?string
    {
        if (! $this->filledText($value)) {
            return null;
        }

        $text = preg_replace('/\s+/u', ' ', strip_tags($value));

        return trim((string) $text);
    }

    protected function filledText(?string $value): bool
    {
        return trim((string) $value) !== '';
    }
}
