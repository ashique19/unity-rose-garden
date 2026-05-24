<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MeterReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'flat_id',
        'reading_date',
        'reading_unit',
    ];

    protected $casts = [
        'reading_date' => 'date',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (MeterReading $meterReading) {
            if ($meterReading->reading_date) {
                // Ensure we are working with a Carbon instance
                $date = Carbon::parse($meterReading->reading_date);

                // Find and delete any existing record for this flat in the same month/year
                static::where('flat_id', $meterReading->flat_id)
                    ->whereYear('reading_date', $date->year)
                    ->whereMonth('reading_date', $date->month)
                    ->delete();
            }
        });
    }

    /**
     * Get the flat that owns the meter reading.
     */
    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }
}