<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeField extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',
        'key',
        'label',
        'input_type',
        'default_value',
        'validation_rules',
        'is_required',
        'position',
        'layout_x',
        'layout_y',
        'layout_width',
        'layout_height',
        'font_size',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'layout_x' => 'integer',
        'layout_y' => 'integer',
        'layout_width' => 'integer',
        'layout_height' => 'integer',
        'font_size' => 'integer',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
