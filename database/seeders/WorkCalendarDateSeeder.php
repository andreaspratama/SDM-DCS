<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkCalendarDate;

class WorkCalendarDateSeeder extends Seeder
{
    public function run(): void
    {
        $calendars = [

            // =====================================================
            // JULI 2026
            // HK G/K = 18
            // =====================================================

            [
                'date' => '2026-07-01',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2026-07-02',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2026-07-03',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2026-07-06',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2026-07-07',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],

            // =====================================================
            // AGUSTUS 2026
            // HK G/K = 19
            // =====================================================

            [
                'date' => '2026-08-17',
                'is_workday' => false,
                'type' => 'libur_nasional',
                'name' => 'Hari Kemerdekaan',
            ],

            /*
            |--------------------------------------------------------------------------
            | 24 AGUSTUS
            |--------------------------------------------------------------------------
            | Ada kegiatan SDM - Outbond Guru/Karyawan.
            | Berdasarkan aturan absensi: TETAP MASUK dan WAJIB ABSEN.
            |--------------------------------------------------------------------------
            */
            [
                'date' => '2026-08-24',
                'is_workday' => true,
                'type' => 'hari_kerja_khusus',
                'name' => 'SDM - Outbond Guru/Karyawan',
            ],

            [
                'date' => '2026-08-25',
                'is_workday' => false,
                'type' => 'libur_nasional',
                'name' => 'Maulid Nabi Muhammad',
            ],

            // =====================================================
            // DESEMBER 2026
            // HK G/K = 14
            // Libur GK setelah kegiatan semester selesai
            // =====================================================

            [
                'date' => '2026-12-21',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2026-12-22',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2026-12-23',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2026-12-24',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2026-12-25',
                'is_workday' => false,
                'type' => 'libur_nasional',
                'name' => 'Hari Natal',
            ],
            [
                'date' => '2026-12-28',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2026-12-29',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2026-12-30',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2026-12-31',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],

            // =====================================================
            // JANUARI 2027
            // HK G/K = 18
            // =====================================================

            [
                'date' => '2027-01-01',
                'is_workday' => false,
                'type' => 'libur_nasional',
                'name' => 'Tahun Baru',
            ],
            [
                'date' => '2027-01-04',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2027-01-05',
                'is_workday' => false,
                'type' => 'libur_nasional',
                'name' => 'Isra Miraj',
            ],

            // =====================================================
            // FEBRUARI 2027
            // HK G/K = 19
            // =====================================================

            [
                'date' => '2027-02-05',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],

            // =====================================================
            // MARET 2027
            // HK G/K = 13
            // =====================================================

            [
                'date' => '2027-03-05',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2027-03-08',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2027-03-09',
                'is_workday' => false,
                'type' => 'libur_nasional',
                'name' => 'Nyepi',
            ],
            [
                'date' => '2027-03-10',
                'is_workday' => false,
                'type' => 'libur_nasional',
                'name' => 'Lebaran',
            ],
            [
                'date' => '2027-03-11',
                'is_workday' => false,
                'type' => 'libur_nasional',
                'name' => 'Lebaran',
            ],
            [
                'date' => '2027-03-12',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2027-03-15',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2027-03-16',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2027-03-25',
                'is_workday' => false,
                'type' => 'libur_nasional',
                'name' => 'Kamis Putih',
            ],
            [
                'date' => '2027-03-26',
                'is_workday' => false,
                'type' => 'libur_nasional',
                'name' => 'Jumat Agung',
            ],

            // =====================================================
            // MEI 2027
            // HK G/K = 18
            // =====================================================

            [
                'date' => '2027-05-06',
                'is_workday' => false,
                'type' => 'libur_nasional',
                'name' => 'Kenaikan Tuhan Yesus',
            ],
            [
                'date' => '2027-05-17',
                'is_workday' => false,
                'type' => 'libur_nasional',
                'name' => 'Idul Adha',
            ],
            [
                'date' => '2027-05-20',
                'is_workday' => false,
                'type' => 'libur_nasional',
                'name' => 'Waisak',
            ],

            // =====================================================
            // JUNI 2027
            // HK G/K = 13
            // =====================================================

            [
                'date' => '2027-06-01',
                'is_workday' => false,
                'type' => 'libur_nasional',
                'name' => 'Hari Lahir Pancasila',
            ],

            [
                'date' => '2027-06-21',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2027-06-22',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2027-06-23',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2027-06-24',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2027-06-25',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],

            [
                'date' => '2027-06-28',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2027-06-29',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
            [
                'date' => '2027-06-30',
                'is_workday' => false,
                'type' => 'libur_gk',
                'name' => 'Libur Guru/Karyawan',
            ],
        ];

        foreach ($calendars as $calendar) {

            WorkCalendarDate::updateOrCreate(
                [
                    'academic_year' => '2026/2027',
                    'date' => $calendar['date'],
                    'unit_id' => null,
                ],
                [
                    'is_workday' => $calendar['is_workday'],
                    'type' => $calendar['type'],
                    'name' => $calendar['name'],
                    'description' => 'Sesuai Kaldik TA 2026/2027',
                ]
            );
        }
    }
}