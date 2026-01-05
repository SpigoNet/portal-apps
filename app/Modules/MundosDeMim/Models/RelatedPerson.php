<?php

namespace App\Modules\MundosDeMim\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class RelatedPerson extends Model
{
    protected $table = 'mundos_de_mim_related_people';

    protected $fillable = [
        'user_id',
        'name',         // string [cite: 32]
        'relationship', // string (namorado, filho) [cite: 32]
        'photo_path',   // string [cite: 33]
        'is_active',    // boolean [cite: 34]
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
