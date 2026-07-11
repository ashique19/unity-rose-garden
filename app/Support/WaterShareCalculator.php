<?php

namespace App\Support;

use InvalidArgumentException;

class WaterShareCalculator
{
    /**
     * Split a common water bill equally among water-enabled flats.
     *
     * @throws InvalidArgumentException when no flats participate
     */
    public static function share(float|string $total, int $enabledFlatCount): string
    {
        if ($enabledFlatCount <= 0) {
            throw new InvalidArgumentException('Cannot split water bill: no water-enabled flats.');
        }

        $share = (float) $total / $enabledFlatCount;

        return number_format($share, 2, '.', '');
    }
}
