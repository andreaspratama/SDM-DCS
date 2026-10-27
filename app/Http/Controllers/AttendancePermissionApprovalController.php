<?php

namespace App\Http\Controllers;

use App\Models\AttendancePermission;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\EmployeeAnnualLeave;
use App\Models\AnnualLeaveTransaction;
use App\Models\LeavePeriod;
use App\Services\AnnualLeaveService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Employee;
use Carbon\Carbon;

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

        $isTU = $user->isTU();

        $dateFrom = $request->date_from;
        $dateTo   = $request->date_to;

        // TU wajib mempunyai unit
        if ($isTU && !$user->unit_id) {
            abort(
                403,
                'Akun TU belum memiliki unit.'
            );
        }

        return view(
            'pages.absensi.permission-approval',
            compact(
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
        AttendancePermission $permission,
        AnnualLeaveService $annualLeaveService
    ) {
        $user = auth()->user();

        $this->authorizePermission($user, $permission);

        $validated = $request->validate([
            'approval_note' => 'nullable|string|max:1000',
        ]);

        try {
            DB::transaction(function () use (
                $permission,
                $user,
                $validated,
                $annualLeaveService
            ) {
                // Kunci pengajuan agar tidak diproses bersamaan.
                $lockedPermission = AttendancePermission::query()
                    ->whereKey($permission->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$lockedPermission->isPending()) {
                    throw ValidationException::withMessages([
                        'permission' => 'Pengajuan ini sudah pernah diproses.',
                    ]);
                }

                // Cuti Tahunan memerlukan pemeriksaan dan pemotongan saldo.
                if ($lockedPermission->type === 'Cuti Tahunan') {
                    $employee = Employee::findOrFail(
                        $lockedPermission->employee_id
                    );

                    $period = LeavePeriod::where('is_active', true)
                        ->lockForUpdate()
                        ->first();

                    if (!$period) {
                        throw ValidationException::withMessages([
                            'permission' => 'Periode Cuti Tahunan aktif belum tersedia.',
                        ]);
                    }

                    $entitlement = EmployeeAnnualLeave::where(
                        'employee_id',
                        $employee->id
                    )
                        ->where('leave_period_id', $period->id)
                        ->where('is_eligible', true)
                        ->lockForUpdate()
                        ->first();

                    if (!$entitlement) {
                        throw ValidationException::withMessages([
                            'permission' => 'Pegawai belum ditetapkan sebagai penerima Cuti Tahunan pada periode aktif.',
                        ]);
                    }

                    // Perlindungan tambahan jika debit sudah tercatat.
                    $existingDebit = AnnualLeaveTransaction::where(
                        'attendance_permission_id',
                        $lockedPermission->id
                    )
                        ->where('type', AnnualLeaveTransaction::TYPE_DEBIT)
                        ->exists();

                    if (!$existingDebit) {
                        $days = $annualLeaveService->countWorkingDays(
                            $employee,
                            $lockedPermission->date_start,
                            $lockedPermission->date_end
                        );

                        if ($days < 1) {
                            throw ValidationException::withMessages([
                                'permission' => 'Pengajuan Cuti Tahunan tidak memiliki hari kerja yang dapat dipotong.',
                            ]);
                        }

                        $debit = AnnualLeaveTransaction::where(
                            'employee_annual_leave_id',
                            $entitlement->id
                        )
                            ->where('type', AnnualLeaveTransaction::TYPE_DEBIT)
                            ->sum('days');

                        $credit = AnnualLeaveTransaction::where(
                            'employee_annual_leave_id',
                            $entitlement->id
                        )
                            ->where('type', AnnualLeaveTransaction::TYPE_CREDIT)
                            ->sum('days');

                        $balance = (int) $entitlement->allowance_days
                            - (int) $debit
                            + (int) $credit;

                        if ($balance < $days) {
                            throw ValidationException::withMessages([
                                'permission' => "Saldo Cuti Tahunan tidak mencukupi. Saldo: {$balance} hari; kebutuhan: {$days} hari.",
                            ]);
                        }

                        AnnualLeaveTransaction::create([
                            'employee_annual_leave_id' => $entitlement->id,
                            'attendance_permission_id' => $lockedPermission->id,
                            'type' => AnnualLeaveTransaction::TYPE_DEBIT,
                            'days' => $days,
                            'description' => 'Pemotongan saldo dari persetujuan Cuti Tahunan',
                            'created_by' => $user->id,
                        ]);
                    }
                }

                // Status dan transaksi debit disimpan bersama.
                $lockedPermission->update([
                    'status' => AttendancePermission::STATUS_APPROVED,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_note' => $validated['approval_note'] ?? null,
                ]);
            });
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first(),
                    'errors' => $e->errors(),
                ], 422);
            }

            throw $e;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan izin berhasil disetujui.',
            ]);
        }

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
            'approval_note' => [
                'required',
                'string',
                'min:3',
                'max:1000',
            ],
        ], [
            'approval_note.required' => 'Alasan penolakan wajib diisi.',
            'approval_note.min' => 'Alasan penolakan minimal 3 karakter.',
            'approval_note.max' => 'Alasan penolakan maksimal 1000 karakter.',
        ]);


        // Hanya pending
        if (!$permission->isPending()) {

            if ($request->expectsJson()) {

                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan ini sudah pernah diproses.',
                ], 422);
            }

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


        // =====================================================
        // AJAX
        // =====================================================

        if ($request->expectsJson()) {

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan izin berhasil ditolak.',
            ]);
        }


        // =====================================================
        // REQUEST BIASA
        // =====================================================

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

    // =====================================================
    // DATATABLE PENGAJUAN IZIN
    // =====================================================
    public function datatable(Request $request)
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

        $isTU = $user->isTU();


        // =====================================================
        // TU WAJIB PUNYA UNIT
        // =====================================================

        if ($isTU && !$user->unit_id) {

            abort(
                403,
                'Akun TU belum memiliki unit.'
            );
        }


        // =====================================================
        // QUERY DASAR
        // =====================================================

        $query = AttendancePermission::query()

            ->select([
                'id',
                'employee_id',
                'date_start',
                'date_end',
                'time_start',
                'time_end',
                'type',
                'dinas_mode',
                'description',
                'approval_note',
                'attachment',
                'status',

                // WAJIB karena dipakai filter Pimpinan
                'approver_user_id',

                'approved_by',
                'approved_at',

                // TANGGAL/JAM PENGAJUAN DIBUAT
                'created_at',
            ])

            ->with([
                'employee:id,nama,unit_id',
                'employee.unit:id,nama',
                'approvedBy:id,name',
            ]);

        
        // =====================================================
        // FILTER TANGGAL IZIN
        //
        // Pengajuan ditampilkan jika rentang tanggal izinnya
        // beririsan dengan rentang filter yang dipilih.
        // =====================================================

        if (
            $request->filled('date_from')
            || $request->filled('date_to')
        ) {
            $dateFrom = $request->filled('date_from')
                ? $request->date_from
                : '1000-01-01';

            $dateTo = $request->filled('date_to')
                ? $request->date_to
                : '9999-12-31';

            $query
                ->whereDate('date_start', '<=', $dateTo)
                ->whereRaw(
                    'COALESCE(date_end, date_start) >= ?',
                    [$dateFrom]
                );
        }


        // =====================================================
        // FILTER TU
        //
        // TU hanya melihat pegawai dari unitnya.
        // =====================================================

        if ($isTU) {

            $query->whereHas(
                'employee',
                function ($q) use ($user) {

                    $q->where(
                        'unit_id',
                        $user->unit_id
                    );
                }
            );
        }


        // =====================================================
        // FILTER PIMPINAN
        //
        // Pimpinan hanya melihat pengajuan
        // yang ditujukan kepadanya.
        // =====================================================

        if (
            !$user->isAdmin()
            && !$isTU
            && $user->isPimpinan()
        ) {

            $query->where(
                'approver_user_id',
                $user->id
            );
        }


        // =====================================================
        // URUTAN
        //
        // Pending paling atas.
        // =====================================================

        $query->orderByRaw("
            CASE
                WHEN status = 'pending' THEN 1
                WHEN status = 'approved' THEN 2
                WHEN status = 'rejected' THEN 3
                ELSE 4
            END
        ");

        $query->orderByDesc('date_start');
        $query->orderByDesc('id');


        // =====================================================
        // DATATABLES
        // =====================================================

        return DataTables::eloquent($query)

            ->addIndexColumn()


            // =================================================
            // PEGAWAI
            // =================================================

            ->addColumn(
                'pegawai',
                function ($permission) {

                    return '<b>'
                        . e(
                            $permission->employee?->nama
                            ?? '-'
                        )
                        . '</b>';
                }
            )


            // =================================================
            // PENCARIAN PEGAWAI
            // =================================================

            ->filterColumn(
                'pegawai',
                function ($query, $keyword) {

                    $query->whereHas(
                        'employee',
                        function ($q) use ($keyword) {

                            $q->where(
                                'nama',
                                'like',
                                '%' . $keyword . '%'
                            );
                        }
                    );
                }
            )


            // =================================================
            // UNIT
            // =================================================

            ->addColumn(
                'unit',
                function ($permission) {

                    return e(
                        $permission->employee?->unit?->nama
                        ?? '-'
                    );
                }
            )


            // =================================================
            // TANGGAL IZIN
            // =================================================

            ->addColumn(
                'tanggal',
                function ($permission) {

                    if (!$permission->date_start) {
                        return '-';
                    }

                    $start = $permission->date_start;
                    $end   = $permission->date_end;

                    $html = $start->format('d-m-Y');

                    if (
                        $end
                        &&
                        $end->format('Y-m-d')
                        !==
                        $start->format('Y-m-d')
                    ) {

                        $html .=
                            '<br>'
                            . '<span class="text-muted">'
                            . 's/d '
                            . $end->format('d-m-Y')
                            . '</span>';
                    }

                    return $html;
                }
            )


            // =================================================
            // TANGGAL DIBUAT / DIAJUKAN
            // =================================================

            ->addColumn(
                'dibuat',
                function ($permission) {

                    if (!$permission->created_at) {
                        return '-';
                    }

                    return $permission->created_at
                        ->format('d-m-Y H:i');
                }
            )


            // =================================================
            // JAM
            // =================================================

            ->addColumn(
                'jam',
                function ($permission) {

                    $type = $permission->type;

                    // -----------------------------------------
                    // PERGI DINAS
                    // -----------------------------------------
                    if ($type === 'Pergi Dinas') {

                        // Dinas sementara dengan jam keluar dan kembali
                        if ($permission->dinas_mode === 'parsial') {

                            if (
                                $permission->time_start
                                && $permission->time_end
                            ) {
                                return
                                    '<div style="color:#0d6efd;font-weight:600;">'
                                    . '<i class="fa-solid fa-car-side me-1"></i>'
                                    . 'Dinas Sementara'
                                    . '</div>'
                                    . '<div class="small text-muted">'
                                    . e(substr($permission->time_start, 0, 5))
                                    . ' - '
                                    . e(substr($permission->time_end, 0, 5))
                                    . '</div>';
                            }

                            return
                                '<span class="text-muted">'
                                . 'Jam dinas belum diisi'
                                . '</span>';
                        }

                        // Dinas satu hari penuh
                        if ($permission->dinas_mode === 'sehari') {
                            return
                                '<span style="color:#198754;font-weight:600;">'
                                . '<i class="fa-solid fa-calendar-day me-1"></i>'
                                . 'Dinas Sehari Penuh'
                                . '</span>';
                        }

                        // Dinas beberapa hari
                        if ($permission->dinas_mode === 'beberapa_hari') {
                            return
                                '<span style="color:#198754;font-weight:600;">'
                                . '<i class="fa-solid fa-calendar-days me-1"></i>'
                                . 'Dinas Beberapa Hari'
                                . '</span>';
                        }

                        // Data lama yang belum memiliki mode dinas
                        if (
                            $permission->time_start
                            && $permission->time_end
                        ) {
                            return
                                '<div style="font-weight:600;">'
                                . 'Dinas (mode belum diketahui)'
                                . '</div>'
                                . '<div class="small text-muted">'
                                . e(substr($permission->time_start, 0, 5))
                                . '–'
                                . e(substr($permission->time_end, 0, 5))
                                . '</div>';
                        }

                        return
                            '<span class="text-muted">'
                            . 'Mode dinas belum diketahui'
                            . '</span>';
                    }


                    // -----------------------------------------
                    // IZIN KELUAR / KEPERLUAN PRIBADI
                    // -----------------------------------------

                    if (
                        in_array(
                            $type,
                            [
                                'Izin Keluar Sementara',
                                'Keperluan Pribadi',
                            ],
                            true
                        )
                    ) {

                        if (
                            $permission->time_start
                            &&
                            $permission->time_end
                        ) {

                            return
                                '<span style="font-weight:600;">'
                                . e(
                                    substr(
                                        $permission->time_start,
                                        0,
                                        5
                                    )
                                )
                                . '<br>'
                                . '<span style="font-weight:400;">'
                                . 's/d'
                                . '</span>'
                                . '<br>'
                                . e(
                                    substr(
                                        $permission->time_end,
                                        0,
                                        5
                                    )
                                )
                                . '</span>';
                        }

                        return
                            '<span class="text-muted">-</span>';
                    }


                    // -----------------------------------------
                    // IZIN TERLAMBAT
                    // -----------------------------------------

                    if (
                        $type === 'Izin Terlambat'
                    ) {

                        if ($permission->time_start) {

                            return
                                '<div style="color:#dc3545;font-weight:600;">'
                                . '<i class="fa-solid fa-clock me-1"></i>'
                                . 'Datang: '
                                . e(
                                    substr(
                                        $permission->time_start,
                                        0,
                                        5
                                    )
                                )
                                . '</div>';
                        }

                        return
                            '<span class="text-muted">'
                            . 'Jam belum diisi'
                            . '</span>';
                    }


                    // -----------------------------------------
                    // IZIN PULANG AWAL
                    // -----------------------------------------

                    if (
                        $type === 'Izin Pulang Awal'
                    ) {

                        if ($permission->time_start) {

                            return
                                '<div style="color:#fd7e14;font-weight:600;">'
                                . '<i class="fa-solid fa-person-walking-arrow-right me-1"></i>'
                                . 'Pulang: '
                                . e(
                                    substr(
                                        $permission->time_start,
                                        0,
                                        5
                                    )
                                )
                                . '</div>';
                        }

                        return
                            '<span class="text-muted">'
                            . 'Jam belum diisi'
                            . '</span>';
                    }


                    // -----------------------------------------
                    // FULL DAY
                    // -----------------------------------------

                    return
                        '<span style="color:#6c757d;font-weight:600;">'
                        . '<i class="fa-solid fa-calendar-day me-1"></i>'
                        . ' Full Day'
                        . '</span>';
                }
            )


            // =================================================
            // JENIS
            // =================================================

            ->editColumn(
                'type',
                function ($permission) {

                    return '<b>'
                        . e(
                            $permission->type
                            ?? '-'
                        )
                        . '</b>';
                }
            )


            // =================================================
            // KETERANGAN
            // =================================================

            ->editColumn(
                'description',
                function ($permission) {

                    return e(
                        $permission->description
                        ?? '-'
                    );
                }
            )

            // =================================================
            // ALASAN PENOLAKAN
            // =================================================

            ->addColumn(
                'approval_note',
                function ($permission) {

                    if (
                        $permission->status
                            !== AttendancePermission::STATUS_REJECTED
                        || empty($permission->approval_note)
                    ) {
                        return '<span class="text-muted">-</span>';
                    }

                    return
                        '<div class="text-danger small" style="white-space:normal; min-width:180px;">'
                        . '<i class="fa-solid fa-circle-exclamation me-1"></i>'
                        . '<strong>Alasan penolakan:</strong><br>'
                        . nl2br(e($permission->approval_note))
                        . '</div>';
                }
            )


            // =================================================
            // STATUS
            // =================================================

            ->editColumn(
                'status',
                function ($permission) {

                    if (
                        $permission->status
                        === AttendancePermission::STATUS_PENDING
                    ) {

                        $html =
                            '<span class="badge bg-warning text-dark">'
                            . '⏳ Pending'
                            . '</span>';

                    } elseif (
                        $permission->status
                        === AttendancePermission::STATUS_APPROVED
                    ) {

                        $html =
                            '<span class="badge bg-success">'
                            . '✔ Approved'
                            . '</span>';

                    } elseif (
                        $permission->status
                        === AttendancePermission::STATUS_REJECTED
                    ) {

                        $html =
                            '<span class="badge bg-danger">'
                            . '✖ Rejected'
                            . '</span>';

                    } else {

                        $html =
                            '<span class="badge bg-secondary">'
                            . e(
                                $permission->status
                            )
                            . '</span>';
                    }


                    if ($permission->approved_at) {

                        $html .=
                            '<div class="small text-muted mt-1">'
                            . $permission
                                ->approved_at
                                ->format('d-m-Y H:i')
                            . '</div>';
                    }


                    return $html;
                }
            )


            // =================================================
            // LAMPIRAN
            // =================================================

            ->addColumn(
                'attachment',
                function ($permission) {

                    if (!$permission->attachment) {

                        return
                            '<span class="text-muted">-</span>';
                    }


                    $url = asset(
                        'storage/' . $permission->attachment
                    );


                    return
                        '<a href="'
                        . e($url)
                        . '"
                        target="_blank"
                        class="btn btn-sm btn-outline-primary">
                            📎 Lihat
                        </a>';
                }
            )

            ->addColumn(
                'dibuat',
                function ($permission) {

                    if (!$permission->created_at) {
                        return '-';
                    }

                    $tanggal = $permission->created_at
                        ->timezone('Asia/Jakarta')
                        ->translatedFormat('d M Y');

                    $jam = $permission->created_at
                        ->timezone('Asia/Jakarta')
                        ->format('H:i');

                    return
                        '<div class="text-nowrap">'
                        . '<div class="fw-semibold">'
                        . e($tanggal)
                        . '</div>'
                        . '<div class="small text-muted">'
                        . '<i class="fa-regular fa-clock me-1"></i>'
                        . e($jam)
                        . '</div>'
                        . '</div>';
                }
            )


            // =================================================
            // AKSI
            // =================================================

            ->addColumn(
                'aksi',
                function ($permission) use ($isTU) {

                    // TU tidak mempunyai tombol approval
                    if ($isTU) {
                        return '';
                    }


                    // Sudah diproses
                    if (
                        $permission->status
                        !== AttendancePermission::STATUS_PENDING
                    ) {

                        $html =
                            '<div class="small text-muted">'
                            . 'Sudah diproses';


                        if ($permission->approvedBy) {

                            $html .=
                                '<br>Oleh: <b>'
                                . e(
                                    $permission
                                        ->approvedBy
                                        ->name
                                    ?? '-'
                                )
                                . '</b>';
                        }


                        $html .= '</div>';

                        return $html;
                    }


                    // URL APPROVE
                    $approveUrl = route(
                        'attendancePermission.approve',
                        $permission->id
                    );


                    // URL REJECT
                    $rejectUrl = route(
                        'attendancePermission.reject',
                        $permission->id
                    );


                    return
                        '<div class="d-flex gap-2 flex-wrap justify-content-center">'

                        .

                        '<button type="button"
                            class="btn btn-success btn-sm btn-approve-permission"
                            data-url="' . e($approveUrl) . '">

                            ✔ Setujui

                        </button>'

                        .

                        '<button type="button"
                            class="btn btn-danger btn-sm btn-reject-permission"
                            data-url="' . e($rejectUrl) . '">

                            ✖ Tolak

                        </button>'

                        .

                        '</div>';
                }
            )


            // =================================================
            // HTML
            // =================================================

            ->rawColumns([
                'pegawai',
                'tanggal',
                'jam',
                'type',
                'description',
                'approval_note',
                'status',
                'attachment',
                'dibuat',
                'aksi',
            ])

            ->make(true);
    }

}