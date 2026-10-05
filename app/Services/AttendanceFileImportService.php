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

    protected array $employeesExact = [];
    
    protected array $employeesNormalized = [];

    protected array $missingUids = [];

    protected int $insertedLogs = 0;

    protected int $savedAttendances = 0;

    protected int $duplicates = 0;

    protected int $skipped = 0;

    // TAMBAHAN BARU -------------------------------
    protected array $skippedReasons = [
        'empty_uid' => 0,
        'empty_datetime' => 0,
        'invalid_uid' => 0,
        'employee_not_found' => 0,
        'invalid_datetime' => 0,
    ];

    // DIBAWAH ADALAH PERUBAHAN BARU

    private function resetState(): void
    {
        $this->employees = [];

        $this->employeesExact = [];

        $this->employeesNormalized = [];

        $this->missingUids = [];

        $this->insertedLogs = 0;

        $this->savedAttendances = 0;

        $this->duplicates = 0;

        $this->skipped = 0;

        $this->skippedReasons = [
            'empty_uid' => 0,
            'empty_datetime' => 0,
            'invalid_uid' => 0,
            'employee_not_found' => 0,
            'invalid_datetime' => 0,
        ];
    }

    public function import(
        UploadedFile $file,
        int $unitId,
        EmployeeScheduleService $scheduleService
    ): array {

        // =====================================================
        // TK GAMA / GM
        // Format khusus:
        // - C3 = periode tanggal
        // - baris 4 = nomor tanggal
        // - setiap pegawai = 2 baris
        // - jam scan berada di A:O
        // =====================================================

        if ($this->isTkGamaFormat($file)) {

            return $this->importTkGama(
                $file,
                $unitId
            );
        }

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

            'skipped_reasons' =>
                $this->skippedReasons,

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

        // return [
        //     'format' => $format,
        //     'inserted_logs' => 0,
        //     'saved_attendances' => 0,
        //     'duplicates' => 0,
        //     'skipped' => 0,
        //     'missing_uids' => [],
        //     'needs_process' => false,
        // ];
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

            // =====================================================
            // CEK HEADER
            // =====================================================
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

            // =====================================================
            // UM RAW TANPA HEADER
            // Contoh:
            // 73,01/09/2026 06:29
            // 73,01/09/2026 16:22
            // =====================================================
            $firstUid = trim(
                (string) ($rows[0][0] ?? '')
            );

            $firstDateTime = trim(
                (string) ($rows[0][1] ?? '')
            );

            if (
                $firstUid !== '' &&
                $firstDateTime !== '' &&
                preg_match('/^\d+$/', $firstUid)
            ) {
                try {
                    Carbon::parse($firstDateTime);

                    return 'um_raw';

                } catch (\Throwable $e) {
                    // lanjut ke error format
                }
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
            // DAILY SCHOOL / TK TAMA
            // -----------------------------------------------------

            $header = array_map(
                fn ($value) => $this->normalizeHeader($value),
                $rows[0] ?? []
            );

            // =====================================================
            // TK TAMA
            //
            // Struktur aktual:
            //
            // Nama
            // No. Staff
            // Dept.
            // Tanggal
            // Hari
            // Tipe
            // Jadwal
            // [kosong]
            // Masuk
            // [kosong]
            // Keluar
            // Masuk
            // Keluar
            // Lembur Masuk
            // Lembur Keluar
            // =====================================================

            if (
                ($header[0] ?? '') === 'nama' &&
                ($header[1] ?? '') === 'no. staff' &&
                ($header[2] ?? '') === 'dept.' &&
                ($header[3] ?? '') === 'tanggal' &&
                ($header[4] ?? '') === 'hari' &&
                ($header[5] ?? '') === 'tipe' &&
                ($header[6] ?? '') === 'jadwal' &&
                ($header[8] ?? '') === 'masuk' &&
                ($header[10] ?? '') === 'keluar' &&
                ($header[11] ?? '') === 'masuk' &&
                ($header[12] ?? '') === 'keluar' &&
                ($header[13] ?? '') === 'lembur masuk' &&
                ($header[14] ?? '') === 'lembur keluar'
            ) {
                return 'tktama_daily';
            }

            // =====================================================
            // SD / SMP
            // =====================================================
            //
            // Jangan sampai format TK Tama masuk sini.
            // =====================================================

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
            // Format terbaru:
            //
            // Nama
            // No. Staff
            // Tanggal
            // Hari
            // Masuk
            // Keluar
            // Masuk
            // Keluar
            // Lembur Masuk
            // Lembur Keluar
            // -----------------------------------------------------
            if (
                ($header[0] ?? '') === 'nama' &&
                ($header[1] ?? '') === 'no. staff' &&
                ($header[2] ?? '') === 'tanggal' &&
                ($header[3] ?? '') === 'hari' &&
                ($header[4] ?? '') === 'masuk' &&
                ($header[5] ?? '') === 'keluar'
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

        // =====================================================
        // DETEKSI HEADER
        // Mendukung:
        //
        // UID,DateTime
        // 73,01/09/2026 06:29
        //
        // maupun tanpa header:
        //
        // 73,01/09/2026 06:29
        // 73,01/09/2026 16:22
        // =====================================================

        $firstRow = $rows[0] ?? [];

        $header = array_map(
            fn ($value) => $this->normalizeHeader($value),
            $firstRow
        );

        $uidIndex = 0;
        $dateTimeIndex = 1;

        $hasHeader =
            in_array('uid', $header, true) &&
            in_array('datetime', $header, true);

        if ($hasHeader) {

            $uidIndex = array_search(
                'uid',
                $header,
                true
            );

            $dateTimeIndex = array_search(
                'datetime',
                $header,
                true
            );

            $dataRows = array_slice($rows, 1);

        } else {

            // File UM tanpa header
            $dataRows = $rows;
        }


        // =====================================================
        // TAMPUNG DATA VALID DULU
        //
        // Jangan langsung hapus database.
        // Kita pastikan file memang berisi data valid
        // sebelum melakukan proses replace.
        // =====================================================

        $validScans = [];

        $employeeIds = [];

        $minDate = null;
        $maxDate = null;


        // =====================================================
        // PARSE SEMUA BARIS
        // =====================================================

        foreach ($dataRows as $row) {

            if (
                !array_key_exists($uidIndex, $row) ||
                !array_key_exists($dateTimeIndex, $row)
            ) {
                $this->skipped++;
                $this->skippedReasons['empty_uid']++;
                continue;
            }


            // =================================================
            // UID + DATETIME
            // =================================================

            $rawUid = $row[$uidIndex] ?? null;

            $rawDateTime = $row[$dateTimeIndex] ?? null;

            $uid = trim(
                (string) ($rawUid ?? '')
            );

            $datetime = trim(
                (string) ($rawDateTime ?? '')
            );


            // Baris benar-benar kosong
            if ($uid === '' && $datetime === '') {
                continue;
            }


            // =================================================
            // UID KOSONG
            // =================================================

            if ($uid === '') {
                $this->skipped++;
                $this->skippedReasons['empty_uid']++;
                continue;
            }


            // =================================================
            // BERSIHKAN UID
            // =================================================

            $uid = preg_replace(
                '/\.0+$/',
                '',
                $uid
            );

            $uid = trim($uid);


            // =================================================
            // NORMALISASI UID
            // =================================================

            $normalizedUid = $this->normalizeUid(
                $uid
            );

            if (
                $normalizedUid === '' ||
                $normalizedUid === '0'
            ) {
                $this->skipped++;
                $this->skippedReasons['invalid_uid']++;
                continue;
            }


            // =================================================
            // CARI EMPLOYEE
            // =================================================

            $employee = $this->employeeByNormalizedUid(
                $normalizedUid
            );

            if (!$employee) {
                $this->skipped++;
                $this->skippedReasons['employee_not_found']++;
                continue;
            }


            // =================================================
            // DATETIME KOSONG
            // =================================================

            if ($datetime === '') {
                $this->skipped++;
                $this->skippedReasons['empty_datetime']++;
                continue;
            }


            // =================================================
            // BERSIHKAN SPASI
            // =================================================

            $datetime = preg_replace(
                '/\s+/',
                ' ',
                $datetime
            );


            // =================================================
            // PARSE DATETIME
            // =================================================

            $scanTime = null;

            $formats = [
                'd/m/Y H:i:s',
                'd/m/Y H:i',

                'd-m-Y H:i:s',
                'd-m-Y H:i',

                'Y-m-d H:i:s',
                'Y-m-d H:i',

                'd/m/Y g:i:s A',
                'd/m/Y g:i A',

                'd-m-Y g:i:s A',
                'd-m-Y g:i A',

                'Y-m-d g:i:s A',
                'Y-m-d g:i A',
            ];


            foreach ($formats as $format) {

                try {

                    $scanTime = Carbon::createFromFormat(
                        $format,
                        $datetime
                    );

                    if ($scanTime) {
                        break;
                    }

                } catch (\Throwable $e) {

                    // lanjut format berikutnya

                }
            }


            // =================================================
            // FALLBACK
            // =================================================

            if (!$scanTime) {

                try {

                    $scanTime = Carbon::parse(
                        $datetime
                    );

                } catch (\Throwable $e) {

                    $scanTime = null;
                }
            }


            // =================================================
            // DATETIME TIDAK VALID
            // =================================================

            if (!$scanTime) {

                $this->skipped++;

                $this->skippedReasons[
                    'invalid_datetime'
                ]++;

                continue;
            }


            // =================================================
            // NORMALISASI DATETIME
            // =================================================

            $scanTime = $scanTime->format(
                'Y-m-d H:i:s'
            );

            $scanCarbon = Carbon::parse(
                $scanTime
            );


            // =================================================
            // SIMPAN KE ARRAY TERLEBIH DAHULU
            // =================================================

            $validScans[] = [
                'employee_id' => $employee->id,
                'uid' => $employee->uid,
                'scan_time' => $scanTime,
            ];

            $employeeIds[$employee->id] = true;


            // =================================================
            // TENTUKAN RENTANG TANGGAL
            // =================================================

            $scanDate = $scanCarbon->toDateString();

            if (
                $minDate === null ||
                $scanDate < $minDate
            ) {
                $minDate = $scanDate;
            }

            if (
                $maxDate === null ||
                $scanDate > $maxDate
            ) {
                $maxDate = $scanDate;
            }
        }


        // =====================================================
        // JIKA TIDAK ADA DATA VALID
        //
        // JANGAN HAPUS DATABASE SAMA SEKALI.
        // =====================================================

        if (empty($validScans)) {
            return;
        }


        // =====================================================
        // SYNC / REPLACE DATA LAMA
        //
        // Contoh:
        //
        // File baru:
        // 01/09/2026 - 30/09/2026
        //
        // Maka scan lama employee yang ada di file
        // pada tanggal tersebut akan dihapus.
        // =====================================================

        AttendanceLog::whereIn(
            'employee_id',
            array_keys($employeeIds)
        )
            ->whereBetween(
                'scan_time',
                [
                    $minDate . ' 00:00:00',
                    $maxDate . ' 23:59:59',
                ]
            )
            ->delete();


        // =====================================================
        // MASUKKAN DATA TERBARU
        // =====================================================

        foreach ($validScans as $scan) {

            $log = AttendanceLog::firstOrCreate(
                [
                    'employee_id' =>
                        $scan['employee_id'],

                    'scan_time' =>
                        $scan['scan_time'],
                ],
                [
                    'uid' =>
                        $scan['uid'],
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

            // =====================================================
            // UID ASLI / EXACT
            //
            // Contoh SHS:
            // 01 tetap 01
            // 1  tetap 1
            // =====================================================
            $exactUid = trim(
                (string) $employee->uid
            );


            // Bersihkan hanya format Excel seperti "1.0".
            // TIDAK menghilangkan leading zero.
            $exactUid = preg_replace(
                '/\.0+$/',
                '',
                $exactUid
            );


            if ($exactUid === '') {
                continue;
            }


            // Cache EXACT:
            //
            // 01 => employee Admin
            // 1  => employee Guru
            $this->employeesExact[$exactUid] =
                $employee;


            // =====================================================
            // UID NORMAL
            //
            // Dipakai untuk mesin seperti UM:
            // 0003 => 3
            // =====================================================

            // INI JUGA PERUBAHAN BARU YAA ------------------
            $normalizedUid =
                $this->normalizeUid(
                    $exactUid
                );


            if ($normalizedUid !== '') {

                // Cache umum
                $this->employees[
                    $normalizedUid
                ] = $employee;

                // Cache khusus UID normalized
                $this->employeesNormalized[
                    $normalizedUid
                ] = $employee;
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
    private function employeeByExactUid(
    $uid
    ): ?Employee {

        $uid = trim(
            (string) ($uid ?? '')
        );


        // Excel kadang menghasilkan:
        // 1.0
        //
        // Tetapi:
        // 01 TETAP 01
        $uid = preg_replace(
            '/\.0+$/',
            '',
            $uid
        );


        if ($uid === '') {
            return null;
        }


        // =====================================================
        // CARI EXACT UID
        //
        // 01 ≠ 1
        // 02 ≠ 2
        // =====================================================
        if (
            !isset(
                $this->employeesExact[$uid]
            )
        ) {

            $this->missingUids[$uid] =
                true;

            return null;
        }


        return $this->employeesExact[$uid];
    }

    
    // INI JUGA PERUBAHAN BARU YAA --------------------------
    private function employeeByNormalizedUid($uid): ?Employee
    {
        $uid = $this->normalizeUid($uid);

        if ($uid === '') {
            return null;
        }

        if (!isset($this->employees[$uid])) {

            $this->missingUids[$uid] = true;

            return null;
        }

        return $this->employees[$uid];
    }

    private function importSmaMatrix(
    string $path
    ): void {

        $spreadsheet =
            IOFactory::load($path);


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
            //
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

                    $year =
                        (int) $match[1];

                    $month =
                        (int) $match[2];


                    // Periode harus
                    // berada dalam bulan yang sama.
                    if (
                        (int) $match[3] !== $year
                        ||
                        (int) $match[4] !== $month
                    ) {

                        throw new \RuntimeException(
                            'Periode SMA harus berada dalam satu bulan.'
                        );
                    }
                }
            }


            // =====================================================
            // HEADER
            // =====================================================
            if (
                $this->normalizeHeader(
                    $firstCell
                ) === 'employee id'
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
            array_slice(
                $rows,
                $headerIndex + 1
            )
            as $row
        ) {

            // =====================================================
            // UID SMA HARUS EXACT
            //
            // 01 ≠ 1
            // 02 ≠ 2
            // =====================================================
            $uid = trim(
                (string) ($row[0] ?? '')
            );


            if ($uid === '') {
                continue;
            }


            $employee =
                $this->employeeByExactUid(
                    $uid
                );


            // =====================================================
            // PROSES KOLOM TANGGAL 1 - 31
            // =====================================================
            foreach (
                $header as
                $columnIndex => $dayRaw
            ) {

                // Kolom:
                // 0 = Employee ID
                // 1 = Name
                // 2 = Department
                if ($columnIndex < 3) {
                    continue;
                }


                $dayRaw = trim(
                    (string) $dayRaw
                );


                if (
                    $dayRaw === ''
                    ||
                    !ctype_digit($dayRaw)
                ) {
                    continue;
                }


                $day =
                    (int) $dayRaw;


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
                        $row[$columnIndex]
                        ?? ''
                    )
                );


                if ($cell === '') {
                    continue;
                }


                // =================================================
                // AMBIL SEMUA JAM
                //
                // Contoh cell:
                //
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


                $rawTimes =
                    $matches[0] ?? [];


                if (empty($rawTimes)) {

                    $this->skipped++;

                    continue;
                }


                // =================================================
                // HAPUS EXACT DUPLICATE
                //
                // 06:47
                // 06:47
                // 16:23
                //
                // menjadi:
                //
                // 06:47
                // 16:23
                // =================================================
                $times = array_values(
                    array_unique(
                        $rawTimes
                    )
                );


                $duplicateInCell =
                    count($rawTimes)
                    -
                    count($times);


                if ($duplicateInCell > 0) {

                    $this->duplicates +=
                        $duplicateInCell;
                }


                // Employee tidak ditemukan
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


                // =================================================
                // SIMPAN SEMUA RAW SCAN
                // =================================================
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


                    if (
                        $log->wasRecentlyCreated
                    ) {

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

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {

            $rows = $worksheet->toArray(
                null,
                true,
                true,
                false
            );

            foreach ($rows as $index => $row) {

                if (
        in_array(
            'Hanna Megawati',
            array_map(
                fn ($value) => trim((string) $value),
                $row
            ),
            true
        )
    ) {
        // dd([
        //     'index' => $index,
        //     'row' => $row,
        // ]);
    }

                // =====================================================
                // SKIP BARIS KOSONG
                // =====================================================
                if (empty(array_filter(
                    $row,
                    fn ($value) =>
                        $value !== null &&
                        trim((string) $value) !== ''
                ))) {
                    continue;
                }


                // =====================================================
                // HEADER
                // =====================================================
                $firstColumn =
                    $this->normalizeHeader(
                        $row[0] ?? ''
                    );

                if ($firstColumn === 'nama') {
                    continue;
                }


                // =====================================================
                // UID
                // =====================================================
                $uid = trim(
                    (string) ($row[1] ?? '')
                );

                if ($uid === '') {
                    continue;
                }


                // =====================================================
                // TANGGAL
                // =====================================================
                $dateValue =
                    $row[2] ?? null;

                if (
                    $dateValue === null ||
                    trim((string) $dateValue) === ''
                ) {
                    continue;
                }


                try {

                    if (
                        $dateValue
                        instanceof \DateTimeInterface
                    ) {

                        $date =
                            Carbon::instance(
                                $dateValue
                            )->format('Y-m-d');

                    } else {

                        $date =
                            Carbon::createFromFormat(
                                'd/m/Y',
                                trim(
                                    (string) $dateValue
                                )
                            )->format('Y-m-d');
                    }

                } catch (\Throwable $e) {

                    $this->skipped++;

                    $this->skippedReasons[] =
                        "Tanggal TK Tama tidak valid: {$dateValue}";

                    continue;
                }


                // =====================================================
                // EMPLOYEE
                // =====================================================
                $employee =
                    $this->employeeByExactUid(
                        $uid
                    );

                if (!$employee) {

                    $this->skipped++;

                    $this->skippedReasons[] =
                        "UID TK Tama belum terdaftar: {$uid}";

                    $this->missingUids[$uid] = true;

                    continue;
                }


                // =====================================================
                // AMBIL SEMUA SCAN TK TAMA
                //
                // Format Excel:
                // 0 = Nama
                // 1 = No. Staff
                // 2 = Tanggal
                // 3 = Hari
                // 4 = Masuk
                // 5 = Keluar
                // 6 = Masuk
                // 7 = Keluar
                // 8 = Lembur Masuk
                // 9 = Lembur Keluar
                // =====================================================
                $scanColumns = [

                    // ABSENSI REGULER
                    4 => [
                        'type' => 'in',
                        'overtime' => false,
                    ],

                    5 => [
                        'type' => 'out',
                        'overtime' => false,
                    ],

                    6 => [
                        'type' => 'in',
                        'overtime' => false,
                    ],

                    7 => [
                        'type' => 'out',
                        'overtime' => false,
                    ],

                    // LEMBUR
                    8 => [
                        'type' => 'in',
                        'overtime' => true,
                    ],

                    9 => [
                        'type' => 'out',
                        'overtime' => true,
                    ],
                ];

                $activities = [];

                foreach ($scanColumns as $column => $config) {

                    $time = $this->parseTkTamaTime(
                        $row[$column] ?? null
                    );

                    if (!$time) {
                        continue;
                    }

                    $activities[] = [
                        'time' => $time,
                        'type' => $config['type'],
                        'overtime' => $config['overtime'],
                    ];
                }


                // =====================================================
                // TIDAK ADA SCAN
                // =====================================================
                if (empty($activities)) {
                    continue;
                }


                // =====================================================
                // SORT BERDASARKAN JAM
                // =====================================================
                usort(
                    $activities,
                    function ($a, $b) {

                        return strcmp(
                            $a['time'],
                            $b['time']
                        );
                    }
                );


                // =====================================================
                // CHECK IN = IN TERAWAL
                // CHECK OUT = OUT TERAKHIR
                // =====================================================
                $checkIn = null;
                $checkOut = null;


                foreach ($activities as $activity) {

                    if (
                        $activity['type'] === 'in'
                        &&
                        $checkIn === null
                    ) {

                        $checkIn =
                            $activity['time'];
                    }


                    if (
                        $activity['type'] === 'out'
                    ) {

                        $checkOut =
                            $activity['time'];
                    }
                }


                // =====================================================
                // SIMPAN KHUSUS TK TAMA
                // =====================================================
                $this->saveTkTamaAttendance(
                    $employee,
                    $date,
                    $checkIn,
                    $checkOut,
                    $activities,
                    $scheduleService
                );
            }
        }
    }

    private function saveTkTamaAttendance(
        Employee $employee,
        string $date,
        ?string $checkIn,
        ?string $checkOut,
        array $activities,
        EmployeeScheduleService $scheduleService
    ): void {

        // =====================================================
        // TIDAK ADA SCAN
        // =====================================================
        if (
            !$checkIn &&
            !$checkOut
        ) {
            return;
        }


        // =====================================================
        // UNIT EMPLOYEE PADA TANGGAL TERSEBUT
        // =====================================================
        $unitId =
            app(\App\Services\EmployeeUnitService::class)
                ->getUnitId(
                    $employee,
                    $date
                );


        // =====================================================
        // KALDIK
        //
        // true  = eksplisit hari kerja
        // false = eksplisit libur
        // null  = tidak ada override
        // =====================================================
        $calendarService =
            app(\App\Services\WorkCalendarService::class);

        $calendar =
            $calendarService->getCalendar(
                $date,
                $unitId
            );

        $calendarWorkday =
            $calendar
                ? (bool) $calendar->is_workday
                : null;


        // =====================================================
        // JADWAL NORMAL
        // =====================================================
        $schedule =
            $scheduleService->getSchedule(
                $employee,
                $date
            );


        // =====================================================
        // CEK APAKAH ADA SCAN DARI KOLOM LEMBUR
        // =====================================================
        $hasOvertimeScan = collect($activities)
            ->contains(
                fn ($activity) =>
                    !empty($activity['overtime'])
            );


        // =====================================================
        // TENTUKAN APAKAH INI LEMBUR
        //
        // 1. Ada scan lembur dari Excel
        //    ATAU
        // 2. Kaldik secara eksplisit libur
        //
        // Tapi jika ada jadwal normal dan scan normal,
        // jangan dianggap lembur hanya karena calendar null.
        // =====================================================
        $isLemburHariLibur =
            $hasOvertimeScan
            ||
            $calendarWorkday === false;


        // =====================================================
        // STATUS
        // =====================================================
        $status =
            $isLemburHariLibur
                ? 'lembur'
                : 'hadir';


        // =====================================================
        // HITUNG TERLAMBAT
        //
        // Hanya attendance normal.
        // Lembur tidak dihitung terlambat.
        // =====================================================
        $lateMinutes = 0;

        if (
            !$isLemburHariLibur
            &&
            $checkIn
            &&
            $schedule
            &&
            !empty($schedule['jam_masuk'])
        ) {

            try {

                $actualIn =
                    Carbon::parse(
                        $date . ' ' . $checkIn
                    );

                $expectedIn =
                    Carbon::parse(
                        $date . ' ' .
                        $schedule['jam_masuk']
                    );

                if (
                    $actualIn->greaterThan(
                        $expectedIn
                    )
                ) {

                    $lateMinutes =
                        (int) $expectedIn
                            ->diffInMinutes(
                                $actualIn
                            );
                }

            } catch (\Throwable $e) {

                $lateMinutes = 0;
            }
        }


        // =====================================================
        // SIMPAN ATTENDANCE
        // =====================================================
        $attendance =
            Attendance::updateOrCreate(
                [
                    'employee_id' =>
                        $employee->id,

                    'date' =>
                        $date,
                ],
                [
                    'check_in' =>
                        $checkIn,

                    'check_out' =>
                        $checkOut,

                    'late_minutes' =>
                        $lateMinutes,

                    'status' =>
                        $status,
                ]
            );


        // =====================================================
        // COUNTER
        // =====================================================
        if (
            $attendance->wasRecentlyCreated
            ||
            $attendance->wasChanged()
        ) {

            $this->savedAttendances++;

        } else {

            $this->duplicates++;
        }


        // =====================================================
        // HAPUS ACTIVITY LAMA
        // =====================================================
        $attendance
            ->activities()
            ->delete();


        // =====================================================
        // SIMPAN SEMUA ACTIVITY
        // =====================================================
        foreach ($activities as $activity) {

            $attendance
                ->activities()
                ->create([
                    'time' =>
                        $activity['time'],

                    'type' =>
                        $activity['type'],

                    'is_with_permission' =>
                        false,
                ]);
        }
    }

    private function parseTkTamaTime($value): ?string
    {
        // =========================================================
        // NULL
        // =========================================================

        if ($value === null) {
            return null;
        }

        // =========================================================
        // DateTime dari PhpSpreadsheet
        // =========================================================

        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }

        // =========================================================
        // Excel TIME SERIAL
        //
        // Contoh:
        // 0.284027... = 06:49
        // =========================================================

        if (is_numeric($value)) {

            try {

                $numericValue = (float) $value;

                // Pastikan ini benar-benar nilai waktu,
                // bukan angka UID atau angka lain.
                if (
                    $numericValue >= 0 &&
                    $numericValue < 1
                ) {

                    return \PhpOffice\PhpSpreadsheet\Shared\Date
                        ::excelToDateTimeObject($numericValue)
                        ->format('H:i:s');
                }

            } catch (\Throwable $e) {

                return null;
            }

            return null;
        }

        // =========================================================
        // STRING
        // =========================================================

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        // =========================================================
        // FORMAT JAM TK TAMA
        //
        // 6:49
        // 06:49
        // 6:49:00
        // 06:49:00
        // =========================================================

        $formats = [
            'G:i',
            'H:i',
            'G:i:s',
            'H:i:s',
        ];

        foreach ($formats as $format) {

            try {

                $time = Carbon::createFromFormat(
                    $format,
                    $value
                );

                return $time->format('H:i:s');

            } catch (\Throwable $e) {

                // Coba format berikutnya
            }
        }

        // =========================================================
        // FALLBACK
        // =========================================================

        try {

            return Carbon::parse($value)
                ->format('H:i:s');

        } catch (\Throwable $e) {

            return null;
        }
    }

    // =====================================================
    // DETEKSI FORMAT TK GAMA
    // =====================================================
    private function isTkGamaFormat($file): bool
    {
        try {

            $spreadsheet =
                \PhpOffice\PhpSpreadsheet\IOFactory::load(
                    $file->getRealPath()
                );

            $sheet =
                $spreadsheet->getActiveSheet();


            $judul =
                trim(
                    (string) $sheet
                        ->getCell('A1')
                        ->getValue()
                );


            $labelTanggal =
                trim(
                    (string) $sheet
                        ->getCell('A3')
                        ->getValue()
                );


            $periode =
                trim(
                    (string) $sheet
                        ->getCell('C3')
                        ->getValue()
                );


            // =================================================
            // VALIDASI STRUKTUR TK GAMA
            // =================================================
            if (
                stripos(
                    $judul,
                    'Lap. Detail Absensi'
                ) === false
            ) {
                return false;
            }


            if (
                strcasecmp(
                    $labelTanggal,
                    'Waktu Absen'
                ) !== 0
            ) {
                return false;
            }


            if (
                !preg_match(
                    '/^\d{4}-\d{2}-\d{2}\s*~\s*\d{4}-\d{2}-\d{2}$/',
                    $periode
                )
            ) {
                return false;
            }


            // Baris 4 harus berisi nomor tanggal
            $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
                $sheet->getHighestColumn()
            );

            for (
                $column = 1;
                $column <= $highestColumn;
                $column++
            ) {

                $value =
                    $sheet
                        ->getCellByColumnAndRow(
                            $column,
                            4
                        )
                        ->getValue();

                if (
                    (int) $value !== $column
                ) {
                    return false;
                }
            }


            return true;

        } catch (\Throwable $e) {

            return false;
        }
    }


    // =====================================================
    // IMPORT FORMAT TK GAMA
    // =====================================================
    private function importTkGama(
        $file,
        int $unitId
    ): array {

        $spreadsheet =
            \PhpOffice\PhpSpreadsheet\IOFactory::load(
                $file->getRealPath()
            );

        $sheet =
            $spreadsheet->getActiveSheet();


        // =================================================
        // PERIODE
        // =================================================
        $periode =
            trim(
                (string) $sheet
                    ->getCell('C3')
                    ->getValue()
            );


        if (
            !preg_match(
                '/^(\d{4}-\d{2}-\d{2})\s*~\s*(\d{4}-\d{2}-\d{2})$/',
                $periode,
                $matches
            )
        ) {

            throw new \RuntimeException(
                'Periode tanggal TK Gama tidak dapat dibaca.'
            );
        }


        $startDate =
            \Carbon\Carbon::parse(
                $matches[1]
            )->startOfDay();


        $endDate =
            \Carbon\Carbon::parse(
                $matches[2]
            )->startOfDay();


        // =================================================
        // HASIL
        // =================================================
        $insertedLogs = 0;

        $duplicates = 0;

        $skipped = 0;

        $missingUids = [];


        // =================================================
        // POLA JAM
        //
        // Contoh:
        // 06:2715:0415:06
        //
        // menjadi:
        // 06:27
        // 15:04
        // 15:06
        // =================================================
        $timePattern =
            '/\d{1,2}:\d{2}/';


        $maxRow =
            $sheet->getHighestRow();


        // =================================================
        // LOOP PEGAWAI
        //
        // Baris:
        // 5 = ID
        // 6 = scan
        //
        // 7 = ID
        // 8 = scan
        // dst.
        // =================================================
        for (
            $row = 5;
            $row <= $maxRow;
            $row++
        ) {

            $idLabel =
                trim(
                    (string) $sheet
                        ->getCellByColumnAndRow(
                            1,
                            $row
                        )
                        ->getValue()
                );


            if (
                strtoupper($idLabel) !== 'ID:'
            ) {
                continue;
            }


            // =================================================
            // UID
            // =================================================
            $uid =
                trim(
                    (string) $sheet
                        ->getCellByColumnAndRow(
                            3,
                            $row
                        )
                        ->getValue()
                );


            if ($uid === '') {

                $skipped++;

                continue;
            }


            // =================================================
            // NORMALISASI UID
            //
            // Supaya:
            // 00017
            // 17
            //
            // tetap bisa dicocokkan.
            // =================================================
            $normalizedUid =
                ltrim(
                    $uid,
                    '0'
                );

            if ($normalizedUid === '') {
                $normalizedUid = '0';
            }


            // =================================================
            // CARI EMPLOYEE SESUAI UNIT
            // =================================================
            $employee =
                \App\Models\Employee::where(
                    'unit_id',
                    $unitId
                )
                ->whereRaw(
                    "TRIM(LEADING '0' FROM uid) = ?",
                    [
                        $normalizedUid
                    ]
                )
                ->first();


            if (!$employee) {

                if (
                    !in_array(
                        $uid,
                        $missingUids,
                        true
                    )
                ) {

                    $missingUids[] =
                        $uid;
                }

                continue;
            }


            // =================================================
            // BARIS SCAN
            // =================================================
            $scanRow =
                $row + 1;


            if (
                $scanRow > $maxRow
            ) {
                continue;
            }


            // =================================================
            // KOLOM TANGGAL
            // Jumlah kolom mengikuti periode/file Excel
            // Tidak lagi dibatasi 15 hari
            // =================================================
            $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
                $sheet->getHighestColumn()
            );

            for (
                $column = 1;
                $column <= $highestColumn;
                $column++
            ) {

                $rawValue =
                    $sheet
                        ->getCellByColumnAndRow(
                            $column,
                            $scanRow
                        )
                        ->getValue();


                if (
                    $rawValue === null
                    ||
                    trim((string) $rawValue) === ''
                ) {
                    continue;
                }


                // =================================================
                // TANGGAL
                //
                // Kolom pertama = start date
                // Kolom kedua = +1 hari
                // dst.
                // =================================================
                $tanggal =
                    $startDate
                        ->copy()
                        ->addDays(
                            $column - 1
                        );


                // Jangan melewati end date
                if (
                    $tanggal->gt(
                        $endDate
                    )
                ) {
                    continue;
                }


                // =================================================
                // AMBIL SEMUA JAM
                // =================================================
                preg_match_all(
                    $timePattern,
                    (string) $rawValue,
                    $timeMatches
                );


                $times =
                    $timeMatches[0]
                    ?? [];


                if (empty($times)) {

                    $skipped++;

                    continue;
                }


                // =================================================
                // SIMPAN SEMUA SCAN
                // =================================================
                foreach (
                    $times as $time
                ) {

                    try {

                        $scanTime =
                            \Carbon\Carbon::createFromFormat(
                                'Y-m-d H:i',
                                $tanggal->format(
                                    'Y-m-d'
                                )
                                . ' '
                                . $time
                            )->format(
                                'Y-m-d H:i:s'
                            );

                    } catch (
                        \Throwable $e
                    ) {

                        $skipped++;

                        continue;
                    }


                    // =================================================
                    // FIRST OR CREATE
                    // =================================================
                    $log =
                        \App\Models\AttendanceLog::firstOrCreate(
                            [
                                'employee_id' =>
                                    $employee->id,

                                'scan_time' =>
                                    $scanTime,
                            ],
                            [
                                'uid' =>
                                    $employee->uid,
                            ]
                        );


                    if (
                        $log->wasRecentlyCreated
                    ) {

                        $insertedLogs++;

                    } else {

                        $duplicates++;
                    }
                }
            }


            // =================================================
            // LEWATI BARIS SCAN
            // =================================================
            $row++;
        }


        // =================================================
        // HASIL
        // =================================================
        return [

            'format' =>
                'tk_gama_detail',

            'inserted_logs' =>
                $insertedLogs,

            'saved_attendances' =>
                0,

            'duplicates' =>
                $duplicates,

            'skipped' =>
                $skipped,

            'missing_uids' =>
                $missingUids,

            // GM tetap RAW LOG
            // sehingga Process Data tetap digunakan
            'needs_process' =>
                true,
        ];
    }
}