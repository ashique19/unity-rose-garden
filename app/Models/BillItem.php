<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    protected $fillable = ['bill_detail_id', 'item_type', 'item_label', 'quantity', 'rate', 'amount'];

    public function billDetail(): BelongsTo
    {
        return $this->belongsTo(BillDetail::class, 'bill_detail_id');
    }
}