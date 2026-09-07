<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\AttendanceLog;
use App\Models\Attendance;

class AttendanceFileImportService
{
    protected int $unitId;

    protected array $employees = [];
    
    protected array $employeesNormalized = [];

    protected array $missingUids = [];

    protected int $insertedLogs = 0;

    protected int $savedAttendances = 0;

    protected int $duplicates = 0;

    protected int $skipped = 0;

    private function resetState(): void
    {
        $this->employees = [];
        $this->employeesNormalized = [];
        $this->missingUids = [];

        $this->insertedLogs = 0;
        $this->savedAttendances = 0;
        $this->duplicates = 0;
        $this->skipped = 0;
    }

    public function import(
        UploadedFile $file,
        int $unitId,
        EmployeeScheduleService $scheduleService
    ): array {

        $this->resetState();

        $this->unitId = $unitId;

        $this->prepareEmployees();

        // =====================================================
        // PATH FILE UPLOAD
        // =====================================================
        $path = $file->getRealPath();

        if (!$path) {
            throw new \RuntimeException(
                'File upload tidak dapat dibaca.'
            );
        }

        $format = $this->detectFormatFromPath(
            $path,
            $file->getClientOriginalExtension()
        );

        // ==========================================
        // JALANKAN PARSER SESUAI FORMAT
        // ==========================================
        switch ($format) {

            case 'um_raw':

                $this->importUmRaw(
                    $path
                );

                break;


            case 'sma_matrix':

                $this->importSmaMatrix(
                    $path
                );

                break;


            case 'tkgama_matrix':

                $this->importTkGamaMatrix(
                    $path
                );

                break;


            case 'sekolah_daily':

                $this->importSekolahDaily(
                    $path,
                    $scheduleService
                );

                break;


            case 'tktama_daily':

                $this->importTkTamaDaily(
                    $path,
                    $scheduleService
                );

                break;


            default:

                throw new \RuntimeException(
                    "Parser untuk format {$format} belum diaktifkan."
                );
        }


        return [
            'format' => $format,

            'inserted_logs' =>
                $this->insertedLogs,

            'saved_attendances' =>
                $this->savedAttendances,

            'duplicates' =>
                $this->duplicates,

            'skipped' =>
                $this->skipped,

            'missing_uids' =>
                array_keys($this->missingUids),

            'needs_process' => in_array(
                $format,
                [
                    'um_raw',
                    'sma_matrix',
                    'tkgama_matrix',
                ],
                true
            ),
        ];

        return [
            'format' => $format,
            'inserted_logs' => 0,
            'saved_attendances' => 0,
            'duplicates' => 0,
            'skipped' => 0,
            'missing_uids' => [],
            'needs_process' => false,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | DETECT FORMAT
    |--------------------------------------------------------------------------
    |
    | Format yang kita punya:
    |
    | um_raw
    | sma_matrix
    | tkgama_matrix
    | sekolah_daily
    | tktama_daily
    |
    */
    public function detectFormatFromPath(
        string $path,
        ?string $extension = null
    ): string {

        $extension = strtolower(
            $extension ?: pathinfo($path, PATHINFO_EXTENSION)
        );

        // =========================================================
        // CSV / TXT → cek format UM
        // =========================================================
        if (in_array($extension, ['csv', 'txt'])) {

            $rows = $this->readCsvRows($path);

            if (empty($rows)) {
                throw new \RuntimeException(
                    'File CSV kosong atau tidak dapat dibaca.'
                );
            }

            $header = array_map(
                fn ($value) => $this->normalizeHeader($value),
                $rows[0]
            );

            if (
                in_array('uid', $header) &&
                in_array('datetime', $header)
            ) {
                return 'um_raw';
            }

            throw new \RuntimeException(
                'Format CSV belum dikenali oleh sistem.'
            );
        }


        // =========================================================
        // XLS / XLSX
        // =========================================================
        if (in_array($extension, ['xls', 'xlsx'])) {

            $spreadsheet = IOFactory::load($path);

            $rows = $spreadsheet
                ->getActiveSheet()
                ->toArray(
                    null,
                    true,
                    true,
                    false
                );

            if (empty($rows)) {
                throw new \RuntimeException(
                    'File Excel kosong atau tidak dapat dibaca.'
                );
            }

            // -----------------------------------------------------
            // SMA
            // -----------------------------------------------------
            // Attendance Record
            // ...
            // Employee ID | Name | Department | 1 | 2 | ... | 31
            // -----------------------------------------------------

            $firstCell = $this->normalizeHeader(
                $rows[0][0] ?? null
            );

            if ($firstCell === 'attendance record') {

                foreach (array_slice($rows, 0, 10) as $row) {

                    $first = $this->normalizeHeader(
                        $row[0] ?? null
                    );

                    if ($first === 'employee id') {
                        return 'sma_matrix';
                    }
                }
            }


            // -----------------------------------------------------
            // TK GAMA
            // -----------------------------------------------------
            // Lap. Detail Absensi
            // Waktu Absen ... 2026-08-01 ~ 2026-08-31
            // 1 | 2 | ... | 31
            // ID: ... UID ... Nama: ...
            // -----------------------------------------------------

            if (
                str_contains(
                    $firstCell,
                    'lap. detail absensi'
                )
            ) {
                return 'tkgama_matrix';
            }


            // -----------------------------------------------------
            // SD / SMP
            // -----------------------------------------------------
            // Nama | No. Staff | Dept. | Tanggal | Hari | ...
            // ... Jadwal masuk | Masuk | Jadwal keluar | Keluar
            // -----------------------------------------------------

            $header = array_map(
                fn ($value) => $this->normalizeHeader($value),
                $rows[0] ?? []
            );

            if (
                ($header[0] ?? '') === 'nama' &&
                ($header[1] ?? '') === 'no. staff' &&
                ($header[2] ?? '') === 'dept.' &&
                ($header[3] ?? '') === 'tanggal'
            ) {
                return 'sekolah_daily';
            }


            // -----------------------------------------------------
            // TK TAMA
            // -----------------------------------------------------
            // Nama | No. Staff | Jadwal | Masuk | Keluar | ...
            //
            // lalu ada:
            // GURU--------01/08/2026
            // -----------------------------------------------------

            if (
                ($header[0] ?? '') === 'nama' &&
                ($header[1] ?? '') === 'no. staff' &&
                ($header[2] ?? '') === 'jadwal' &&
                ($header[3] ?? '') === 'masuk' &&
                ($header[4] ?? '') === 'keluar'
            ) {
                return 'tktama_daily';
            }


            throw new \RuntimeException(
                'Format Excel belum dikenali oleh sistem.'
            );
        }


        throw new \RuntimeException(
            'Ekstensi file tidak didukung: ' . $extension
        );
    }


    /*
    |--------------------------------------------------------------------------
    | READ CSV
    |--------------------------------------------------------------------------
    */
    private function readCsvRows(string $path): array
    {
        $content = file_get_contents($path);

        if ($content === false) {
            return [];
        }

        // UTF-8 BOM
        $content = preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            $content
        );

        $lines = preg_split(
            "/\r\n|\n|\r/",
            $content
        );

        $lines = array_values(
            array_filter(
                $lines,
                fn ($line) => trim($line) !== ''
            )
        );

        if (empty($lines)) {
            return [];
        }

        // Deteksi delimiter
        $firstLine = $lines[0];

        $delimiter = ',';

        $counts = [
            ';'  => substr_count($firstLine, ';'),
            ','  => substr_count($firstLine, ','),
            "\t" => substr_count($firstLine, "\t"),
        ];

        arsort($counts);

        $bestDelimiter = array_key_first($counts);

        if (($counts[$bestDelimiter] ?? 0) > 0) {
            $delimiter = $bestDelimiter;
        }

        $rows = [];

        foreach ($lines as $line) {

            $rows[] = str_getcsv(
                $line,
                $delimiter
            );
        }

        return $rows;
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE HEADER
    |--------------------------------------------------------------------------
    */
    private function normalizeHeader($value): string
    {
        $value = (string) ($value ?? '');

        // hilangkan BOM kalau ada
        $value = preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            $value
        );

        $value = trim($value);

        // rapikan spasi berlebih
        $value = preg_replace(
            '/\s+/',
            ' ',
            $value
        );

        return strtolower($value);
    }

    private function importUmRaw(string $path): void
    {
        $rows = $this->readCsvRows($path);

        if (empty($rows)) {
            throw new \RuntimeException(
                'File UM kosong atau tidak dapat dibaca.'
            );
        }

        // ==============================
        // CARI POSISI HEADER
        // ==============================
        $header = array_map(
            fn ($value) => $this->normalizeHeader($value),
            $rows[0]
        );

        $uidIndex = array_search('uid', $header);
        $dateTimeIndex = array_search('datetime', $header);

        if (
            $uidIndex === false ||
            $dateTimeIndex === false
        ) {
            throw new \RuntimeException(
                'Header UID / DateTime pada file UM tidak ditemukan.'
            );
        }


        // ==============================
        // PROSES DATA
        // ==============================
        foreach (array_slice($rows, 1) as $row) {

            $uid = $row[$uidIndex] ?? null;
            $datetime = $row[$dateTimeIndex] ?? null;

            $uid = $this->normalizeUid($uid);
            $datetime = trim((string) $datetime);

            if ($uid === '' || $datetime === '') {
                $this->skipped++;
                continue;
            }


            // UID 0 dianggap invalid
            if ($uid === '0') {
                $this->skipped++;
                continue;
            }


            // ==============================
            // CARI EMPLOYEE SESUAI UNIT
            // ==============================
            $employee = $this->employeeByNormalizedUid(
                $uid
            );

            if (!$employee) {
                $this->skipped++;
                continue;
            }


            // ==============================
            // PARSE TANGGAL
            // ==============================
            try {

                $scanTime = Carbon::createFromFormat(
                    'd/m/Y H:i',
                    $datetime
                );

            } catch (\Throwable $e) {

                try {

                    $scanTime = Carbon::parse($datetime);

                } catch (\Throwable $e) {

                    $this->skipped++;
                    continue;
                }
            }


            // ==============================
            // SIMPAN LOG
            // ==============================
            $log = AttendanceLog::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'scan_time' => $scanTime->format(
                        'Y-m-d H:i:s'
                    ),
                ],
                [
                    'uid' => $employee->uid,
                ]
            );


            if ($log->wasRecentlyCreated) {

                $this->insertedLogs++;

            } else {

                $this->duplicates++;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PREPARE EMPLOYEE
    |--------------------------------------------------------------------------
    */
    private function prepareEmployees(): void
    {
        $employees = Employee::where(
                'unit_id',
                $this->unitId
            )
            ->get([
                'id',
                'uid',
                'nama',
                'unit_id',
                'work_schedule_id',
            ]);

        foreach ($employees as $employee) {

            // ==========================================
            // EXACT UID
            // contoh:
            // "01" tetap "01"
            // "1"  tetap "1"
            // ==========================================
            $exactUid = trim(
                (string) $employee->uid
            );

            if ($exactUid !== '') {
                $this->employees[$exactUid] = $employee;
            }


            // ==========================================
            // NORMALIZED UID
            // khusus format yang memang membutuhkan,
            // terutama UM
            // ==========================================
            $normalizedUid = $this->normalizeUid(
                $employee->uid
            );

            if ($normalizedUid !== '') {

                // Jangan overwrite kalau ternyata
                // ada "01" dan "1" dalam unit yang sama.
                if (
                    !isset(
                        $this->employeesNormalized[
                            $normalizedUid
                        ]
                    )
                ) {
                    $this->employeesNormalized[
                        $normalizedUid
                    ] = $employee;
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE UID
    |--------------------------------------------------------------------------
    */
    private function normalizeUid($uid): string
    {
        $uid = trim(
            (string) ($uid ?? '')
        );

        // Excel kadang mengubah 1 menjadi 1.0
        $uid = preg_replace(
            '/\.0+$/',
            '',
            $uid
        );

        // 001 → 1
        $uid = ltrim(
            $uid,
            '0'
        );

        return trim($uid);
    }


    /*
    |--------------------------------------------------------------------------
    | FIND EMPLOYEE
    |--------------------------------------------------------------------------
    */
    private function employeeByExactUid($uid): ?Employee
    {
        $uid = trim(
            (string) ($uid ?? '')
        );

        // Excel kadang menghasilkan "1.0"
        $uid = preg_replace(
            '/\.0+$/',
            '',
            $uid
        );

        if ($uid === '') {
            return null;
        }

        if (!isset($this->employees[$uid])) {

            $this->missingUids[$uid] = true;

            return null;
        }

        return $this->employees[$uid];
    }

    private function employeeByNormalizedUid($uid): ?Employee
    {
        $uid = $this->normalizeUid($uid);

        if ($uid === '') {
            return null;
        }

        if (
            !isset(
                $this->employeesNormalized[$uid]
            )
        ) {

            $this->missingUids[$uid] = true;

            return null;
        }

        return $this->employeesNormalized[$uid];
    }

    private function importSmaMatrix(string $path): void
    {
        $spreadsheet = IOFactory::load($path);

        $rows = $spreadsheet
            ->getActiveSheet()
            ->toArray(
                null,
                true,
                true,
                false
            );

        if (empty($rows)) {
            throw new \RuntimeException(
                'File SMA kosong atau tidak dapat dibaca.'
            );
        }

        $headerIndex = null;
        $header = [];
        $year = null;
        $month = null;

        // =========================================================
        // CARI PERIODE + HEADER
        // =========================================================
        foreach ($rows as $index => $row) {

            $firstCell = trim(
                (string) ($row[0] ?? '')
            );

            // Contoh:
            // Made Date:2026/08/01-2026/08/31
            if (
                str_starts_with(
                    strtolower($firstCell),
                    'made date:'
                )
            ) {

                if (
                    preg_match(
                        '/(\d{4})\/(\d{2})\/\d{2}\s*-\s*(\d{4})\/(\d{2})\/\d{2}/',
                        $firstCell,
                        $match
                    )
                ) {

                    $year = (int) $match[1];
                    $month = (int) $match[2];

                    // File SMA yang kita dukung sekarang
                    // harus satu bulan
                    if (
                        (int) $match[3] !== $year ||
                        (int) $match[4] !== $month
                    ) {
                        throw new \RuntimeException(
                            'Periode SMA harus berada dalam satu bulan.'
                        );
                    }
                }
            }


            if (
                $this->normalizeHeader($firstCell)
                === 'employee id'
            ) {

                $headerIndex = $index;
                $header = $row;
            }
        }


        if ($headerIndex === null) {
            throw new \RuntimeException(
                'Header Employee ID pada file SMA tidak ditemukan.'
            );
        }

        if (!$year || !$month) {
            throw new \RuntimeException(
                'Periode Made Date pada file SMA tidak ditemukan.'
            );
        }


        // =========================================================
        // PROSES SETIAP EMPLOYEE
        // =========================================================
        foreach (
            array_slice($rows, $headerIndex + 1)
            as $row
        ) {

            $uid = $this->normalizeUid(
                $row[0] ?? null
            );

            if ($uid === '') {
                continue;
            }


            $employee = $this->employeeByExactUid(
                $uid
            );


            // =====================================================
            // PROSES KOLOM TANGGAL 1 - 31
            // =====================================================
            foreach ($header as $columnIndex => $dayRaw) {

                // kolom 0-2 = UID, nama, department
                if ($columnIndex < 3) {
                    continue;
                }

                $dayRaw = trim(
                    (string) $dayRaw
                );

                if (
                    $dayRaw === '' ||
                    !ctype_digit($dayRaw)
                ) {
                    continue;
                }

                $day = (int) $dayRaw;

                if (
                    !checkdate(
                        $month,
                        $day,
                        $year
                    )
                ) {
                    continue;
                }


                $cell = trim(
                    (string) ($row[$columnIndex] ?? '')
                );

                if ($cell === '') {
                    continue;
                }


                // =================================================
                // AMBIL SEMUA JAM DARI CELL
                //
                // contoh:
                // 06:58
                // 12:06
                // 12:07
                // 16:11
                // =================================================
                preg_match_all(
                    '/(?:[01]\d|2[0-3]):[0-5]\d/',
                    $cell,
                    $matches
                );

                $rawTimes = $matches[0] ?? [];

                if (empty($rawTimes)) {
                    $this->skipped++;
                    continue;
                }


                // exact duplicate:
                // 06:47 06:47 16:23
                // menjadi:
                // 06:47 16:23
                $times = array_values(
                    array_unique($rawTimes)
                );


                $duplicateInCell =
                    count($rawTimes) - count($times);

                if ($duplicateInCell > 0) {
                    $this->duplicates += $duplicateInCell;
                }


                // Employee belum ada di master
                if (!$employee) {

                    $this->skipped += count($times);

                    continue;
                }


                $date = sprintf(
                    '%04d-%02d-%02d',
                    $year,
                    $month,
                    $day
                );


                // =================================================
                // SIMPAN SEMUA SCAN
                // =================================================
                foreach ($times as $time) {

                    try {

                        $scanTime = Carbon::createFromFormat(
                            'Y-m-d H:i',
                            $date . ' ' . $time
                        );

                    } catch (\Throwable $e) {

                        $this->skipped++;

                        continue;
                    }


                    $log = AttendanceLog::firstOrCreate(
                        [
                            'employee_id' =>
                                $employee->id,

                            'scan_time' =>
                                $scanTime->format(
                                    'Y-m-d H:i:s'
                                ),
                        ],
                        [
                            'uid' =>
                                $employee->uid,
                        ]
                    );


                    if ($log->wasRecentlyCreated) {

                        $this->insertedLogs++;

                    } else {

                        $this->duplicates++;
                    }
                }
            }
        }
    }

    private function importTkGamaMatrix(string $path): void
    {
        $spreadsheet = IOFactory::load($path);

        $rows = $spreadsheet
            ->getActiveSheet()
            ->toArray(
                null,
                true,
                true,
                false
            );

        if (empty($rows)) {
            throw new \RuntimeException(
                'File TK Gama kosong atau tidak dapat dibaca.'
            );
        }


        // =========================================================
        // CARI PERIODE
        // contoh:
        // 2026-08-01 ~ 2026-08-31
        // =========================================================
        $year = null;
        $month = null;

        foreach (array_slice($rows, 0, 10) as $row) {

            foreach ($row as $cell) {

                $cell = trim(
                    (string) ($cell ?? '')
                );

                if (
                    preg_match(
                        '/(\d{4})-(\d{2})-(\d{2})\s*~\s*(\d{4})-(\d{2})-(\d{2})/',
                        $cell,
                        $match
                    )
                ) {

                    $year = (int) $match[1];
                    $month = (int) $match[2];

                    if (
                        (int) $match[4] !== $year ||
                        (int) $match[5] !== $month
                    ) {
                        throw new \RuntimeException(
                            'Periode TK Gama harus berada dalam satu bulan.'
                        );
                    }

                    break 2;
                }
            }
        }


        if (!$year || !$month) {

            throw new \RuntimeException(
                'Periode absensi TK Gama tidak ditemukan.'
            );
        }


        // =========================================================
        // CARI HEADER TANGGAL 1 - 31
        // =========================================================
        $dayHeaderIndex = null;
        $dayHeader = [];

        foreach ($rows as $index => $row) {

            $first = trim(
                (string) ($row[0] ?? '')
            );

            if ($first === '1') {

                $numericCount = collect($row)
                    ->filter(function ($value) {

                        $value = trim(
                            (string) ($value ?? '')
                        );

                        return $value !== ''
                            && ctype_digit($value);

                    })
                    ->count();

                if ($numericCount >= 20) {

                    $dayHeaderIndex = $index;
                    $dayHeader = $row;

                    break;
                }
            }
        }


        if ($dayHeaderIndex === null) {

            throw new \RuntimeException(
                'Header tanggal TK Gama tidak ditemukan.'
            );
        }


        // =========================================================
        // PROSES SETIAP EMPLOYEE
        // =========================================================
        for (
            $rowIndex = $dayHeaderIndex + 1;
            $rowIndex < count($rows);
            $rowIndex++
        ) {

            $row = $rows[$rowIndex];

            $firstCell = $this->normalizeHeader(
                $row[0] ?? null
            );


            // Baris employee ditandai "ID:"
            if ($firstCell !== 'id:') {
                continue;
            }


            $uid = trim(
                (string) ($row[2] ?? '')
            );

            $nama = trim(
                (string) ($row[10] ?? '')
            );


            if ($uid === '') {
                $this->skipped++;
                continue;
            }


            // TK Gama pakai EXACT UID
            $employee = $this->employeeByExactUid(
                $uid
            );


            // =====================================================
            // BARIS SETELAH EMPLOYEE = DATA SCAN
            // =====================================================
            $scanRow = $rows[$rowIndex + 1] ?? [];

            if (empty($scanRow)) {
                continue;
            }


            foreach (
                $dayHeader as $columnIndex => $dayRaw
            ) {

                $dayRaw = trim(
                    (string) ($dayRaw ?? '')
                );

                if (
                    $dayRaw === '' ||
                    !ctype_digit($dayRaw)
                ) {
                    continue;
                }

                $day = (int) $dayRaw;

                if (
                    !checkdate(
                        $month,
                        $day,
                        $year
                    )
                ) {
                    continue;
                }


                $cell = trim(
                    (string) (
                        $scanRow[$columnIndex] ?? ''
                    )
                );

                if ($cell === '') {
                    continue;
                }


                // =================================================
                // contoh:
                //
                // 06:3413:0114:4715:00
                //
                // menjadi:
                //
                // 06:34
                // 13:01
                // 14:47
                // 15:00
                // =================================================
                preg_match_all(
                    '/(?:[01]\d|2[0-3]):[0-5]\d/',
                    $cell,
                    $matches
                );

                $rawTimes = $matches[0] ?? [];

                if (empty($rawTimes)) {

                    $this->skipped++;

                    continue;
                }


                // hapus exact duplicate
                $times = array_values(
                    array_unique($rawTimes)
                );


                $duplicateInCell =
                    count($rawTimes) -
                    count($times);

                if ($duplicateInCell > 0) {

                    $this->duplicates +=
                        $duplicateInCell;
                }


                if (!$employee) {

                    $this->skipped +=
                        count($times);

                    continue;
                }


                $date = sprintf(
                    '%04d-%02d-%02d',
                    $year,
                    $month,
                    $day
                );


                foreach ($times as $time) {

                    try {

                        $scanTime =
                            Carbon::createFromFormat(
                                'Y-m-d H:i',
                                $date . ' ' . $time
                            );

                    } catch (\Throwable $e) {

                        $this->skipped++;

                        continue;
                    }


                    $log =
                        AttendanceLog::firstOrCreate(
                            [
                                'employee_id' =>
                                    $employee->id,

                                'scan_time' =>
                                    $scanTime->format(
                                        'Y-m-d H:i:s'
                                    ),
                            ],
                            [
                                'uid' =>
                                    $employee->uid,
                            ]
                        );


                    if ($log->wasRecentlyCreated) {

                        $this->insertedLogs++;

                    } else {

                        $this->duplicates++;
                    }
                }
            }
        }
    }

    private function parseDailyDate($value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_numeric($value)) {

            try {

                $date = \PhpOffice\PhpSpreadsheet\Shared\Date
                    ::excelToDateTimeObject($value);

                return Carbon::instance($date)->startOfDay();

            } catch (\Throwable $e) {
                return null;
            }
        }

        $value = trim(
            (string) ($value ?? '')
        );

        if ($value === '') {
            return null;
        }

        $formats = [
            'd/m/Y',
            'd-m-Y',
            'Y-m-d',
            'd-M-y',
            'd-M-Y',
        ];

        foreach ($formats as $format) {

            try {

                return Carbon::createFromFormat(
                    $format,
                    $value
                )->startOfDay();

            } catch (\Throwable $e) {
                //
            }
        }

        try {

            return Carbon::parse($value)
                ->startOfDay();

        } catch (\Throwable $e) {

            return null;
        }
    }

    private function parseDailyTime($value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Kalau PhpSpreadsheet mengembalikan DateTime
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }

        // Kalau Excel time berbentuk angka serial
        if (is_numeric($value)) {

            try {

                return \PhpOffice\PhpSpreadsheet\Shared\Date
                    ::excelToDateTimeObject((float) $value)
                    ->format('H:i:s');

            } catch (\Throwable $e) {

                return null;
            }
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        // =====================================================
        // FORMAT JAM YANG DIDUKUNG
        //
        // 06:57
        // 6:57
        // 06:57:00
        // 6:57:00
        // =====================================================
        $formats = [
            'H:i:s',
            'G:i:s',
            'H:i',
            'G:i',
        ];

        foreach ($formats as $format) {

            try {

                $time = Carbon::createFromFormat(
                    $format,
                    $value
                );

                if ($time !== false) {

                    return $time->format('H:i:s');
                }

            } catch (\Throwable $e) {

                // coba format berikutnya
            }
        }

        // Fallback terakhir
        try {

            return Carbon::parse($value)
                ->format('H:i:s');

        } catch (\Throwable $e) {

            return null;
        }
    }

    private function saveDailyAttendance(
        Employee $employee,
        string $date,
        ?string $checkIn,
        ?string $checkOut,
        EmployeeScheduleService $scheduleService
    ): void {

        // Tidak ada scan sama sekali
        if (!$checkIn && !$checkOut) {
            return;
        }


        // =========================================================
        // AMBIL JADWAL DARI SISTEM
        //
        // BUKAN jadwal yang tercetak di file fingerprint.
        //
        // EmployeeScheduleService sudah mempertimbangkan:
        // - Kaldik
        // - Jadwal khusus employee
        // - Jadwal dasar
        // =========================================================
        $schedule = $scheduleService->getSchedule(
            $employee,
            $date
        );


        // Jika hari tersebut memang libur menurut sistem
        if (!$schedule) {

            $this->skipped++;

            return;
        }


        // =========================================================
        // HITUNG TERLAMBAT
        // =========================================================
        $lateMinutes = 0;

        $scheduleIn =
            $schedule['jam_masuk']
            ?? null;


        if ($checkIn && $scheduleIn) {

            try {

                $actualIn = Carbon::parse(
                    $date . ' ' . $checkIn
                );

                $expectedIn = Carbon::parse(
                    $date . ' ' . $scheduleIn
                );


                if ($actualIn->greaterThan($expectedIn)) {

                    $lateMinutes = (int)
                        $expectedIn->diffInMinutes(
                            $actualIn
                        );
                }

            } catch (\Throwable $e) {

                $lateMinutes = 0;
            }
        }


        // =========================================================
        // SIMPAN ATTENDANCE
        // =========================================================
        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $date,
            ],
            [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'late_minutes' => $lateMinutes,
                'status' => 'hadir',
            ]
        );


        if (
            $attendance->wasRecentlyCreated ||
            $attendance->wasChanged()
        ) {

            $this->savedAttendances++;

        } else {

            $this->duplicates++;
        }


        // =========================================================
        // SINKRONKAN ACTIVITY
        //
        // SD/SMP hanya menyediakan:
        // - Masuk
        // - Keluar
        //
        // Jadi jangan mengarang aktivitas tengah hari.
        // =========================================================
        $attendance->activities()->delete();


        if ($checkIn) {

            $attendance->activities()->create([
                'time' => $checkIn,
                'type' => 'in',
                'is_with_permission' => false,
            ]);
        }


        if ($checkOut) {

            $attendance->activities()->create([
                'time' => $checkOut,
                'type' => 'out',
                'is_with_permission' => false,
            ]);
        }
    }

    private function importSekolahDaily(
        string $path,
        EmployeeScheduleService $scheduleService
    ): void {

        $spreadsheet = IOFactory::load($path);

        // =========================================================
        // BACA SEMUA SHEET
        //
        // Contoh SMP:
        // - Guru
        // - Staff
        // =========================================================
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {

            $rows = $worksheet->toArray(
                null,
                true,
                true,
                false
            );

            if (empty($rows)) {
                continue;
            }


            // =====================================================
            // CARI HEADER PADA SHEET INI
            // =====================================================
            $headerIndex = null;
            $columnMap = [];


            foreach ($rows as $index => $row) {

                $normalized = [];

                foreach ($row as $column => $value) {

                    $header = $this->normalizeHeader(
                        $value
                    );

                    if ($header !== '') {
                        $normalized[$header] = $column;
                    }
                }


                if (
                    isset($normalized['nama']) &&
                    isset($normalized['no. staff']) &&
                    isset($normalized['tanggal']) &&
                    isset($normalized['masuk']) &&
                    isset($normalized['keluar'])
                ) {

                    $headerIndex = $index;
                    $columnMap = $normalized;

                    break;
                }
            }


            // Sheet yang tidak punya format attendance
            // dilewati saja.
            if ($headerIndex === null) {
                continue;
            }


            // =====================================================
            // PROSES DATA PADA SHEET INI
            // =====================================================
            for (
                $i = $headerIndex + 1;
                $i < count($rows);
                $i++
            ) {

                $row = $rows[$i];


                // ===============================================
                // UID
                // ===============================================
                $uid = trim(
                    (string) (
                        $row[
                            $columnMap['no. staff']
                        ] ?? ''
                    )
                );


                // ===============================================
                // NAMA
                // ===============================================
                $nama = trim(
                    (string) (
                        $row[
                            $columnMap['nama']
                        ] ?? ''
                    )
                );


                if ($uid === '') {
                    continue;
                }


                // ===============================================
                // EMPLOYEE
                //
                // Exact UID:
                // "01" tetap berbeda dengan "1"
                // ===============================================
                $employee = $this->employeeByExactUid(
                    $uid
                );


                // ===============================================
                // TANGGAL
                // ===============================================
                $dateValue =
                    $row[
                        $columnMap['tanggal']
                    ] ?? null;


                $date = $this->parseDailyDate(
                    $dateValue
                );


                if (!$date) {

                    $this->skipped++;

                    continue;
                }


                // ===============================================
                // JAM MASUK
                // ===============================================
                $checkIn = $this->parseDailyTime(
                    $row[
                        $columnMap['masuk']
                    ] ?? null
                );


                // ===============================================
                // JAM PULANG
                // ===============================================
                $checkOut = $this->parseDailyTime(
                    $row[
                        $columnMap['keluar']
                    ] ?? null
                );


                // Dua-duanya kosong
                // berarti tidak ada scan.
                if (!$checkIn && !$checkOut) {
                    continue;
                }


                // UID tidak ditemukan di master employee
                if (!$employee) {

                    $this->skipped++;

                    continue;
                }


                // ===============================================
                // SIMPAN ATTENDANCE
                // ===============================================
                $this->saveDailyAttendance(
                    $employee,
                    $date->format('Y-m-d'),
                    $checkIn,
                    $checkOut,
                    $scheduleService
                );
            }
        }
    }

    private function importTkTamaDaily(
        string $path,
        EmployeeScheduleService $scheduleService
    ): void {

        $spreadsheet = IOFactory::load($path);

        // =========================================================
        // TK TAMA
        //
        // Format:
        // Nama | No. Staff | Jadwal | Masuk | Keluar | ...
        //
        // Tanggal berada pada baris pemisah, contoh:
        // GURU--------01/08/2026
        // =========================================================
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {

            $rows = $worksheet->toArray(
                null,
                true,
                true,
                false
            );

            $currentDate = null;


            foreach ($rows as $row) {

                $firstColumn = trim(
                    (string) ($row[0] ?? '')
                );


                // =================================================
                // DETEKSI TANGGAL
                //
                // contoh:
                // GURU--------01/08/2026
                // =================================================
                if (
                    preg_match(
                        '/(\d{2}\/\d{2}\/\d{4})/',
                        $firstColumn,
                        $matches
                    )
                ) {

                    try {

                        $currentDate = Carbon::createFromFormat(
                            'd/m/Y',
                            $matches[1]
                        )->format('Y-m-d');

                    } catch (\Throwable $e) {

                        $currentDate = null;
                    }

                    continue;
                }


                // Belum menemukan tanggal
                if (!$currentDate) {
                    continue;
                }


                // =================================================
                // SKIP HEADER
                // =================================================
                if (
                    $this->normalizeHeader(
                        $firstColumn
                    ) === 'nama'
                ) {
                    continue;
                }


                // =================================================
                // DATA EMPLOYEE
                // =================================================
                $uid = trim(
                    (string) ($row[1] ?? '')
                );

                if ($uid === '') {
                    continue;
                }


                // =================================================
                // JAM AKTUAL
                //
                // col 2 = Jadwal mesin
                // col 3 = Masuk aktual
                // col 4 = Keluar aktual
                // =================================================
                $checkIn = $this->parseDailyTime(
                    $row[3] ?? null
                );

                $checkOut = $this->parseDailyTime(
                    $row[4] ?? null
                );


                // Tidak ada fingerprint sama sekali
                if (!$checkIn && !$checkOut) {
                    continue;
                }


                // =================================================
                // EMPLOYEE
                // =================================================
                $employee = $this->employeeByExactUid(
                    $uid
                );


                if (!$employee) {

                    $this->skipped++;

                    continue;
                }


                // =================================================
                // SIMPAN
                //
                // Penting:
                // - boleh hanya Masuk
                // - boleh hanya Keluar
                // =================================================
                $this->saveDailyAttendance(
                    $employee,
                    $currentDate,
                    $checkIn,
                    $checkOut,
                    $scheduleService
                );
            }
        }
    }
}