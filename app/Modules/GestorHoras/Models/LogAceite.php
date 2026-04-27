<?php

namespace App\Modules\GestorHoras\Models;

use Illuminate\Database\Eloquent\Model;

class LogAceite extends Model
{
    protected $table = 'logs_aceite';

    protected $fillable = [
        'gh_contrato_id',
        'gh_contrato_item_id',
        'user_id',
        'ip_address',
        'snapshot_hash',
        'snapshot_json',
    ];
}
