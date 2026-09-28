<?php

namespace App\Http\Controllers;

use App\Models\AttendancePermission;
use Illuminate\Http\Request;

class AttendancePermissionApprovalController extends Controller
{
    // =====================================================
    // DAFTAR PENGAJUAN IZIN
    // =====================================================
    public function index(Request $request)
    {
        $user = auth()->user();

        abort_unless(
            $user
            && (
                $user->isAdmin()
                || $user->isPimpinan()
                || $user->isTU()
            ),
            403
        );

        $query = AttendancePermission::with([
            'employee.unit',
            'employee.division',
            'approver',
            'approvedBy',
        ]);

        // =================================================
        // FILTER TANGGAL
        // =================================================

        $dateFrom = $request->date_from;
        $dateTo   = $request->date_to;

        if ($dateFrom && $dateTo) {

            $query->where(function ($q) use ($dateFrom, $dateTo) {

                // Pengajuan yang tanggalnya bersinggungan
                // dengan periode filter

                $q->whereDate('date_start', '<=', $dateTo)
                ->where(function ($q2) use ($dateFrom) {

                    $q2->whereDate('date_end', '>=', $dateFrom)
                        ->orWhere(function ($q3) use ($dateFrom) {

                            $q3->whereNull('date_end')
                                ->whereDate('date_start', '>=', $dateFrom);

                        });

                });

            });

        } elseif ($dateFrom) {

            $query->where(function ($q) use ($dateFrom) {

                $q->whereDate('date_end', '>=', $dateFrom)
                ->orWhere(function ($q2) use ($dateFrom) {

                    $q2->whereNull('date_end')
                        ->whereDate('date_start', '>=', $dateFrom);

                });

            });

        } elseif ($dateTo) {

            $query->whereDate('date_start', '<=', $dateTo);

        }


        // =================================================
        // ADMIN
        // Semua pengajuan
        // =================================================

        if ($user->isAdmin()) {

            // Semua pengajuan

        }

        // =================================================
        // TU
        // Hanya unit TU
        // =================================================

        elseif ($user->isTU()) {

            if (!$user->unit_id) {

                abort(
                    403,
                    'Akun TU belum memiliki unit.'
                );
            }

            $query->whereHas(
                'employee',
                function ($employee) use ($user) {

                    $employee->where(
                        'unit_id',
                        $user->unit_id
                    );

                }
            );

        }

        // =================================================
        // PIMPINAN
        // Hanya pengajuan yang ditujukan kepadanya
        // =================================================

        else {

            $query->where(
                'approver_user_id',
                $user->id
            );

        }


        // =================================================
        // SORTING
        // =================================================

        $permissions = $query
            ->orderByRaw("
                CASE
                    WHEN status = 'pending' THEN 1
                    WHEN status = 'approved' THEN 2
                    WHEN status = 'rejected' THEN 3
                    ELSE 4
                END
            ")
            ->latest()
            ->get();


        $isTU = $user->isTU();


        return view(
            'pages.absensi.permission-approval',
            compact(
                'permissions',
                'isTU',
                'dateFrom',
                'dateTo'
            )
        );
    }


    // =====================================================
    // APPROVE
    // =====================================================
    public function approve(
        Request $request,
        AttendancePermission $permission
    ) {
        $user = auth()->user();

        $this->authorizePermission(
            $user,
            $permission
        );

        $request->validate([
            'approval_note' => 'nullable|string|max:1000',
        ]);


        // Hanya pending
        if (!$permission->isPending()) {

            return back()->with([
                'message' => 'Pengajuan ini sudah pernah diproses.',
                'alert-type' => 'warning',
            ]);
        }


        $permission->update([

            'status' =>
                AttendancePermission::STATUS_APPROVED,

            'approved_by' =>
                $user->id,

            'approved_at' =>
                now(),

            'approval_note' =>
                $request->approval_note,
        ]);


        return back()->with([
            'message' => 'Pengajuan izin berhasil disetujui.',
            'alert-type' => 'success',
        ]);
    }


    // =====================================================
    // REJECT
    // =====================================================
    public function reject(
        Request $request,
        AttendancePermission $permission
    ) {
        $user = auth()->user();

        $this->authorizePermission(
            $user,
            $permission
        );

        $request->validate([
            'approval_note' => 'nullable|string|max:1000',
        ]);


        // Hanya pending
        if (!$permission->isPending()) {

            return back()->with([
                'message' => 'Pengajuan ini sudah pernah diproses.',
                'alert-type' => 'warning',
            ]);
        }


        $permission->update([

            'status' =>
                AttendancePermission::STATUS_REJECTED,

            'approved_by' =>
                $user->id,

            'approved_at' =>
                now(),

            'approval_note' =>
                $request->approval_note,
        ]);


        return back()->with([
            'message' => 'Pengajuan izin berhasil ditolak.',
            'alert-type' => 'success',
        ]);
    }


    // =====================================================
    // SECURITY APPROVAL
    // =====================================================
    private function authorizePermission(
        $user,
        AttendancePermission $permission
    ): void {

        // Wajib login
        abort_unless(
            $user,
            403
        );


        // =================================================
        // ADMIN BOLEH MEMPROSES SEMUA
        // =================================================
        if ($user->isAdmin()) {
            return;
        }


        // =================================================
        // SELAIN PIMPINAN DITOLAK
        // =================================================
        abort_unless(
            $user->isPimpinan(),
            403
        );


        // =================================================
        // PIMPINAN HANYA BOLEH MEMPROSES
        // PENGAJUAN YANG DITUJUKAN KEPADANYA
        // =================================================
        abort_unless(
            (int) $permission->approver_user_id
            ===
            (int) $user->id,
            403
        );
    }
}