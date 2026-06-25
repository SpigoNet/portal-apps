<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Alfred\Models\ConsumoAgua;
use App\Modules\Alfred\Models\LogDiaRuim;
use App\Modules\Alfred\Models\Medicamento;
use App\Modules\Alfred\Models\Rotina;
use App\Modules\Alfred\Models\UserProfile;
use App\Modules\GestorHoras\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'google_id',
        'microsoft_id',
        'avatar',
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

    public function aiModelDefaults()
    {
        return $this->hasMany(\App\Models\AiUserModelDefault::class);
    }

    public function isProfessor(): bool
    {
        $config = \App\Modules\ANT\Models\AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y').'-'.(date('m') > 6 ? '2' : '1');

        return \Illuminate\Support\Facades\DB::table('ant_professor_materia')
            ->where('user_id', $this->id)
            ->where('semestre', $semestreAtual)
            ->exists();
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function medicamentos(): HasMany
    {
        return $this->hasMany(Medicamento::class);
    }

    public function consumoAgua(): HasMany
    {
        return $this->hasMany(ConsumoAgua::class);
    }

    public function logsDiaRuim(): HasMany
    {
        return $this->hasMany(LogDiaRuim::class);
    }

    public function rotinas(): HasMany
    {
        return $this->hasMany(Rotina::class);
    }

    public function getTreettaskUrl(): string
    {
        return $this->profile?->treetask_url ?? config('services.treetask.url', 'https://apps.spigo.net');
    }

    public function getTreettaskUserId(): string
    {
        return $this->profile?->treetask_user_id ?? config('services.treetask.user_id', '1');
    }

    public function getTreettaskToken(): string
    {
        return $this->profile?->treetask_token ?? config('services.treetask.token', '');
    }

    public function getMetaAguaMl(): int
    {
        return $this->profile?->meta_agua_ml ?? 2500;
    }

    public function getEnergiaAtual(): string
    {
        return $this->profile?->energia_atual ?? 'media';
    }

    public function isModoDiaRuim(): bool
    {
        return $this->profile?->modo_dia_ruim ?? false;
    }
}
