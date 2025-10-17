<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientPrice extends Model
{
    protected $fillable = [
        'ingredient_id',
        'supplier_id',
        'price',
        'purchase_unit_quantity',
        'purchase_unit',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'purchase_unit_quantity' => 'decimal:3',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
