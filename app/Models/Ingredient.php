<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'is_perishable',
        'shelf_life_days',
        'min_stock',
    ];

    protected $casts = [
        'is_perishable' => 'boolean',
        'shelf_life_days' => 'integer',
        'min_stock' => 'decimal:3',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(IngredientPrice::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }
}
