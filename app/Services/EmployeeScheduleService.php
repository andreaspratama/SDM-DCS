<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;

class EmployeeScheduleService
{
    protected WorkCalendarService $workCalendar;

    public function __construct(WorkCalendarService $workCalendar)
    {
        $this->workCalendar = $workCalendar;
    }

    public function getSchedule(Employee $employee, $tanggal): ?array
    {
        // =====================================================
        // NORMALISASI TANGGAL
        // =====================================================
        $date = Carbon::parse($tanggal)->toDateString();

        // =====================================================
        // 1. CEK WORK CALENDAR / KALDIK BARU
        // =====================================================
        $calendarStatus = $this->workCalendar->isWorkday(
            $date,
            $employee->unit_id
        );

        // Kaldik secara eksplisit menyatakan LIBUR
        if ($calendarStatus === false) {
            return null;
        }

        // Untuk proses jadwal berikutnya kita gunakan object Carbon
        $tanggal = Carbon::parse($tanggal);

        /*
        |--------------------------------------------------------------------------
        | 3. CEK JADWAL KHUSUS KARYAWAN
        |--------------------------------------------------------------------------
        */

        $specialSchedule = $employee->employeeWorkSchedules()
            ->with('days')
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->whereDate('tanggal_selesai', '>=', $tanggal)
            ->latest('id')
            ->first();

        if ($specialSchedule) {

            $day = $specialSchedule->days
                ->firstWhere('hari', $tanggal->dayOfWeekIso);

            if (!$day || $day->is_libur) {
                return null;
            }

            return [
                'jam_masuk' => $day->jam_masuk,
                'jam_pulang' => $day->jam_pulang,
                'sumber' => 'khusus',
                'schedule_id' => $specialSchedule->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. JADWAL DASAR
        |--------------------------------------------------------------------------
        */

        if (!$employee->workSchedule) {
            return null;
        }

        $day = $employee->workSchedule
            ->days
            ->firstWhere('hari', $tanggal->dayOfWeekIso);

        if (!$day || $day->is_libur) {
            return null;
        }

        return [
            'jam_masuk' => $day->jam_masuk,
            'jam_pulang' => $day->jam_pulang,
            'sumber' => 'dasar',
            'schedule_id' => $employee->workSchedule->id,
        ];
    }
}