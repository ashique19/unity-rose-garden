<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GasMeterReading extends Model
{
    protected $fillable = [
        'flat_id',
        'bill_month',
        'reading_date',
        'previous_m3',
        'current_m3',
        'confirmed_m3',
        'photo_path',
        'gemini_suggestion',
    ];

    protected function casts(): array
    {
        return [
            'bill_month' => 'date',
            'reading_date' => 'date',
            'previous_m3' => 'decimal:2',
            'current_m3' => 'decimal:2',
            'confirmed_m3' => 'decimal:2',
            'gemini_suggestion' => 'decimal:2',
        ];
    }

    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }

    public function consumedM3(): float
    {
        return max(0, (float) $this->current_m3 - (float) $this->previous_m3);
    }

    /**
     * Photo uploads create draft rows; a reading is confirmed once an admin saves the m³ value.
     */
    public function isConfirmed(): bool
    {
        return $this->confirmed_m3 !== null;
    }
}
