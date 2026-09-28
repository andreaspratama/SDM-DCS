<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\AttendancePermission;

use App\Services\EmployeeScheduleService;
use App\Services\WorkCalendarService;
use App\Services\EmployeeUnitService;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AttendanceExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithColumnWidths,
    WithEvents
{
    protected $startDate;
    protected $endDate;
    protected $unitId;
    protected $downloadedBy;


    public function __construct(
        $startDate,
        $endDate,
        $unitId = null,
        $downloadedBy = null
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

        $this->downloadedBy =
            $downloadedBy;
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

        $employeeUnitService =
            app(
                EmployeeUnitService::class
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

            $unitId = $this->unitId;

            $employeesQuery->where(function ($query) use (
                $unitId,
                $startDate,
                $endDate
            ) {

                // Pegawai yang sudah punya histori unit
                $query->whereHas(
                    'unitHistories',
                    function ($history) use (
                        $unitId,
                        $startDate,
                        $endDate
                    ) {

                        $history
                            ->where(
                                'unit_id',
                                $unitId
                            )
                            ->whereDate(
                                'tanggal_mulai',
                                '<=',
                                $endDate
                            )
                            ->where(function ($period) use ($startDate) {

                                $period
                                    ->whereNull('tanggal_selesai')
                                    ->orWhereDate(
                                        'tanggal_selesai',
                                        '>=',
                                        $startDate
                                    );
                            });
                    }
                )

                // Pegawai lama yang belum punya histori
                ->orWhere(function ($legacy) use ($unitId) {

                    $legacy
                        ->whereDoesntHave('unitHistories')
                        ->where(
                            'unit_id',
                            $unitId
                        );
                });
            });
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

                'total_menit_keluar_tanpa_izin' => 0,

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
                // MASA AKTIF PEGAWAI
                // =============================================

                // Belum mulai bekerja
                if (
                    $emp->tanggal_masuk &&
                    $tanggal < $emp->tanggal_masuk->toDateString()
                ) {
                    continue;
                }

                // Sudah keluar / nonaktif
                // tanggal_keluar = hari terakhir masih bekerja
                if (
                    $emp->tanggal_keluar &&
                    $tanggal > $emp->tanggal_keluar->toDateString()
                ) {
                    continue;
                }

                // =============================================
                // UNIT PEGAWAI PADA TANGGAL INI
                // =============================================
                $unitIdPadaTanggal =
                    $employeeUnitService
                        ->getUnitId(
                            $emp,
                            $tanggal
                        );

                // =============================================
                // FILTER UNIT PER TANGGAL
                // =============================================
                if (
                    $this->unitId
                    &&
                    (int) $unitIdPadaTanggal !== (int) $this->unitId
                ) {
                    continue;
                }


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
                            $unitIdPadaTanggal
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
// HITUNG SEMUA OUT -> IN
//
// Tidak bergantung pada check_out.
// Jadi walaupun Pulang = null,
// aktivitas OUT -> IN tetap dihitung.
// =================================================
$totalMenitKeluar = 0;


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
    // HANYA PASANGAN OUT -> IN
    // =========================================
    if (
        $current->type !== 'out'
        ||
        $next->type !== 'in'
    ) {
        continue;
    }


    $jamKeluar =
        Carbon::parse(
            $tanggal
            . ' '
            . $current->time
        );


    $jamKembali =
        Carbon::parse(
            $tanggal
            . ' '
            . $next->time
        );


    if (
        !$jamKembali->gt(
            $jamKeluar
        )
    ) {
        continue;
    }


    // =========================================
    // DURASI OUT -> IN
    // =========================================
    $durasiKeluar =
        (int) $jamKeluar
            ->diffInMinutes(
                $jamKembali
            );


    $totalMenitKeluar +=
        $durasiKeluar;


    // =========================================
    // CEK IZIN APPROVED
    // =========================================
    $adaIzin =
        $izinHari->contains(
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


    // =========================================
    // PERGI TANPA IZIN
    // =========================================
    if (!$adaIzin) {

        $summary[
            'total_menit_keluar_tanpa_izin'
        ] +=
            $durasiKeluar;
    }
}


// =================================================
// TOTAL JAM KERJA AKTUAL
//
// Bagian ini tetap membutuhkan
// check-in dan check-out.
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
                        $jenis;
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
                $this->unitId
                    ? \App\Models\Unit::find($this->unitId)?->nama ?? '-'
                    : $emp->unit?->nama ?? '-',

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
                $summary[
                    'total_menit_keluar_tanpa_izin'
                ],
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

            'Menit Pergi Tanpa Izin',
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


            // Menit pergi tanpa izin
            $sheet
                ->getStyle(
                    'K2:K' . $lastRow
                )
                ->getNumberFormat()
                ->setFormatCode(
                    '0'
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet =
                    $event->sheet
                        ->getDelegate();


                // =============================================
                // BARIS TERAKHIR DATA
                // =============================================
                $lastRow =
                    $sheet->getHighestRow();


                // Beri jarak dari tabel
                $tanggalRow =
                    $lastRow + 3;


                // Beri ruang untuk tanda tangan
                $namaRow =
                    $tanggalRow + 4;

                // =============================================
                // MERGE RUANG TANDA TANGAN
                // Supaya tidak ada garis vertikal di tengah
                // =============================================
                for (
                    $row = $tanggalRow + 1;
                    $row < $namaRow;
                    $row++
                ) {
                    $sheet->mergeCells(
                        "I{$row}:K{$row}"
                    );
                }

                // =============================================
                // HILANGKAN GRIDLINE DI AREA TANDA TANGAN
                // =============================================
                $sheet
                    ->getStyle(
                        "I{$tanggalRow}:K{$namaRow}"
                    )
                    ->getFill()
                    ->setFillType(
                        Fill::FILL_SOLID
                    )
                    ->getStartColor()
                    ->setARGB(
                        'FFFFFFFF'
                    );


                // =============================================
                // TANGGAL DOWNLOAD
                // =============================================
                $tanggalDownload =
                    Carbon::now('Asia/Jakarta')
                        ->locale('id')
                        ->translatedFormat(
                            'd F Y'
                        );


                // =============================================
                // TANGGAL
                // =============================================
                $sheet->mergeCells(
                    "I{$tanggalRow}:K{$tanggalRow}"
                );

                $sheet->setCellValue(
                    "I{$tanggalRow}",
                    'Semarang, ' .
                    $tanggalDownload
                );


                // =============================================
                // NAMA YANG DOWNLOAD
                // =============================================
                $sheet->mergeCells(
                    "I{$namaRow}:K{$namaRow}"
                );

                $sheet->setCellValue(
                    "I{$namaRow}",
                    $this->downloadedBy
                        ?: '-'
                );


                // =============================================
                // ALIGNMENT
                // =============================================
                $sheet
                    ->getStyle(
                        "I{$tanggalRow}:K{$namaRow}"
                    )
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    );


                // Nama dibuat bold + underline
                $sheet
                    ->getStyle(
                        "I{$namaRow}:K{$namaRow}"
                    )
                    ->getFont()
                    ->setBold(true)
                    ->setUnderline(true);
            },
        ];
    }
}