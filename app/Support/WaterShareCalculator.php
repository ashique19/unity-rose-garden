<?php

namespace App\Support;

use InvalidArgumentException;

class WaterShareCalculator
{
    /**
     * Split a common building bill equally among participating flats.
     *
     * @throws InvalidArgumentException when no flats participate
     */
    public static function share(float|string $total, int $enabledFlatCount, string $label = 'common bill'): string
    {
        if ($enabledFlatCount <= 0) {
            throw new InvalidArgumentException("Cannot split {$label}: no enabled flats.");
        }

        $share = (float) $total / $enabledFlatCount;

        return number_format($share, 2, '.', '');
    }
}
