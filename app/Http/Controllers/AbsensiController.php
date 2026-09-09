<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Unit;
use App\Models\UnitFormToken;
use App\Models\AttendanceLog;
use App\Models\AttendanceActivity;
use App\Models\AttendancePermission;
use Carbon\CarbonPeriod;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Services\EmployeeScheduleService;
use App\Services\ApprovalResolverService;
use App\Services\AttendanceFileImportService;
use App\Services\WorkCalendarService;

class AbsensiController extends Controller
{
    private function hitungSummary($employee, $start, $end)
    {
        $period = \Carbon\CarbonPeriod::create($start, $end);

        $summary = [
            'hadir' => 0,
            'izin' => 0,
            'telat' => 0,
            'total_menit_telat' => 0,
            'pulang_cepat' => 0,
            'total_menit_pulang_cepat' => 0,
            'tanpa_keterangan' => 0,
            'keluar_tanpa_izin' => 0,
            'total_menit' => 0,
        ];

        foreach ($period as $date) {

            if ($date->isWeekend()) continue;

            $tanggal = $date->toDateString();

            $att = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $tanggal)
                ->first();

            $izin = AttendancePermission::where('employee_id', $employee->id)
                ->whereDate('date_start', '<=', $tanggal)
                ->whereDate('date_end', '>=', $tanggal)
                ->get();

            $izinCount = $izin->count();

            $jamMasuk = optional($att)->check_in;
            $jamPulang = optional($att)->check_out;

            $jamMasukStandar = optional($employee->workSchedule)->jam_masuk ?? '07:30:00';
            $jamPulangStandar = optional($employee->workSchedule)->jam_pulang ?? '15:30:00';

            if ($att) {

                $summary['hadir']++;

                if ($jamMasuk && $jamMasuk > $jamMasukStandar) {
                    $summary['telat']++;
                }

                if ($jamPulang && $jamPulang < $jamPulangStandar) {
                    $summary['pulang_cepat']++;
                }

                if ($izinCount > 0) {
                    $summary['izin']++;
                } else {
                    if (
                        ($jamMasuk && $jamMasuk > $jamMasukStandar) ||
                        ($jamPulang && $jamPulang < $jamPulangStandar)
                    ) {
                        $summary['keluar_tanpa_izin']++;
                    }
                }

                if ($jamMasuk && $jamPulang) {
                    $jamStart = \Carbon\Carbon::parse($jamMasuk);
                    $jamEnd   = \Carbon\Carbon::parse($jamPulang);

                    if ($jamEnd->gt($jamStart)) {
                        $summary['total_menit'] += $jamStart->diffInMinutes($jamEnd);
                    }
                }

            } else {

                if ($izinCount > 0) {
                    $summary['izin']++;
                } else {
                    $summary['tanpa_keterangan']++;
                }
            }
        }

        return $summary;
    }

    private function getKepalaSekolahUnitId(): ?int
    {
        $user = auth()->user();

        if (
            !$user
            ||
            !$user->employee_id
        ) {
            return null;
        }

        $employeeLogin = Employee::select(
                'id',
                'unit_id',
                'role'
            )
            ->find(
                $user->employee_id
            );

        if (
            !$employeeLogin
            ||
            $employeeLogin->role !== 'Kepala Sekolah'
            ||
            !$employeeLogin->unit_id
        ) {
            return null;
        }

        return (int) $employeeLogin->unit_id;
    }

    private function abortIfKepalaBidang(): void
    {
        $user = auth()->user();

        if (
            !$user
            ||
            !$user->employee_id
        ) {
            return;
        }

        $employeeRole = Employee::where(
                'id',
                $user->employee_id
            )
            ->value('role');

        if ($employeeRole === 'Kepala Bidang') {

            abort(
                403,
                'Kepala Bidang tidak memiliki akses ke Rekap Absensi.'
            );
        }
    }

    public function datatable(
        Request $request,
        EmployeeScheduleService $scheduleService,
        WorkCalendarService $calendarService
    ) {
        $this->abortIfKepalaBidang();

        try {

            // =====================================================
            // RANGE TANGGAL
            // =====================================================
            $startDate =
                $request->start_date
                ?: Attendance::min('date');

            $endDate =
                $request->end_date
                ?: Attendance::max('date');


            if (
                !$startDate
                ||
                !$endDate
            ) {

                return DataTables::of([])
                    ->make(true);
            }


            $startDate =
                \Carbon\Carbon::parse(
                    $startDate
                )->toDateString();


            $endDate =
                \Carbon\Carbon::parse(
                    $endDate
                )->toDateString();


            // =====================================================
            // EMPLOYEE + JADWAL
            // =====================================================
            $employeesQuery = Employee::with([
                'workSchedule.days',
                'employeeWorkSchedules.days',
            ]);


            // =====================================================
            // SECURITY UNIT
            // =====================================================
            $kepsekUnitId =
                $this->getKepalaSekolahUnitId();


            // =====================================================
            // KEPALA SEKOLAH
            //
            // Request unit dari browser DIABAIKAN.
            // Selalu paksa ke unit miliknya.
            // =====================================================
            if ($kepsekUnitId !== null) {

                $employeesQuery->where(
                    'unit_id',
                    $kepsekUnitId
                );

            }


            // =====================================================
            // ADMIN / DIREKTUR / ROLE LAIN
            // =====================================================
            elseif (
                $request->filled('unit_id')
            ) {

                $employeesQuery->where(
                    'unit_id',
                    $request->unit_id
                );
            }


            $employees =
                $employeesQuery->get();


            // =====================================================
            // ATTENDANCE + ACTIVITIES
            // =====================================================
            $attendances = Attendance::with([
                    'activities'
                ])
                ->whereBetween(
                    'date',
                    [
                        $startDate,
                        $endDate
                    ]
                )
                ->get()
                ->groupBy(
                    function ($item) {

                        return
                            $item->employee_id
                            . '_'
                            . \Carbon\Carbon::parse(
                                $item->date
                            )->toDateString();
                    }
                );


            // =====================================================
            // SEMUA IZIN APPROVED
            // =====================================================
            $allIzin =
                AttendancePermission::where(
                    'date_start',
                    '<=',
                    $endDate
                )
                ->where(
                    'date_end',
                    '>=',
                    $startDate
                )
                ->where(
                    'status',
                    AttendancePermission::STATUS_APPROVED
                )
                ->get();


            $result = [];


            // =====================================================
            // LOOP EMPLOYEE
            // =====================================================
            foreach ($employees as $emp) {

                $period =
                    \Carbon\CarbonPeriod::create(
                        $startDate,
                        $endDate
                    );


                $summary = [

                    'total_hari_kerja' => 0,

                    'hadir' => 0,

                    'izin' => 0,

                    'telat' => 0,

                    'total_menit_telat' => 0,

                    'pulang_cepat' => 0,

                    'total_menit_pulang_cepat' => 0,

                    'tanpa_keterangan' => 0,

                    'keluar_tanpa_izin' => 0,

                    'total_menit' => 0,

                    'tidak_masuk' => 0,

                    'hari_kerja_khusus' => 0,
                ];


                // =================================================
                // LOOP TANGGAL
                // =================================================
                foreach ($period as $date) {

                    $tanggal =
                        $date->toDateString();


                    // =============================================
                    // JANGAN HITUNG MASA DEPAN
                    // =============================================
                    if ($date->isFuture()) {
                        continue;
                    }


                    // =============================================
                    // JADWAL
                    // =============================================
                    $jadwal =
                        $scheduleService->getSchedule(
                            $emp,
                            $tanggal
                        );


                    // =============================================
                    // KALDIK
                    // =============================================
                    $calendar =
                        $calendarService->getCalendar(
                            $tanggal,
                            $emp->unit_id
                        );


                    $isHariKerjaKhusus =
                        $calendar
                        &&
                        (bool) $calendar->is_workday
                        &&
                        $calendar->type
                            === 'hari_kerja_khusus';


                    // =============================================
                    // REKAP UTAMA HANYA HARI KERJA
                    //
                    // Lembur / kegiatan resmi hari libur
                    // tidak menambah Hari Kerja reguler.
                    // =============================================
                    if (!$jadwal) {
                        continue;
                    }


                    $summary[
                        'total_hari_kerja'
                    ]++;


                    // =============================================
                    // ATTENDANCE
                    // =============================================
                    $key =
                        $emp->id
                        . '_'
                        . $tanggal;


                    $att =
                        $attendances
                            ->get($key)
                            ?->first();


                    // =============================================
                    // IZIN APPROVED HARI INI
                    // =============================================
                    $izinHari =
                        $allIzin
                            ->where(
                                'employee_id',
                                $emp->id
                            )
                            ->filter(
                                function ($izin) use (
                                    $tanggal
                                ) {

                                    $izinMulai =
                                        \Carbon\Carbon::parse(
                                            $izin->date_start
                                        )
                                        ->toDateString();


                                    $izinSelesai =
                                        \Carbon\Carbon::parse(
                                            $izin->date_end
                                        )
                                        ->toDateString();


                                    return
                                        $tanggal
                                            >= $izinMulai
                                        &&
                                        $tanggal
                                            <= $izinSelesai;
                                }
                            )
                            ->values();


                    $izinCount =
                        $izinHari->count();


                    // =================================================
                    // ADA ABSENSI
                    // =================================================
                    if ($att) {

                        $summary['hadir']++;


                        $jamMasuk =
                            $att->check_in;

                        $jamPulang =
                            $att->check_out;


                        $masukFix =
                            $jamMasuk
                            ? \Carbon\Carbon::parse(
                                $tanggal
                                . ' '
                                . $jamMasuk
                            )
                            : null;


                        $pulangFix =
                            $jamPulang
                            ? \Carbon\Carbon::parse(
                                $tanggal
                                . ' '
                                . $jamPulang
                            )
                            : null;


                        // =============================================
                        // SAFETY JADWAL
                        // =============================================
                        $jamMasukStandar =
                            $jadwal['jam_masuk']
                            ?? null;

                        $jamPulangStandar =
                            $jadwal['jam_pulang']
                            ?? null;


                        if (
                            !$jamMasukStandar
                            ||
                            !$jamPulangStandar
                        ) {
                            continue;
                        }


                        // =============================================
                        // JAM STANDAR
                        // =============================================
                        $standarMasuk =
                            \Carbon\Carbon::parse(
                                $tanggal
                                . ' '
                                . $jamMasukStandar
                            );


                        $standarPulang =
                            \Carbon\Carbon::parse(
                                $tanggal
                                . ' '
                                . $jamPulangStandar
                            );


                        // =================================================
                        // IZIN TERLAMBAT
                        // =================================================
                        $izinTerlambat =
                            $izinHari->first(
                                function ($permission) {

                                    return
                                        $permission->type
                                            === 'Izin Terlambat'
                                        &&
                                        $permission->time_start;
                                }
                            );


                        // =================================================
                        // TERLAMBAT
                        // =================================================
                        $telat =
                            $masukFix
                            &&
                            $masukFix->gt(
                                $standarMasuk
                            );


                        if ($telat) {

                            // Default:
                            // hitung sejak jadwal masuk.
                            $menitTelat =
                                (int) $standarMasuk
                                    ->diffInMinutes(
                                        $masukFix
                                    );


                            // =========================================
                            // ADA IZIN TERLAMBAT
                            // =========================================
                            if ($izinTerlambat) {

                                $jamIzinDatang =
                                    \Carbon\Carbon::parse(
                                        $tanggal
                                        . ' '
                                        . $izinTerlambat
                                            ->time_start
                                    );


                                // Toleransi fingerprint 5 menit
                                $batasIzinDatang =
                                    $jamIzinDatang
                                        ->copy()
                                        ->addMinutes(5);


                                // =====================================
                                // DATANG MASIH DALAM BATAS IZIN
                                // =====================================
                                if (
                                    $masukFix->lte(
                                        $batasIzinDatang
                                    )
                                ) {

                                    $telat = false;

                                    $menitTelat = 0;

                                } else {

                                    // =================================
                                    // Datang melewati batas izin.
                                    //
                                    // Yang dihitung hanya kelebihan
                                    // setelah jam izin.
                                    // =================================
                                    $menitTelat =
                                        (int) $jamIzinDatang
                                            ->diffInMinutes(
                                                $masukFix
                                            );
                                }
                            }


                            // =========================================
                            // MASIH PELANGGARAN
                            // =========================================
                            if (
                                $telat
                                &&
                                $menitTelat > 0
                            ) {

                                $summary['telat']++;


                                $summary[
                                    'total_menit_telat'
                                ] += $menitTelat;
                            }
                        }


                        // =================================================
                        // IZIN PULANG AWAL
                        // =================================================
                        $izinPulangAwal =
                            $izinHari->first(
                                function ($permission) {

                                    return
                                        $permission->type
                                            === 'Izin Pulang Awal'
                                        &&
                                        $permission->time_start;
                                }
                            );


                        // =================================================
                        // PULANG CEPAT
                        // =================================================
                        $pulangCepat =
                            $pulangFix
                            &&
                            $pulangFix->lt(
                                $standarPulang
                            );


                        if ($pulangCepat) {

                            // Default:
                            // dihitung sampai jam pulang standar.
                            $menitPulangCepat =
                                (int) $pulangFix
                                    ->diffInMinutes(
                                        $standarPulang
                                    );


                            // =========================================
                            // ADA IZIN PULANG AWAL
                            // =========================================
                            if ($izinPulangAwal) {

                                $jamIzinPulang =
                                    \Carbon\Carbon::parse(
                                        $tanggal
                                        . ' '
                                        . $izinPulangAwal
                                            ->time_start
                                    );


                                // Toleransi fingerprint 5 menit
                                $batasIzinPulang =
                                    $jamIzinPulang
                                        ->copy()
                                        ->subMinutes(5);


                                // =====================================
                                // PULANG SESUAI IZIN
                                //
                                // Misal izin 14:00
                                // scan 13:56
                                // masih dianggap sesuai.
                                // =====================================
                                if (
                                    $pulangFix->gte(
                                        $batasIzinPulang
                                    )
                                ) {

                                    $pulangCepat =
                                        false;

                                    $menitPulangCepat =
                                        0;

                                } else {

                                    // =================================
                                    // Pulang lebih awal lagi
                                    // dari batas izin.
                                    //
                                    // Hanya selisih terhadap jam izin
                                    // yang menjadi pelanggaran.
                                    // =================================
                                    $menitPulangCepat =
                                        (int) $pulangFix
                                            ->diffInMinutes(
                                                $jamIzinPulang
                                            );
                                }
                            }


                            // =========================================
                            // MASIH PELANGGARAN
                            // =========================================
                            if (
                                $pulangCepat
                                &&
                                $menitPulangCepat > 0
                            ) {

                                $summary[
                                    'pulang_cepat'
                                ]++;


                                $summary[
                                    'total_menit_pulang_cepat'
                                ] +=
                                    $menitPulangCepat;
                            }
                        }


                        // =================================================
                        // IZIN APPROVED
                        //
                        // Hitung 1 hari punya izin,
                        // bukan jumlah permission.
                        // =================================================
                        if ($izinCount > 0) {

                            $summary['izin']++;
                        }


                        // =================================================
                        // ACTIVITIES
                        // =================================================
                        $activities =
                            $att->activities
                                ->sortBy('time')
                                ->values();


                        // =================================================
                        // KELUAR TANPA IZIN
                        // =================================================
                        for (
                            $i = 0;
                            $i < $activities->count() - 1;
                            $i++
                        ) {

                            $current =
                                $activities[$i];

                            $next =
                                $activities[$i + 1];


                            // =========================================
                            // HANYA OUT → IN
                            // =========================================
                            if (
                                $current->type !== 'out'
                                ||
                                $next->type !== 'in'
                            ) {
                                continue;
                            }


                            $jamKeluar =
                                \Carbon\Carbon::parse(
                                    $tanggal
                                    . ' '
                                    . $current->time
                                );


                            $jamKembali =
                                \Carbon\Carbon::parse(
                                    $tanggal
                                    . ' '
                                    . $next->time
                                );


                            // =========================================
                            // HARUS DALAM JAM KERJA
                            // =========================================
                            if (
                                $jamKeluar->lt(
                                    $standarMasuk
                                )
                                ||
                                $jamKeluar->gte(
                                    $standarPulang
                                )
                            ) {
                                continue;
                            }


                            // =========================================
                            // CEK IZIN APPROVED
                            //
                            // Cocok untuk:
                            //
                            // - Izin Keluar Sementara
                            // - Keperluan Pribadi data lama
                            // - jenis izin rentang jam lainnya
                            // =========================================
                            $adaIzin =
                                $izinHari->contains(
                                    function (
                                        $permission
                                    ) use (
                                        $tanggal,
                                        $jamKeluar,
                                        $jamKembali
                                    ) {

                                        if (
                                            !$permission
                                                ->time_start
                                            ||
                                            !$permission
                                                ->time_end
                                        ) {
                                            return false;
                                        }


                                        $izinMulai =
                                            \Carbon\Carbon::parse(
                                                $tanggal
                                                . ' '
                                                . $permission
                                                    ->time_start
                                            );


                                        $izinSelesai =
                                            \Carbon\Carbon::parse(
                                                $tanggal
                                                . ' '
                                                . $permission
                                                    ->time_end
                                            );


                                        return
                                            $izinMulai->lte(
                                                $jamKeluar
                                            )
                                            &&
                                            $izinSelesai->gte(
                                                $jamKembali
                                            );
                                    }
                                );


                            if (!$adaIzin) {

                                $summary[
                                    'keluar_tanpa_izin'
                                ]++;
                            }
                        }


                        // =================================================
                        // TOTAL JAM KERJA AKTUAL
                        //
                        // check-in → check-out
                        // dikurangi OUT → IN
                        // =================================================
                        if (
                            $masukFix
                            &&
                            $pulangFix
                            &&
                            $pulangFix->gt(
                                $masukFix
                            )
                        ) {

                            $totalMenit =
                                (int) $masukFix
                                    ->diffInMinutes(
                                        $pulangFix
                                    );


                            $totalMenitKeluar =
                                0;


                            for (
                                $i = 0;
                                $i < $activities->count() - 1;
                                $i++
                            ) {

                                $current =
                                    $activities[$i];

                                $next =
                                    $activities[$i + 1];


                                if (
                                    $current->type
                                        !== 'out'
                                    ||
                                    $next->type
                                        !== 'in'
                                ) {
                                    continue;
                                }


                                $keluar =
                                    \Carbon\Carbon::parse(
                                        $tanggal
                                        . ' '
                                        . $current->time
                                    );


                                $kembali =
                                    \Carbon\Carbon::parse(
                                        $tanggal
                                        . ' '
                                        . $next->time
                                    );


                                if (
                                    $kembali->gt(
                                        $keluar
                                    )
                                ) {

                                    $totalMenitKeluar +=
                                        (int) $keluar
                                            ->diffInMinutes(
                                                $kembali
                                            );
                                }
                            }


                            $menitKerjaAktual =
                                max(
                                    0,
                                    $totalMenit
                                    - $totalMenitKeluar
                                );


                            $summary[
                                'total_menit'
                            ] +=
                                $menitKerjaAktual;
                        }

                    } else {

                        // =================================================
                        // TIDAK ADA ABSENSI
                        // =================================================

                        if ($isHariKerjaKhusus) {

                            // =============================================
                            // Hari Kerja Khusus resmi
                            // dianggap hadir.
                            // =============================================
                            $summary['hadir']++;

                            $summary[
                                'hari_kerja_khusus'
                            ]++;

                        } elseif ($izinCount > 0) {

                            // =============================================
                            // IZIN FULL DAY / TIDAK MASUK
                            //
                            // Secara fisik tidak hadir.
                            // Tapi punya keterangan resmi.
                            // =============================================
                            $summary[
                                'tidak_masuk'
                            ]++;

                            $summary[
                                'izin'
                            ]++;

                            // Jangan tambah tanpa_keterangan.

                        } else {

                            // =============================================
                            // BENAR-BENAR TANPA KETERANGAN
                            // =============================================
                            $summary[
                                'tidak_masuk'
                            ]++;

                            $summary[
                                'tanpa_keterangan'
                            ]++;
                        }
                    }
                }


                // =====================================================
                // RESULT PER EMPLOYEE
                // =====================================================
                $result[] = [

                    'nama' =>
                        $emp->nama,

                    'total_hari_kerja' =>
                        $summary[
                            'total_hari_kerja'
                        ],

                    'izin' =>
                        $summary['izin'],

                    'total_hadir' =>
                        $summary['hadir'],

                    'tidak_masuk' =>
                        $summary[
                            'tidak_masuk'
                        ],

                    'total_telat' =>
                        $summary['telat'],

                    'total_menit_telat' =>
                        $summary[
                            'total_menit_telat'
                        ],

                    'pulang_cepat' =>
                        $summary[
                            'pulang_cepat'
                        ],

                    'total_menit_pulang_cepat' =>
                        $summary[
                            'total_menit_pulang_cepat'
                        ],

                    'tanpa_keterangan' =>
                        $summary[
                            'tanpa_keterangan'
                        ],

                    'keluar_tanpa_izin' =>
                        $summary[
                            'keluar_tanpa_izin'
                        ],

                    'total_jam' =>
                        round(
                            $summary[
                                'total_menit'
                            ] / 60,
                            1
                        ),

                    'aksi' =>
                        route(
                            'absensi.detailRange',
                            [
                                'employee' =>
                                    $emp->id,

                                'start' =>
                                    $startDate,

                                'end' =>
                                    $endDate,
                            ]
                        ),
                ];
            }


            // =====================================================
            // DATATABLE
            // =====================================================
            return DataTables::of(
                    $result
                )
                ->addColumn(
                    'aksi',
                    function ($row) {

                        return
                            '<a href="'
                            . $row['aksi']
                            . '" class="btn btn-info btn-sm">'
                            . 'Detail'
                            . '</a>';
                    }
                )
                ->rawColumns([
                    'aksi'
                ])
                ->make(true);


        } catch (\Throwable $e) {

            return response()->json([

                'error' =>
                    true,

                'message' =>
                    $e->getMessage(),

                'line' =>
                    $e->getLine(),

            ]);
        }
    }

    public function index()
    {
        $this->abortIfKepalaBidang();

        $kepsekUnitId =
            $this->getKepalaSekolahUnitId();

        // =====================================================
        // CEK APAKAH LOGIN SEBAGAI KEPALA SEKOLAH
        // =====================================================
        $kepsekUnitId =
            $this->getKepalaSekolahUnitId();


        $isKepsek =
            $kepsekUnitId !== null;


        // =====================================================
        // DAFTAR UNIT
        // =====================================================
        if ($isKepsek) {

            // Kepala Sekolah hanya menerima unit miliknya
            $units = Unit::where(
                    'id',
                    $kepsekUnitId
                )
                ->get();

        } else {

            // Admin / Direktur
            $units = Unit::orderBy(
                    'nama'
                )
                ->get();
        }


        $lockedUnit =
            $isKepsek
            ? $units->first()
            : null;


        return view(
            'pages.absensi.index',
            compact(
                'units',
                'isKepsek',
                'kepsekUnitId',
                'lockedUnit'
            )
        );
    }

    public function detailRange(
        Request $request,
        EmployeeScheduleService $scheduleService,
        $employeeId,
        WorkCalendarService $calendarService
    ) {
        $this->abortIfKepalaBidang();
        // =====================================================
        // EMPLOYEE
        // =====================================================
        $employee = Employee::with([
            'unit',
            'workSchedule.days',
            'employeeWorkSchedules.days'
        ])->findOrFail($employeeId);

        // =====================================================
        // SECURITY KEPALA SEKOLAH
        // =====================================================
        $kepsekUnitId =
            $this->getKepalaSekolahUnitId();


        if (
            $kepsekUnitId !== null
            &&
            (int) $employee->unit_id
                !== (int) $kepsekUnitId
        ) {

            abort(
                403,
                'Anda tidak memiliki akses ke data pegawai unit ini.'
            );
        }


        // =====================================================
        // RANGE TANGGAL
        // =====================================================
        $start = $request->start
            ? Carbon::parse($request->start)->startOfDay()
            : Carbon::now()->startOfMonth();

        $end = $request->end
            ? Carbon::parse($request->end)->endOfDay()
            : Carbon::now()->endOfMonth();


        // =====================================================
        // DATA PER HARI
        // =====================================================
        $period = CarbonPeriod::create(
            $start,
            $end
        );

        $data = [];


        foreach ($period as $date) {

            $tanggal = $date->toDateString();


            // =================================================
            // JANGAN HITUNG MASA DEPAN
            // =================================================
            if ($date->isFuture()) {
                continue;
            }


            // =================================================
            // JADWAL PEGAWAI
            // =================================================
            $jadwal = $scheduleService->getSchedule(
                $employee,
                $tanggal
            );


            // =================================================
            // KALDIK
            // =================================================
            $calendar = $calendarService->getCalendar(
                $tanggal,
                $employee->unit_id
            );


            // =================================================
            // HARI KERJA KHUSUS
            // =================================================
            $isHariKerjaKhusus =
                $calendar
                && (bool) $calendar->is_workday
                && $calendar->type === 'hari_kerja_khusus';


            // =================================================
            // ABSENSI + ACTIVITIES
            //
            // Harus dicari SEBELUM hari tanpa jadwal di-skip,
            // karena hari libur bisa mempunyai:
            //
            // - lembur
            // - kegiatan resmi
            // =================================================
            $absen = Attendance::with([
                'activities' => function ($q) {
                    $q->orderBy('time');
                }
            ])
            ->where(
                'employee_id',
                $employeeId
            )
            ->whereDate(
                'date',
                $tanggal
            )
            ->first();


            // =================================================
            // STATUS ATTENDANCE DATABASE
            // =================================================
            $attendanceStatus = strtolower(
                (string) optional($absen)->status
            );


            // =================================================
            // KEGIATAN RESMI
            // =================================================
            $isKegiatanResmi =
                $absen
                && $attendanceStatus === 'kegiatan_resmi';


            // =================================================
            // LEMBUR
            // =================================================
            $isLembur =
                $absen
                && $attendanceStatus === 'lembur';


            // =================================================
            // HARI LIBUR BIASA
            //
            // Tidak punya jadwal:
            //
            // + tidak ada lembur
            // + tidak ada kegiatan resmi
            //
            // → tidak perlu ditampilkan.
            // =================================================
            if (
                !$jadwal
                && !$isLembur
                && !$isKegiatanResmi
            ) {
                continue;
            }


            // =================================================
            // IZIN APPROVED
            // =================================================
            $izin = AttendancePermission::where(
                    'employee_id',
                    $employeeId
                )
                ->whereDate(
                    'date_start',
                    '<=',
                    $tanggal
                )
                ->whereDate(
                    'date_end',
                    '>=',
                    $tanggal
                )
                ->where(
                    'status',
                    AttendancePermission::STATUS_APPROVED
                )
                ->get();


            // =================================================
            // HITUNG MENIT LEMBUR
            //
            // check-in → check-out
            // dikurangi semua pasangan OUT → IN.
            //
            // Kalau scan masuk/pulang tidak lengkap,
            // durasi tetap 0.
            // =================================================
            $menitLembur = 0;


            if (
                $isLembur
                && $absen
                && $absen->check_in
                && $absen->check_out
            ) {

                $mulaiLembur = Carbon::parse(
                    $tanggal
                    . ' '
                    . $absen->check_in
                );

                $selesaiLembur = Carbon::parse(
                    $tanggal
                    . ' '
                    . $absen->check_out
                );


                if (
                    $selesaiLembur->gt(
                        $mulaiLembur
                    )
                ) {

                    $totalMenitLembur =
                        (int) $mulaiLembur
                            ->diffInMinutes(
                                $selesaiLembur
                            );


                    $menitKeluarLembur = 0;


                    $activitiesLembur =
                        $absen->activities
                            ->sortBy('time')
                            ->values();


                    for (
                        $i = 0;
                        $i < $activitiesLembur->count() - 1;
                        $i++
                    ) {

                        $current =
                            $activitiesLembur[$i];

                        $next =
                            $activitiesLembur[$i + 1];


                        // =====================================
                        // HANYA PASANGAN OUT → IN
                        // =====================================
                        if (
                            $current->type !== 'out'
                            || $next->type !== 'in'
                        ) {
                            continue;
                        }


                        $keluar = Carbon::parse(
                            $tanggal
                            . ' '
                            . $current->time
                        );

                        $kembali = Carbon::parse(
                            $tanggal
                            . ' '
                            . $next->time
                        );


                        if ($kembali->gt($keluar)) {

                            $menitKeluarLembur +=
                                (int) $keluar
                                    ->diffInMinutes(
                                        $kembali
                                    );
                        }
                    }


                    $menitLembur = max(
                        0,
                        $totalMenitLembur
                        - $menitKeluarLembur
                    );
                }
            }


            // =================================================
            // STATUS HARI UNTUK VIEW
            // =================================================
            if ($isKegiatanResmi) {

                $status = 'KEGIATAN_RESMI';

            } elseif ($isLembur) {

                $status = 'LEMBUR';

            } elseif ($isHariKerjaKhusus) {

                $status = 'HARI_KERJA_KHUSUS';

            } elseif ($izin->count() > 0) {

                $status = 'IZIN';

            } elseif ($absen) {

                $status = 'HADIR';

            } else {

                $status = 'ALPHA';
            }


            // =================================================
            // MASUKKAN DATA HARIAN
            // =================================================
            $data[] = [

                'tanggal' =>
                    $tanggal,

                'absen' =>
                    $absen,

                'izin' =>
                    $izin,

                'status' =>
                    $status,

                'jadwal' =>
                    $jadwal,


                // =============================================
                // LEMBUR
                // =============================================
                'is_lembur' =>
                    $isLembur,

                'menit_lembur' =>
                    $menitLembur,


                // =============================================
                // KEGIATAN RESMI
                // =============================================
                'is_kegiatan_resmi' =>
                    $isKegiatanResmi,

                'official_activity_name' =>
                    $calendar?->official_activity_name,


                // =============================================
                // KALDIK
                // =============================================
                'is_hari_kerja_khusus' =>
                    $isHariKerjaKhusus,

                'calendar_type' =>
                    $calendar?->type,

                'calendar_name' =>
                    $calendar?->name,

                'calendar_description' =>
                    $calendar?->description,

                'academic_year' =>
                    $calendar?->academic_year,
            ];
        }


        // =====================================================
        // SUMMARY
        // =====================================================
        $summary = [

            'hadir' => 0,

            'izin' => 0,

            'tidak_masuk' => 0,

            'hari_kerja_khusus' => 0,

            'kegiatan_resmi' => 0,

            'hari_lembur' => 0,

            'total_menit_lembur' => 0,

            'telat' => 0,

            'total_menit_telat' => 0,

            'pulang_cepat' => 0,

            'total_menit_pulang_cepat' => 0,

            'tanpa_keterangan' => 0,

            'keluar_tanpa_izin' => 0,

            // Dalam menit
            'total_jam_keluar' => 0,
        ];


        // =====================================================
        // HITUNG SUMMARY
        // =====================================================
        foreach ($data as $row) {

            $tanggal =
                $row['tanggal'];

            $att =
                $row['absen'];

            $izin =
                $row['izin'];

            $jadwal =
                $row['jadwal'];


            // =================================================
            // KEGIATAN RESMI
            //
            // Tidak masuk:
            //
            // - hari kerja reguler
            // - hadir reguler
            // - terlambat
            // - pulang cepat
            // - keluar tanpa izin
            // - jam lembur
            // =================================================
            $isKegiatanResmi =
                (bool) (
                    $row['is_kegiatan_resmi']
                    ?? false
                );


            if ($isKegiatanResmi) {

                $summary['kegiatan_resmi']++;

                continue;
            }


            // =================================================
            // LEMBUR
            //
            // Dipisahkan dari statistik reguler.
            // =================================================
            $isLembur =
                (bool) (
                    $row['is_lembur']
                    ?? false
                );


            if ($isLembur) {

                $summary['hari_lembur']++;

                $summary['total_menit_lembur']
                    += (int) (
                        $row['menit_lembur']
                        ?? 0
                    );

                continue;
            }


            // =================================================
            // HARI KERJA KHUSUS
            // =================================================
            $isHariKerjaKhusus =
                (bool) (
                    $row['is_hari_kerja_khusus']
                    ?? false
                );


            if ($isHariKerjaKhusus) {

                $summary[
                    'hari_kerja_khusus'
                ]++;
            }


            // =================================================
            // IZIN
            // =================================================
            $izinCount =
                $izin->count();


            // =================================================
            // JAM ABSENSI
            // =================================================
            $jamMasuk =
                optional($att)->check_in;

            $jamPulang =
                optional($att)->check_out;


            // =================================================
            // SAFETY
            //
            // Untuk statistik reguler harus ada jadwal.
            // =================================================
            if (!$jadwal) {
                continue;
            }


            $jamMasukStandar =
                $jadwal['jam_masuk']
                ?? null;

            $jamPulangStandar =
                $jadwal['jam_pulang']
                ?? null;


            if (
                !$jamMasukStandar
                || !$jamPulangStandar
            ) {
                continue;
            }


            // =================================================
            // JAM STANDAR
            // =================================================
            $standarMasuk = Carbon::parse(
                $tanggal
                . ' '
                . $jamMasukStandar
            );


            $standarPulang = Carbon::parse(
                $tanggal
                . ' '
                . $jamPulangStandar
            );


            // =================================================
            // ADA ABSENSI
            // =================================================
            if ($att) {

                $summary['hadir']++;


                // =================================================
                // JAM MASUK / PULANG
                // =================================================
                $masukFix = $jamMasuk
                    ? Carbon::parse(
                        $tanggal
                        . ' '
                        . $jamMasuk
                    )
                    : null;


                $pulangFix = $jamPulang
                    ? Carbon::parse(
                        $tanggal
                        . ' '
                        . $jamPulang
                    )
                    : null;


                // =================================================
                // TERLAMBAT
                // =================================================
                $telat =
                    $masukFix
                    && $masukFix->gt(
                        $standarMasuk
                    );


                // =================================================
                // CEK IZIN TERLAMBAT
                // =================================================
                $izinTerlambat = $izin->first(
                    function ($permission) {

                        return
                            $permission->type === 'Izin Terlambat'
                            &&
                            $permission->time_start;
                    }
                );


                if ($telat) {

                    // Normalnya dihitung dari jam standar
                    $menitTelat =
                        (int) $standarMasuk
                            ->diffInMinutes(
                                $masukFix
                            );


                    // =============================================
                    // ADA IZIN TERLAMBAT
                    // =============================================
                    if ($izinTerlambat) {

                        $batasIzinDatang =
                            Carbon::parse(
                                $tanggal
                                . ' '
                                . $izinTerlambat->time_start
                            );


                        // Toleransi fingerprint 5 menit
                        $batasIzinDenganToleransi =
                            $batasIzinDatang
                                ->copy()
                                ->addMinutes(5);


                        // =========================================
                        // DATANG MASIH DALAM BATAS IZIN
                        // → BUKAN PELANGGARAN
                        // =========================================
                        if (
                            $masukFix->lte(
                                $batasIzinDenganToleransi
                            )
                        ) {

                            $telat = false;
                            $menitTelat = 0;

                        } else {

                            // =====================================
                            // DATANG MELEBIHI BATAS IZIN
                            //
                            // Hanya kelebihannya yang dihitung.
                            // =====================================
                            $menitTelat =
                                (int) $batasIzinDatang
                                    ->diffInMinutes(
                                        $masukFix
                                    );
                        }
                    }


                    // =============================================
                    // MASIH TERLAMBAT SETELAH CEK IZIN
                    // =============================================
                    if (
                        $telat
                        &&
                        $menitTelat > 0
                    ) {

                        $summary['telat']++;

                        $summary[
                            'total_menit_telat'
                        ] += $menitTelat;
                    }
                }


                // =================================================
                // PULANG CEPAT
                // =================================================
                $pulangCepat =
                    $pulangFix
                    && $pulangFix->lt(
                        $standarPulang
                    );


                // =================================================
                // CEK IZIN PULANG AWAL
                // =================================================
                $izinPulangAwal = $izin->first(
                    function ($permission) {

                        return
                            $permission->type === 'Izin Pulang Awal'
                            &&
                            $permission->time_start;
                    }
                );


                if ($pulangCepat) {

                    // Normalnya dihitung sampai jam pulang standar
                    $menitPulangCepat =
                        (int) $pulangFix
                            ->diffInMinutes(
                                $standarPulang
                            );


                    // =============================================
                    // ADA IZIN PULANG AWAL
                    // =============================================
                    if ($izinPulangAwal) {

                        $jamIzinPulang =
                            Carbon::parse(
                                $tanggal
                                . ' '
                                . $izinPulangAwal->time_start
                            );


                        // Toleransi fingerprint 5 menit
                        $batasAwalDenganToleransi =
                            $jamIzinPulang
                                ->copy()
                                ->subMinutes(5);


                        // =========================================
                        // PULANG SESUAI / DALAM TOLERANSI IZIN
                        // =========================================
                        if (
                            $pulangFix->gte(
                                $batasAwalDenganToleransi
                            )
                        ) {

                            $pulangCepat = false;
                            $menitPulangCepat = 0;

                        } else {

                            // =====================================
                            // Pulang bahkan lebih awal
                            // dari batas yang diizinkan.
                            //
                            // Hanya selisih dari jam izin.
                            // =====================================
                            $menitPulangCepat =
                                (int) $pulangFix
                                    ->diffInMinutes(
                                        $jamIzinPulang
                                    );
                        }
                    }


                    // =============================================
                    // MASIH PELANGGARAN SETELAH CEK IZIN
                    // =============================================
                    if (
                        $pulangCepat
                        &&
                        $menitPulangCepat > 0
                    ) {

                        $summary[
                            'pulang_cepat'
                        ]++;

                        $summary[
                            'total_menit_pulang_cepat'
                        ] += $menitPulangCepat;
                    }
                }


                // =================================================
                // IZIN
                // =================================================
                if ($izinCount > 0) {

                    $summary['izin']++;
                }


                // =================================================
                // KELUAR TENGAH JAM KERJA
                //
                // Cari pasangan:
                //
                // OUT → IN
                // =================================================
                $activities =
                    $att->activities
                        ->sortBy('time')
                        ->values();


                for (
                    $i = 0;
                    $i < $activities->count() - 1;
                    $i++
                ) {

                    $current =
                        $activities[$i];

                    $next =
                        $activities[$i + 1];


                    // =============================================
                    // HANYA OUT → IN
                    // =============================================
                    if (
                        $current->type !== 'out'
                        || $next->type !== 'in'
                    ) {
                        continue;
                    }


                    $jamKeluar = Carbon::parse(
                        $tanggal
                        . ' '
                        . $current->time
                    );


                    $jamKembali = Carbon::parse(
                        $tanggal
                        . ' '
                        . $next->time
                    );


                    // =============================================
                    // HANYA KELUAR DALAM JAM KERJA
                    // =============================================
                    if (
                        $jamKeluar->lt(
                            $standarMasuk
                        )
                        ||
                        $jamKeluar->gte(
                            $standarPulang
                        )
                    ) {
                        continue;
                    }


                    // =============================================
                    // CEK IZIN YANG MENG-COVER
                    // WAKTU KELUAR → KEMBALI
                    // =============================================
                    $adaIzin =
                        $izin->contains(
                            function ($permission) use (
                                $tanggal,
                                $jamKeluar,
                                $jamKembali
                            ) {

                                if (
                                    !$permission->time_start
                                    ||
                                    !$permission->time_end
                                ) {
                                    return false;
                                }


                                $izinMulai =
                                    Carbon::parse(
                                        $tanggal
                                        . ' '
                                        . $permission->time_start
                                    );


                                $izinSelesai =
                                    Carbon::parse(
                                        $tanggal
                                        . ' '
                                        . $permission->time_end
                                    );


                                return
                                    $izinMulai->lte(
                                        $jamKeluar
                                    )
                                    &&
                                    $izinSelesai->gte(
                                        $jamKembali
                                    );
                            }
                        );


                    // =============================================
                    // KELUAR TANPA IZIN
                    // =============================================
                    if (!$adaIzin) {

                        $summary[
                            'keluar_tanpa_izin'
                        ]++;
                    }
                }


                // =================================================
                // TOTAL JAM KERJA AKTUAL
                //
                // check-in → check-out
                // dikurangi semua OUT → IN.
                // =================================================
                if (
                    $jamMasuk
                    && $jamPulang
                ) {

                    $jamStart =
                        Carbon::parse(
                            $tanggal
                            . ' '
                            . $jamMasuk
                        );


                    $jamEnd =
                        Carbon::parse(
                            $tanggal
                            . ' '
                            . $jamPulang
                        );


                    if (
                        $jamEnd->gt(
                            $jamStart
                        )
                    ) {

                        // =========================================
                        // TOTAL DATANG → PULANG
                        // =========================================
                        $totalMenit =
                            (int) $jamStart
                                ->diffInMinutes(
                                    $jamEnd
                                );


                        // =========================================
                        // TOTAL WAKTU DI LUAR
                        // =========================================
                        $totalMenitKeluar = 0;


                        $activitiesKerja =
                            $att->activities
                                ->sortBy('time')
                                ->values();


                        for (
                            $i = 0;
                            $i < $activitiesKerja->count() - 1;
                            $i++
                        ) {

                            $current =
                                $activitiesKerja[$i];

                            $next =
                                $activitiesKerja[$i + 1];


                            if (
                                $current->type !== 'out'
                                ||
                                $next->type !== 'in'
                            ) {
                                continue;
                            }


                            $keluar =
                                Carbon::parse(
                                    $tanggal
                                    . ' '
                                    . $current->time
                                );


                            $kembali =
                                Carbon::parse(
                                    $tanggal
                                    . ' '
                                    . $next->time
                                );


                            if (
                                $kembali->gt(
                                    $keluar
                                )
                            ) {

                                $totalMenitKeluar +=
                                    (int) $keluar
                                        ->diffInMinutes(
                                            $kembali
                                        );
                            }
                        }


                        // =========================================
                        // JAM KERJA AKTUAL
                        // =========================================
                        $menitKerjaAktual =
                            max(
                                0,
                                $totalMenit
                                - $totalMenitKeluar
                            );


                        $summary[
                            'total_jam_keluar'
                        ] += $menitKerjaAktual;
                    }
                }

            } else {

                // =================================================
                // TIDAK ADA ABSENSI
                // =================================================

                if ($isHariKerjaKhusus) {

                    // =============================================
                    // Sesuai rule yang sekarang dipakai:
                    //
                    // Hari Kerja Khusus dianggap hadir
                    // walaupun fingerprint tidak ada.
                    // =============================================
                    $summary['hadir']++;

                } elseif ($izinCount > 0) {

                    $summary['izin']++;

                    $summary[
                        'tidak_masuk'
                    ]++;

                } else {

                    $summary[
                        'tidak_masuk'
                    ]++;

                    $summary[
                        'tanpa_keterangan'
                    ]++;
                }
            }
        }


        // =====================================================
        // TOTAL HARI KERJA REGULER
        //
        // Lembur dan Kegiatan Resmi tidak menambah
        // jumlah Hari Kerja.
        // =====================================================
        $summary['total_hari_kerja'] =
            collect($data)
                ->filter(function ($row) {

                    $isLembur =
                        (bool) (
                            $row['is_lembur']
                            ?? false
                        );


                    $isKegiatanResmi =
                        (bool) (
                            $row[
                                'is_kegiatan_resmi'
                            ]
                            ?? false
                        );


                    return
                        !$isLembur
                        &&
                        !$isKegiatanResmi;
                })
                ->count();


        // =====================================================
        // TOTAL JAM KERJA
        // =====================================================
        $summary['total_jam_keluar_jam'] =
            round(
                $summary['total_jam_keluar']
                / 60,
                1
            );


        // =====================================================
        // TOTAL JAM LEMBUR
        // =====================================================
        $summary['total_jam_lembur_jam'] =
            round(
                $summary['total_menit_lembur']
                / 60,
                1
            );


        // =====================================================
        // VIEW
        // =====================================================
        return view(
            'pages.absensi.detail-range',
            compact(
                'data',
                'employee',
                'start',
                'end',
                'summary'
            )
        );
    }

    public function formUpload()
    {
        $units = Unit::all();
        return view('pages.absensi.form', compact('units'));
    }

    public function uploadLog(
        Request $request,
        AttendanceFileImportService $importService,
        EmployeeScheduleService $scheduleService
    )
    {
        $request->validate([
            'unit_id' => [
                'required',
                'exists:units,id',
            ],

            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xls,xlsx',
                'max:10240',
            ],
        ]);

        try {

            $result = $importService->import(
                $request->file('file'),
                (int) $request->unit_id,
                $scheduleService
            );

            $message = 'Import absensi berhasil. ';

            if (($result['inserted_logs'] ?? 0) > 0) {
                $message .=
                    'Raw scan: ' .
                    number_format($result['inserted_logs']) .
                    '. ';
            }

            if (($result['saved_attendances'] ?? 0) > 0) {
                $message .=
                    'Attendance: ' .
                    number_format($result['saved_attendances']) .
                    '. ';
            }

            if (($result['duplicates'] ?? 0) > 0) {
                $message .=
                    'Duplicate: ' .
                    number_format($result['duplicates']) .
                    '. ';
            }

            if (($result['skipped'] ?? 0) > 0) {
                $message .=
                    'Skipped: ' .
                    number_format($result['skipped']) .
                    '. ';
            }

            if (($result['needs_process'] ?? false) === true) {
                $message .=
                    'File ini berisi raw scan. ' .
                    'Silakan klik Process Absensi.';
            }

            $missingUids =
                $result['missing_uids'] ?? [];

            return redirect()
                ->back()
                ->with([
                    'success' => $message,
                    'import_result' => $result,
                    'missing_uids' => $missingUids,
                ]);

        } catch (\Throwable $e) {

            \Log::error(
                'Import absensi gagal',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'Import absensi gagal: ' .
                    $e->getMessage()
                );
        }
    }

//     public function upload(Request $request)
// {
//     $file = $request->file('file');
//     $rows = array_map('str_getcsv', file($file));

//     $header = array_map('strtolower', $rows[0]);

//     if (in_array('datetime', $header)) {
//         $type = 'um';
//     } else {
//         $type = 'sekolah';
//     }

//     unset($rows[0]);

//     foreach ($rows as $row) {

//         // 🟦 UM
//         if ($type == 'um') {

//             $uid = $row[0];
//             $datetime = $row[1];

//             $time = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $datetime);

//             $employee = Employee::where('uid', $uid)->first();
//             if (!$employee) continue;

//             AttendanceLog::firstOrCreate([
//                 'employee_id' => $employee->id,
//                 'uid' => $employee->uid,
//                 'scan_time' => $time
//             ]);
//         }

//         // 🟩 SEKOLAH
//         else {

//             $uid     = $row[0];
//             $unit    = $row[1];
//             $tanggal = $row[2];
//             $in      = $row[3];
//             $out     = $row[4];

//             $date = \Carbon\Carbon::createFromFormat('d-M-y', $tanggal)->format('Y-m-d');
//             $checkIn  = \Carbon\Carbon::parse($date . ' ' . $in);
//             $checkOut = \Carbon\Carbon::parse($date . ' ' . $out);

//             $employee = Employee::where('uid', $uid)
//                 ->whereHas('unit', function ($q) use ($unit) {
//                     $q->where('code', $unit);
//                 })
//                 ->first();

//             if (!$employee) continue;

//             Attendance::updateOrCreate(
//                 [
//                     'employee_id' => $employee->id,
//                     'date' => $date
//                 ],
//                 [
//                     'check_in' => $checkIn->format('H:i:s'),
//                     'check_out' => $checkOut->format('H:i:s'),
//                     'late_minutes' => 0,
//                 ]
//             );
//         }
//     }

//     if ($type == 'um') {
//         $this->processAttendance();
//     }

//     return back()->with('success', 'Upload berhasil');
// }

    private function parseSekolah($row)
    {
        // contoh:
        // 1;Rina;20-Jul-26;06:55;17:43

        $employeeId = $row[0];

        if (!$row[3] || !$row[4]) return;

        $checkIn = \Carbon\Carbon::createFromFormat(
            'd-M-y H:i',
            $row[2] . ' ' . $row[3]
        )->format('Y-m-d H:i:s');

        $checkOut = \Carbon\Carbon::createFromFormat(
            'd-M-y H:i',
            $row[2] . ' ' . $row[4]
        )->format('Y-m-d H:i:s');

        \App\Models\Attendance::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'date' => date('Y-m-d', strtotime($checkIn)),
            ],
            [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
            ]
        );
    }

    public function processLogs(EmployeeScheduleService $scheduleService)
    {
        $logs = AttendanceLog::with('employee')
            ->orderBy('scan_time')
            ->get();

        // GROUP BY employee + tanggal
        $grouped = $logs->groupBy(function ($item) {
            return $item->employee_id . '-' .
                date('Y-m-d', strtotime($item->scan_time));
        });

        foreach ($grouped as $key => $items) {

            $employee = $items->first()->employee;

            if (!$employee) {
                continue;
            }

            $employee_id = $employee->id;

            $date = date(
                'Y-m-d',
                strtotime($items->first()->scan_time)
            );

            $checkIn = $items->first()->scan_time;
            $checkOut = $items->last()->scan_time;

            // =====================================================
            // AMBIL JADWAL SESUAI EMPLOYEE + TANGGAL
            // =====================================================
            $jadwal = $scheduleService->getSchedule(
                $employee,
                $date
            );

            // Tidak ada jadwal pada tanggal tersebut
            if (!$jadwal) {
                continue;
            }

            // =====================================================
            // JAM MASUK STANDAR
            // =====================================================
            $jamMasuk = \Carbon\Carbon::parse(
                $date . ' ' . $jadwal['jam_masuk']
            );

            $jamScan = \Carbon\Carbon::parse($checkIn);

            // =====================================================
            // HITUNG TERLAMBAT
            // =====================================================
            $late = 0;

            if ($jamScan->gt($jamMasuk)) {

                $late = $jamMasuk->diffInMinutes($jamScan);

            }

            // =====================================================
            // SIMPAN ATTENDANCE
            // =====================================================
            Attendance::updateOrCreate(
                [
                    'employee_id' => $employee_id,
                    'date' => $date,
                ],
                [
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'late_minutes' => $late,
                    'status' => 'hadir',
                ]
            );
        }

        return back()->with(
            'success',
            'Absensi berhasil diproses!'
        );
    }
    
    private function parseTanggal($value)
    {
        if (!$value) {
            return null;
        }

        try {

            return \Carbon\Carbon::parse($value);

        } catch (\Exception $e) {

            return null;

        }
    }

    public function process(
        EmployeeScheduleService $scheduleService,
        WorkCalendarService $calendarService
    )
    {
        // =====================================================
        // AMBIL SEMUA RAW LOG
        // =====================================================
        $logs = AttendanceLog::whereNotNull(
            'employee_id'
        )->get();


        // =====================================================
        // PARSE WAKTU
        // =====================================================
        $logs = $logs
            ->map(function ($log) {

                $log->parsed_time =
                    $this->parseTanggal(
                        $log->scan_time
                    );

                return $log;

            })
            ->filter(function ($log) {

                return $log->parsed_time;

            });


        // =====================================================
        // GROUP EMPLOYEE + TANGGAL
        // =====================================================
        $grouped = $logs->groupBy(
            function ($item) {

                return
                    $item->employee_id
                    . '-'
                    . $item->parsed_time
                        ->format('Y-m-d');
            }
        );


        foreach ($grouped as $items) {

            $items = $items
                ->sortBy('parsed_time')
                ->values();


            if ($items->isEmpty()) {
                continue;
            }


            $first = $items->first();

            $employeeId =
                $first->employee_id;

            $tanggal =
                $first->parsed_time
                    ->format('Y-m-d');


            // =================================================
            // EMPLOYEE
            // =================================================
            $employee = Employee::with(
                'workSchedule'
            )->find($employeeId);


            if (!$employee) {
                continue;
            }


            // =================================================
            // JADWAL AKTUAL
            // =================================================
            $jadwal =
                $scheduleService->getSchedule(
                    $employee,
                    $tanggal
                );

            // =================================================
            // CEK KALDIK
            // =================================================
            $calendar = $calendarService->getCalendar(
                $tanggal,
                $employee->unit_id
            );


            // =================================================
            // KEGIATAN RESMI DI HARI LIBUR
            //
            // Contoh:
            // 17 Agustus tetap libur nasional,
            // tetapi ada Upacara Hari Kemerdekaan.
            // =================================================
            $isKegiatanResmi =
                !$jadwal
                &&
                $calendar
                &&
                !$calendar->is_workday
                &&
                (bool) $calendar->has_official_activity;


            // =================================================
            // LEMBUR
            //
            // Tidak ada jadwal + bukan kegiatan resmi.
            // =================================================
            $isLembur =
                !$jadwal
                &&
                !$isKegiatanResmi;

            // =================================================
            // JAM REFERENSI UNTUK MENENTUKAN IN / OUT
            //
            // Hari kerja normal:
            // → pakai jadwal aktual.
            //
            // Hari libur:
            // → Lembur maupun Kegiatan Resmi
            // → pakai template dasar pegawai sebagai referensi.
            //
            // Jam referensi ini TIDAK digunakan untuk menghitung
            // telat pada lembur / kegiatan resmi.
            // =================================================
            if ($jadwal) {

                // =============================================
                // HARI KERJA NORMAL
                // =============================================
                $jamReferensiMasuk =
                    \Carbon\Carbon::parse(
                        $tanggal
                        . ' '
                        . $jadwal['jam_masuk']
                    );

                $jamReferensiPulang =
                    \Carbon\Carbon::parse(
                        $tanggal
                        . ' '
                        . $jadwal['jam_pulang']
                    );

            } else {

                // =============================================
                // HARI LIBUR
                //
                // Bisa:
                // - LEMBUR
                // - KEGIATAN RESMI
                //
                // Gunakan template dasar hanya untuk menentukan
                // scan pertama kemungkinan IN atau OUT.
                // =============================================
                $baseSchedule =
                    $employee->workSchedule;


                if (
                    $baseSchedule
                    && $baseSchedule->jam_masuk
                    && $baseSchedule->jam_pulang
                ) {

                    $jamReferensiMasuk =
                        \Carbon\Carbon::parse(
                            $tanggal
                            . ' '
                            . $baseSchedule->jam_masuk
                        );

                    $jamReferensiPulang =
                        \Carbon\Carbon::parse(
                            $tanggal
                            . ' '
                            . $baseSchedule->jam_pulang
                        );

                } else {

                    // =========================================
                    // FALLBACK
                    //
                    // Hanya jika pegawai belum mempunyai
                    // template jadwal dasar.
                    // =========================================
                    $jamReferensiMasuk =
                        \Carbon\Carbon::parse(
                            $tanggal . ' 08:00:00'
                        );

                    $jamReferensiPulang =
                        \Carbon\Carbon::parse(
                            $tanggal . ' 16:00:00'
                        );
                }
            }


            // =================================================
            // TITIK TENGAH JADWAL REFERENSI
            // =================================================
            $durasiDetik =
                $jamReferensiMasuk
                    ->diffInSeconds(
                        $jamReferensiPulang
                    );


            $midPoint =
                $jamReferensiMasuk
                    ->copy()
                    ->addSeconds(
                        (int) floor(
                            $durasiDetik / 2
                        )
                    );


            // =================================================
            // TENTUKAN TIPE SCAN PERTAMA
            // =================================================
            $firstScanTime =
                $first->parsed_time;


            $firstType =
                $firstScanTime->lte($midPoint)
                    ? 'in'
                    : 'out';


            // =================================================
            // SUSUN ACTIVITY
            // =================================================
            $activities = [];


            foreach (
                $items as $index => $item
            ) {

                if ($index % 2 === 0) {

                    $type =
                        $firstType;

                } else {

                    $type =
                        $firstType === 'in'
                            ? 'out'
                            : 'in';
                }


                $note =
                    $type === 'in'
                        ? 'Masuk'
                        : 'Keluar';


                // Scan pertama OUT:
                // kemungkinan lupa scan masuk.
                if (
                    $index === 0
                    &&
                    $type === 'out'
                ) {

                    $note =
                        'Keluar/Pulang '
                        . '(scan masuk tidak tercatat)';
                }


                // Activity terakhir IN:
                // kemungkinan lupa scan pulang.
                if (
                    $index === $items->count() - 1
                    &&
                    $type === 'in'
                ) {

                    $note =
                        $items->count() === 1
                            ? 'Masuk '
                                . '(scan pulang tidak tercatat)'
                            : 'Masuk/Kembali '
                                . '(scan pulang tidak tercatat)';
                }


                $activities[] = [

                    'time' =>
                        $item->parsed_time
                            ->format('H:i:s'),

                    'type' =>
                        $type,

                    'note' =>
                        $note,
                ];
            }


            // =================================================
            // CHECK IN
            // =================================================
            $firstActivity =
                $activities[0];


            $checkIn =
                $firstActivity['type'] === 'in'
                    ? $firstActivity['time']
                    : null;


            // =================================================
            // CHECK OUT
            // =================================================
            $lastActivity =
                $activities[
                    count($activities) - 1
                ];


            $checkOut =
                $lastActivity['type'] === 'out'
                    ? $lastActivity['time']
                    : null;


            // =================================================
            // TERLAMBAT
            //
            // LEMBUR TIDAK BOLEH DIHITUNG TELAT.
            // =================================================
            $late = 0;


            if (
                !$isLembur
                &&
                !$isKegiatanResmi
                &&
                $checkIn
            ) {

                $jamScanMasuk =
                    \Carbon\Carbon::parse(
                        $tanggal
                        . ' '
                        . $checkIn
                    );


                if (
                    $jamScanMasuk->gt(
                        $jamReferensiMasuk
                    )
                ) {

                    $late =
                        (int)
                        $jamReferensiMasuk
                            ->diffInMinutes(
                                $jamScanMasuk
                            );
                }
            }


            // =================================================
            // STATUS
            // =================================================
            if ($isKegiatanResmi) {

                $status = 'kegiatan_resmi';

            } elseif ($isLembur) {

                $status = 'lembur';

            } else {

                $status = 'hadir';
            }


            // =================================================
            // SIMPAN ATTENDANCE
            // =================================================
            $attendance =
                Attendance::updateOrCreate(
                    [
                        'employee_id' =>
                            $employeeId,

                        'date' =>
                            $tanggal,
                    ],
                    [
                        'check_in' =>
                            $checkIn,

                        'check_out' =>
                            $checkOut,

                        'late_minutes' =>
                            $late,

                        'status' =>
                            $status,
                    ]
                );


            // =================================================
            // REBUILD ACTIVITIES
            // =================================================
            $attendance
                ->activities()
                ->delete();


            foreach ($activities as $activity) {

                $attendance
                    ->activities()
                    ->create([
                        'time' =>
                            $activity['time'],

                        'type' =>
                            $activity['type'],

                        'note' =>
                            $activity['note'],
                    ]);
            }
        }


        return back()->with(
            'success',
            'Absensi berhasil diproses!'
        );
    }

    public function formIzin()
    {
        $employees = Employee::all();
        return view('pages.absensi.formIzin', compact('employees'));
    }

    public function formIzinStore(
        Request $request,
        ApprovalResolverService $approvalResolver,
        string $token
    ) {
        // =====================================================
        // VALIDASI TOKEN UNIT
        // =====================================================
        $tokenHash = hash(
            'sha256',
            $token
        );

        $formToken = UnitFormToken::where(
                'token_hash',
                $tokenHash
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        if (!$formToken) {
            abort(404);
        }


        // =====================================================
        // JENIS IZIN
        // =====================================================
        $allowedTypes = [

            // Izin operasional
            'Izin Keluar Sementara',
            'Izin Terlambat',
            'Izin Pulang Awal',
            'Izin Tidak Masuk',
            'Sakit',

            // Izin khusus / kepegawaian
            'Pernikahan Pegawai',
            'Pernikahan Anak Pegawai',
            'Istri Pegawai Melahirkan / Gugur Kandungan',
            'Suami/istri/anak/orangtua/mertua Pegawai masuk RS',
            'Suami/istri/anak/orangtua/mertua Pegawai meninggal dunia',
            'Izin Lainnya'
        ];


        $type = $request->input('type');


        // =====================================================
        // VALIDASI DASAR
        // =====================================================
        $rules = [

            'employee_id' => [
                'required',
                'exists:employees,id',
            ],

            'date_start' => [
                'required',
                'date',
            ],

            'type' => [
                'required',
                \Illuminate\Validation\Rule::in(
                    $allowedTypes
                ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048',
            ],
        ];


        // =====================================================
        // IZIN PARSIAL
        //
        // Hanya berlaku satu tanggal.
        // =====================================================
        $isPartialPermission = in_array(
            $type,
            [
                'Izin Keluar Sementara',
                'Izin Terlambat',
                'Izin Pulang Awal',
            ],
            true
        );


        if ($isPartialPermission) {

            // Tanggal selesai tidak wajib.
            // Nanti otomatis = tanggal mulai.
            $rules['date_end'] = [
                'nullable',
                'date',
            ];

        } else {

            // Izin sehari penuh / beberapa hari
            $rules['date_end'] = [
                'required',
                'date',
                'after_or_equal:date_start',
            ];
        }


        // =====================================================
        // JAM
        // =====================================================

        // -----------------------------------------------------
        // IZIN KELUAR / KEPERLUAN PRIBADI
        //
        // Wajib:
        // jam keluar + jam kembali
        // -----------------------------------------------------
        if (
            $type === 'Izin Keluar Sementara'
        ) {

            $rules['time_start'] = [
                'required',
                'date_format:H:i',
            ];

            $rules['time_end'] = [
                'required',
                'date_format:H:i',
                'after:time_start',
            ];

        }

        // -----------------------------------------------------
        // IZIN TERLAMBAT
        //
        // time_start kita gunakan sebagai
        // jam perkiraan datang.
        // -----------------------------------------------------
        elseif (
            in_array(
                $type,
                [
                    'Izin Terlambat',
                    'Izin Pulang Awal',
                ],
                true
            )
        ) {

            $rules['time_start'] = [
                'required',
                'date_format:H:i',
            ];

            $rules['time_end'] = [
                'nullable',
                'date_format:H:i',
            ];

        }

        // -----------------------------------------------------
        // IZIN SEHARI PENUH
        //
        // Tidak membutuhkan jam.
        // -----------------------------------------------------
        else {

            $rules['time_start'] = [
                'nullable',
                'date_format:H:i',
            ];

            $rules['time_end'] = [
                'nullable',
                'date_format:H:i',
            ];
        }


        // =====================================================
        // VALIDASI
        // =====================================================
        $validated = $request->validate(
            $rules,
            [
                'employee_id.required' =>
                    'Nama karyawan wajib dipilih.',

                'date_start.required' =>
                    'Tanggal mulai wajib diisi.',

                'date_end.required' =>
                    'Tanggal selesai wajib diisi.',

                'date_end.after_or_equal' =>
                    'Tanggal selesai tidak boleh sebelum tanggal mulai.',

                'type.required' =>
                    'Jenis izin wajib dipilih.',

                'type.in' =>
                    'Jenis izin tidak valid.',

                'time_start.required' =>
                    'Jam wajib diisi untuk jenis izin ini.',

                'time_end.required' =>
                    'Jam kembali wajib diisi.',

                'time_end.after' =>
                    'Jam kembali harus setelah jam keluar.',

                'attachment.mimes' =>
                    'Lampiran hanya boleh JPG, JPEG, PNG, atau PDF.',

                'attachment.max' =>
                    'Ukuran lampiran maksimal 2 MB.',
            ]
        );


        // =====================================================
        // CEK EMPLOYEE SESUAI UNIT TOKEN
        // =====================================================
        $employee = Employee::where(
                'id',
                $validated['employee_id']
            )
            ->where(
                'unit_id',
                $formToken->unit_id
            )
            ->first();

        if (!$employee) {

            abort(
                403,
                'Karyawan tidak sesuai dengan unit form.'
            );
        }


        // =====================================================
        // NORMALISASI TANGGAL / JAM
        // =====================================================

        if ($isPartialPermission) {

            // Izin parsial hanya berlaku satu hari
            $dateEnd =
                $validated['date_start'];

        } else {

            $dateEnd =
                $validated['date_end'];
        }


        // -----------------------------------------------------
        // IZIN FULL DAY
        //
        // Pastikan tidak ada jam lama/stale yang tersimpan.
        // -----------------------------------------------------
        if (!$isPartialPermission) {

            $timeStart = null;
            $timeEnd   = null;

        }

        // -----------------------------------------------------
        // IZIN TERLAMBAT
        // -----------------------------------------------------
        elseif (
            in_array(
                $type,
                [
                    'Izin Terlambat',
                    'Izin Pulang Awal',
                ],
                true
            )
        ) {

            $timeStart =
                $validated['time_start'];

            $timeEnd =
                null;

        }

        // -----------------------------------------------------
        // IZIN KELUAR / KEPERLUAN PRIBADI
        // -----------------------------------------------------
        else {

            $timeStart =
                $validated['time_start'];

            $timeEnd =
                $validated['time_end'];
        }


        // =====================================================
        // CARI APPROVER
        // =====================================================
        $approver = $approvalResolver->resolve(
            $employee
        );


        // =====================================================
        // UPLOAD LAMPIRAN
        // =====================================================
        $filePath = null;

        if ($request->hasFile('attachment')) {

            $file = $request->file(
                'attachment'
            );

            if (!$file->isValid()) {

                return back()
                    ->withErrors([
                        'attachment' =>
                            'File gagal diupload. Silakan coba lagi.'
                    ])
                    ->withInput();
            }

            $filePath = $file->store(
                'izin_files',
                'public'
            );
        }


        // =====================================================
        // SIMPAN IZIN
        // =====================================================
        AttendancePermission::create([

            'employee_id' =>
                $employee->id,

            'date_start' =>
                $validated['date_start'],

            'date_end' =>
                $dateEnd,

            'time_start' =>
                $timeStart,

            'time_end' =>
                $timeEnd,

            'type' =>
                $type,

            'description' =>
                $validated['description']
                ?? null,

            'attachment' =>
                $filePath,


            // =================================================
            // APPROVAL
            // =================================================
            'status' =>
                AttendancePermission::STATUS_PENDING,

            'approver_user_id' =>
                $approver?->id,

            'approved_by' =>
                null,

            'approved_at' =>
                null,

            'approval_note' =>
                null,
        ]);


        // =====================================================
        // SUCCESS
        // =====================================================
        return back()->with(
            'success',
            '✅ Pengajuan izin berhasil dikirim dan menunggu persetujuan pimpinan.'
        );
    }

    public function generateAttendance()
    {
        $logs = AttendanceLog::orderBy('scan_time')->get()
            ->groupBy(function ($item) {
                return $item->employee_id . '-' . date('Y-m-d', strtotime($item->scan_time));
            });

        foreach ($logs as $group) {

            $first = $group->first();
            $last  = $group->last();

            $date = date('Y-m-d', strtotime($first->scan_time));

            Attendance::updateOrCreate(
                [
                    'employee_id' => $first->employee_id,
                    'date' => $date
                ],
                [
                    'check_in'  => $first->scan_time,
                    'check_out' => $last->scan_time,
                    'late_minutes' => 0
                ]
            );
        }

        return back()->with('success', 'Generate attendance berhasil!');
    }

    public function export(Request $request)
    {
        $this->abortIfKepalaBidang();
        
        // =====================================================
        // SECURITY UNIT
        // =====================================================
        $kepsekUnitId =
            $this->getKepalaSekolahUnitId();


        // =====================================================
        // KEPALA SEKOLAH
        // Selalu pakai unit miliknya.
        // Request dari URL diabaikan.
        // =====================================================
        if ($kepsekUnitId !== null) {

            $unitId =
                $kepsekUnitId;

        } else {

            // Admin / Direktur boleh memilih unit
            $unitId =
                $request->filled('unit_id')
                ? (int) $request->unit_id
                : null;
        }


        $startDate =
            $request->start_date;

        $endDate =
            $request->end_date;

        // ==========================
        // DEFAULT TANGGAL
        // ==========================

        if (!$startDate || !$endDate) {

            $startDate = \Carbon\Carbon::now()
                ->startOfMonth()
                ->toDateString();

            $endDate = \Carbon\Carbon::now()
                ->endOfMonth()
                ->toDateString();
        }

        // ==========================
        // NAMA UNIT
        // ==========================

        if ($unitId) {

            $unit = Unit::find($unitId);

            $namaUnit = $unit
                ? $unit->nama
                : 'Unit';

        } else {

            $namaUnit = 'Semua_Unit';
        }

        // ==========================
        // NAMA FILE
        // ==========================

        $namaFile =
            'Rekap_Absensi_' .
            $namaUnit . '_' .
            \Carbon\Carbon::parse($startDate)->format('d-m-Y') .
            '_sampai_' .
            \Carbon\Carbon::parse($endDate)->format('d-m-Y') .
            '.xlsx';

        // ==========================
        // DOWNLOAD
        // ==========================

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport(
                $startDate,
                $endDate,
                $unitId
            ),
            $namaFile
        );
    }
}