@extends('layouts.admin')

@section('title', 'Edit Jadwal Khusus')

@section('content')

<style>
    .special-page {
        padding: 24px;
    }

    .special-header {
        margin-bottom: 22px;
    }

    .special-header h2 {
        font-weight: 700;
        margin-bottom: 5px;
        color: #1f2937;
    }

    .special-header p {
        margin: 0;
        color: #6b7280;
    }

    .special-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,0,0,.04);
        padding: 22px;
    }

    .day-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 10px;
    }

    .day-card.is-holiday {
        background: #fafafa;
    }

    .day-name {
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 10px;
    }

    .day-state {
        font-size: 12px;
        font-weight: 700;
    }

    .state-work {
        color: #15803d;
    }

    .state-holiday {
        color: #b91c1c;
    }

    .preset-box {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 16px;
        margin: 22px 0;
    }

    .preset-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .form-actions {
        border-top: 1px solid #e5e7eb;
        padding-top: 20px;
        margin-top: 22px;
        display: flex;
        gap: 10px;
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


    $employeeScheduleMap = $employees->mapWithKeys(
        function ($employee) {

            $days = [];

            if ($employee->workSchedule) {

                foreach ($employee->workSchedule->days as $day) {

                    $days[$day->hari] = [
                        'is_libur' =>
                            (bool) $day->is_libur,

                        'jam_masuk' =>
                            $day->jam_masuk
                                ? substr($day->jam_masuk, 0, 5)
                                : null,

                        'jam_pulang' =>
                            $day->jam_pulang
                                ? substr($day->jam_pulang, 0, 5)
                                : null,
                    ];
                }
            }

            return [
                (string) $employee->id => [
                    'nama' =>
                        $employee->nama,

                    'unit' =>
                        $employee->unit?->nama ?? '-',

                    'template' =>
                        $employee->workSchedule?->nama,

                    'days' =>
                        $days,
                ],
            ];
        }
    );

@endphp


<div class="special-page">

    <div class="special-header">

        <h2>
            Edit Jadwal Khusus
        </h2>

        <p>
            Ubah periode maupun pola kerja khusus pegawai.
        </p>

    </div>


    @if($errors->any())

        <div class="alert alert-danger">

            <strong>Data belum dapat disimpan:</strong>

            <ul class="mb-0 mt-2">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif


    <div class="special-card">

        <form
            action="{{
                route(
                    'employeeWorkSchedule.update',
                    $employeeWorkSchedule->id
                )
            }}"
            method="POST"
        >

            @csrf
            @method('PUT')


            {{-- INFORMASI --}}
            <div class="row g-3">


                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Pegawai
                    </label>

                    <select
                        name="employee_id"
                        id="employeeSelect"
                        class="form-select"
                        required
                    >

                        @foreach($employees as $employee)

                            <option
                                value="{{ $employee->id }}"
                                @selected(
                                    old(
                                        'employee_id',
                                        $employeeWorkSchedule->employee_id
                                    )
                                    == $employee->id
                                )
                            >
                                {{ $employee->nama }}
                                —
                                {{ $employee->unit?->nama ?? '-' }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Nama Jadwal
                    </label>

                    <input
                        type="text"
                        name="nama"
                        value="{{
                            old(
                                'nama',
                                $employeeWorkSchedule->nama
                            )
                        }}"
                        class="form-control"
                        required
                    >

                </div>


                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Tanggal Mulai
                    </label>

                    <input
                        type="date"
                        name="tanggal_mulai"
                        value="{{
                            old(
                                'tanggal_mulai',
                                $employeeWorkSchedule
                                    ->tanggal_mulai
                                    ->format('Y-m-d')
                            )
                        }}"
                        class="form-control"
                        required
                    >

                </div>


                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Tanggal Selesai
                    </label>

                    <input
                        type="date"
                        name="tanggal_selesai"
                        value="{{
                            old(
                                'tanggal_selesai',
                                $employeeWorkSchedule
                                    ->tanggal_selesai
                                    ->format('Y-m-d')
                            )
                        }}"
                        class="form-control"
                        required
                    >

                </div>


                <div class="col-12">

                    <label class="form-label fw-semibold">
                        Keterangan
                    </label>

                    <textarea
                        name="keterangan"
                        class="form-control"
                        rows="2"
                    >{{ old('keterangan', $employeeWorkSchedule->keterangan) }}</textarea>

                </div>

            </div>


            {{-- PRESET --}}
            <div class="preset-box">

                <strong>
                    Pengaturan Cepat
                </strong>

                <div class="small text-muted">
                    Preset akan mengganti pola Senin–Minggu di bawah.
                </div>

                <div class="preset-buttons">

                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        id="presetBase"
                    >
                        Salin Jadwal Dasar
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary"
                        id="presetSaturday"
                    >
                        Sabtu Masuk s.d. 12:00
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary"
                        id="presetPartTime"
                    >
                        Part Time Selasa & Kamis
                    </button>

                </div>

            </div>


            {{-- DAYS --}}
            @foreach(range(1,7) as $hari)

                @php

                    $day =
                        $employeeWorkSchedule
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
                    class="day-card"
                    id="dayCard{{ $hari }}"
                >

                    <div class="day-name">

                        {{ $dayNames[$hari] }}

                        <span
                            id="dayState{{ $hari }}"
                            class="day-state ms-2"
                        ></span>

                    </div>


                    <input
                        type="hidden"
                        name="days[{{ $hari }}][hari]"
                        value="{{ $hari }}"
                    >


                    <div class="row g-3">


                        <div class="col-md-4">

                            <label class="form-label">
                                Status
                            </label>

                            <select
                                name="days[{{ $hari }}][is_libur]"
                                id="dayStatus{{ $hari }}"
                                class="form-select day-status"
                                data-day="{{ $hari }}"
                            >

                                <option
                                    value="0"
                                    @selected(
                                        (string)$isLibur === '0'
                                    )
                                >
                                    Hari Kerja
                                </option>

                                <option
                                    value="1"
                                    @selected(
                                        (string)$isLibur === '1'
                                    )
                                >
                                    Libur
                                </option>

                            </select>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Jam Masuk
                            </label>

                            <input
                                type="time"
                                name="days[{{ $hari }}][jam_masuk]"
                                id="dayStart{{ $hari }}"
                                value="{{ $jamMasuk }}"
                                class="form-control"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Jam Pulang
                            </label>

                            <input
                                type="time"
                                name="days[{{ $hari }}][jam_pulang]"
                                id="dayEnd{{ $hari }}"
                                value="{{ $jamPulang }}"
                                class="form-control"
                            >

                        </div>

                    </div>

                </div>

            @endforeach


            <div class="form-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Simpan Perubahan
                </button>

                <a
                    href="{{ route('employeeWorkSchedule.index') }}"
                    class="btn btn-light border"
                >
                    Kembali
                </a>

            </div>

        </form>

    </div>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const employeeData =
            @json($employeeScheduleMap);

        const employeeSelect =
            document.getElementById(
                'employeeSelect'
            );


        function employee()
        {
            return employeeData[
                employeeSelect.value
            ] ?? null;
        }


        function setDay(
            day,
            holiday,
            start = '',
            end = ''
        ) {

            document.getElementById(
                'dayStatus' + day
            ).value =
                holiday ? '1' : '0';


            document.getElementById(
                'dayStart' + day
            ).value =
                holiday ? '' : start;


            document.getElementById(
                'dayEnd' + day
            ).value =
                holiday ? '' : end;


            refreshDay(day);
        }


        function refreshDay(day)
        {
            const status =
                document.getElementById(
                    'dayStatus' + day
                );

            const start =
                document.getElementById(
                    'dayStart' + day
                );

            const end =
                document.getElementById(
                    'dayEnd' + day
                );

            const card =
                document.getElementById(
                    'dayCard' + day
                );

            const state =
                document.getElementById(
                    'dayState' + day
                );


            const holiday =
                status.value === '1';


            start.disabled =
                holiday;

            end.disabled =
                holiday;


            card.classList.toggle(
                'is-holiday',
                holiday
            );


            if (holiday) {

                state.textContent =
                    'LIBUR';

                state.className =
                    'day-state state-holiday ms-2';

            } else {

                state.textContent =
                    'HARI KERJA';

                state.className =
                    'day-state state-work ms-2';
            }
        }


        function copyBase()
        {
            const emp =
                employee();


            if (
                !emp
                ||
                !emp.template
            ) {

                alert(
                    'Pegawai belum mempunyai jadwal dasar.'
                );

                return false;
            }


            for (
                let day = 1;
                day <= 7;
                day++
            ) {

                const base =
                    emp.days[day]
                    ??
                    emp.days[String(day)];


                if (
                    !base
                    ||
                    base.is_libur
                ) {

                    setDay(
                        day,
                        true
                    );

                } else {

                    setDay(
                        day,
                        false,
                        base.jam_masuk,
                        base.jam_pulang
                    );
                }
            }


            return true;
        }


        function baseStart()
        {
            const emp =
                employee();


            if (emp) {

                for (
                    let day = 1;
                    day <= 7;
                    day++
                ) {

                    const base =
                        emp.days[day]
                        ??
                        emp.days[String(day)];


                    if (
                        base
                        &&
                        !base.is_libur
                        &&
                        base.jam_masuk
                    ) {

                        return base.jam_masuk;
                    }
                }
            }


            return '07:30';
        }


        document
            .querySelectorAll(
                '.day-status'
            )
            .forEach(
                function (status) {

                    status.addEventListener(
                        'change',
                        function () {

                            refreshDay(
                                status.dataset.day
                            );
                        }
                    );
                }
            );


        document
            .getElementById(
                'presetBase'
            )
            .addEventListener(
                'click',
                copyBase
            );


        document
            .getElementById(
                'presetSaturday'
            )
            .addEventListener(
                'click',
                function () {

                    if (!copyBase()) {
                        return;
                    }

                    setDay(
                        6,
                        false,
                        baseStart(),
                        '12:00'
                    );
                }
            );


        document
            .getElementById(
                'presetPartTime'
            )
            .addEventListener(
                'click',
                function () {

                    const start =
                        baseStart();


                    for (
                        let day = 1;
                        day <= 7;
                        day++
                    ) {

                        setDay(
                            day,
                            true
                        );
                    }


                    setDay(
                        2,
                        false,
                        start,
                        '12:00'
                    );


                    setDay(
                        4,
                        false,
                        start,
                        '12:00'
                    );
                }
            );


        for (
            let day = 1;
            day <= 7;
            day++
        ) {

            refreshDay(day);
        }

    }
);

</script>

@endsection