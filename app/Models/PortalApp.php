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
        'pwa_scope'
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
}
