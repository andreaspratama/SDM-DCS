
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
                        ⏰ Terlambat: <b>{{ $summary['telat'] }}x</b>
                    </div>

                    <div style="color:orange;">
                        🏃 Pulang Cepat: <b>{{ $summary['pulang_cepat'] }}x</b>
                    </div>

                    <div style="color:#856404;">
                        ⚠️ Tidak Masuk: <b>{{ $summary['tanpa_keterangan'] }}x</b>
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

                    // ✅ STATUS FIX (collection aware)
                    if ($row['izin'] && $row['izin']->count()) {
                        $status = 'IZIN';
                    } elseif ($att) {
                        $status = 'HADIR';
                    } else {
                        $status = 'TANPA_KETERANGAN';
                    }

                    // 🎨 WARNA
                    $color = match($status) {
                        'HADIR' => ['border'=>'#28a745','bg'=>'#eafaf1','text'=>'green'],
                        'IZIN' => ['border'=>'#0d6efd','bg'=>'#e7f1ff','text'=>'blue'],
                        'TANPA_KETERANGAN' => ['border'=>'#ffc107','bg'=>'#fff8e1','text'=>'#856404'],
                        default => ['border'=>'#dc3545','bg'=>'#fdecea','text'=>'red']
                    };

                    // 🔥 TIMELINE FIX TOTAL
                    $timeline = [];

                    // ⏰ TERLAMBAT
                    if ($jamMasuk && $jamMasuk > $jamMasukStandar) {
                        $timeline[] = [
                            'start' => $jamMasukStandar,
                            'end' => $jamMasuk,
                            'label' => 'Terlambat',
                            'color' => 'red'
                        ];
                    }

                    // 📄 SEMUA IZIN (MULTI)
                    if ($row['izin'] && $row['izin']->count()) {
                        foreach ($row['izin'] as $izin) {
                            if ($izin->time_start && $izin->time_end) {
                                $timeline[] = [
                                    'start' => $izin->time_start,
                                    'end' => $izin->time_end,
                                    'label' => $izin->type,
                                    'color' => '#0d6efd'
                                ];
                            }
                        }
                    }

                    // ⏰ PULANG CEPAT
                    if ($jamPulang && $jamPulang < $jamPulangStandar) {
                        $timeline[] = [
                            'start' => $jamPulang,
                            'end' => $jamPulangStandar,
                            'label' => 'Pulang Cepat',
                            'color' => 'orange'
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
                                @if($status == 'HADIR') ✔ Hadir
                                @elseif($status == 'IZIN') 📄 Izin
                                @elseif($status == 'TANPA_KETERANGAN') ⚠️ Tanpa Keterangan
                                @else ❌ Alpha
                                @endif
                            </span>
                        </div>

                        <hr>

                        {{-- JAM --}}
                        <div style="font-size:14px;">
                            <div>
                                ⏰ Masuk: <b>{{ $jamMasuk ?? '-' }}</b>
                                @if($jamMasuk)
                                    @if($telat)
                                        <span style="color:red;">(Terlambat)</span>
                                    @else
                                        <span style="color:green;">(On Time)</span>
                                    @endif
                                @endif
                            </div>

                            <div>
                                ⏰ Pulang: <b>{{ $jamPulang ?? '-' }}</b>
                                @if($jamPulang)
                                    @if($pulangCepat)
                                        <span style="color:red;">(Pulang Cepat)</span>
                                    @else
                                        <span style="color:green;">(Sesuai)</span>
                                    @endif
                                @endif
                            </div>
                        </div>

                        {{-- 🔥 TIMELINE --}}
                        @if(count($timeline))
                            <div style="margin-top:10px; font-size:13px;">
                                <b>📊 Detail Aktivitas:</b>

                                @foreach($timeline as $t)
                                    <div style="margin-top:4px; color:{{ $t['color'] }};">
                                        ⏰ {{ $t['start'] }} - {{ $t['end'] }} → {{ $t['label'] }}
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