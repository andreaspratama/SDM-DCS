
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
                    $att = $row['absen'];

                    $jamMasuk = optional($att)->check_in;
                    $jamPulang = optional($att)->check_out;

                    $jamMasukStandar = optional($employee->workSchedule)->jam_masuk ?? '07:30:00';
                    $jamPulangStandar = optional($employee->workSchedule)->jam_pulang ?? '15:30:00';

                    $telat = $jamMasuk && $jamMasuk > $jamMasukStandar;
                    $pulangCepat = $jamPulang && $jamPulang < $jamPulangStandar;

                    // =====================================================
                    // STATUS HARI
                    // =====================================================
                    if ($row['is_hari_kerja_khusus'] ?? false) {

                        $status = 'HARI_KERJA_KHUSUS';

                    } elseif ($row['izin'] && $row['izin']->count()) {

                        $status = 'IZIN';

                    } elseif ($att) {

                        $status = 'HADIR';

                    } else {

                        $status = 'TANPA_KETERANGAN';
                    }

                    // 🎨 WARNA
                    $color = match($status) {

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
                    // TIMELINE / DETAIL AKTIVITAS
                    // =====================================================
                    $timeline = [];

                    // =====================================================
                    // TERLAMBAT
                    // =====================================================
                    if ($status !== 'HARI_KERJA_KHUSUS' && $jamMasuk && $jamMasuk > $jamMasukStandar) {

                        $standarMasukFix = \Carbon\Carbon::parse(
                            $row['tanggal'] . ' ' . $jamMasukStandar
                        );

                        $masukFix = \Carbon\Carbon::parse(
                            $row['tanggal'] . ' ' . $jamMasuk
                        );

                        $menitTelat = (int) $standarMasukFix
                            ->diffInMinutes($masukFix);

                        $timeline[] = [
                            'text' => '⏰ Terlambat: ' . $menitTelat . ' menit',
                            'color' => '#dc2626',
                        ];
                    }


                    // =====================================================
                    // SEMUA IZIN
                    // =====================================================
                    if ($row['izin'] && $row['izin']->count()) {

                        foreach ($row['izin'] as $izin) {

                            if ($izin->time_start && $izin->time_end) {

                                $timeline[] = [
                                    'text' => '📄 ' .
                                        $izin->time_start .
                                        ' - ' .
                                        $izin->time_end .
                                        ' → ' .
                                        $izin->type,

                                    'color' => '#0d6efd',
                                ];
                            }
                        }
                    }

                    // =====================================================
                    // AKTIVITAS KELUAR → MASUK DI TENGAH JAM KERJA
                    // =====================================================
                    if ($status !== 'HARI_KERJA_KHUSUS' && $att && $att->activities) {

                        $activities = $att->activities
                            ->sortBy('time')
                            ->values();

                        for ($i = 0; $i < $activities->count() - 1; $i++) {

                            $current = $activities[$i];
                            $next    = $activities[$i + 1];

                            // Hanya pasangan OUT → IN
                            if (
                                $current->type !== 'out' ||
                                $next->type !== 'in'
                            ) {
                                continue;
                            }

                            $jamKeluar = \Carbon\Carbon::parse(
                                $row['tanggal'] . ' ' . $current->time
                            );

                            $jamKembali = \Carbon\Carbon::parse(
                                $row['tanggal'] . ' ' . $next->time
                            );

                            $standarMasuk = \Carbon\Carbon::parse(
                                $row['tanggal'] . ' ' . $row['jadwal']['jam_masuk']
                            );

                            $standarPulang = \Carbon\Carbon::parse(
                                $row['tanggal'] . ' ' . $row['jadwal']['jam_pulang']
                            );

                            // Hanya aktivitas di dalam jam kerja
                            if (
                                $jamKeluar->lt($standarMasuk) ||
                                $jamKeluar->gte($standarPulang)
                            ) {
                                continue;
                            }

                            $durasiKeluar = (int) $jamKeluar
                                ->diffInMinutes($jamKembali);

                            // =================================================
                            // CEK IZIN
                            // =================================================
                            $izinAktivitas = $row['izin']->first(
                                function ($permission) use (
                                    $row,
                                    $jamKeluar,
                                    $jamKembali
                                ) {

                                    if (
                                        !$permission->time_start ||
                                        !$permission->time_end
                                    ) {
                                        return false;
                                    }

                                    $izinMulai = \Carbon\Carbon::parse(
                                        $row['tanggal'] . ' ' .
                                        $permission->time_start
                                    );

                                    $izinSelesai = \Carbon\Carbon::parse(
                                        $row['tanggal'] . ' ' .
                                        $permission->time_end
                                    );

                                    return $izinMulai->lte($jamKeluar)
                                        && $izinSelesai->gte($jamKembali);
                                }
                            );

                            // =================================================
                            // MASUKKAN KE TIMELINE
                            // =================================================
                            if ($izinAktivitas) {

                                $timeline[] = [
                                    'text' =>
                                        '📄 Keluar Dengan Izin: ' .
                                        $durasiKeluar .
                                        ' menit (' .
                                        $current->time .
                                        ' - ' .
                                        $next->time .
                                        ')',

                                    'color' => '#0d6efd',
                                ];

                            } else {

                                $timeline[] = [
                                    'text' =>
                                        '🚨 Keluar Tanpa Izin: ' .
                                        $durasiKeluar .
                                        ' menit (' .
                                        $current->time .
                                        ' - ' .
                                        $next->time .
                                        ')',

                                    'color' => '#dc2626',
                                ];
                            }
                        }
                    }


                    // =====================================================
                    // PULANG CEPAT
                    // =====================================================
                    if ($status !== 'HARI_KERJA_KHUSUS' && $jamPulang && $jamPulang < $jamPulangStandar) {

                        $pulangFix = \Carbon\Carbon::parse(
                            $row['tanggal'] . ' ' . $jamPulang
                        );

                        $standarPulangFix = \Carbon\Carbon::parse(
                            $row['tanggal'] . ' ' . $jamPulangStandar
                        );

                        $menitPulangCepat = (int) $pulangFix
                            ->diffInMinutes($standarPulangFix);

                        $timeline[] = [
                            'text' => '🏃 Pulang Cepat: ' .
                                $menitPulangCepat .
                                ' menit',

                            'color' => '#c2410c',
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

                                @if($status === 'HADIR')

                                    <i class="fa-solid fa-circle-check me-1"></i>
                                    Hadir

                                @elseif($status === 'IZIN')

                                    <i class="fa-solid fa-file-circle-check me-1"></i>
                                    Izin

                                @elseif($status === 'HARI_KERJA_KHUSUS')

                                    <i class="fa-solid fa-calendar-check me-1"></i>
                                    Hari Kerja Khusus

                                @elseif($status === 'TANPA_KETERANGAN')

                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                    Tanpa Keterangan

                                @endif

                            </span>
                        </div>

                        <hr>

                        {{-- JAM --}}
                        <div style="font-size:14px;">
                            @if($status === 'HARI_KERJA_KHUSUS')

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

                                        {{ $row['calendar_name'] ?? 'Hari Kerja Khusus' }}
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
                                            Sesuai Kaldik TA {{ $row['academic_year'] }}
                                        </div>

                                    @endif

                                </div>

                            @else

                                <div style="margin-top:12px;">

                                    <div>
                                        <i class="fa-solid fa-right-to-bracket me-1"></i>

                                        Masuk:
                                        <b>{{ $jamMasuk ?? '-' }}</b>

                                        @if($jamMasuk)

                                            @if($telat)
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

                                            @if($pulangCepat)
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

                                    @if($izin->time_start && $izin->time_end)
                                        <div style="font-size:12px; color:#666;">
                                            ⏰ {{ $izin->time_start }} - {{ $izin->time_end }}
                                        </div>
                                    @else
                                        <div style="
                                            margin-top:5px;
                                            padding:6px;
                                            background:#fff3cd;
                                            color:#856404;
                                            border-radius:6px;
                                            font-size:12px;
                                        ">
                                            ⚠️ Jam izin belum lengkap
                                        </div>
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