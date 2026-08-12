<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Unit;
use App\Models\AttendanceLog;
use App\Models\AttendanceActivity;
use App\Models\AttendancePermission;
use Carbon\CarbonPeriod;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class AbsensiController extends Controller
{
    private function hitungSummary($employee, $start, $end)
    {
        $period = \Carbon\CarbonPeriod::create($start, $end);

        $summary = [
            'hadir' => 0,
            'izin' => 0,
            'telat' => 0,
            'pulang_cepat' => 0,
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

    public function datatable(Request $request)
    {
        try {

            // range tanggal
            $startDate = $request->start_date ?: Attendance::min('date');
            $endDate   = $request->end_date   ?: Attendance::max('date');

            if (!$startDate || !$endDate) {
                return DataTables::of([])->make(true);
            }

            $startDate = \Carbon\Carbon::parse($startDate)->toDateString();
            $endDate   = \Carbon\Carbon::parse($endDate)->toDateString();

            $employeesQuery = Employee::with('workSchedule');

            if ($request->filled('unit_id')) {
                $employeesQuery->where('unit_id', $request->unit_id);
            }

            $employees = $employeesQuery->get();

            // 🔥 attendance di-index biar cepat & akurat
            $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
                ->get()
                ->groupBy(function ($item) {
                    return $item->employee_id . '_' . \Carbon\Carbon::parse($item->date)->toDateString();
                });

            // 🔥 izin
            $allIzin = AttendancePermission::where('date_start', '<=', $endDate)
                ->where('date_end', '>=', $startDate)
                ->get();

            $result = [];

            foreach ($employees as $emp) {

                $period = \Carbon\CarbonPeriod::create($startDate, $endDate);

                $summary = [
                    'total_hari_kerja' => 0,
                    'hadir' => 0,
                    'izin' => 0,
                    'telat' => 0,
                    'pulang_cepat' => 0,
                    'tanpa_keterangan' => 0,
                    'keluar_tanpa_izin' => 0,
                    'total_menit' => 0,
                    'tidak_masuk' => 0,
                ];

                $jamMasukStandar  = optional($emp->workSchedule)->jam_masuk ?? '07:30:00';
                $jamPulangStandar = optional($emp->workSchedule)->jam_pulang ?? '15:30:00';

                foreach ($period as $date) {

                    if ($date->isWeekend()) continue;

                    $tanggal = $date->toDateString();

                    $summary['total_hari_kerja']++;

                    // 🔥 ambil attendance
                    $key = $emp->id . '_' . $tanggal;
                    $att = $attendances->get($key)?->first();

                    // 🔥 hitung izin
                    $izinCount = $allIzin
                        ->where('employee_id', $emp->id)
                        ->filter(function ($i) use ($tanggal) {
                            return $tanggal >= $i->date_start && $tanggal <= $i->date_end;
                        })
                        ->count();

                    // =======================
                    // ADA ABSENSI
                    // =======================
                    if ($att) {

                        $summary['hadir']++;

                        $jamMasuk  = $att->check_in;
                        $jamPulang = $att->check_out;

                        $masukFix = $jamMasuk
                            ? \Carbon\Carbon::parse($tanggal . ' ' . $jamMasuk)
                            : null;

                        $pulangFix = $jamPulang
                            ? \Carbon\Carbon::parse($tanggal . ' ' . $jamPulang)
                            : null;

                        $standarMasuk  = \Carbon\Carbon::parse($tanggal . ' ' . $jamMasukStandar);
                        $standarPulang = \Carbon\Carbon::parse($tanggal . ' ' . $jamPulangStandar);

                        $telat = $masukFix && $masukFix->gt($standarMasuk);
                        $pulangCepat = $pulangFix && $pulangFix->lt($standarPulang);

                        if ($telat) $summary['telat']++;
                        if ($pulangCepat) $summary['pulang_cepat']++;

                        if ($izinCount > 0) {
                            $summary['izin']++;
                        } else {
                            if ($telat || $pulangCepat) {
                                $summary['keluar_tanpa_izin']++;
                            }
                        }

                        if ($masukFix && $pulangFix && $pulangFix->gt($masukFix)) {
                            $summary['total_menit'] += $masukFix->diffInMinutes($pulangFix);
                        }

                    }

                    // =======================
                    // TIDAK ADA ABSENSI
                    // =======================
                    else {

                        $summary['tidak_masuk']++;

                        if ($izinCount > 0) {
                            $summary['izin']++;
                        } else {
                            $summary['tanpa_keterangan']++;
                        }
                    }
                }

                $result[] = [
                    'nama' => $emp->nama,
                    'total_hari_kerja' => $summary['total_hari_kerja'],
                    'izin' => $summary['izin'],
                    'total_hadir' => $summary['hadir'],
                    'tidak_masuk' => $summary['tidak_masuk'],
                    'total_telat' => $summary['telat'],
                    'pulang_cepat' => $summary['pulang_cepat'],
                    'tanpa_keterangan' => $summary['tanpa_keterangan'],
                    'keluar_tanpa_izin' => $summary['keluar_tanpa_izin'],
                    'total_jam' => round($summary['total_menit'] / 60, 1),

                    'aksi' => route('absensi.detailRange', [
                        'employee' => $emp->id,
                        'start' => $startDate,
                        'end' => $endDate
                    ])
                ];
            }

            return DataTables::of($result)
                ->addColumn('aksi', function ($row) {
                    return '<a href="'.$row['aksi'].'" class="btn btn-info btn-sm">Detail</a>';
                })
                ->rawColumns(['aksi'])
                ->make(true);

        } catch (\Throwable $e) {

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
        }
    }

    // public function datatable(Request $request)
    // {
    //     $startDate = $request->start_date;
    //     $endDate   = $request->end_date;

    //     $query = DB::table('attendances')
    //         ->join('employees', 'employees.id', '=', 'attendances.employee_id')
    //         ->join('units', 'units.id', '=', 'employees.unit_id')
    //         ->select(
    //             'employees.id as employee_id',
    //             'employees.nama',
    //             'units.nama as unit_nama',
    //             DB::raw('COUNT(attendances.id) as total_hadir'),
    //             DB::raw('COALESCE(SUM(attendances.late_minutes),0) as total_telat')
    //         );

    //     // 🔥 FILTER TANGGAL (OPTIONAL, jangan paksa)
    //     if ($startDate && $endDate) {
    //         $query->whereBetween('attendances.date', [$startDate, $endDate]);
    //     }

    //     // 🔥 FILTER UNIT (OPTIONAL, jangan hardcode dulu)
    //     if ($request->unit) {
    //         $query->where('units.nama', $request->unit);
    //     }

    //     $query->groupBy(
    //         'employees.id',
    //         'employees.nama',
    //         'units.nama'
    //     );

    //     return DataTables::of($query)
    //         ->addColumn('aksi', function ($row) use ($startDate, $endDate) {

    //             $url = route('absensi.detailRange', [
    //                 'employee' => $row->employee_id,
    //                 'start' => $startDate,
    //                 'end' => $endDate
    //             ]);

    //             return '
    //                 <a href="'.$url.'" class="btn btn-info btn-sm">
    //                     <i class="bi bi-eye-fill"></i> | Detail
    //                 </a>
    //             ';
    //         })
    //         ->rawColumns(['aksi'])
    //         ->make(true);
    // }

    public function index()
    {
        return view('pages.absensi.index');
    }

    public function detailRange(Request $request, $employeeId)
    {
        // 🔥 ambil employee dulu
        $employee = Employee::with(['unit','workSchedule'])->findOrFail($employeeId);

        $start = $request->start 
            ? Carbon::parse($request->start)->startOfDay()
            : Carbon::now()->startOfMonth();

        $end = $request->end 
            ? Carbon::parse($request->end)->endOfDay()
            : Carbon::now()->endOfMonth();

        // 🔥 kalau tidak ada filter → pakai bulan berjalan
        if (!$start || !$end) {
            $start = Carbon::now()->startOfMonth()->toDateString();
            $end   = Carbon::now()->endOfMonth()->toDateString();
        }

        // Query Baru

        $period = CarbonPeriod::create($start, $end);

        $data = [];

        foreach ($period as $date) {

            // 🔥 SKIP SABTU & MINGGU
            if ($date->isWeekend()) {
                continue;
            }

            $tanggal = $date->toDateString();

            $absen = Attendance::where('employee_id', $employeeId)
                ->whereDate('date', $tanggal)
                ->first();

            $izin = AttendancePermission::where('employee_id', $employeeId)
                ->whereDate('date_start', '<=', $tanggal)
                ->whereDate('date_end', '>=', $tanggal)
                ->get();

            if ($izin && $izin->count()) {
                $status = 'IZIN';
            } elseif ($absen) {
                $status = 'HADIR';
            } else {
                $status = 'ALPHA';
            }

            $data[] = [
                'tanggal' => $tanggal,
                'absen' => $absen,
                'izin' => $izin,
                'status' => $status,
            ];
        }

        $summary = [
            'hadir' => 0,
            'izin' => 0,
            'telat' => 0,
            'pulang_cepat' => 0,
            'tanpa_keterangan' => 0,
            'keluar_tanpa_izin' => 0,
            'total_jam_keluar' => 0
        ];

        foreach ($data as $row) {

            $att = $row['absen'];
            $izin = $row['izin'];
            $izinCount = $izin ? $izin->count() : 0;

            $jamMasuk = optional($att)->check_in;
            $jamPulang = optional($att)->check_out;

            $jamMasukStandar = optional($employee->workSchedule)->jam_masuk ?? '07:30:00';
            $jamPulangStandar = optional($employee->workSchedule)->jam_pulang ?? '15:30:00';

            // =====================
            // ADA ABSENSI
            // =====================
            if ($att) {

                $summary['hadir']++;

                // TELAT
                if ($jamMasuk && $jamMasuk > $jamMasukStandar) {
                    $summary['telat']++;
                }

                // PULANG CEPAT
                if ($jamPulang && $jamPulang < $jamPulangStandar) {
                    $summary['pulang_cepat']++;
                }

                // ADA IZIN DI HARI ITU
                if ($izinCount > 0) {
                    $summary['izin']++;
                } else {

                    // 🔥 INI YANG LO MAU
                    // keluar / pulang cepat TANPA IZIN
                    if (
                        ($jamMasuk && $jamMasuk > $jamMasukStandar) ||
                        ($jamPulang && $jamPulang < $jamPulangStandar)
                    ) {
                        $summary['keluar_tanpa_izin']++;
                    }
                }

                // TOTAL JAM KERJA
                if ($jamMasuk && $jamPulang) {
                    $jamStart = Carbon::parse($jamMasuk);
                    $jamEnd = Carbon::parse($jamPulang);

                    if ($jamEnd->greaterThan($jamStart)) {
                        $summary['total_jam_keluar'] += $jamStart->diffInMinutes($jamEnd);
                    }
                }

            }

            // =====================
            // TIDAK ADA ABSENSI
            // =====================
            else {

                if ($izinCount > 0) {
                    $summary['izin']++; // izin full day
                } else {
                    $summary['tanpa_keterangan']++; // alpha
                }
            }
        }

        // 🔥 TOTAL HARI KERJA (optional kalau mau ditampilin)
        $summary['total_hari_kerja'] = count($data);

        // convert menit ke jam
        $summary['total_jam_keluar_jam'] = round($summary['total_jam_keluar'] / 60, 1);

        // convert menit ke jam
        $summary['total_jam_keluar_jam'] = round($summary['total_jam_keluar'] / 60, 1);

        return view('pages.absensi.detail-range', compact('data', 'employee', 'start', 'end', 'summary'));
    }

    public function formUpload()
    {
        $units = Unit::all();
        return view('pages.absensi.form', compact('units'));
    }

    public function uploadLog(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt',
            'unit_id' => 'required|exists:units,id'
        ]);

        $unitId = $request->unit_id;

        $file = fopen($request->file('file'), 'r');
        $first = true;

        while (($row = fgetcsv($file)) !== false) {

            if ($first) {
                $first = false;
                continue;
            }

            if (!isset($row[0])) continue;

            $data = str_contains($row[0], ';')
                ? explode(';', $row[0])
                : $row;

            // =========================
            // 🔵 FORMAT 1 (UID + DATETIME)
            // =========================
            if (count($data) == 2) {

                $uid = ltrim(trim($data[0]), '0');
                $datetime = trim($data[1]);

                try {
                    $scanTime = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $datetime);
                } catch (\Exception $e) {
                    continue;
                }

                $employee = Employee::whereRaw("TRIM(LEADING '0' FROM uid) = ?", [$uid])
                    ->where('unit_id', $unitId)
                    ->first();

                if (!$employee) continue;

                AttendanceLog::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'scan_time' => $scanTime
                    ],
                    [
                        'uid' => $employee->uid // ✅ AMAN (pakai uid asli DB)
                    ]
                );
            }

            // =========================
            // 🟢 FORMAT 2 (REKAP HARIAN)
            // =========================
            elseif (count($data) >= 5) {

                $uid = ltrim(trim($data[0]), '0');
                $tanggalRaw = trim($data[2]);
                $jamMasuk = trim($data[3]);
                $jamPulang = trim($data[4]);

                if (!$jamMasuk || !$jamPulang) continue;

                try {

                    $tanggalRaw = str_replace('/', '-', $tanggalRaw);

                    if (preg_match('/[A-Za-z]/', $tanggalRaw)) {

                        $bulanMap = [
                            'jan'=>'01','feb'=>'02','mar'=>'03','apr'=>'04','may'=>'05','jun'=>'06',
                            'jul'=>'07','aug'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dec'=>'12'
                        ];

                        [$day, $mon, $year] = explode('-', $tanggalRaw);

                        $mon = strtolower(substr($mon, 0, 3));
                        $month = $bulanMap[$mon] ?? null;

                        if (!$month) continue;

                    } else {

                        [$day, $month, $year] = explode('-', $tanggalRaw);
                    }

                    $year = strlen($year) == 2 ? '20'.$year : $year;

                    $day = str_pad($day, 2, '0', STR_PAD_LEFT);
                    $month = str_pad($month, 2, '0', STR_PAD_LEFT);

                    $tanggalFix = "$year-$month-$day";

                    $checkIn = \Carbon\Carbon::createFromFormat('Y-m-d H:i', "$tanggalFix $jamMasuk");
                    $checkOut = \Carbon\Carbon::createFromFormat('Y-m-d H:i', "$tanggalFix $jamPulang");

                } catch (\Exception $e) {
                    continue;
                }

                $employee = Employee::whereRaw("TRIM(LEADING '0' FROM uid) = ?", [$uid])
                    ->where('unit_id', $unitId)
                    ->first();

                if (!$employee) continue;

                // ✅ FIX UTAMA ADA DISINI
                AttendanceLog::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'scan_time' => $checkIn
                    ],
                    [
                        'uid' => $employee->uid
                    ]
                );

                AttendanceLog::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'scan_time' => $checkOut
                    ],
                    [
                        'uid' => $employee->uid
                    ]
                );
            }
        }

        fclose($file);

        return back()->with('success', 'Upload log berhasil!');
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

    public function processLogs()
    {
        $logs = AttendanceLog::with('employee')
            ->orderBy('scan_time')
            ->get();

        // GROUP BY employee + tanggal
        $grouped = $logs->groupBy(function ($item) {
            return $item->employee_id . '-' . date('Y-m-d', strtotime($item->scan_time));
        });

        foreach ($grouped as $key => $items) {

            $employee_id = $items->first()->employee_id;
            $date = date('Y-m-d', strtotime($items->first()->scan_time));

            $checkIn = $items->first()->scan_time;
            $checkOut = $items->last()->scan_time;

            // ⏰ jam masuk standar
            $jamMasuk = \Carbon\Carbon::createFromTime(7, 30, 0);
            $jamScan = \Carbon\Carbon::parse($checkIn);

            $late = 0;
            if ($jamScan->gt($jamMasuk)) {
                $late = $jamScan->diffInMinutes($jamMasuk);
            }

            Attendance::updateOrCreate(
                [
                    'employee_id' => $employee_id,
                    'date' => $date,
                ],
                [
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'late_minutes' => $late,
                ]
            );
        }

        return back()->with('success', 'Absensi berhasil diproses!');
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

    public function process()
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

            // 🔥 ambil employee + schedule (AMAN)
            $employee = Employee::with('workSchedule')->find($employee_id);

            // default biar gak error
            $jamMasuk = '07:30:00';

            if ($employee && $employee->workSchedule) {
                $jamMasuk = $employee->workSchedule->jam_masuk;
            }

            // ambil checkin & checkout
            $checkIn  = $first->parsed_time;
            $checkOut = $last->parsed_time;

            // hitung telat
            $jamMasukFix = \Carbon\Carbon::parse($tanggal . ' ' . $jamMasuk);

            $lateMinutes = 0;

            if ($checkIn->gt($jamMasukFix)) {
                $lateMinutes = $checkIn->diffInMinutes($jamMasukFix);
            }

            // simpan attendance
            $attendance = Attendance::updateOrCreate(
                [
                    'employee_id' => $employee_id,
                    'date' => $tanggal
                ],
                [
                    'check_in' => $checkIn->format('H:i:s'),
                    'check_out' => $checkOut->format('H:i:s'),
                    'late_minutes' => $lateMinutes,
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

    public function formIzinStore(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date_start'  => 'required|date',
            'date_end'    => 'required|date|after_or_equal:date_start',
            'time_start'  => 'nullable|date_format:H:i',
            'time_end'    => 'nullable|date_format:H:i|after:time_start',
            'type'        => 'required|in:Izin Keluar,Izin Terlambat,Keperluan Pribadi, Cuti, Sakit',
            'description' => 'nullable|string|max:255',
            'attachment'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        $filePath = null;

        if ($request->hasFile('attachment')) {
            $filePath = $request->file('attachment')->store('izin_files', 'public');
        }

        AttendancePermission::create([
            'employee_id' => $request->employee_id,
            'date_start'  => $request->date_start,
            'date_end'    => $request->date_end,
            'time_start'  => $request->time_start,
            'time_end'    => $request->time_end,
            'type'        => $request->type,
            'description' => $request->description,
            'attachment'  => $filePath,
        ]);

        return back()->with('success', '✅ Izin berhasil dikirim!');
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