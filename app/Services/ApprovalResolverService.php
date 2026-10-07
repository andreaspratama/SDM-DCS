<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;

class ApprovalResolverService
{
    /**
     * Menentukan user yang harus meng-approve
     * pengajuan izin employee tertentu.
     *
     * ALUR:
     *
     * UNIT SEKOLAH
     * Guru / Staff / Waka
     *      → Kepala Sekolah
     *
     * UNIT UM
     * Staff
     *      → Kepala Bidang sesuai division
     *
     * Kepala Bidang Sarpras / Pengadaan
     *      → Sekretaris
     *
     * Kepala Bidang lainnya
     *      → Direktur
     *
     * Kepala Sekolah
     *      → Direktur
     *
     * Sekretaris
     *      → Direktur
     *
     * Direktur
     *      → Tidak ada approver
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
        //
        // → Kepala Bidang pada division yang sama
        // =====================================================
        if (
            $employee->role === 'Staff'
            && (int) $employee->unit_id === 1
        ) {
            // Staff UM wajib mempunyai division
            if (!$employee->division_id) {
                return null;
            }

            return User::whereNotNull('employee_id')
                ->whereHas('employee', function ($q) use ($employee) {
                    $q->where('role', 'Kepala Bidang')
                        ->where('unit_id', 1)
                        ->where('division_id', $employee->division_id);
                })
                ->first();
        }


        // =====================================================
        // KEPALA BIDANG UM
        //
        // Sarpras     (division_id = 8)
        // Pengadaan  (division_id = 10)
        //
        // → Sekretaris
        //
        // Kepala Bidang lainnya
        // → Direktur
        // =====================================================
        if (
            $employee->role === 'Kepala Bidang'
            && (int) $employee->unit_id === 1
        ) {
            if (in_array(
                (int) $employee->division_id,
                [8, 10],
                true
            )) {
                return $this->getSekretaris();
            }

            return $this->getDirektur();
        }


        // =====================================================
        // KEPALA SEKOLAH
        //
        // → Direktur
        // =====================================================
        if ($employee->role === 'Kepala Sekolah') {
            return $this->getDirektur();
        }


        // =====================================================
        // SEKRETARIS
        //
        // → Direktur
        // =====================================================
        if ($employee->role === 'Sekretaris') {
            return $this->getDirektur();
        }


        // =====================================================
        // DIREKTUR
        //
        // → Tidak mempunyai approver
        // =====================================================
        if ($employee->role === 'Direktur') {
            return null;
        }


        // =====================================================
        // ROLE BELUM DIKENALI
        // =====================================================
        return null;
    }


    /**
     * Cari akun Sekretaris UM.
     */
    private function getSekretaris(): ?User
    {
        return User::whereNotNull('employee_id')
            ->whereHas('employee', function ($q) {
                $q->where('role', 'Sekretaris')
                    ->where('unit_id', 1);
            })
            ->first();
    }


    /**
     * Cari akun Direktur UM.
     */
    private function getDirektur(): ?User
    {
        return User::whereNotNull('employee_id')
            ->whereHas('employee', function ($q) {
                $q->where('role', 'Direktur')
                    ->where('unit_id', 1);
            })
            ->first();
    }
}