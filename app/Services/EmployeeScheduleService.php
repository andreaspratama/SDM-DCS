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
        $date = Carbon::parse(
            $tanggal
        )->toDateString();


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
        // 1. CEK WORK CALENDAR / KALDIK
        // BERDASARKAN UNIT PADA TANGGAL TERSEBUT
        // =====================================================
        $calendarStatus =
            $this->workCalendar
                ->isWorkday(
                    $date,
                    $unitId
                );


        // Kaldik secara eksplisit menyatakan LIBUR
        if ($calendarStatus === false) {
            return null;
        }


        $tanggal =
            Carbon::parse($date);


        // =====================================================
        // 2. CEK JADWAL KHUSUS KARYAWAN
        // =====================================================
        $specialSchedule =
            $employee
                ->employeeWorkSchedules()
                ->with('days')
                ->whereDate(
                    'tanggal_mulai',
                    '<=',
                    $tanggal
                )
                ->whereDate(
                    'tanggal_selesai',
                    '>=',
                    $tanggal
                )
                ->latest('id')
                ->first();


        if ($specialSchedule) {

            $day =
                $specialSchedule
                    ->days
                    ->firstWhere(
                        'hari',
                        $tanggal->dayOfWeekIso
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
        // 3. CARI RIWAYAT UNIT + JADWAL
        // BERDASARKAN TANGGAL
        // =====================================================
        $history =
            $employee
                ->unitHistories()
                ->with(
                    'workSchedule.days'
                )
                ->whereDate(
                    'tanggal_mulai',
                    '<=',
                    $date
                )
                ->where(
                    function ($query) use ($date) {

                        $query
                            ->whereNull(
                                'tanggal_selesai'
                            )
                            ->orWhereDate(
                                'tanggal_selesai',
                                '>=',
                                $date
                            );
                    }
                )
                ->orderByDesc(
                    'tanggal_mulai'
                )
                ->first();


        // =====================================================
        // 4. JADWAL DASAR SESUAI PERIODE
        // =====================================================
        $baseSchedule =
            $history?->workSchedule;


        // =====================================================
        // FALLBACK UNTUK PEGAWAI LAMA
        // YANG BELUM PUNYA RIWAYAT UNIT
        // =====================================================
        if (!$baseSchedule) {

            $baseSchedule =
                $employee->workSchedule;
        }


        if (!$baseSchedule) {
            return null;
        }


        $day =
            $baseSchedule
                ->days
                ->firstWhere(
                    'hari',
                    $tanggal->dayOfWeekIso
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
                    ? 'riwayat_unit'
                    : 'dasar',

            'schedule_id' =>
                $baseSchedule->id,
        ];
    }
}