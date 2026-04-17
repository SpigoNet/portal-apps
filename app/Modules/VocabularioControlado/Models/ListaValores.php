<?php

namespace App\Modules\VocabularioControlado\Models;

use Illuminate\Database\Eloquent\Model;

class ListaValores extends Model
{
    protected $table = 'listaValores';

    protected $primaryKey = 'idValor';

    public $timestamps = false;

    protected $fillable = ['value_pairs_name', 'stored_value', 'displayed_value'];

    public static function byLista(string $nomeLista): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('value_pairs_name', $nomeLista)
            ->orderBy('displayed_value')
            ->get();
    }
}
