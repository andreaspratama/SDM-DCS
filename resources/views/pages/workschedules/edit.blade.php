@extends('layouts.admin')

@section('title', 'Atur Template Jadwal')

@section('content')

<style>
    .schedule-edit-page {
        padding: 24px;
    }

    .schedule-edit-header {
        margin-bottom: 24px;
    }

    .schedule-edit-header h2 {
        margin-bottom: 5px;
        font-weight: 700;
        color: #1f2937;
    }

    .schedule-edit-header p {
        margin: 0;
        color: #6b7280;
    }

    .schedule-edit-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,0,0,.04);
        overflow: hidden;
    }

    .schedule-edit-card-header {
        padding: 18px 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .schedule-edit-card-header h5 {
        margin: 0;
        font-weight: 700;
        color: #1f2937;
    }

    .schedule-edit-card-body {
        padding: 22px;
    }

    .day-config {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        background: #fff;
    }

    .day-config:hover {
        background: #fafafa;
    }

    .day-title {
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 12px;
    }

    .holiday-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .schedule-actions {
        border-top: 1px solid #e5e7eb;
        margin-top: 24px;
        padding-top: 20px;
        display: flex;
        gap: 10px;
    }

    .schedule-actions .btn {
        border-radius: 9px;
        font-weight: 600;
        padding: 9px 18px;
    }

    @media(max-width:768px) {
        .schedule-edit-page {
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


<div class="schedule-edit-page">

    <div class="schedule-edit-header">

        <h2>
            Atur Template Jadwal
        </h2>

        <p>
            Atur hari kerja dan jam masuk/pulang untuk template ini.
        </p>

    </div>


    @if($errors->any())

        <div class="alert alert-danger">

            <div class="fw-bold mb-2">
                Data belum dapat disimpan:
            </div>

            <ul class="mb-0">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    <div class="row">

        <div class="col-xl-9">

            <div class="schedule-edit-card">

                <div class="schedule-edit-card-header">

                    <h5>
                        {{ $workSchedule->nama }}
                    </h5>

                </div>


                <div class="schedule-edit-card-body">

                    <form
                        action="{{
                            route(
                                'workSchedule.update',
                                $workSchedule->id
                            )
                        }}"
                        method="POST"
                    >

                        @csrf
                        @method('PUT')


                        {{-- NAMA TEMPLATE --}}
                        <div class="mb-4">

                            <label class="form-label fw-semibold">
                                Nama Template
                            </label>

                            <input
                                type="text"
                                name="nama"
                                value="{{
                                    old(
                                        'nama',
                                        $workSchedule->nama
                                    )
                                }}"
                                class="form-control"
                                required
                            >

                        </div>


                        {{-- DAYS --}}
                        @foreach(range(1, 7) as $hari)

                            @php

                                $day =
                                    $workSchedule
                                        ->days
                                        ->firstWhere(
                                            'hari',
                                            $hari
                                        );

                                $isLibur =
                                    old(
                                        "days.$hari.is_libur",
                                        $day
                                            ? (
                                                $day->is_libur
                                                    ? '1'
                                                    : '0'
                                            )
                                            : '1'
                                    );

                                $jamMasuk =
                                    old(
                                        "days.$hari.jam_masuk",
                                        $day?->jam_masuk
                                            ? substr(
                                                $day->jam_masuk,
                                                0,
                                                5
                                            )
                                            : ''
                                    );

                                $jamPulang =
                                    old(
                                        "days.$hari.jam_pulang",
                                        $day?->jam_pulang
                                            ? substr(
                                                $day->jam_pulang,
                                                0,
                                                5
                                            )
                                            : ''
                                    );

                            @endphp


                            <div
                                class="day-config"
                                data-day="{{ $hari }}"
                            >

                                <div class="day-title">

                                    {{ $dayNames[$hari] }}

                                </div>


                                <input
                                    type="hidden"
                                    name="days[{{ $hari }}][hari]"
                                    value="{{ $hari }}"
                                >


                                <div class="row g-3 align-items-end">

                                    {{-- STATUS --}}
                                    <div class="col-md-3">

                                        <label class="form-label fw-semibold">

                                            Status

                                        </label>

                                        <select
                                            name="days[{{ $hari }}][is_libur]"
                                            class="form-select day-status"
                                            data-day="{{ $hari }}"
                                        >

                                            <option
                                                value="0"
                                                @selected(
                                                    (string) $isLibur
                                                    === '0'
                                                )
                                            >
                                                Hari Kerja
                                            </option>

                                            <option
                                                value="1"
                                                @selected(
                                                    (string) $isLibur
                                                    === '1'
                                                )
                                            >
                                                Libur
                                            </option>

                                        </select>

                                    </div>


                                    {{-- JAM MASUK --}}
                                    <div class="col-md-3">

                                        <label class="form-label fw-semibold">

                                            Jam Masuk

                                        </label>

                                        <input
                                            type="time"
                                            name="days[{{ $hari }}][jam_masuk]"
                                            value="{{ $jamMasuk }}"
                                            class="form-control day-time-input"
                                            data-day="{{ $hari }}"
                                        >

                                    </div>


                                    {{-- JAM PULANG --}}
                                    <div class="col-md-3">

                                        <label class="form-label fw-semibold">

                                            Jam Pulang

                                        </label>

                                        <input
                                            type="time"
                                            name="days[{{ $hari }}][jam_pulang]"
                                            value="{{ $jamPulang }}"
                                            class="form-control day-time-input"
                                            data-day="{{ $hari }}"
                                        >

                                    </div>


                                    {{-- INFO --}}
                                    <div class="col-md-3">

                                        <div
                                            class="small text-muted day-info"
                                            id="dayInfo{{ $hari }}"
                                        >
                                        </div>

                                    </div>

                                </div>

                            </div>

                        @endforeach


                        <div class="schedule-actions">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Simpan Jadwal
                            </button>


                            <a
                                href="{{ route(
                                    'workSchedule.index'
                                ) }}"
                                class="btn btn-light border"
                            >

                                Kembali

                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const statuses =
            document.querySelectorAll(
                '.day-status'
            );


        function updateDay(day)
        {
            const status =
                document.querySelector(
                    '.day-status[data-day="'
                    + day
                    + '"]'
                );

            const inputs =
                document.querySelectorAll(
                    '.day-time-input[data-day="'
                    + day
                    + '"]'
                );

            const info =
                document.getElementById(
                    'dayInfo' + day
                );


            if (!status) {
                return;
            }


            const isLibur =
                status.value === '1';


            inputs.forEach(
                function (input) {

                    input.disabled =
                        isLibur;

                    if (isLibur) {

                        input.classList.add(
                            'bg-light'
                        );

                    } else {

                        input.classList.remove(
                            'bg-light'
                        );
                    }
                }
            );


            if (info) {

                if (isLibur) {

                    info.innerHTML =
                        '<span class="text-danger fw-semibold">Libur</span>';

                } else {

                    info.innerHTML =
                        '<span class="text-success fw-semibold">Hari Kerja</span>';
                }
            }
        }


        statuses.forEach(
            function (status) {

                const day =
                    status.dataset.day;

                status.addEventListener(
                    'change',
                    function () {

                        updateDay(day);
                    }
                );

                updateDay(day);
            }
        );

    }
);

</script>

@endsection