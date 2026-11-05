<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransmissionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'printer_id',
        'recipe_id',
        'command',
        'response',
        'payload',
        'was_successful',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'was_successful' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
