<?php

namespace App\Http\Controllers;

use App\Models\AttendancePermission;
use Illuminate\Http\Request;

class AttendancePermissionApprovalController extends Controller
{
    // =====================================================
    // DAFTAR PENGAJUAN IZIN
    // =====================================================
    public function index()
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->isAdmin() || $user->isPimpinan()),
            403
        );

        $query = AttendancePermission::with([
            'employee.unit',
            'employee.division',
            'approver',
            'approvedBy',
        ]);

        // =================================================
        // ADMIN
        // Melihat semua pengajuan
        // =================================================
        if ($user->isAdmin()) {

            // tidak perlu filter

        }

        // =================================================
        // PIMPINAN
        // Hanya pengajuan yang memang ditujukan kepadanya
        // =================================================
        else {

            $query->where(
                'approver_user_id',
                $user->id
            );
        }


        // Pending di atas
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


        return view(
            'pages.absensi.permission-approval',
            compact('permissions')
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