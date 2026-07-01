<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Flat extends Model
{
    protected $fillable = ['name', 'status'];


    public function meterReeadings()
    {
        return $this->hasMany('\App\MeterReading');
    }

    /**
     * Get all custom/exceptional charges assigned to this flat.
     */
    public function customCharges(): HasMany
    {
        return $this->hasMany(CustomCharge::class, 'flat_id');
    }
    
}
