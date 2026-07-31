<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Parse bill-month inputs (Y-m / Y-m-d) without day-of-month overflow.
 *
 * Carbon::createFromFormat('Y-m', ...) keeps today's day. On the 31st,
 * "2026-06" becomes June 31 → July 1. Always reset to day 1.
 */
class BillMonth
{
    public static function parse(?string $month, ?Carbon $fallback = null): Carbon
    {
        $timezone = config('app.timezone', 'Asia/Dhaka');

        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return Carbon::createFromFormat('!Y-m', $month, $timezone)->startOfMonth();
        }

        if ($month && preg_match('/^\d{4}-\d{2}-\d{2}$/', $month)) {
            return Carbon::parse($month, $timezone)->startOfMonth();
        }

        return ($fallback?->copy() ?? now($timezone))->startOfMonth();
    }

    public static function label(string $yearMonth): string
    {
        return self::parse($yearMonth)->format('F Y');
    }
}
