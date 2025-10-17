<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'notes',
    ];

    public function ingredientPrices(): HasMany
    {
        return $this->hasMany(IngredientPrice::class);
    }
}
