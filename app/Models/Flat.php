<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flat extends Model
{
    protected $fillable = ['name'];


    public function meterReeadings()
    {
        return $this->hasMany('\App\MeterReading');
    }
}
