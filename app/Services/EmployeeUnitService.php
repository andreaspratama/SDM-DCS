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

        $tanggal =
            Carbon::parse($tanggal)
                ->toDateString();

        $history =
            $employee
                ->unitHistories
                ->filter(function ($history) use ($tanggal) {

                    if (!$history->tanggal_mulai) {
                        return false;
                    }

                    $mulai =
                        $history
                            ->tanggal_mulai
                            ->toDateString();

                    $selesai =
                        $history->tanggal_selesai
                            ? $history
                                ->tanggal_selesai
                                ->toDateString()
                            : null;

                    return
                        $mulai <= $tanggal
                        &&
                        (
                            $selesai === null
                            ||
                            $selesai >= $tanggal
                        );
                })
                ->sortByDesc('tanggal_mulai')
                ->first();

        if ($history) {
            return (int) $history->unit_id;
        }

        return $employee->unit_id
            ? (int) $employee->unit_id
            : null;
    }

    public function getUnit(
        Employee $employee,
        string|Carbon $tanggal
    ): ?Unit {

        $unitId =
            $this->getUnitId(
                $employee,
                $tanggal
            );

        if (!$unitId) {
            return null;
        }

        return Unit::find($unitId);
    }
}