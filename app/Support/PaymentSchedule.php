<?php

namespace App\Support;

use Carbon\Carbon;

class PaymentSchedule
{
    public static function intervalDays(string $frecuencia): int
    {
        return [
            'Diario' => 1,
            'Semanal' => 7,
            'Quincenal' => 14,
            'Mensual' => 30,
        ][$frecuencia] ?? 30;
    }

    public static function buildDates(
        string $firstDate,
        int $count,
        string $frecuencia,
        bool $descansoDomingos = false,
        bool $useCalendarMonths = false
    ): array {
        if ($count <= 0) {
            return [];
        }

        if ($descansoDomingos && $frecuencia === 'Diario') {
            return self::buildDailyWithoutSundays($firstDate, $count);
        }

        $dates = [];
        $days = self::intervalDays($frecuencia);

        for ($i = 0; $i < $count; $i++) {
            $date = Carbon::parse($firstDate);
            if ($useCalendarMonths && $frecuencia === 'Mensual') {
                $date->addMonths($i);
            } else {
                $date->addDays($days * $i);
            }

            $dates[] = self::avoidSunday($date, $descansoDomingos)->toDateString();
        }

        return $dates;
    }

    public static function lastDate(
        string $firstDate,
        int $count,
        string $frecuencia,
        bool $descansoDomingos = false,
        bool $useCalendarMonths = false
    ): ?string {
        $dates = self::buildDates($firstDate, $count, $frecuencia, $descansoDomingos, $useCalendarMonths);

        return empty($dates) ? null : $dates[array_key_last($dates)];
    }

    private static function buildDailyWithoutSundays(string $firstDate, int $count): array
    {
        $dates = [];
        $date = self::avoidSunday(Carbon::parse($firstDate), true);

        while (count($dates) < $count) {
            $dates[] = $date->toDateString();
            do {
                $date = $date->copy()->addDay();
            } while ($date->isSunday());
        }

        return $dates;
    }

    private static function avoidSunday(Carbon $date, bool $enabled): Carbon
    {
        return $enabled && $date->isSunday() ? $date->copy()->addDay() : $date;
    }
}
