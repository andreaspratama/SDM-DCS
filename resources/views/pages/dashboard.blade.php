@extends('layouts.admin')

@section('title')
    Dashboard | SDM Absensi
@endsection


@push('prepend-style')

<style>

    .dashboard-hero {
        background:
            linear-gradient(
                135deg,
                #0d6efd 0%,
                #0dcaf0 100%
            );

        border-radius: 18px;
        padding: 24px 28px;
        color: white;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }

    .dashboard-hero::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(255,255,255,.09);
        right: -60px;
        top: -90px;
    }

    .dashboard-hero h1 {
        font-weight: 700;
        margin-bottom: 5px;
    }

    .dashboard-hero p {
        margin-bottom: 0;
        opacity: .9;
    }


    /* =========================================
       STAT CARD
    ========================================= */

    .stat-card {
        border: 0;
        border-radius: 16px;
        box-shadow:
            0 4px 18px rgba(0,0,0,.06);
        height: 100%;
        transition:
            transform .2s ease,
            box-shadow .2s ease;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow:
            0 8px 24px rgba(0,0,0,.09);
    }

    .stat-card .card-body {
        padding: 20px;
    }

    .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 23px;
    }

    .stat-label {
        font-size: 13px;
        color: #6c757d;
        margin-bottom: 3px;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #212529;
        line-height: 1.1;
    }

    .stat-note {
        font-size: 12px;
        color: #6c757d;
        margin-top: 6px;
    }


    /* =========================================
       CONTENT CARD
    ========================================= */

    .dashboard-card {
        border: 0;
        border-radius: 16px;
        box-shadow:
            0 4px 18px rgba(0,0,0,.055);
        overflow: hidden;
    }

    .dashboard-card .card-header {
        background: white;
        border-bottom: 1px solid #edf0f4;
        padding: 18px 20px;
    }

    .dashboard-card .card-title {
        font-weight: 700;
        font-size: 16px;
        margin: 0;
    }


    /* =========================================
       UNIT PROGRESS
    ========================================= */

    .unit-progress {
        height: 7px;
        border-radius: 50px;
        overflow: hidden;
        background: #e9ecef;
    }

    .unit-progress .progress-bar {
        border-radius: 50px;
    }


    /* =========================================
       CALENDAR
    ========================================= */

    .calendar-item {
        padding: 15px;
        border-radius: 12px;
        background: #f8f9fa;
        margin-bottom: 10px;
        border: 1px solid #edf0f4;
    }


    /* =========================================
       PERMISSION
    ========================================= */

    .permission-row:last-child {
        border-bottom: 0 !important;
    }

</style>

@endpush



@section('content')

<main class="app-main">


    {{-- =====================================================
         HEADER
    ===================================================== --}}
    <div class="app-content-header">

        <div class="container-fluid">

            <div class="row align-items-center">

                <div class="col-sm-7">

                    <h1 class="mb-0 fs-3 fw-bold">
                        Dashboard
                    </h1>

                </div>

                <div class="col-sm-5">

                    <ol class="breadcrumb float-sm-end mb-0">

                        <li class="breadcrumb-item">
                            Home
                        </li>

                        <li class="breadcrumb-item active">
                            Dashboard
                        </li>

                    </ol>

                </div>

            </div>

        </div>

    </div>



    <div class="app-content">

        <div class="container-fluid">


            {{-- =================================================
                 HERO
            ================================================= --}}
            <div class="dashboard-hero">

                <div class="row align-items-center">

                    <div class="col-md-8">

                        <div class="small opacity-75 mb-1">
                            Selamat datang
                        </div>

                        <h1 class="fs-3">
                            {{ auth()->user()->name }}
                        </h1>

                        <p>

                            <i class="bi bi-building me-1"></i>

                            Monitoring SDM & Absensi

                            <span class="mx-1">•</span>

                            {{ $scopeName }}

                        </p>

                    </div>


                    <div class="col-md-4 text-md-end mt-3 mt-md-0">

                        <div class="fs-5 fw-semibold">

                            {{ \Carbon\Carbon::parse($today)
                                ->locale('id')
                                ->translatedFormat('l, d F Y') }}

                        </div>

                        @if($loginEmployee)

                            <div class="small opacity-75">

                                {{ $loginEmployee->role }}

                                @if($loginEmployee->unit)

                                    • {{ $loginEmployee->unit->nama }}

                                @endif

                            </div>

                        @endif

                    </div>

                </div>

            </div>



            {{-- =================================================
                 SUMMARY CARDS
            ================================================= --}}
            <div class="row g-3 mb-4">


                {{-- TOTAL PEGAWAI --}}
                <div class="col-xl-3 col-md-6">

                    <div class="card stat-card">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <div class="stat-label">
                                        Total Pegawai
                                    </div>

                                    <div class="stat-value">
                                        {{ number_format($totalPegawai) }}
                                    </div>

                                    <div class="stat-note">
                                        {{ $scopeName }}
                                    </div>

                                </div>


                                <div
                                    class="
                                        stat-icon
                                        bg-primary-subtle
                                        text-primary
                                    "
                                >

                                    <i class="bi bi-people-fill"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>



                {{-- FINGERPRINT HARI INI --}}
                <div class="col-xl-3 col-md-6">

                    <div class="card stat-card">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <div class="stat-label">
                                        Fingerprint Hari Ini
                                    </div>

                                    <div class="stat-value">
                                        {{ number_format($fingerprintHariIni) }}
                                    </div>

                                    <div class="stat-note">

                                        {{ $fingerprintPercentage }}%

                                        dari total pegawai

                                    </div>

                                </div>


                                <div
                                    class="
                                        stat-icon
                                        bg-success-subtle
                                        text-success
                                    "
                                >

                                    <i class="bi bi-fingerprint"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>



                {{-- IZIN --}}
                <div class="col-xl-3 col-md-6">

                    <div class="card stat-card">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <div class="stat-label">
                                        Izin Aktif Hari Ini
                                    </div>

                                    <div class="stat-value">
                                        {{ number_format($izinHariIni) }}
                                    </div>

                                    <div class="stat-note">
                                        Izin yang sudah disetujui
                                    </div>

                                </div>


                                <div
                                    class="
                                        stat-icon
                                        bg-info-subtle
                                        text-info
                                    "
                                >

                                    <i class="bi bi-file-earmark-check-fill"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>



                {{-- PENDING APPROVAL --}}
                <div class="col-xl-3 col-md-6">

                    <div class="card stat-card">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <div class="stat-label">
                                        Menunggu Approval
                                    </div>

                                    <div class="stat-value">
                                        {{ number_format($pendingApproval) }}
                                    </div>

                                    <div class="stat-note">

                                        @if(auth()->user()->isPimpinan())

                                            Perlu perhatian Anda

                                        @else

                                            Total pengajuan pending

                                        @endif

                                    </div>

                                </div>


                                <div
                                    class="
                                        stat-icon
                                        bg-warning-subtle
                                        text-warning
                                    "
                                >

                                    <i class="bi bi-hourglass-split"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


            </div>



            {{-- =================================================
                 INFO TAMBAHAN
            ================================================= --}}
            <div class="row g-4 mb-4">


                {{-- =================================================
                     UNIT SUMMARY
                ================================================= --}}
                <div class="col-lg-7">

                    <div class="card dashboard-card h-100">

                        <div
                            class="
                                card-header
                                d-flex
                                justify-content-between
                                align-items-center
                            "
                        >

                            <div>

                                <h3 class="card-title">
                                    <i
                                        class="
                                            bi
                                            bi-bar-chart-fill
                                            text-primary
                                            me-2
                                        "
                                    ></i>

                                    Ringkasan Fingerprint per Unit
                                </h3>

                                <small class="text-muted">
                                    Data fingerprint untuk hari ini
                                </small>

                            </div>


                            @if(!$isKabid)

                                <a
                                    href="{{ route('absensi.index') }}"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Lihat Rekap
                                </a>

                            @endif

                        </div>


                        <div class="card-body p-0">

                            <div class="table-responsive">

                                <table
                                    class="
                                        table
                                        align-middle
                                        mb-0
                                    "
                                >

                                    <thead class="table-light">

                                        <tr>

                                            <th class="ps-4">
                                                Unit
                                            </th>

                                            <th class="text-center">
                                                Pegawai
                                            </th>

                                            <th class="text-center">
                                                Fingerprint
                                            </th>

                                            <th style="width:220px;">
                                                Cakupan
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                    @forelse($unitSummary as $unit)

                                        @php

                                            $percentage =
                                                $unit->total_pegawai > 0
                                                    ? round(
                                                        (
                                                            $unit->fingerprint_hari_ini
                                                            /
                                                            $unit->total_pegawai
                                                        )
                                                        * 100
                                                    )
                                                    : 0;

                                        @endphp


                                        <tr>

                                            <td class="ps-4 fw-semibold">
                                                {{ $unit->nama }}
                                            </td>


                                            <td class="text-center">

                                                {{ $unit->total_pegawai }}

                                            </td>


                                            <td class="text-center">

                                                <span
                                                    class="
                                                        badge
                                                        text-bg-success
                                                    "
                                                >
                                                    {{ $unit->fingerprint_hari_ini }}
                                                </span>

                                            </td>


                                            <td>

                                                <div
                                                    class="
                                                        d-flex
                                                        align-items-center
                                                        gap-2
                                                    "
                                                >

                                                    <div
                                                        class="
                                                            progress
                                                            unit-progress
                                                            flex-grow-1
                                                        "
                                                    >

                                                        <div
                                                            class="
                                                                progress-bar
                                                                bg-primary
                                                            "
                                                            style="
                                                                width:
                                                                {{ $percentage }}%;
                                                            "
                                                        ></div>

                                                    </div>


                                                    <small
                                                        class="
                                                            fw-semibold
                                                            text-muted
                                                        "
                                                    >
                                                        {{ $percentage }}%
                                                    </small>

                                                </div>

                                            </td>

                                        </tr>

                                    @empty

                                        <tr>

                                            <td
                                                colspan="4"
                                                class="
                                                    text-center
                                                    text-muted
                                                    py-4
                                                "
                                            >
                                                Tidak ada data unit.
                                            </td>

                                        </tr>

                                    @endforelse

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>



                {{-- =================================================
                     RIGHT SIDE
                ================================================= --}}
                <div class="col-lg-5">


                    {{-- KALDIK --}}
                    <div class="card dashboard-card mb-4">

                        <div class="card-header">

                            <h3 class="card-title">

                                <i
                                    class="
                                        bi
                                        bi-calendar-event-fill
                                        text-info
                                        me-2
                                    "
                                ></i>

                                Kalender Kerja Hari Ini

                            </h3>

                        </div>


                        <div class="card-body">

                            @forelse($calendarToday as $calendar)

                                <div class="calendar-item">

                                    <div
                                        class="
                                            d-flex
                                            justify-content-between
                                            align-items-start
                                            gap-2
                                        "
                                    >

                                        <div>

                                            <div class="fw-bold">

                                                {{ $calendar->name }}

                                            </div>


                                            <div
                                                class="
                                                    small
                                                    text-muted
                                                    mt-1
                                                "
                                            >

                                                @if($calendar->unit_id)

                                                    {{
                                                        $unitNames[
                                                            $calendar->unit_id
                                                        ] ?? 'Unit'
                                                    }}

                                                @else

                                                    Semua Unit

                                                @endif

                                            </div>


                                            @if($calendar->description)

                                                <div
                                                    class="
                                                        small
                                                        mt-2
                                                        text-secondary
                                                    "
                                                >
                                                    {{ $calendar->description }}
                                                </div>

                                            @endif

                                        </div>


                                        @if($calendar->is_workday)

                                            <span
                                                class="
                                                    badge
                                                    text-bg-success
                                                "
                                            >
                                                Hari Kerja
                                            </span>

                                        @else

                                            <span
                                                class="
                                                    badge
                                                    text-bg-danger
                                                "
                                            >
                                                Libur
                                            </span>

                                        @endif

                                    </div>

                                </div>

                            @empty

                                <div
                                    class="
                                        text-center
                                        py-4
                                        text-muted
                                    "
                                >

                                    <i
                                        class="
                                            bi
                                            bi-calendar-check
                                            fs-2
                                            d-block
                                            mb-2
                                        "
                                    ></i>

                                    Tidak ada agenda khusus hari ini.

                                    <div class="small mt-1">
                                        Mengikuti jadwal kerja normal.
                                    </div>

                                </div>

                            @endforelse

                        </div>

                    </div>



                    {{-- BELUM PULANG --}}
                    <div class="card dashboard-card">

                        <div class="card-body">

                            <div
                                class="
                                    d-flex
                                    align-items-center
                                    justify-content-between
                                "
                            >

                                <div>

                                    <div
                                        class="
                                            text-muted
                                            small
                                            mb-1
                                        "
                                    >
                                        Scan Masuk Tanpa Scan Pulang
                                    </div>

                                    <div
                                        class="
                                            fs-3
                                            fw-bold
                                        "
                                    >
                                        {{ $belumPulang }}
                                    </div>

                                    <small class="text-muted">
                                        Data fingerprint hari ini
                                    </small>

                                </div>


                                <div
                                    class="
                                        stat-icon
                                        bg-danger-subtle
                                        text-danger
                                    "
                                >

                                    <i class="bi bi-box-arrow-right"></i>

                                </div>

                            </div>

                        </div>

                    </div>


                </div>

            </div>



            {{-- =================================================
                 PENGAJUAN IZIN TERBARU
            ================================================= --}}
            <div class="card dashboard-card mb-4">


                <div
                    class="
                        card-header
                        d-flex
                        justify-content-between
                        align-items-center
                    "
                >

                    <div>

                        <h3 class="card-title">

                            <i
                                class="
                                    bi
                                    bi-file-earmark-text-fill
                                    text-warning
                                    me-2
                                "
                            ></i>

                            Pengajuan Izin Menunggu Approval

                        </h3>

                        <small class="text-muted">
                            Pengajuan terbaru yang perlu diproses
                        </small>

                    </div>


                    @if(
                        auth()->user()->isAdmin()
                        ||
                        auth()->user()->isPimpinan()
                    )

                        <a
                            href="{{
                                route(
                                    'attendancePermission.index'
                                )
                            }}"
                            class="
                                btn
                                btn-sm
                                btn-outline-primary
                            "
                        >

                            Lihat Semua

                        </a>

                    @endif

                </div>


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table
                            class="
                                table
                                table-hover
                                align-middle
                                mb-0
                            "
                        >

                            <thead class="table-light">

                                <tr>

                                    <th class="ps-4">
                                        Pegawai
                                    </th>

                                    <th>
                                        Unit
                                    </th>

                                    <th>
                                        Jenis Izin
                                    </th>

                                    <th>
                                        Periode
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                            @forelse(
                                $latestPermissions
                                as $permission
                            )

                                <tr class="permission-row">

                                    <td class="ps-4">

                                        <div class="fw-semibold">

                                            {{
                                                $permission
                                                    ->employee
                                                    ?->nama
                                                ?? '-'
                                            }}

                                        </div>

                                    </td>


                                    <td>

                                        {{
                                            $permission
                                                ->employee
                                                ?->unit
                                                ?->nama
                                            ?? '-'
                                        }}

                                    </td>


                                    <td>

                                        <span
                                            class="
                                                badge
                                                bg-light
                                                text-dark
                                                border
                                            "
                                        >

                                            {{ $permission->type }}

                                        </span>

                                    </td>


                                    <td>

                                        <div class="small">

                                            {{
                                                \Carbon\Carbon::parse(
                                                    $permission->date_start
                                                )->format('d/m/Y')
                                            }}

                                            @if(
                                                $permission->date_end
                                                !=
                                                $permission->date_start
                                            )

                                                -

                                                {{
                                                    \Carbon\Carbon::parse(
                                                        $permission->date_end
                                                    )->format('d/m/Y')
                                                }}

                                            @endif

                                        </div>

                                    </td>


                                    <td>

                                        <span
                                            class="
                                                badge
                                                text-bg-warning
                                            "
                                        >
                                            Pending
                                        </span>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="5"
                                        class="
                                            text-center
                                            py-5
                                            text-muted
                                        "
                                    >

                                        <i
                                            class="
                                                bi
                                                bi-check-circle-fill
                                                text-success
                                                fs-3
                                                d-block
                                                mb-2
                                            "
                                        ></i>

                                        Tidak ada pengajuan izin
                                        yang menunggu approval.

                                    </td>

                                </tr>

                            @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


        </div>

    </div>

</main>

@endsection