<?php

namespace App\Logging;

class ConfigureDhakaTimezone
{
    /**
     * Ensure Monolog records use the application timezone (Asia/Dhaka).
     *
     * @param  \Illuminate\Log\Logger  $logger
     */
    public function __invoke($logger): void
    {
        $timezone = new \DateTimeZone(config('app.timezone', 'Asia/Dhaka'));

        foreach ($logger->getHandlers() as $handler) {
            if (method_exists($handler, 'setTimezone')) {
                $handler->setTimezone($timezone);
            }

            $formatter = method_exists($handler, 'getFormatter') ? $handler->getFormatter() : null;
            if ($formatter && method_exists($formatter, 'setTimezone')) {
                $formatter->setTimezone($timezone);
            }
        }
    }
}
