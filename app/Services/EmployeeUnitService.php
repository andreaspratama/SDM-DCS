<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Unit;
use Carbon\Carbon;

class EmployeeUnitService
{
    public function getUnitId(
        Employee $employee,
        string|Carbon $tanggal
    ): ?int {

        $tanggal = Carbon::parse($tanggal)
            ->toDateString();

        $history = $employee
            ->unitHistories()
            ->whereDate(
                'tanggal_mulai',
                '<=',
                $tanggal
            )
            ->where(function ($query) use ($tanggal) {

                $query
                    ->whereNull('tanggal_selesai')
                    ->orWhereDate(
                        'tanggal_selesai',
                        '>=',
                        $tanggal
                    );
            })
            ->orderByDesc('tanggal_mulai')
            ->first();

        // Kalau sudah punya histori,
        // gunakan unit sesuai tanggal.
        if ($history) {
            return (int) $history->unit_id;
        }

        // Pegawai lama yang belum punya histori
        // tetap menggunakan unit saat ini.
        return $employee->unit_id
            ? (int) $employee->unit_id
            : null;
    }


    public function getUnit(
        Employee $employee,
        string|Carbon $tanggal
    ): ?Unit {

        $unitId = $this->getUnitId(
            $employee,
            $tanggal
        );

        if (!$unitId) {
            return null;
        }

        return Unit::find($unitId);
    }
}