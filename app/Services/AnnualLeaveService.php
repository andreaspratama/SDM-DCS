<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAnnualLeave;
use App\Models\LeavePeriod;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AnnualLeaveService
{
    public function __construct(
        protected EmployeeScheduleService $scheduleService
    ) {
    }

    /**
     * Mengambil periode cuti yang aktif.
     */
    public function activePeriod(): ?LeavePeriod
    {
        return LeavePeriod::query()
            ->where('is_active', true)
            ->first();
    }

    /**
     * Mengambil hak cuti pegawai pada periode tertentu.
     */
    public function entitlement(
        Employee $employee,
        LeavePeriod $period
    ): ?EmployeeAnnualLeave {
        return EmployeeAnnualLeave::query()
            ->where('employee_id', $employee->id)
            ->where('leave_period_id', $period->id)
            ->where('is_eligible', true)
            ->first();
    }

    /**
     * Menghitung hari kerja dalam rentang tanggal cuti.
     *
     * Jadwal diperiksa per tanggal menggunakan
     * EmployeeScheduleService yang sudah ada.
     */
    public function countWorkingDays(
        Employee $employee,
        string $startDate,
        string $endDate
    ): int {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'date_end' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            ]);
        }

        $total = 0;

        $date = $start->copy();

        while ($date->lte($end)) {
            if (
                $this->scheduleService->getSchedule(
                    $employee,
                    $date->copy()
                ) !== null
            ) {
                $total++;
            }

            $date->addDay();
        }

        return $total;
    }

    /**
     * Menghitung saldo dari jatah dan buku transaksi.
     *
     * Saldo = jatah - debit + credit.
     */
    public function balance(
        EmployeeAnnualLeave $entitlement
    ): int {
        $debits = $entitlement->transactions()
            ->where('type', 'debit')
            ->sum('days');

        $credits = $entitlement->transactions()
            ->where('type', 'credit')
            ->sum('days');

        return (int) $entitlement->allowance_days
            - (int) $debits
            + (int) $credits;
    }

    /**
     * Memastikan rentang tanggal berada dalam periode
     * dan pegawai memiliki hak cuti yang aktif.
     */
    public function validateRequest(
        Employee $employee,
        string $startDate,
        string $endDate
    ): array {
        $period = $this->activePeriod();

        if (!$period) {
            throw ValidationException::withMessages([
                'date_start' => 'Belum ada periode cuti tahunan yang aktif.',
            ]);
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if (
            $start->lt($period->start_date->copy()->startOfDay())
            || $end->gt($period->end_date->copy()->startOfDay())
        ) {
            throw ValidationException::withMessages([
                'date_start' => 'Tanggal cuti harus berada dalam periode tahun ajaran yang aktif.',
            ]);
        }

        $entitlement = $this->entitlement(
            $employee,
            $period
        );

        if (!$entitlement) {
            throw ValidationException::withMessages([
                'employee_id' => 'Pegawai ini belum ditetapkan sebagai penerima cuti tahunan pada periode aktif.',
            ]);
        }

        $days = $this->countWorkingDays(
            $employee,
            $start->toDateString(),
            $end->toDateString()
        );

        if ($days < 1) {
            throw ValidationException::withMessages([
                'date_start' => 'Rentang tanggal yang dipilih tidak memiliki hari kerja berdasarkan jadwal pegawai.',
            ]);
        }

        return [
            'period' => $period,
            'entitlement' => $entitlement,
            'working_days' => $days,
            'balance' => $this->balance($entitlement),
        ];
    }
}
