<?php

namespace App\Services;

use App\Models\WorkCalendarDate;
use Carbon\Carbon;

class WorkCalendarService
{
    /**
     * Cari override kalender kerja untuk tanggal tertentu.
     *
     * Prioritas:
     * 1. Kalender khusus unit
     * 2. Kalender umum (unit_id NULL)
     */
    public function getCalendar(string $date, ?int $unitId = null): ?WorkCalendarDate
    {
        $date = Carbon::parse($date)->toDateString();

        // =====================================================
        // 1. CARI KHUSUS UNIT
        // =====================================================
        if ($unitId) {

            $unitCalendar = WorkCalendarDate::whereDate('date', $date)
                ->where('unit_id', $unitId)
                ->first();

            if ($unitCalendar) {
                return $unitCalendar;
            }
        }

        // =====================================================
        // 2. CARI YANG BERLAKU UNTUK SEMUA UNIT
        // =====================================================
        return WorkCalendarDate::whereDate('date', $date)
            ->whereNull('unit_id')
            ->first();
    }


    /**
     * Apakah tanggal merupakan hari kerja menurut kaldik?
     *
     * Return:
     * true  = secara eksplisit hari kerja
     * false = secara eksplisit libur
     * null  = tidak ada override di kaldik
     */
    public function isWorkday(string $date, ?int $unitId = null): ?bool
    {
        $calendar = $this->getCalendar(
            $date,
            $unitId
        );

        if (!$calendar) {
            return null;
        }

        return (bool) $calendar->is_workday;
    }


    /**
     * Apakah tanggal secara eksplisit libur?
     */
    public function isHoliday(string $date, ?int $unitId = null): bool
    {
        return $this->isWorkday(
            $date,
            $unitId
        ) === false;
    }
}
