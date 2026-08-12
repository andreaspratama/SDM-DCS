<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\AttendancePermission;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */

    protected $startDate;
    protected $endDate;
    protected $unitId;

    public function __construct($startDate, $endDate, $unitId = null)
    {
        $this->startDate = Carbon::parse($startDate)->toDateString();
        $this->endDate   = Carbon::parse($endDate)->toDateString();
        $this->unitId    = $unitId;
    }

    public function collection()
    {
        $startDate = $this->startDate;
        $endDate   = $this->endDate;

        $employeesQuery = Employee::with('workSchedule');

        if ($this->unitId) {
            $employeesQuery->where('unit_id', $this->unitId);
        }

        $employees = $employeesQuery->get();

        // Attendance pada periode yang dipilih
        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($item) {
                return $item->employee_id . '_' .
                    Carbon::parse($item->date)->toDateString();
            });

        // Izin yang bersinggungan dengan periode
        $allIzin = AttendancePermission::where('date_start', '<=', $endDate)
            ->where('date_end', '>=', $startDate)
            ->get();

        $result = [];

        foreach ($employees as $emp) {

            $period = CarbonPeriod::create(
                $startDate,
                $endDate
            );

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

            $jamMasukStandar =
                optional($emp->workSchedule)->jam_masuk
                ?? '07:30:00';

            $jamPulangStandar =
                optional($emp->workSchedule)->jam_pulang
                ?? '15:30:00';

            foreach ($period as $date) {

                // Sabtu dan Minggu tidak dihitung
                if ($date->isWeekend()) {
                    continue;
                }

                $tanggal = $date->toDateString();

                $summary['total_hari_kerja']++;

                // =========================
                // ATTENDANCE
                // =========================

                $key = $emp->id . '_' . $tanggal;

                $att = $attendances
                    ->get($key)?->first();

                // =========================
                // IZIN
                // =========================

                $izinCount = $allIzin
                    ->where('employee_id', $emp->id)
                    ->filter(function ($i) use ($tanggal) {

                        return $tanggal >= $i->date_start
                            && $tanggal <= $i->date_end;
                    })
                    ->count();

                // =========================
                // ADA ABSENSI
                // =========================

                if ($att) {

                    $summary['hadir']++;

                    $jamMasuk  = $att->check_in;
                    $jamPulang = $att->check_out;

                    $masukFix = $jamMasuk
                        ? Carbon::parse(
                            $tanggal . ' ' . $jamMasuk
                        )
                        : null;

                    $pulangFix = $jamPulang
                        ? Carbon::parse(
                            $tanggal . ' ' . $jamPulang
                        )
                        : null;

                    $standarMasuk = Carbon::parse(
                        $tanggal . ' ' . $jamMasukStandar
                    );

                    $standarPulang = Carbon::parse(
                        $tanggal . ' ' . $jamPulangStandar
                    );

                    $telat =
                        $masukFix &&
                        $masukFix->gt($standarMasuk);

                    $pulangCepat =
                        $pulangFix &&
                        $pulangFix->lt($standarPulang);

                    if ($telat) {
                        $summary['telat']++;
                    }

                    if ($pulangCepat) {
                        $summary['pulang_cepat']++;
                    }

                    if ($izinCount > 0) {

                        $summary['izin']++;

                    } else {

                        if ($telat || $pulangCepat) {
                            $summary['keluar_tanpa_izin']++;
                        }
                    }

                    // Total jam kerja
                    if (
                        $masukFix &&
                        $pulangFix &&
                        $pulangFix->gt($masukFix)
                    ) {
                        $summary['total_menit'] +=
                            $masukFix->diffInMinutes($pulangFix);
                    }
                }

                // =========================
                // TIDAK ADA ABSENSI
                // =========================

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

                $emp->nama,

                $summary['total_hari_kerja'],

                $summary['izin'],

                $summary['hadir'],

                $summary['tidak_masuk'],

                $summary['telat'],

                $summary['pulang_cepat'],

                $summary['tanpa_keterangan'],

                $summary['keluar_tanpa_izin'],

                round(
                    $summary['total_menit'] / 60,
                    1
                ),
            ];
        }

        return collect($result);
    }

    /**
     * Header Excel
     */
    public function headings(): array
    {
        return [
            'Nama',
            'Hari Kerja',
            'Izin',
            'Hadir',
            'Tidak Masuk',
            'Telat',
            'Pulang Cepat',
            'Tanpa Keterangan',
            'Keluar Tanpa Izin',
            'Total Jam',
        ];
    }

    /**
     * Styling Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }
}
