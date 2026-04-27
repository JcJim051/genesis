<?php

namespace App\Services\Ipt;

use App\Models\ColombiaHoliday;
use Carbon\Carbon;

class BusinessDayService
{
    public function nextBusinessDay(Carbon $date): Carbon
    {
        $candidate = $date->copy();

        while ($this->isNonBusinessDay($candidate)) {
            $candidate->addDay();
        }

        return $candidate;
    }

    public function addMonthsAndAdjust(Carbon $baseDate, int $months): Carbon
    {
        $candidate = $baseDate->copy()->addMonthsNoOverflow(max(0, $months));

        return $this->nextBusinessDay($candidate);
    }

    public function isNonBusinessDay(Carbon $date): bool
    {
        if ($date->isWeekend()) {
            return true;
        }

        return ColombiaHoliday::query()
            ->whereDate('fecha', $date->toDateString())
            ->where('activo', true)
            ->exists();
    }
}
