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

    public function datatable(
        Request $request,
        EmployeeScheduleService $scheduleService,
        WorkCalendarService $calendarService
    ) {
        try {

            // =====================================================
            // RANGE TANGGAL
            // =====================================================
            $startDate = $request->start_date ?: Attendance::min('date');
            $endDate   = $request->end_date   ?: Attendance::max('date');

            if (!$startDate || !$endDate) {
                return DataTables::of([])->make(true);
            }

            $startDate = \Carbon\Carbon::parse($startDate)->toDateString();
            $endDate   = \Carbon\Carbon::parse($endDate)->toDateString();


            // =====================================================
            // EMPLOYEE + JADWAL
            // =====================================================
            $employeesQuery = Employee::with([
                'workSchedule.days',
                'employeeWorkSchedules.days',
            ]);

            if ($request->filled('unit_id')) {
                $employeesQuery->where(
                    'unit_id',
                    $request->unit_id
                );
            }

            $employees = $employeesQuery->get();


            // =====================================================
            // ATTENDANCE + ACTIVITIES
            // =====================================================
            $attendances = Attendance::with([
                    'activities'
                ])
                ->whereBetween(
                    'date',
                    [$startDate, $endDate]
                )
                ->get()
                ->groupBy(function ($item) {

                    return $item->employee_id . '_' .
                        \Carbon\Carbon::parse(
                            $item->date
                        )->toDateString();
                });


            // =====================================================
            // IZIN
            // HANYA YANG SUDAH APPROVED
            // =====================================================
            $allIzin = AttendancePermission::where(
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

                $period = \Carbon\CarbonPeriod::create(
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
                ];


                // =================================================
                // LOOP TANGGAL
                // =================================================
                foreach ($period as $date) {

                    $tanggal = $date->toDateString();


                    // =================================================
                    // JANGAN HITUNG MASA DEPAN
                    // =================================================
                    if ($date->isFuture()) {
                        continue;
                    }


                    // =================================================
                    // CEK KALDIK + JADWAL PEGAWAI
                    // =================================================
                    $jadwal = $scheduleService->getSchedule(
                        $emp,
                        $tanggal
                    );

                    $calendar = $calendarService->getCalendar(
                        $tanggal,
                        $emp->unit_id
                    );

                    $isHariKerjaKhusus =
                        $calendar
                        && $calendar->is_workday
                        && $calendar->type === 'hari_kerja_khusus';

                    // Tidak ada jadwal = bukan hari kerja
                    if (!$jadwal) {
                        continue;
                    }


                    // Hari kerja
                    $summary['total_hari_kerja']++;


                    // =================================================
                    // AMBIL ATTENDANCE
                    // =================================================
                    $key = $emp->id . '_' . $tanggal;

                    $att = $attendances
                        ->get($key)
                        ?->first();


                    // =================================================
                    // IZIN APPROVED PADA HARI INI
                    // =================================================
                    $izinHari = $allIzin
                        ->where(
                            'employee_id',
                            $emp->id
                        )
                        ->filter(function ($izin) use ($tanggal) {

                            $izinMulai = \Carbon\Carbon::parse(
                                $izin->date_start
                            )->toDateString();

                            $izinSelesai = \Carbon\Carbon::parse(
                                $izin->date_end
                            )->toDateString();

                            return $tanggal >= $izinMulai
                                && $tanggal <= $izinSelesai;
                        })
                        ->values();


                    $izinCount = $izinHari->count();


                    // =================================================
                    // ADA ABSENSI
                    // =================================================
                    if ($att) {

                        $summary['hadir']++;


                        $jamMasuk = $att->check_in;
                        $jamPulang = $att->check_out;


                        $masukFix = $jamMasuk
                            ? \Carbon\Carbon::parse(
                                $tanggal . ' ' . $jamMasuk
                            )
                            : null;


                        $pulangFix = $jamPulang
                            ? \Carbon\Carbon::parse(
                                $tanggal . ' ' . $jamPulang
                            )
                            : null;


                        // =================================================
                        // JAM STANDAR
                        // =================================================
                        $standarMasuk = \Carbon\Carbon::parse(
                            $tanggal . ' ' .
                            $jadwal['jam_masuk']
                        );

                        $standarPulang = \Carbon\Carbon::parse(
                            $tanggal . ' ' .
                            $jadwal['jam_pulang']
                        );


                        // =================================================
                        // TERLAMBAT
                        // =================================================
                        $telat = $masukFix
                            && $masukFix->gt(
                                $standarMasuk
                            );

                        if ($telat) {

                            $menitTelat = (int)
                                $standarMasuk
                                    ->diffInMinutes(
                                        $masukFix
                                    );

                            $summary['telat']++;

                            $summary['total_menit_telat']
                                += $menitTelat;
                        }


                        // =================================================
                        // PULANG CEPAT
                        // =================================================
                        $pulangCepat = $pulangFix
                            && $pulangFix->lt(
                                $standarPulang
                            );

                        if ($pulangCepat) {

                            $menitPulangCepat = (int)
                                $pulangFix
                                    ->diffInMinutes(
                                        $standarPulang
                                    );

                            $summary['pulang_cepat']++;

                            $summary[
                                'total_menit_pulang_cepat'
                            ] += $menitPulangCepat;
                        }


                        // =================================================
                        // IZIN APPROVED
                        // =================================================
                        if ($izinCount > 0) {
                            $summary['izin']++;
                        }


                        // =================================================
                        // ACTIVITIES
                        // =================================================
                        $activities = $att->activities
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

                            $current = $activities[$i];
                            $next    = $activities[$i + 1];


                            // Hanya pasangan OUT → IN
                            if (
                                $current->type !== 'out'
                                || $next->type !== 'in'
                            ) {
                                continue;
                            }


                            $jamKeluar = \Carbon\Carbon::parse(
                                $tanggal . ' ' .
                                $current->time
                            );

                            $jamKembali = \Carbon\Carbon::parse(
                                $tanggal . ' ' .
                                $next->time
                            );


                            // =================================================
                            // KELUAR HARUS DALAM JAM KERJA
                            // =================================================
                            if (
                                $jamKeluar->lt($standarMasuk)
                                || $jamKeluar->gte($standarPulang)
                            ) {
                                continue;
                            }


                            // =================================================
                            // CEK IZIN APPROVED YANG MENG-COVER
                            // OUT → IN
                            // =================================================
                            $adaIzin = $izinHari->contains(
                                function ($permission) use (
                                    $tanggal,
                                    $jamKeluar,
                                    $jamKembali
                                ) {

                                    if (
                                        !$permission->time_start
                                        || !$permission->time_end
                                    ) {
                                        return false;
                                    }


                                    $izinMulai =
                                        \Carbon\Carbon::parse(
                                            $tanggal . ' ' .
                                            $permission->time_start
                                        );


                                    $izinSelesai =
                                        \Carbon\Carbon::parse(
                                            $tanggal . ' ' .
                                            $permission->time_end
                                        );


                                    return $izinMulai->lte(
                                            $jamKeluar
                                        )
                                        && $izinSelesai->gte(
                                            $jamKembali
                                        );
                                }
                            );


                            // Tidak ada izin approved
                            if (!$adaIzin) {

                                $summary[
                                    'keluar_tanpa_izin'
                                ]++;
                            }
                        }


                        // =================================================
                        // TOTAL JAM KERJA AKTUAL
                        //
                        // check-in pertama
                        // sampai check-out terakhir
                        //
                        // dikurangi seluruh OUT → IN
                        // =================================================
                        if (
                            $masukFix
                            && $pulangFix
                            && $pulangFix->gt($masukFix)
                        ) {

                            // Total rentang datang sampai pulang
                            $totalMenit = (int)
                                $masukFix
                                    ->diffInMinutes(
                                        $pulangFix
                                    );


                            // Total waktu keluar
                            $totalMenitKeluar = 0;


                            for (
                                $i = 0;
                                $i < $activities->count() - 1;
                                $i++
                            ) {

                                $current = $activities[$i];
                                $next    = $activities[$i + 1];


                                // Hanya OUT → IN
                                if (
                                    $current->type !== 'out'
                                    || $next->type !== 'in'
                                ) {
                                    continue;
                                }


                                $keluar =
                                    \Carbon\Carbon::parse(
                                        $tanggal . ' ' .
                                        $current->time
                                    );


                                $kembali =
                                    \Carbon\Carbon::parse(
                                        $tanggal . ' ' .
                                        $next->time
                                    );


                                if (
                                    $kembali->gt($keluar)
                                ) {

                                    $totalMenitKeluar +=
                                        (int)
                                        $keluar
                                            ->diffInMinutes(
                                                $kembali
                                            );
                                }
                            }


                            // =================================================
                            // JAM KERJA AKTUAL
                            // =================================================
                            $menitKerjaAktual = max(
                                0,
                                $totalMenit
                                - $totalMenitKeluar
                            );


                            $summary['total_menit']
                                += $menitKerjaAktual;
                        }
                    }

                    // =================================================
                    // TIDAK ADA ABSENSI
                    // =================================================
                    else {

                        // =============================================
                        // HARI KERJA KHUSUS
                        //
                        // Contoh:
                        // Outbond Guru/Karyawan
                        //
                        // Tidak ada fingerprint TIDAK dianggap
                        // tidak masuk / tanpa keterangan.
                        // =============================================
                        if ($isHariKerjaKhusus) {

                            // Hari kerja khusus resmi dianggap hadir
                            // walaupun tidak ada fingerprint.
                            $summary['hadir']++;

                            // Kalau summary datatable sudah punya counter ini:
                            if (isset($summary['hari_kerja_khusus'])) {
                                $summary['hari_kerja_khusus']++;
                            }

                        } else {

                            $summary['tidak_masuk']++;

                            if ($izinCount > 0) {

                                $summary['izin']++;

                            } else {

                                $summary['tanpa_keterangan']++;
                            }
                        }
                    }
                }


                // =====================================================
                // HASIL PER EMPLOYEE
                // =====================================================
                $result[] = [

                    'nama' => $emp->nama,

                    'total_hari_kerja' =>
                        $summary['total_hari_kerja'],

                    'izin' =>
                        $summary['izin'],

                    'total_hadir' =>
                        $summary['hadir'],

                    'tidak_masuk' =>
                        $summary['tidak_masuk'],

                    'total_telat' =>
                        $summary['telat'],

                    'total_menit_telat' =>
                        $summary['total_menit_telat'],

                    'pulang_cepat' =>
                        $summary['pulang_cepat'],

                    'total_menit_pulang_cepat' =>
                        $summary[
                            'total_menit_pulang_cepat'
                        ],

                    'tanpa_keterangan' =>
                        $summary['tanpa_keterangan'],

                    'keluar_tanpa_izin' =>
                        $summary['keluar_tanpa_izin'],

                    'total_jam' => round(
                        $summary['total_menit'] / 60,
                        1
                    ),

                    'aksi' => route(
                        'absensi.detailRange',
                        [
                            'employee' => $emp->id,
                            'start' => $startDate,
                            'end' => $endDate,
                        ]
                    ),
                ];
            }


            // =====================================================
            // DATATABLE
            // =====================================================
            return DataTables::of($result)

                ->addColumn(
                    'aksi',
                    function ($row) {

                        return '<a href="' .
                            $row['aksi'] .
                            '" class="btn btn-info btn-sm">
                                Detail
                            </a>';
                    }
                )

                ->rawColumns([
                    'aksi'
                ])

                ->make(true);


        } catch (\Throwable $e) {

            return response()->json([

                'error' => true,

                'message' => $e->getMessage(),

                'line' => $e->getLine(),

            ]);
        }
    }

    public function index()
    {
        return view('pages.absensi.index');
    }

    public function detailRange(
        Request $request,
        EmployeeScheduleService $scheduleService,
        $employeeId,
        WorkCalendarService $calendarService
    )
    {
        // =====================================================
        // EMPLOYEE
        // =====================================================
        $employee = Employee::with([
            'unit',
            'workSchedule.days',
            'employeeWorkSchedules.days'
        ])->findOrFail($employeeId);


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
        $period = CarbonPeriod::create($start, $end);

        $data = [];

        foreach ($period as $date) {

            $tanggal = $date->toDateString();

            // Jangan hitung masa depan
            if ($date->isFuture()) {
                continue;
            }

            // =================================================
            // CEK KALDIK + JADWAL PEGAWAI
            // =================================================
            $jadwal = $scheduleService->getSchedule(
                $employee,
                $tanggal
            );

            $calendar = $calendarService->getCalendar(
                $tanggal,
                $employee->unit_id
            );

            $isHariKerjaKhusus =
                $calendar
                && $calendar->is_workday
                && $calendar->type === 'hari_kerja_khusus';

            // Tidak punya jadwal = bukan hari kerja
            if (!$jadwal) {
                continue;
            }


            // =================================================
            // ABSENSI + ACTIVITIES
            // =================================================
            $absen = Attendance::with([
                'activities' => function ($q) {
                    $q->orderBy('time');
                }
            ])
            ->where('employee_id', $employeeId)
            ->whereDate('date', $tanggal)
            ->first();


            // =================================================
            // IZIN
            // =================================================
            $izin = AttendancePermission::where(
                    'employee_id',
                    $employeeId
                )
                ->whereDate('date_start', '<=', $tanggal)
                ->whereDate('date_end', '>=', $tanggal)
                ->where(
                    'status',
                    AttendancePermission::STATUS_APPROVED
                )
                ->get();


            // =================================================
            // STATUS HARI
            // =================================================
            if ($isHariKerjaKhusus) {

                $status = 'HARI_KERJA_KHUSUS';

            } elseif ($izin->count() > 0) {

                $status = 'IZIN';

            } elseif ($absen) {

                $status = 'HADIR';

            } else {

                $status = 'ALPHA';
            }


            $data[] = [
                'tanggal' => $tanggal,
                'absen'   => $absen,
                'izin'    => $izin,
                'status'  => $status,
                'jadwal'  => $jadwal,
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

            'telat' => 0,
            'total_menit_telat' => 0,

            'pulang_cepat' => 0,
            'total_menit_pulang_cepat' => 0,

            'tanpa_keterangan' => 0,
            'keluar_tanpa_izin' => 0,

            'total_jam_keluar' => 0,
        ];


        // =====================================================
        // HITUNG SUMMARY
        // =====================================================
        foreach ($data as $row) {

            $tanggal = $row['tanggal'];

            $att = $row['absen'];
            $izin = $row['izin'];
            $jadwal = $row['jadwal'];

            // =============================================
            // AMBIL STATUS KALDIK DARI DATA HARI INI
            // =============================================
            $isHariKerjaKhusus = (bool) (
                $row['is_hari_kerja_khusus'] ?? false
            );

            $izinCount = $izin->count();

            $jamMasuk = optional($att)->check_in;
            $jamPulang = optional($att)->check_out;

            $jamMasukStandar = $jadwal['jam_masuk'];
            $jamPulangStandar = $jadwal['jam_pulang'];


            // =================================================
            // STANDAR JAM KERJA
            // =================================================
            $standarMasuk = Carbon::parse(
                $tanggal . ' ' . $jamMasukStandar
            );

            $standarPulang = Carbon::parse(
                $tanggal . ' ' . $jamPulangStandar
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
                    ? Carbon::parse($tanggal . ' ' . $jamMasuk)
                    : null;

                $pulangFix = $jamPulang
                    ? Carbon::parse($tanggal . ' ' . $jamPulang)
                    : null;


                // =================================================
                // TERLAMBAT
                // =================================================
                $telat = $masukFix
                    && $masukFix->gt($standarMasuk);

                if ($telat) {

                    $menitTelat = (int) $standarMasuk
                        ->diffInMinutes($masukFix);

                    $summary['telat']++;

                    $summary['total_menit_telat']
                        += $menitTelat;
                }


                // =================================================
                // PULANG CEPAT
                // =================================================
                $pulangCepat = $pulangFix
                    && $pulangFix->lt($standarPulang);

                if ($pulangCepat) {

                    $menitPulangCepat = (int) $pulangFix
                        ->diffInMinutes($standarPulang);

                    $summary['pulang_cepat']++;

                    $summary['total_menit_pulang_cepat']
                        += $menitPulangCepat;
                }


                // =================================================
                // IZIN
                // =================================================
                if ($izinCount > 0) {
                    $summary['izin']++;
                }


                // =================================================
                // KELUAR TENGAH JAM KERJA
                // =================================================
                $activities = $att->activities
                    ->sortBy('time')
                    ->values();


                /*
                * Contoh:
                *
                * 07:20 IN
                * 09:00 OUT
                * 10:00 IN
                * 15:35 OUT
                *
                * Yang dicek:
                *
                * 09:00 OUT
                *      ↓
                * 10:00 IN
                */
                for (
                    $i = 0;
                    $i < $activities->count() - 1;
                    $i++
                ) {

                    $current = $activities[$i];
                    $next = $activities[$i + 1];


                    // Hanya cari pasangan OUT → IN
                    if (
                        $current->type !== 'out'
                        || $next->type !== 'in'
                    ) {
                        continue;
                    }


                    $jamKeluar = Carbon::parse(
                        $tanggal . ' ' . $current->time
                    );

                    $jamKembali = Carbon::parse(
                        $tanggal . ' ' . $next->time
                    );


                    // =================================================
                    // HARUS KELUAR DI DALAM JAM KERJA
                    // =================================================
                    if (
                        $jamKeluar->lt($standarMasuk)
                        || $jamKeluar->gte($standarPulang)
                    ) {
                        continue;
                    }


                    // =================================================
                    // CEK IZIN YANG MENG-COVER WAKTU KELUAR
                    // =================================================
                    $adaIzin = $izin->contains(
                        function ($permission) use (
                            $tanggal,
                            $jamKeluar,
                            $jamKembali
                        ) {

                            if (
                                !$permission->time_start
                                || !$permission->time_end
                            ) {
                                return false;
                            }


                            $izinMulai = Carbon::parse(
                                $tanggal . ' ' .
                                $permission->time_start
                            );

                            $izinSelesai = Carbon::parse(
                                $tanggal . ' ' .
                                $permission->time_end
                            );


                            // Izin harus meng-cover
                            // waktu keluar sampai kembali
                            return $izinMulai->lte($jamKeluar)
                                && $izinSelesai->gte($jamKembali);
                        }
                    );


                    // =================================================
                    // TIDAK ADA IZIN
                    // =================================================
                    if (!$adaIzin) {

                        $summary['keluar_tanpa_izin']++;
                    }
                }


                // =================================================
                // TOTAL JAM KERJA AKTUAL
                // check-in pertama → check-out terakhir
                // dikurangi semua OUT → IN di tengah hari
                // =================================================
                if ($jamMasuk && $jamPulang) {

                    $jamStart = Carbon::parse(
                        $tanggal . ' ' . $jamMasuk
                    );

                    $jamEnd = Carbon::parse(
                        $tanggal . ' ' . $jamPulang
                    );

                    if ($jamEnd->greaterThan($jamStart)) {

                        // Total dari datang sampai pulang
                        $totalMenit = (int) $jamStart
                            ->diffInMinutes($jamEnd);

                        // Total waktu pegawai berada di luar
                        $totalMenitKeluar = 0;

                        $activitiesKerja = $att->activities
                            ->sortBy('time')
                            ->values();

                        for (
                            $i = 0;
                            $i < $activitiesKerja->count() - 1;
                            $i++
                        ) {

                            $current = $activitiesKerja[$i];
                            $next    = $activitiesKerja[$i + 1];

                            // Cari pasangan OUT → IN
                            if (
                                $current->type !== 'out' ||
                                $next->type !== 'in'
                            ) {
                                continue;
                            }

                            $keluar = Carbon::parse(
                                $tanggal . ' ' . $current->time
                            );

                            $kembali = Carbon::parse(
                                $tanggal . ' ' . $next->time
                            );

                            if ($kembali->greaterThan($keluar)) {

                                $totalMenitKeluar += (int) $keluar
                                    ->diffInMinutes($kembali);
                            }
                        }

                        // Jam kerja aktual
                        $menitKerjaAktual = max(
                            0,
                            $totalMenit - $totalMenitKeluar
                        );

                        $summary['total_jam_keluar']
                            += $menitKerjaAktual;
                    }
                }

            }

            // =================================================
            // TIDAK ADA ABSENSI
            // =================================================
            else {

                // =============================================
                // HARI KERJA KHUSUS
                // =============================================
                if ($isHariKerjaKhusus) {

                    // Kegiatan resmi → dihitung hadir
                    $summary['hadir']++;

                    // Tapi tetap dicatat sebagai hari khusus
                    $summary['hari_kerja_khusus']++;

                } elseif ($izinCount > 0) {

                    $summary['izin']++;
                    $summary['tidak_masuk']++;

                } else {

                    $summary['tidak_masuk']++;
                    $summary['tanpa_keterangan']++;
                }
            }
        }


        // =====================================================
        // TOTAL HARI KERJA
        // =====================================================
        $summary['total_hari_kerja'] = count($data);


        // =====================================================
        // TOTAL JAM
        // =====================================================
        $summary['total_jam_keluar_jam'] = round(
            $summary['total_jam_keluar'] / 60,
            1
        );


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

    public function process(EmployeeScheduleService $scheduleService)
    {
        $logs = AttendanceLog::whereNotNull('employee_id')->get();

        // Parse tanggal
        $logs = $logs->map(function ($log) {
            $log->parsed_time = $this->parseTanggal($log->scan_time);
            return $log;
        })->filter(function ($log) {
            return $log->parsed_time;
        });

        // Group berdasarkan pegawai + tanggal
        $grouped = $logs->groupBy(function ($item) {
            return $item->employee_id . '-' . $item->parsed_time->format('Y-m-d');
        });

        foreach ($grouped as $items) {

            $items = $items->sortBy('parsed_time')->values();

            $first = $items->first();
            $last  = $items->last();

            $employee_id = $first->employee_id;
            $tanggal = $first->parsed_time->format('Y-m-d');

            // =====================================================
            // AMBIL EMPLOYEE
            // =====================================================
            $employee = Employee::find($employee_id);

            if (!$employee) {
                continue;
            }

            // =====================================================
            // AMBIL JADWAL SESUAI KALDIK + JADWAL PEGAWAI
            // =====================================================
            $jadwal = $scheduleService->getSchedule(
                $employee,
                $tanggal
            );

            // Tidak ada jadwal = bukan hari kerja
            if (!$jadwal) {
                continue;
            }

            $jamMasuk = $jadwal['jam_masuk'];

            $checkIn = $first->parsed_time;

            // Kalau cuma 1 scan, berarti belum ada scan pulang
            $checkOut = $items->count() > 1
                ? $last->parsed_time
                : null;

            // hitung telat
            $jamMasukFix = \Carbon\Carbon::parse($tanggal . ' ' . $jamMasuk);

            $lateMinutes = 0;

            if ($checkIn->gt($jamMasukFix)) {
                $lateMinutes = $jamMasukFix->diffInMinutes($checkIn);
            }

            // simpan attendance
            $attendance = Attendance::updateOrCreate(
                [
                    'employee_id' => $employee_id,
                    'date' => $tanggal
                ],
                [
                    'check_in' => $checkIn->format('H:i:s'),
                    'check_out' => $checkOut
                        ? $checkOut->format('H:i:s')
                        : null,
                    'late_minutes' => $lateMinutes,
                    'status' => 'hadir',
                ]
            );

            // hapus aktivitas lama
            $attendance->activities()->delete();

            foreach ($items as $i => $log) {

                if ($i == 0) {
                    $type = 'in';
                } elseif ($i == ($items->count() - 1)) {
                    $type = 'out';
                } else {
                    $type = ($i % 2 == 0) ? 'in' : 'out';
                }

                AttendanceActivity::create([
                    'attendance_id' => $attendance->id,
                    'time' => $log->parsed_time->format('H:i:s'),
                    'type' => $type,
                    'note' => $type == 'out' ? 'Keluar' : 'Masuk',
                ]);
            }
        }

        return "Processed OK!";
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
    )
    {
        // =====================================================
        // VALIDASI TOKEN UNIT
        // =====================================================

        $tokenHash = hash('sha256', $token);

        $formToken = UnitFormToken::where(
                'token_hash',
                $tokenHash
            )
            ->where('is_active', true)
            ->first();

        if (!$formToken) {
            abort(404);
        }


        // =====================================================
        // VALIDASI FORM
        // =====================================================

        $request->validate([

            'employee_id' => [
                'required',
                'exists:employees,id',
            ],

            'date_start' => [
                'required',
                'date',
            ],

            'date_end' => [
                'required',
                'date',
                'after_or_equal:date_start',
            ],

            'time_start' => [
                'nullable',
                'date_format:H:i',
                'required_with:time_end',
            ],

            'time_end' => [
                'nullable',
                'date_format:H:i',
                'after:time_start',
            ],

            'type' => [
                'required',
                'in:Izin Keluar,Izin Terlambat,Keperluan Pribadi,Cuti,Sakit',
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048',
            ],
        ]);


        // =====================================================
        // CEK EMPLOYEE HARUS SESUAI UNIT TOKEN
        // =====================================================

        $employee = Employee::where(
                'id',
                $request->employee_id
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
        // CARI APPROVER OTOMATIS
        // =====================================================

        $approver = $approvalResolver->resolve(
            $employee
        );


        // =====================================================
        // UPLOAD FILE
        // =====================================================

        $filePath = null;

        if ($request->hasFile('attachment')) {

            $file = $request->file('attachment');

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
        // SIMPAN PENGAJUAN IZIN
        // =====================================================

        AttendancePermission::create([

            'employee_id' =>
                $employee->id,

            'date_start' =>
                $request->date_start,

            'date_end' =>
                $request->date_end,

            'time_start' =>
                $request->time_start,

            'time_end' =>
                $request->time_end,

            'type' =>
                $request->type,

            'description' =>
                $request->description,

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
            '✅ Izin berhasil dikirim dan menunggu approval pimpinan!'
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
        $unitId    = $request->unit_id;
        $startDate = $request->start_date;
        $endDate   = $request->end_date;

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