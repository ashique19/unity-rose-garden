<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flat extends Model
{
    protected $fillable = [
        'name',
        'contact_name',
        'phone',
        'status',
    ];

    public function meterReeadings()
    {
        return $this->hasMany(MeterReading::class);
    }

    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function customCharges(): HasMany
    {
        return $this->hasMany(CustomCharge::class, 'flat_id');
    }

    public function billTypeSettings(): HasMany
    {
        return $this->hasMany(FlatBillTypeSetting::class);
    }

    public function monthlyStatements(): HasMany
    {
        return $this->hasMany(MonthlyStatement::class);
    }

    public function gasMeterReadings(): HasMany
    {
        return $this->hasMany(GasMeterReading::class);
    }

    public function statementForMonth(string $billMonth): ?MonthlyStatement
    {
        return $this->monthlyStatements()
            ->whereDate('bill_month', $billMonth)
            ->with(['lines', 'collections'])
            ->first();
    }

    public function isBillTypeEnabled(string $billTypeKey): bool
    {
        $setting = $this->billTypeSettings()
            ->whereHas('billType', fn ($q) => $q->where('key', $billTypeKey))
            ->first();

        return $setting?->enabled ?? true;
    }
}
