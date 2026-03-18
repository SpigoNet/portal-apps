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
        if ($this->filledText($this->visual_profile)) {
            return true;
        }

        return $this->filledText($this->body_type)
            && $this->filledText($this->eye_color)
            && $this->filledText($this->hair_type);
    }

    public function promptContextSections(int $maxLength = 320): array
    {
        $sections = [
            'perfil visual' => $this->cleanPromptText($this->visual_profile, $maxLength),
            'jeito e energia' => $this->cleanPromptText($this->personality_vibe, $maxLength),
            'gostos e interesses' => $this->cleanPromptText($this->interests_and_symbols, $maxLength),
            'estilo e roupas' => $this->cleanPromptText($this->style_and_wardrobe, $maxLength),
            'cenarios favoritos' => $this->cleanPromptText($this->favorite_settings, $maxLength),
            'detalhes de identidade' => $this->cleanPromptText($this->identity_details, $maxLength),
        ];

        if (! $sections['perfil visual']) {
            $sections['perfil visual'] = $this->cleanPromptText($this->legacyVisualSummary(), $maxLength);
        }

        return array_filter($sections);
    }

    public function avoidPromptContext(int $maxLength = 240): ?string
    {
        return $this->cleanPromptText($this->avoid_in_generations, $maxLength);
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

    protected function cleanPromptText(?string $value, int $maxLength): ?string
    {
        if (! $this->filledText($value)) {
            return null;
        }

        $text = preg_replace('/\s+/u', ' ', strip_tags($value));
        $text = trim((string) $text);

        if (mb_strlen($text) > $maxLength) {
            $text = rtrim(mb_substr($text, 0, $maxLength - 1)).'…';
        }

        return $text;
    }

    protected function filledText(?string $value): bool
    {
        return trim((string) $value) !== '';
    }
}
