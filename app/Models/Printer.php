<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Printer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'host',
        'port',
        'protocol_format',
        'checksum_enabled',
        'timeout_ms',
        'is_active',
    ];

    protected $casts = [
        'checksum_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function transmissionLogs(): HasMany
    {
        return $this->hasMany(TransmissionLog::class);
    }
}
