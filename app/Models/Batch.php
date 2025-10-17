<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Batch extends Model
{
    protected $fillable = [
        'ingredient_id',
        'quantity',
        'received_at',
        'expires_at',
        'unit_cost',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'received_at' => 'date',
        'expires_at' => 'date',
        'unit_cost' => 'decimal:4',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
