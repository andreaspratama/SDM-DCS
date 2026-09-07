@extends('layouts.admin')

@section('title', 'Template Jadwal Kerja')

@section('content')

<style>
    .schedule-page {
        padding: 24px;
    }

    .schedule-header {
        margin-bottom: 24px;
    }

    .schedule-header h2 {
        margin-bottom: 5px;
        font-weight: 700;
        color: #1f2937;
    }

    .schedule-header p {
        margin: 0;
        color: #6b7280;
    }

    .schedule-grid {
        display: grid;
        grid-template-columns: repeat(
            auto-fit,
            minmax(340px, 1fr)
        );
        gap: 20px;
    }

    .schedule-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,0,0,.04);
        overflow: hidden;
    }

    .schedule-card-header {
        padding: 18px 20px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .schedule-name {
        font-size: 17px;
        font-weight: 700;
        color: #1f2937;
    }

    .schedule-count {
        display: inline-flex;
        padding: 5px 10px;
        border-radius: 20px;
        background: #eef2ff;
        color: #4338ca;
        font-size: 12px;
        font-weight: 700;
    }

    .schedule-card-body {
        padding: 16px 20px;
    }

    .day-row {
        display: grid;
        grid-template-columns: 95px 1fr;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .day-row:last-child {
        border-bottom: 0;
    }

    .day-name {
        font-weight: 600;
        color: #475569;
    }

    .day-time {
        color: #1f2937;
        font-weight: 600;
    }

    .day-holiday {
        color: #b91c1c;
        font-weight: 700;
    }

    .schedule-card-footer {
        padding: 16px 20px;
        border-top: 1px solid #e5e7eb;
        background: #fafafa;
    }

    .schedule-card-footer .btn {
        border-radius: 9px;
        font-weight: 600;
    }

    .schedule-info {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
        border-radius: 12px;
        padding: 15px 16px;
        margin-bottom: 20px;
        font-size: 13px;
    }

    @media(max-width:768px) {
        .schedule-page {
            padding: 15px;
        }
    }
</style>


@php
    $dayNames = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];
@endphp


<div class="schedule-page">

    <div class="schedule-header">

        <h2>
            Template Jadwal Kerja
        </h2>

        <p>
            Kelola jam kerja dasar yang digunakan sebagai default pegawai.
        </p>

    </div>


    @if(session('success'))

        <div class="alert alert-success alert-dismissible fade show">

            {{ session('success') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    @endif


    <div class="schedule-info">

        <strong>Catatan:</strong>

        Jadwal ini adalah jadwal dasar. Jika pegawai mempunyai
        jadwal khusus pada periode tertentu, maka jadwal khusus
        akan digunakan lebih dahulu.

    </div>


    <div class="schedule-grid">

        @foreach($schedules as $schedule)

            <div class="schedule-card">

                <div class="schedule-card-header">

                    <div>

                        <div class="schedule-name">
                            {{ $schedule->nama }}
                        </div>

                        <div class="small text-muted mt-1">
                            Template jadwal dasar
                        </div>

                    </div>


                    <span class="schedule-count">

                        {{ $schedule->employees_count }}
                        pegawai

                    </span>

                </div>


                <div class="schedule-card-body">

                    @foreach(range(1, 7) as $hari)

                        @php

                            $day =
                                $schedule
                                    ->days
                                    ->firstWhere(
                                        'hari',
                                        $hari
                                    );

                        @endphp


                        <div class="day-row">

                            <div class="day-name">

                                {{ $dayNames[$hari] }}

                            </div>


                            <div>

                                @if(!$day || $day->is_libur)

                                    <span class="day-holiday">

                                        Libur

                                    </span>

                                @else

                                    <span class="day-time">

                                        {{
                                            \Carbon\Carbon::parse(
                                                $day->jam_masuk
                                            )->format('H:i')
                                        }}

                                        -

                                        {{
                                            \Carbon\Carbon::parse(
                                                $day->jam_pulang
                                            )->format('H:i')
                                        }}

                                    </span>

                                @endif

                            </div>

                        </div>

                    @endforeach

                </div>


                <div class="schedule-card-footer">

                    <a
                        href="{{
                            route(
                                'workSchedule.edit',
                                $schedule->id
                            )
                        }}"
                        class="btn btn-primary w-100"
                    >

                        Atur Jadwal

                    </a>

                </div>

            </div>

        @endforeach

    </div>

</div>

@endsection