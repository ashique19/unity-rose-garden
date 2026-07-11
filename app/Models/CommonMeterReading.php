<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommonMeterReading extends Model
{
    protected $fillable = [
        'meter_key',
        'bill_month',
        'total_amount',
        'previous_reading',
        'current_reading',
        'reading_date',
        'photo_path',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'bill_month' => 'date',
            'reading_date' => 'date',
            'total_amount' => 'decimal:2',
            'previous_reading' => 'decimal:2',
            'current_reading' => 'decimal:2',
        ];
    }
}
