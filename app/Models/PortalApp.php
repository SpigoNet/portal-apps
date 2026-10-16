<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalApp extends Model
{
    protected $fillable = [
        'package_id',
        'title',
        'description',
        'icon', // Agora deve ser o caminho do arquivo (ex: /storage/icons/tarefas.png)
        'start_link',
        'images',
        'visibility',
        // Campos PWA
        'pwa_short_name',
        'pwa_background_color',
        'pwa_theme_color',
        'pwa_display',
        'pwa_orientation',
        'pwa_scope',
    ];

    public function package()
    {
        return $this->belongsTo(\App\Models\Package::class);
    }

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'portal_app_user')->withPivot('role');
    }

    /**
     * Helper para pegar a URL do manifesto deste app específico
     */
    public function getManifestUrlAttribute()
    {
        return route('pwa.manifest', ['id' => $this->id]);
    }

    /**
     * Caminho (relativo a public/) do ícone do app, ou null se o campo `icon`
     * não apontar para um arquivo existente (ex.: valores antigos "fa-solid ...").
     */
    public function getIconFileAttribute(): ?string
    {
        $icone = ltrim((string) $this->icon, '/');

        if ($icone === '' || ! str_contains($icone, '/') || ! is_file(public_path($icone))) {
            return null;
        }

        return $icone;
    }

    /** URL absoluta do ícone, com o ícone padrão do portal como fallback. */
    public function getIconUrlAttribute(): string
    {
        return asset($this->icon_file ?? 'apple-touch-icon.png');
    }

    /** Caminho (relativo a public/) da versão "maskable" do ícone, se existir. */
    public function getMaskableIconFileAttribute(): ?string
    {
        if ($this->icon_file === null) {
            return null;
        }

        $maskable = 'images/apps/maskable/'.basename($this->icon_file);

        return is_file(public_path($maskable)) ? $maskable : null;
    }
}
