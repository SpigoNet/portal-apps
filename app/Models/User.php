<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\GestorHoras\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',  // Adicionado
        'microsoft_id', // Adicionado
        'avatar',     // Adicionado
        'whatsapp_phone',
        'whatsapp_apikey',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function portalApps()
    {
        return $this->belongsToMany(\App\Models\PortalApp::class, 'portal_app_user');
    }


    public function clienteGestorHoras()
    {
        return $this->belongsTo(Cliente::class, 'gh_cliente_id');
    }

    public function streamings()
    {
        return $this->hasMany(\App\Modules\StreamingManager\Models\Streaming::class);
    }

    public function isProfessor(): bool
    {
        $config = \App\Modules\ANT\Models\AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        return \Illuminate\Support\Facades\DB::table('ant_professor_materia')
            ->where('user_id', $this->id)
            ->where('semestre', $semestreAtual)
            ->exists();
    }
}

