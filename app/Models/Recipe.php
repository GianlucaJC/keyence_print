<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'printer_id',
        'name',
        'description',
        'program_number',
        'message_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(RecipeField::class)->orderBy('position');
    }

    public function transmissionLogs(): HasMany
    {
        return $this->hasMany(TransmissionLog::class);
    }
}
