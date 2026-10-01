<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;

class EmployeeScheduleService
{
    protected WorkCalendarService $workCalendar;
    protected EmployeeUnitService $employeeUnitService;

    public function __construct(
        WorkCalendarService $workCalendar,
        EmployeeUnitService $employeeUnitService
    ) {
        $this->workCalendar = $workCalendar;
        $this->employeeUnitService = $employeeUnitService;
    }


public function getSchedule(
    Employee $employee,
    $tanggal
): ?array {

    // =====================================================
    // NORMALISASI TANGGAL
    // =====================================================
    $date = Carbon::parse($tanggal)->toDateString();

    // =====================================================
    // UNIT PEGAWAI PADA TANGGAL TERSEBUT
    // =====================================================
    $unitId =
        $this->employeeUnitService
            ->getUnitId(
                $employee,
                $date
            );

    // =====================================================
    // KALDIK
    // =====================================================
    $calendarStatus =
        $this->workCalendar
            ->isWorkday(
                $date,
                $unitId
            );

    // Secara eksplisit libur
    if ($calendarStatus === false) {
        return null;
    }

    $tanggalCarbon =
        Carbon::parse($date);

    // =====================================================
    // 1. JADWAL KHUSUS PEGAWAI
    //
    // Sudah eager-loaded oleh controller
    // =====================================================
    $specialSchedule =
        $employee
            ->employeeWorkSchedules
            ->filter(function ($schedule) use ($date) {

                return
                    $schedule->tanggal_mulai
                        ->toDateString()
                    <= $date

                    &&

                    $schedule->tanggal_selesai
                        ->toDateString()
                    >= $date;
            })
            ->sortByDesc('id')
            ->first();

    if ($specialSchedule) {

        $day =
            $specialSchedule
                ->days
                ->firstWhere(
                    'hari',
                    $tanggalCarbon->dayOfWeekIso
                );

        if (
            !$day
            ||
            $day->is_libur
        ) {
            return null;
        }

        return [
            'jam_masuk' =>
                $day->jam_masuk,

            'jam_pulang' =>
                $day->jam_pulang,

            'sumber' =>
                'khusus',

            'schedule_id' =>
                $specialSchedule->id,
        ];
    }

    // =====================================================
    // 2. JADWAL BERDASARKAN HISTORI UNIT
    //
    // Sudah eager-loaded
    // =====================================================
    $history =
        $employee
            ->unitHistories
            ->filter(function ($history) use ($date) {

                return
                    $history->tanggal_mulai
                        ->toDateString()
                    <= $date

                    &&

                    (
                        is_null(
                            $history->tanggal_selesai
                        )

                        ||

                        $history->tanggal_selesai
                            ->toDateString()
                        >= $date
                    );
            })
            ->sortByDesc(
                'tanggal_mulai'
            )
            ->first();

    // =====================================================
    // 3. JADWAL DASAR
    // =====================================================
    $baseSchedule =
        $history?->workSchedule
        ?: $employee->workSchedule;

    if (!$baseSchedule) {
        return null;
    }

    $day =
        $baseSchedule
            ->days
            ->firstWhere(
                'hari',
                $tanggalCarbon->dayOfWeekIso
            );

    if (
        !$day
        ||
        $day->is_libur
    ) {
        return null;
    }

    return [
        'jam_masuk' =>
            $day->jam_masuk,

        'jam_pulang' =>
            $day->jam_pulang,

        'sumber' =>
            $history
                ? 'histori'
                : 'dasar',

        'schedule_id' =>
            $baseSchedule->id,
    ];
}
}