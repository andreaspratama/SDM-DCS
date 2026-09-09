<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendancePermission;
use App\Models\Employee;
use App\Models\Unit;
use App\Models\WorkCalendarDate;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // =====================================================
        // USER LOGIN
        // =====================================================
        $user = auth()->user();

        $loginEmployee = null;

        if (
            $user
            &&
            $user->employee_id
        ) {
            $loginEmployee = Employee::with('unit')
                ->find($user->employee_id);
        }


        // =====================================================
        // ROLE EMPLOYEE
        // =====================================================
        $employeeRole =
            $loginEmployee?->role;

        $isKepsek =
            $employeeRole === 'Kepala Sekolah';

        $isKabid =
            $employeeRole === 'Kepala Bidang';


        // =====================================================
        // SCOPE UNIT
        //
        // Kepala Sekolah hanya melihat unit sendiri.
        // Admin / Direktur = semua unit.
        // =====================================================
        $scopeUnitId =
            $isKepsek
                ? $loginEmployee?->unit_id
                : null;


        $scopeName =
            $scopeUnitId
                ? ($loginEmployee?->unit?->nama ?? 'Unit')
                : 'Semua Unit';


        // =====================================================
        // TANGGAL
        // =====================================================
        $today =
            Carbon::today()
                ->toDateString();


        // =====================================================
        // EMPLOYEE YANG BOLEH DILIHAT
        // =====================================================
        $employeeQuery =
            Employee::query();


        if ($scopeUnitId) {

            $employeeQuery->where(
                'unit_id',
                $scopeUnitId
            );
        }


        $employeeIds =
            $employeeQuery
                ->pluck('id');


        $totalPegawai =
            $employeeIds->count();


        // =====================================================
        // FINGERPRINT HARI INI
        // =====================================================
        $fingerprintHariIni =
            Attendance::whereIn(
                    'employee_id',
                    $employeeIds
                )
                ->whereDate(
                    'date',
                    $today
                )
                ->whereNotNull(
                    'check_in'
                )
                ->distinct()
                ->count(
                    'employee_id'
                );


        // =====================================================
        // SUDAH MASUK TAPI BELUM ADA SCAN PULANG
        // =====================================================
        $belumPulang =
            Attendance::whereIn(
                    'employee_id',
                    $employeeIds
                )
                ->whereDate(
                    'date',
                    $today
                )
                ->whereNotNull(
                    'check_in'
                )
                ->whereNull(
                    'check_out'
                )
                ->count();


        // =====================================================
        // IZIN APPROVED HARI INI
        // =====================================================
        $izinHariIni =
            AttendancePermission::whereIn(
                    'employee_id',
                    $employeeIds
                )
                ->where(
                    'status',
                    AttendancePermission::STATUS_APPROVED
                )
                ->whereDate(
                    'date_start',
                    '<=',
                    $today
                )
                ->whereDate(
                    'date_end',
                    '>=',
                    $today
                )
                ->distinct()
                ->count(
                    'employee_id'
                );


        // =====================================================
        // PENDING APPROVAL
        // =====================================================
        $pendingQuery =
            AttendancePermission::with([
                'employee.unit',
                'employee.division',
            ])
            ->where(
                'status',
                AttendancePermission::STATUS_PENDING
            );


        if ($user->isAdmin()) {

            // Admin melihat semua pending

        } elseif ($user->isPimpinan()) {

            // Pimpinan hanya approval yang ditujukan kepadanya
            $pendingQuery->where(
                'approver_user_id',
                $user->id
            );

        } else {

            // User lain tidak ada approval
            $pendingQuery->whereRaw(
                '1 = 0'
            );
        }


        $pendingApproval =
            (clone $pendingQuery)
                ->count();


        // =====================================================
        // PENGAJUAN TERBARU
        // =====================================================
        $latestPermissions =
            (clone $pendingQuery)
                ->orderByDesc(
                    'created_at'
                )
                ->limit(6)
                ->get();


        // =====================================================
        // RINGKASAN PER UNIT
        //
        // Yang dihitung:
        // - total pegawai
        // - pegawai yang punya fingerprint hari ini
        // =====================================================
        $unitSummary =
            DB::table('units')

                ->leftJoin(
                    'employees',
                    'employees.unit_id',
                    '=',
                    'units.id'
                )

                ->leftJoin(
                    'attendances',
                    function ($join) use ($today) {

                        $join->on(
                            'attendances.employee_id',
                            '=',
                            'employees.id'
                        );

                        $join->where(
                            'attendances.date',
                            '=',
                            $today
                        );
                    }
                )

                ->when(
                    $scopeUnitId,
                    function ($query) use (
                        $scopeUnitId
                    ) {

                        $query->where(
                            'units.id',
                            $scopeUnitId
                        );
                    }
                )

                ->select(
                    'units.id',
                    'units.nama',

                    DB::raw(
                        'COUNT(DISTINCT employees.id) as total_pegawai'
                    ),

                    DB::raw(
                        "
                        COUNT(
                            DISTINCT CASE
                                WHEN attendances.check_in IS NOT NULL
                                THEN employees.id
                            END
                        ) as fingerprint_hari_ini
                        "
                    )
                )

                ->groupBy(
                    'units.id',
                    'units.nama'
                )

                ->orderBy(
                    'units.nama'
                )

                ->get();


        // =====================================================
        // KALENDER HARI INI
        // =====================================================
        $calendarQuery =
            WorkCalendarDate::whereDate(
                'date',
                $today
            );


        if ($scopeUnitId) {

            $calendarQuery->where(
                function ($query) use (
                    $scopeUnitId
                ) {

                    $query
                        ->whereNull(
                            'unit_id'
                        )
                        ->orWhere(
                            'unit_id',
                            $scopeUnitId
                        );
                }
            );
        }


        $calendarToday =
            $calendarQuery
                ->orderBy('unit_id')
                ->get();


        // Untuk menampilkan nama unit kalender
        $unitNames =
            Unit::pluck(
                'nama',
                'id'
            );


        // =====================================================
        // PERCENTAGE FINGERPRINT
        // =====================================================
        $fingerprintPercentage =
            $totalPegawai > 0
                ? round(
                    (
                        $fingerprintHariIni
                        /
                        $totalPegawai
                    ) * 100
                )
                : 0;


        return view(
            'pages.dashboard',
            compact(
                'user',
                'loginEmployee',
                'employeeRole',
                'isKepsek',
                'isKabid',
                'scopeUnitId',
                'scopeName',
                'today',
                'totalPegawai',
                'fingerprintHariIni',
                'fingerprintPercentage',
                'belumPulang',
                'izinHariIni',
                'pendingApproval',
                'latestPermissions',
                'unitSummary',
                'calendarToday',
                'unitNames'
            )
        );
    }
}