<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaIngrediente extends Model
{
    protected $table = 'cardapio_categorias_ingrediente';

    protected $fillable = ['nome', 'ordem'];
}
