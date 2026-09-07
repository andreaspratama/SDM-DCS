<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;

class ApprovalResolverService
{
    /**
     * Menentukan user yang harus meng-approve
     * pengajuan izin employee tertentu.
     */
    public function resolve(Employee $employee): ?User
    {
        // =====================================================
        // PEGAWAI UNIT SEKOLAH
        //
        // Guru
        // Staff
        // Waka Kurikulum
        // Waka Kesiswaan
        //
        // → Kepala Sekolah pada unit yang sama
        // =====================================================
        if (
            (int) $employee->unit_id !== 1
            && in_array(
                $employee->role,
                [
                    'Guru',
                    'Staff',
                    'Waka Kurikulum',
                    'Waka Kesiswaan',
                ],
                true
            )
        ) {

            return User::whereNotNull('employee_id')
                ->whereHas('employee', function ($q) use ($employee) {

                    $q->where('role', 'Kepala Sekolah')
                        ->where('unit_id', $employee->unit_id);

                })
                ->first();
        }


        // =====================================================
        // STAFF UM
        // → Kepala Bidang pada divisi yang sama
        // =====================================================
        if (
            $employee->role === 'Staff'
            && (int) $employee->unit_id === 1
        ) {

            if (!$employee->division_id) {
                return null;
            }

            return User::whereNotNull('employee_id')
                ->whereHas('employee', function ($q) use ($employee) {

                    $q->where('role', 'Kepala Bidang')
                        ->where('division_id', $employee->division_id);

                })
                ->first();
        }


        // =====================================================
        // KEPALA SEKOLAH
        // → Direktur
        // =====================================================
        if ($employee->role === 'Kepala Sekolah') {

            return $this->getDirektur();
        }


        // =====================================================
        // KEPALA BIDANG
        // → Direktur
        // =====================================================
        if ($employee->role === 'Kepala Bidang') {

            return $this->getDirektur();
        }


        // =====================================================
        // DIREKTUR
        // → belum memiliki approver di atasnya
        // =====================================================
        if ($employee->role === 'Direktur') {

            return null;
        }


        // Role belum dikenali
        return null;
    }


    /**
     * Cari akun Direktur.
     */
    private function getDirektur(): ?User
    {
        return User::whereNotNull('employee_id')
            ->whereHas('employee', function ($q) {

                $q->where('role', 'Direktur');

            })
            ->first();
    }
}