<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\AttendancePermission;

use App\Services\EmployeeScheduleService;
use App\Services\WorkCalendarService;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AttendanceExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithColumnWidths
{
    protected $startDate;
    protected $endDate;
    protected $unitId;


    public function __construct(
        $startDate,
        $endDate,
        $unitId = null
    ) {
        $this->startDate =
            Carbon::parse(
                $startDate
            )->toDateString();

        $this->endDate =
            Carbon::parse(
                $endDate
            )->toDateString();

        $this->unitId =
            $unitId;
    }


    // =====================================================
    // DATA EXPORT
    // =====================================================
    public function collection()
    {
        $startDate =
            $this->startDate;

        $endDate =
            $this->endDate;


        // =================================================
        // SERVICES
        // =================================================
        $scheduleService =
            app(
                EmployeeScheduleService::class
            );

        $calendarService =
            app(
                WorkCalendarService::class
            );


        // =================================================
        // EMPLOYEE
        // =================================================
        $employeesQuery =
            Employee::with([
                'unit',
                'workSchedule.days',
                'employeeWorkSchedules.days',
            ]);


        if ($this->unitId) {

            $employeesQuery->where(
                'unit_id',
                $this->unitId
            );
        }


        $employees =
            $employeesQuery
                ->orderBy('nama')
                ->get();


        // =================================================
        // ATTENDANCE
        // =================================================
        $attendances =
            Attendance::with([
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
                        . Carbon::parse(
                            $item->date
                        )->toDateString();
                }
            );


        // =================================================
        // IZIN
        // HANYA APPROVED
        // =================================================
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
            ->get()
            ->groupBy(
                'employee_id'
            );


        $result = [];


        // =================================================
        // LOOP EMPLOYEE
        // =================================================
        foreach ($employees as $emp) {

            $period =
                CarbonPeriod::create(
                    $startDate,
                    $endDate
                );


            $summary = [

                'total_hari_kerja' => 0,

                'hadir' => 0,

                'tidak_masuk' => 0,

                'telat' => 0,

                'total_menit_telat' => 0,

                'pulang_cepat' => 0,

                'total_menit_pulang_cepat' => 0,

                'total_menit' => 0,
            ];


            // =================================================
            // KATEGORI IZIN
            //
            // Contoh hasil:
            //
            // Izin Terlambat (2)
            // Izin Pulang Awal (1)
            // Cuti (3)
            // =================================================
            $kategoriIzin = [];


            // =================================================
            // IZIN MILIK EMPLOYEE
            // =================================================
            $izinEmployee =
                $allIzin->get(
                    $emp->id,
                    collect()
                );


            // =================================================
            // LOOP TANGGAL
            // =================================================
            foreach ($period as $date) {

                // =============================================
                // JANGAN HITUNG MASA DEPAN
                // =============================================
                if ($date->isFuture()) {
                    continue;
                }


                $tanggal =
                    $date->toDateString();


                // =============================================
                // JADWAL PEGAWAI
                // =============================================
                $jadwal =
                    $scheduleService
                        ->getSchedule(
                            $emp,
                            $tanggal
                        );


                // =============================================
                // KALDIK
                // =============================================
                $calendar =
                    $calendarService
                        ->getCalendar(
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
                // BUKAN HARI KERJA
                //
                // Sama dengan Rekap utama:
                // lembur / kegiatan resmi tidak dicampur
                // ke statistik hari kerja reguler.
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
                    $izinEmployee
                        ->filter(
                            function ($izin) use (
                                $tanggal
                            ) {

                                $mulai =
                                    Carbon::parse(
                                        $izin->date_start
                                    )
                                    ->toDateString();


                                $selesai =
                                    Carbon::parse(
                                        $izin->date_end
                                    )
                                    ->toDateString();


                                return
                                    $tanggal >= $mulai
                                    &&
                                    $tanggal <= $selesai;
                            }
                        )
                        ->values();


                // =============================================
                // CATAT KATEGORI IZIN
                //
                // Satu jenis izin hanya dihitung sekali
                // per hari.
                // =============================================
                $jenisIzinHari =
                    $izinHari
                        ->pluck('type')
                        ->filter()
                        ->unique();


                foreach ($jenisIzinHari as $jenisIzin) {

                    if (
                        !isset(
                            $kategoriIzin[
                                $jenisIzin
                            ]
                        )
                    ) {

                        $kategoriIzin[
                            $jenisIzin
                        ] = 0;
                    }


                    $kategoriIzin[
                        $jenisIzin
                    ]++;
                }


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
                        ? Carbon::parse(
                            $tanggal
                            . ' '
                            . $jamMasuk
                        )
                        : null;


                    $pulangFix =
                        $jamPulang
                        ? Carbon::parse(
                            $tanggal
                            . ' '
                            . $jamPulang
                        )
                        : null;


                    // =============================================
                    // JAM STANDAR
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


                    $standarMasuk =
                        Carbon::parse(
                            $tanggal
                            . ' '
                            . $jamMasukStandar
                        );


                    $standarPulang =
                        Carbon::parse(
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
                                Carbon::parse(
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
                            // MASIH DALAM BATAS IZIN
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
                                // Hanya hitung kelebihan
                                // setelah waktu izin.
                                // =================================
                                $menitTelat =
                                    (int)
                                    $jamIzinDatang
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
                            ] +=
                                $menitTelat;
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
                                Carbon::parse(
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
                            // SESUAI IZIN
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
                                // Hanya hitung kekurangan
                                // terhadap jam izin pulang.
                                // =================================
                                $menitPulangCepat =
                                    (int)
                                    $pulangFix
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
                    // ACTIVITIES
                    // =================================================
                    $activities =
                        $att->activities
                            ->sortBy('time')
                            ->values();


                    // =================================================
                    // TOTAL JAM KERJA AKTUAL
                    //
                    // Check In -> Check Out
                    // dikurangi seluruh OUT -> IN
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
                            $i <
                                $activities->count() - 1;
                            $i++
                        ) {

                            $current =
                                $activities[$i];

                            $next =
                                $activities[$i + 1];


                            // Hanya OUT -> IN
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
                                    (int)
                                    $keluar
                                        ->diffInMinutes(
                                            $kembali
                                        );
                            }
                        }


                        $menitKerjaAktual =
                            max(
                                0,
                                $totalMenit
                                -
                                $totalMenitKeluar
                            );


                        $summary[
                            'total_menit'
                        ] +=
                            $menitKerjaAktual;
                    }

                } else {

                    // =================================================
                    // TIDAK ADA FINGERPRINT
                    // =================================================

                    if ($isHariKerjaKhusus) {

                        // Hari kerja khusus resmi tetap Hadir
                        $summary['hadir']++;

                    } else {

                        // Baik ada izin maupun tidak,
                        // secara fisik tetap Tidak Masuk.
                        $summary[
                            'tidak_masuk'
                        ]++;
                    }
                }
            }


            // =================================================
            // FORMAT KATEGORI IZIN
            // =================================================
            $kategoriIzinText = '-';


            if (!empty($kategoriIzin)) {

                $parts = [];


                foreach (
                    $kategoriIzin
                    as $jenis => $jumlah
                ) {

                    $parts[] =
                        $jenis
                        . ' ('
                        . $jumlah
                        . ')';
                }


                $kategoriIzinText =
                    implode(
                        ', ',
                        $parts
                    );
            }


            // =================================================
            // RESULT
            // =================================================
            $result[] = [

                // A
                $emp->nama,

                // B
                $emp->unit?->nama
                    ?? '-',

                // C
                $summary[
                    'total_hari_kerja'
                ],

                // D
                $summary['hadir'],

                // E
                $summary[
                    'tidak_masuk'
                ],

                // F
                $kategoriIzinText,

                // G
                $summary['telat'],

                // H
                $summary[
                    'total_menit_telat'
                ],

                // I
                $summary[
                    'pulang_cepat'
                ],

                // J
                $summary[
                    'total_menit_pulang_cepat'
                ],

                // K
                round(
                    $summary[
                        'total_menit'
                    ] / 60,
                    1
                ),
            ];
        }


        return collect(
            $result
        );
    }


    // =====================================================
    // HEADER
    // =====================================================
    public function headings(): array
    {
        return [

            'Nama',

            'Unit',

            'Hari Kerja',

            'Hadir',

            'Tidak Masuk',

            'Kategori Izin',

            'Terlambat',

            'Durasi Terlambat (Menit)',

            'Pulang Cepat',

            'Durasi Pulang Cepat (Menit)',

            'Total Jam Kerja',
        ];
    }


    // =====================================================
    // WIDTH
    // =====================================================
    public function columnWidths(): array
    {
        return [

            'A' => 32,

            'B' => 20,

            // Kategori Izin sengaja agak lebar
            'F' => 42,
        ];
    }


    // =====================================================
    // STYLE
    // =====================================================
    public function styles(
        Worksheet $sheet
    ) {
        $lastRow =
            $sheet->getHighestRow();


        // =================================================
        // FREEZE HEADER
        // =================================================
        $sheet->freezePane(
            'A2'
        );


        // =================================================
        // FILTER HEADER
        // =================================================
        if ($lastRow >= 1) {

            $sheet->setAutoFilter(
                'A1:K' . $lastRow
            );
        }


        // =================================================
        // HEADER
        // =================================================
        $sheet
            ->getStyle('A1:K1')
            ->getFont()
            ->setBold(true);


        $sheet
            ->getStyle('A1:K1')
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            )
            ->setWrapText(true);


        $sheet
            ->getStyle('A1:K1')
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB(
                'FFE9ECEF'
            );


        $sheet->getRowDimension(1)
            ->setRowHeight(35);


        // =================================================
        // BODY
        // =================================================
        if ($lastRow >= 2) {

            $sheet
                ->getStyle(
                    'A2:K' . $lastRow
                )
                ->getAlignment()
                ->setVertical(
                    Alignment::VERTICAL_TOP
                );


            // Angka rata tengah
            $sheet
                ->getStyle(
                    'C2:E' . $lastRow
                )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );


            $sheet
                ->getStyle(
                    'G2:J' . $lastRow
                )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );


            // Kategori izin wrap
            $sheet
                ->getStyle(
                    'F2:F' . $lastRow
                )
                ->getAlignment()
                ->setWrapText(true);


            // Total jam
            $sheet
                ->getStyle(
                    'K2:K' . $lastRow
                )
                ->getNumberFormat()
                ->setFormatCode(
                    '0.0'
                );
        }


        // =================================================
        // GARIS MERAH SETELAH KATEGORI IZIN
        //
        // Kategori Izin = kolom F
        // =================================================
        $sheet
            ->getStyle(
                'F1:F' . $lastRow
            )
            ->getBorders()
            ->getRight()
            ->setBorderStyle(
                Border::BORDER_THICK
            )
            ->getColor()
            ->setARGB(
                'FFFF0000'
            );


        return [

            1 => [

                'font' => [
                    'bold' => true,
                ],

            ],
        ];
    }
}