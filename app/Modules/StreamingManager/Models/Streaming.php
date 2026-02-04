<?php

namespace App\Modules\StreamingManager\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Streaming extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'username',
        'password',
        'monthly_cost',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(StreamingMember::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StreamingPayment::class);
    }

    public function getBalanceAttribute(): float
    {
        return $this->payments()->where('status', 'approved')->sum('amount');
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->monthly_cost <= 0) {
            return 0;
        }

        $dailyCost = $this->monthly_cost / 30;
        return (int) ($this->balance / $dailyCost);
    }

    public function getFundsUntilAttribute(): string
    {
        $days = $this->daysRemaining;
        return now()->addDays($days)->format('d/m/Y');
    }
}
