
@extends('layouts.admin')

@section('title')
    Detail Absensi
@endsection

@section('content')

<main class="app-main">

    <div class="app-content-header">
        <div class="container-fluid">
            <h1 class="mb-3">
                Detail Absensi - {{ $employee->nama }} ({{ $employee->unit->nama ?? '-' }})
            </h1>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form method="GET" class="mb-3">
                <div style="display:flex; gap:10px; align-items:center;">
                    
                    <input type="date" name="start" value="{{ request('start') }}" class="form-control">
                    <input type="date" name="end" value="{{ request('end') }}" class="form-control">

                    <button class="btn btn-primary">Filter</button>

                    <a href="{{ route('absensi.detailRange', $employee->id) }}" 
                    class="btn btn-secondary">
                    Reset
                    </a>
                </div>
            </form>
            <div style="
                margin-bottom:15px;
                padding:15px;
                background:#f8f9fa;
                border-radius:10px;
                border:1px solid #ddd;
            ">
                <b>📊 Statistik Periode</b>

                <div style="display:flex; flex-wrap:wrap; gap:15px; margin-top:10px; font-size:14px;">
                    <div>📅 Hari Kerja: <b>{{ $summary['total_hari_kerja'] }}</b></div>

                    <div>📄 Izin: <b>{{ $summary['izin'] }}</b></div>

                    <div>✔ Hadir: <b>{{ $summary['hadir'] }}</b></div>

                    <div style="color:red;">
                        ⏰ Terlambat:
                        <b>{{ $summary['telat'] }}x</b>

                        @if($summary['total_menit_telat'] > 0)
                            <span>
                                ({{ $summary['total_menit_telat'] }} menit)
                            </span>
                        @endif
                    </div>

                    <div style="color:orange;">
                        🏃 Pulang Cepat:
                        <b>{{ $summary['pulang_cepat'] }}x</b>

                        @if($summary['total_menit_pulang_cepat'] > 0)
                            <span>
                                ({{ $summary['total_menit_pulang_cepat'] }} menit)
                            </span>
                        @endif
                    </div>

                    <div style="color:#856404;">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        Tidak Masuk:
                        <b>{{ $summary['tidak_masuk'] }}x</b>
                    </div>

                    <div style="color:#dc3545;">
                        <i class="fa-solid fa-circle-question me-1"></i>
                        Tanpa Keterangan:
                        <b>{{ $summary['tanpa_keterangan'] }}x</b>
                    </div>

                    <div style="color:#087990;">
                        <i class="fa-solid fa-calendar-check me-1"></i>
                        Hari Kerja Khusus:
                        <b>{{ $summary['hari_kerja_khusus'] ?? 0 }}x</b>
                    </div>

                    <div style="color:#0284c7;">
                        <i class="fa-solid fa-people-group me-1"></i>

                        Kegiatan Resmi:
                        <b>{{ $summary['kegiatan_resmi'] ?? 0 }}x</b>
                    </div>

                    <div style="color:#6f42c1;">
                        <i class="fa-solid fa-business-time me-1"></i>

                        Lembur:
                        <b>
                            {{ $summary['hari_lembur'] ?? 0 }}x
                        </b>
                    </div>

                    <div style="color:#6f42c1;">
                        <i class="fa-solid fa-clock me-1"></i>

                        Jam Lembur:
                        <b>
                            {{ $summary['total_jam_lembur_jam'] ?? 0 }} jam
                        </b>
                    </div>

                    <div style="color:#dc3545;">
                        🚨 Keluar Tanpa Izin: <b>{{ $summary['keluar_tanpa_izin'] }}x</b>
                    </div>

                    <div style="color:#0d6efd;">
                        🕒 Total Jam Kerja: <b>{{ $summary['total_jam_keluar_jam'] }} jam</b>
                    </div>

                </div>
            </div>
            <div class="card">
                <div class="card-body">

                    @foreach($data as $row)

                    @php

    // =====================================================
    // DATA DASAR
    // =====================================================
    $att = $row['absen'] ?? null;

    $jamMasuk =
        optional($att)->check_in;

    $jamPulang =
        optional($att)->check_out;


    // =====================================================
    // STATUS DARI CONTROLLER
    //
    // Jangan hitung ulang status di Blade.
    // Controller sudah menentukan:
    //
    // HADIR
    // IZIN
    // HARI_KERJA_KHUSUS
    // KEGIATAN_RESMI
    // LEMBUR
    // ALPHA
    // =====================================================
    $status =
        $row['status']
        ?? 'ALPHA';


    // =====================================================
    // FLAG
    // =====================================================
    $isLembur =
        (bool) (
            $row['is_lembur']
            ?? false
        );

    $isKegiatanResmi =
        (bool) (
            $row['is_kegiatan_resmi']
            ?? false
        );

    $isHariKerjaKhusus =
        (bool) (
            $row['is_hari_kerja_khusus']
            ?? false
        );


    // =====================================================
    // JADWAL
    //
    // PENTING:
    // Lembur dan Kegiatan Resmi bisa jadwal = NULL.
    //
    // Karena itu JANGAN lagi pakai:
    //
    // $row['jadwal']['jam_masuk']
    //
    // secara langsung.
    // =====================================================
    $jamMasukStandar =
        $row['jadwal']['jam_masuk']
        ?? null;

    $jamPulangStandar =
        $row['jadwal']['jam_pulang']
        ?? null;


    // =====================================================
    // APAKAH HARI REGULER?
    //
    // Hanya hari reguler yang boleh dihitung:
    //
    // - terlambat
    // - pulang cepat
    // - keluar tanpa izin
    // =====================================================
    $isHariReguler =
        !in_array(
            $status,
            [
                'HARI_KERJA_KHUSUS',
                'KEGIATAN_RESMI',
                'LEMBUR'
            ],
            true
        );


    // =====================================================
    // CARI IZIN TERLAMBAT
    // =====================================================
    $izinTerlambat = $row['izin']->first(
        function ($permission) {

            return
                $permission->type === 'Izin Terlambat'
                &&
                $permission->time_start;
        }
    );


    // =====================================================
    // CARI IZIN PULANG AWAL
    // =====================================================
    $izinPulangAwal = $row['izin']->first(
        function ($permission) {

            return
                $permission->type === 'Izin Pulang Awal'
                &&
                $permission->time_start;
        }
    );


    // =====================================================
    // TELAT
    // =====================================================
    $telat =
        $isHariReguler
        && $jamMasuk
        && $jamMasukStandar
        && $jamMasuk > $jamMasukStandar;


    $telatDenganIzin = false;


    if (
        $telat
        &&
        $izinTerlambat
    ) {

        $jamMasukAktual =
            \Carbon\Carbon::parse(
                $row['tanggal']
                . ' '
                . $jamMasuk
            );

        $batasIzin =
            \Carbon\Carbon::parse(
                $row['tanggal']
                . ' '
                . $izinTerlambat->time_start
            )
            ->addMinutes(5);


        if (
            $jamMasukAktual->lte(
                $batasIzin
            )
        ) {

            $telatDenganIzin = true;
            $telat = false;
        }
    }


    // =====================================================
    // PULANG CEPAT
    // =====================================================
    $pulangCepat =
        $isHariReguler
        && $jamPulang
        && $jamPulangStandar
        && $jamPulang < $jamPulangStandar;


    $pulangAwalDenganIzin = false;


    if (
        $pulangCepat
        &&
        $izinPulangAwal
    ) {

        $jamPulangAktual =
            \Carbon\Carbon::parse(
                $row['tanggal']
                . ' '
                . $jamPulang
            );


        $batasIzin =
            \Carbon\Carbon::parse(
                $row['tanggal']
                . ' '
                . $izinPulangAwal->time_start
            )
            ->subMinutes(5);


        if (
            $jamPulangAktual->gte(
                $batasIzin
            )
        ) {

            $pulangAwalDenganIzin = true;
            $pulangCepat = false;
        }
    }


    // =====================================================
    // WARNA CARD
    // =====================================================
    $color = match($status) {

        'LEMBUR' => [
            'border' => '#6f42c1',
            'bg' => '#f3edff',
            'text' => '#6f42c1'
        ],

        'KEGIATAN_RESMI' => [
            'border' => '#0284c7',
            'bg' => '#e0f2fe',
            'text' => '#0369a1'
        ],

        'HADIR' => [
            'border' => '#28a745',
            'bg' => '#eafaf1',
            'text' => 'green'
        ],

        'IZIN' => [
            'border' => '#0d6efd',
            'bg' => '#e7f1ff',
            'text' => 'blue'
        ],

        'HARI_KERJA_KHUSUS' => [
            'border' => '#0dcaf0',
            'bg' => '#e8f8fc',
            'text' => '#087990'
        ],

        'ALPHA',
        'TANPA_KETERANGAN' => [
            'border' => '#ffc107',
            'bg' => '#fff8e1',
            'text' => '#856404'
        ],

        default => [
            'border' => '#dc3545',
            'bg' => '#fdecea',
            'text' => 'red'
        ]
    };


    // =====================================================
    // TIMELINE
    // =====================================================
    $timeline = [];

    // =====================================================
    // TERLAMBAT DENGAN IZIN
    // =====================================================
    if ($telatDenganIzin) {

        $timeline[] = [

            'text' =>
                '✅ Terlambat Dengan Izin'
                . ' (Datang: '
                . $jamMasuk
                . ')',

            'color' =>
                '#0d6efd',
        ];
    }


    // =====================================================
    // PULANG AWAL DENGAN IZIN
    // =====================================================
    if ($pulangAwalDenganIzin) {

        $timeline[] = [

            'text' =>
                '✅ Pulang Awal Dengan Izin'
                . ' (Pulang: '
                . $jamPulang
                . ')',

            'color' =>
                '#0d6efd',
        ];
    }

    // =====================================================
    // TERLAMBAT
    // =====================================================
    if ($telat) {

        $standarMasukFix =
            \Carbon\Carbon::parse(
                $row['tanggal']
                . ' '
                . $jamMasukStandar
            );

        $masukFix =
            \Carbon\Carbon::parse(
                $row['tanggal']
                . ' '
                . $jamMasuk
            );

        $menitTelat =
            (int) $standarMasukFix
                ->diffInMinutes(
                    $masukFix
                );

        $timeline[] = [
            'text' =>
                '⏰ Terlambat: '
                . $menitTelat
                . ' menit',

            'color' =>
                '#dc2626',
        ];
    }


    // =====================================================
    // IZIN
    // =====================================================
    if (
        isset($row['izin'])
        && $row['izin']
        && $row['izin']->count()
    ) {

        foreach ($row['izin'] as $izinItem) {

            if (
                $izinItem->time_start
                && $izinItem->time_end
            ) {

                $timeline[] = [

                    'text' =>
                        '📄 '
                        . $izinItem->time_start
                        . ' - '
                        . $izinItem->time_end
                        . ' → '
                        . $izinItem->type,

                    'color' =>
                        '#0d6efd',
                ];
            }
        }
    }


    // =====================================================
    // AKTIVITAS KELUAR → MASUK
    //
    // HANYA HARI KERJA REGULER.
    //
    // Ini fix utama error:
    //
    // Trying to access array offset on null
    //
    // karena Kegiatan Resmi / Lembur tidak punya jadwal.
    // =====================================================
    if (
        $isHariReguler
        &&
        $att
        &&
        $att->activities
        &&
        $jamMasukStandar
        &&
        $jamPulangStandar
    ) {

        $activities =
            $att->activities
                ->sortBy('time')
                ->values();


        // =============================================
        // JAM STANDAR HARI INI
        // =============================================
        $standarMasuk =
            \Carbon\Carbon::parse(
                $row['tanggal']
                . ' '
                . $jamMasukStandar
            );

        $standarPulang =
            \Carbon\Carbon::parse(
                $row['tanggal']
                . ' '
                . $jamPulangStandar
            );


        // =============================================
        // LOOP ACTIVITIES
        // =============================================
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


            // =========================================
            // JAM KELUAR / KEMBALI
            // =========================================
            $jamKeluar =
                \Carbon\Carbon::parse(
                    $row['tanggal']
                    . ' '
                    . $current->time
                );

            $jamKembali =
                \Carbon\Carbon::parse(
                    $row['tanggal']
                    . ' '
                    . $next->time
                );


            // =========================================
            // HANYA DI DALAM JAM KERJA
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
            // DURASI KELUAR
            // =========================================
            $durasiKeluar =
                (int) $jamKeluar
                    ->diffInMinutes(
                        $jamKembali
                    );


            // =========================================
            // CEK IZIN
            // =========================================
            $izinAktivitas = null;

            if (
                isset($row['izin'])
                &&
                $row['izin']
            ) {

                $izinAktivitas =
                    $row['izin']->first(
                        function ($permission) use (
                            $row,
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
                                \Carbon\Carbon::parse(
                                    $row['tanggal']
                                    . ' '
                                    . $permission->time_start
                                );

                            $izinSelesai =
                                \Carbon\Carbon::parse(
                                    $row['tanggal']
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
            }


            // =========================================
            // TIMELINE
            // =========================================
            if ($izinAktivitas) {

                $timeline[] = [

                    'text' =>
                        '📄 Keluar Dengan Izin: '
                        . $durasiKeluar
                        . ' menit ('
                        . $current->time
                        . ' - '
                        . $next->time
                        . ')',

                    'color' =>
                        '#0d6efd',
                ];

            } else {

                $timeline[] = [

                    'text' =>
                        '🚨 Keluar Tanpa Izin: '
                        . $durasiKeluar
                        . ' menit ('
                        . $current->time
                        . ' - '
                        . $next->time
                        . ')',

                    'color' =>
                        '#dc2626',
                ];
            }
        }
    }


    // =====================================================
    // PULANG CEPAT
    // =====================================================
    if ($pulangCepat) {

        $pulangFix =
            \Carbon\Carbon::parse(
                $row['tanggal']
                . ' '
                . $jamPulang
            );

        $standarPulangFix =
            \Carbon\Carbon::parse(
                $row['tanggal']
                . ' '
                . $jamPulangStandar
            );

        $menitPulangCepat =
            (int) $pulangFix
                ->diffInMinutes(
                    $standarPulangFix
                );

        $timeline[] = [

            'text' =>
                '🏃 Pulang Cepat: '
                . $menitPulangCepat
                . ' menit',

            'color' =>
                '#c2410c',
        ];
    }

@endphp

                    <div style="
                        margin-bottom:20px;
                        padding:16px;
                        border-radius:12px;
                        border:1px solid {{ $color['border'] }};
                        background: {{ $color['bg'] }};
                    ">

                        {{-- HEADER --}}
                        <div style="display:flex; justify-content:space-between;">
                            <h5 style="margin:0;">
                                {{ \Carbon\Carbon::parse($row['tanggal'])->format('d M Y') }}
                            </h5>

                            <span style="font-weight:600; color:{{ $color['text'] }};">

                                @if($status === 'KEGIATAN_RESMI')

                                    <i class="fa-solid fa-people-group me-1"></i>
                                    Kegiatan Resmi

                                @elseif($status === 'LEMBUR')

                                    <i class="fa-solid fa-business-time me-1"></i>
                                    Lembur

                                @elseif($status === 'HADIR')

                                    <i class="fa-solid fa-circle-check me-1"></i>
                                    Hadir

                                @elseif($status === 'IZIN')

                                    <i class="fa-solid fa-file-circle-check me-1"></i>
                                    Izin

                                @elseif($status === 'HARI_KERJA_KHUSUS')

                                    <i class="fa-solid fa-calendar-check me-1"></i>
                                    Hari Kerja Khusus

                                @elseif(
                                    $status === 'ALPHA'
                                    || $status === 'TANPA_KETERANGAN'
                                )

                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                    Tanpa Keterangan

                                @endif

                            </span>
                        </div>

                        <hr>

                        {{-- JAM --}}
                        <div style="font-size:14px;">

                            {{-- =====================================================
                                KEGIATAN RESMI
                            ====================================================== --}}
                            @if($status === 'KEGIATAN_RESMI')

                                <div style="
                                    margin-top:14px;
                                    padding:14px;
                                    border-radius:10px;
                                    background:#e0f2fe;
                                    color:#0369a1;
                                    border:1px solid #7dd3fc;
                                ">

                                    {{-- NAMA KEGIATAN --}}
                                    <div style="
                                        font-size:15px;
                                        font-weight:700;
                                        margin-bottom:5px;
                                    ">

                                        <i class="fa-solid fa-people-group me-1"></i>

                                        {{
                                            $row['official_activity_name']
                                            ?? 'Kegiatan Resmi'
                                        }}

                                    </div>


                                    {{-- NAMA KALDIK --}}
                                    @if(!empty($row['calendar_name']))

                                        <div style="
                                            font-size:13px;
                                            opacity:.8;
                                            margin-bottom:12px;
                                        ">

                                            <i class="fa-solid fa-calendar-day me-1"></i>

                                            {{ $row['calendar_name'] }}

                                            @if(!empty($row['academic_year']))
                                                · TA {{ $row['academic_year'] }}
                                            @endif

                                        </div>

                                    @endif


                                    {{-- JAM MASUK --}}
                                    <div style="margin-bottom:5px;">

                                        <i class="fa-solid fa-right-to-bracket me-1"></i>

                                        Masuk:

                                        <b>
                                            {{ $jamMasuk ?? '-' }}
                                        </b>

                                    </div>


                                    {{-- JAM PULANG --}}
                                    <div style="margin-bottom:5px;">

                                        <i class="fa-solid fa-right-from-bracket me-1"></i>

                                        Pulang:

                                        <b>
                                            {{ $jamPulang ?? '-' }}
                                        </b>

                                    </div>


                                    {{-- KETERANGAN --}}
                                    <div style="
                                        margin-top:12px;
                                        padding:10px 12px;
                                        border-radius:8px;
                                        background:#bae6fd;
                                        color:#075985;
                                        font-size:13px;
                                    ">

                                        <i class="fa-solid fa-circle-info me-1"></i>

                                        Kehadiran pada kegiatan resmi.
                                        Tidak dihitung sebagai lembur,
                                        terlambat, atau pulang cepat.

                                    </div>

                                </div>


                            {{-- =====================================================
                                LEMBUR
                            ====================================================== --}}
                            @elseif($status === 'LEMBUR')

                                @php
                                    $menitLembur =
                                        (int) (
                                            $row['menit_lembur']
                                            ?? 0
                                        );

                                    $jamLembur =
                                        intdiv(
                                            $menitLembur,
                                            60
                                        );

                                    $sisaMenitLembur =
                                        $menitLembur % 60;
                                @endphp


                                <div style="
                                    margin-top:14px;
                                    padding:14px;
                                    border-radius:10px;
                                    background:#f3edff;
                                    color:#5a32a3;
                                    border:1px solid #d8c7ff;
                                ">

                                    <div style="
                                        font-size:15px;
                                        font-weight:700;
                                        margin-bottom:10px;
                                    ">

                                        <i class="fa-solid fa-business-time me-1"></i>
                                        Lembur di Hari Libur

                                    </div>


                                    <div style="margin-bottom:5px;">

                                        <i class="fa-solid fa-right-to-bracket me-1"></i>

                                        Masuk:
                                        <b>{{ $jamMasuk ?? '-' }}</b>

                                    </div>


                                    <div style="margin-bottom:5px;">

                                        <i class="fa-solid fa-right-from-bracket me-1"></i>

                                        Pulang:
                                        <b>{{ $jamPulang ?? '-' }}</b>

                                    </div>


                                    @if($jamMasuk && $jamPulang)

                                        <div style="
                                            margin-top:10px;
                                            padding-top:8px;
                                            border-top:1px dashed #bfa8ef;
                                            font-weight:700;
                                        ">

                                            <i class="fa-solid fa-clock me-1"></i>

                                            Durasi Lembur:

                                            @if($jamLembur > 0)
                                                {{ $jamLembur }} jam
                                            @endif

                                            @if($sisaMenitLembur > 0)
                                                {{ $sisaMenitLembur }} menit
                                            @endif

                                            @if(
                                                $jamLembur === 0
                                                && $sisaMenitLembur === 0
                                            )
                                                0 menit
                                            @endif

                                        </div>

                                    @else

                                        <div style="
                                            margin-top:10px;
                                            padding:8px 10px;
                                            border-radius:8px;
                                            background:#fff3cd;
                                            color:#856404;
                                        ">

                                            <i class="fa-solid fa-triangle-exclamation me-1"></i>

                                            Durasi lembur belum dapat dihitung karena
                                            scan masuk atau pulang tidak lengkap.

                                        </div>

                                    @endif

                                </div>


                            {{-- =====================================================
                                HARI KERJA KHUSUS
                            ====================================================== --}}
                            @elseif($status === 'HARI_KERJA_KHUSUS')

                                <div style="
                                    margin-top:14px;
                                    padding:14px;
                                    border-radius:10px;
                                    background:#cff4fc;
                                    color:#055160;
                                ">

                                    <div style="
                                        font-size:15px;
                                        font-weight:700;
                                        margin-bottom:5px;
                                    ">

                                        <i class="fa-solid fa-calendar-day me-1"></i>

                                        {{
                                            $row['calendar_name']
                                            ?? 'Hari Kerja Khusus'
                                        }}

                                    </div>


                                    @if(!empty($row['calendar_description']))

                                        <div style="font-size:13px;">
                                            {{ $row['calendar_description'] }}
                                        </div>

                                    @endif


                                    @if(!empty($row['academic_year']))

                                        <div style="
                                            margin-top:5px;
                                            font-size:12px;
                                            opacity:.75;
                                        ">

                                            Sesuai Kaldik TA
                                            {{ $row['academic_year'] }}

                                        </div>

                                    @endif

                                </div>


                            {{-- =====================================================
                                HARI NORMAL / IZIN / ALPHA
                            ====================================================== --}}
                            @else

                                <div style="margin-top:12px;">

                                    <div>

                                        <i class="fa-solid fa-right-to-bracket me-1"></i>

                                        Masuk:
                                        <b>{{ $jamMasuk ?? '-' }}</b>

                                        @if($jamMasuk)

                                            @if($telatDenganIzin)

                                                <span style="color:#0d6efd; font-weight:600;">
                                                    (Dengan Izin)
                                                </span>

                                            @elseif($telat)

                                                <span style="color:red;">
                                                    (Terlambat)
                                                </span>

                                            @else

                                                <span style="color:green;">
                                                    (On Time)
                                                </span>

                                            @endif

                                        @endif

                                    </div>


                                    <div>

                                        <i class="fa-solid fa-right-from-bracket me-1"></i>

                                        Pulang:
                                        <b>{{ $jamPulang ?? '-' }}</b>

                                        @if($jamPulang)

                                            @if($pulangAwalDenganIzin)

                                                <span style="color:#0d6efd; font-weight:600;">
                                                    (Dengan Izin)
                                                </span>

                                            @elseif($pulangCepat)

                                                <span style="color:red;">
                                                    (Pulang Cepat)
                                                </span>

                                            @else

                                                <span style="color:green;">
                                                    (Sesuai)
                                                </span>

                                            @endif

                                        @endif

                                    </div>

                                </div>

                            @endif

                        </div>

                        {{-- 🔥 TIMELINE --}}
                        @if(count($timeline))
                            <div style="margin-top:10px; font-size:13px;">
                                <b>📊 Detail Aktivitas:</b>

                                @foreach($timeline as $t)

                                    <div style="
                                        margin-top:6px;
                                        color:{{ $t['color'] }};
                                        font-weight:700;
                                    ">
                                        {{ $t['text'] }}
                                    </div>

                                @endforeach
                            </div>
                        @endif

                        {{-- IZIN --}}
                        @if($row['izin'] && $row['izin']->count())

                            @foreach($row['izin'] as $izin)
                                <div style="
                                    margin-top:10px;
                                    padding:10px;
                                    background:#fff;
                                    border-radius:8px;
                                    border:1px dashed #ccc;
                                    font-size:13px;
                                ">
                                    📌 <b>{{ $izin->type }}</b><br>
                                    <span style="color:#555;">
                                        {{ $izin->description }}
                                    </span>

                                    {{-- =============================================
                                        JAM IZIN
                                    ============================================= --}}

                                    @if(
                                        in_array(
                                            $izin->type,
                                            [
                                                'Izin Keluar Sementara',
                                                'Keperluan Pribadi'
                                            ],
                                            true
                                        )
                                    )

                                        @if($izin->time_start && $izin->time_end)

                                            <div style="
                                                font-size:12px;
                                                color:#666;
                                                margin-top:5px;
                                            ">

                                                🕒
                                                {{ $izin->time_start }}
                                                -
                                                {{ $izin->time_end }}

                                            </div>

                                        @endif


                                    @elseif($izin->type === 'Izin Terlambat')

                                        @if($izin->time_start)

                                            <div style="
                                                font-size:12px;
                                                color:#dc3545;
                                                margin-top:5px;
                                            ">

                                                ⏰ Izin datang:
                                                <b>{{ $izin->time_start }}</b>

                                            </div>

                                        @endif


                                    @elseif($izin->type === 'Izin Pulang Awal')

                                        @if($izin->time_start)

                                            <div style="
                                                font-size:12px;
                                                color:#fd7e14;
                                                margin-top:5px;
                                            ">

                                                🏃 Izin pulang:
                                                <b>{{ $izin->time_start }}</b>

                                            </div>

                                        @endif

                                    @endif
                                    {{-- 🔥 LAMPIRAN TARUH DI SINI --}}
                                    @if($izin->attachment)
                                        <div style="margin-top:6px;">
                                            📎 
                                            <a href="{{ asset('storage/'.$izin->attachment) }}" target="_blank">
                                                Lihat Lampiran
                                            </a>
                                        </div>
                                    @endif

                                </div>
                            @endforeach

                        @endif

                        {{-- WARNING --}}
                        @if($status == 'TANPA_KETERANGAN')
                            <div style="
                                margin-top:10px;
                                padding:10px;
                                background:#fff3cd;
                                color:#856404;
                                border-radius:8px;
                                font-size:13px;
                            ">
                                ⚠️ Tidak ada absensi dan tidak ada izin
                            </div>
                        @endif

                    </div>

                    @endforeach

                </div>
            </div>

        </div>
    </div>

</main>

@endsection