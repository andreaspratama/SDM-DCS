@extends('layouts.admin')

@section('title', 'Tambah Jadwal Khusus')

@section('content')

<style>
    .special-form-page {
        padding: 24px;
    }

    .special-header {
        margin-bottom: 22px;
    }

    .special-header h2 {
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 5px;
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
        overflow: hidden;
    }

    .special-card-header {
        padding: 18px 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .special-card-header h5 {
        margin: 0;
        font-weight: 700;
        color: #1f2937;
    }

    .special-card-body {
        padding: 22px;
    }

    .employee-info {
        display: none;
        margin-top: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
    }

    .employee-info.active {
        display: block;
    }

    .preset-box {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 20px;
    }

    .preset-title {
        font-weight: 700;
        color: #1e40af;
        margin-bottom: 4px;
    }

    .preset-description {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 12px;
    }

    .preset-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .day-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 10px;
        transition: .15s ease;
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

    .form-actions {
        margin-top: 22px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    @media(max-width:768px) {
        .special-form-page {
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


    /*
    |--------------------------------------------------------------------------
    | DATA JADWAL DASAR UNTUK JAVASCRIPT
    |--------------------------------------------------------------------------
    */

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
                                ? substr(
                                    $day->jam_masuk,
                                    0,
                                    5
                                )
                                : null,

                        'jam_pulang' =>
                            $day->jam_pulang
                                ? substr(
                                    $day->jam_pulang,
                                    0,
                                    5
                                )
                                : null,
                    ];
                }
            }


            return [
                (string) $employee->id => [
                    'nama' =>
                        $employee->nama,

                    'unit' =>
                        $employee->unit?->nama
                        ?? '-',

                    'template' =>
                        $employee->workSchedule?->nama
                        ?? null,

                    'days' =>
                        $days,
                ],
            ];
        }
    );

@endphp


<div class="special-form-page">

    {{-- HEADER --}}
    <div class="special-header">

        <h2>
            Tambah Jadwal Khusus Pegawai
        </h2>

        <p>
            Buat pola kerja sementara untuk pegawai pada periode tertentu.
        </p>

    </div>


    {{-- ERRORS --}}
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

        <div class="col-xl-10">

            <div class="special-card">

                <div class="special-card-header">

                    <h5>
                        Informasi Jadwal
                    </h5>

                </div>


                <div class="special-card-body">

                    <form
                        action="{{ route('employeeWorkSchedule.store') }}"
                        method="POST"
                        id="specialScheduleForm"
                    >

                        @csrf


                        {{-- ==========================================
                            PEGAWAI
                        =========================================== --}}
                        <div class="row g-3 mb-4">

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Pegawai

                                    <span class="text-danger">*</span>

                                </label>

                                <select
                                    name="employee_id"
                                    id="employeeSelect"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        -- Pilih Pegawai --
                                    </option>

                                    @foreach($employees as $employee)

                                        <option
                                            value="{{ $employee->id }}"
                                            @selected(
                                                old('employee_id')
                                                == $employee->id
                                            )
                                        >
                                            {{ $employee->nama }}

                                            —

                                            {{ $employee->unit?->nama ?? '-' }}
                                        </option>

                                    @endforeach

                                </select>


                                <div
                                    class="employee-info"
                                    id="employeeInfo"
                                >

                                    <div>
                                        <strong id="infoEmployeeName"></strong>
                                    </div>

                                    <div class="small text-muted mt-1">

                                        Unit:
                                        <span id="infoEmployeeUnit"></span>

                                        &nbsp;•&nbsp;

                                        Jadwal dasar:
                                        <span
                                            id="infoEmployeeTemplate"
                                            class="fw-semibold"
                                        ></span>

                                    </div>

                                </div>

                            </div>


                            {{-- NAMA --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Nama Jadwal Khusus

                                    <span class="text-danger">*</span>

                                </label>

                                <input
                                    type="text"
                                    name="nama"
                                    value="{{ old('nama') }}"
                                    class="form-control"
                                    placeholder="Contoh: Sabtu Masuk Semester 1"
                                    required
                                >

                            </div>


                            {{-- START --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Tanggal Mulai

                                    <span class="text-danger">*</span>

                                </label>

                                <input
                                    type="date"
                                    name="tanggal_mulai"
                                    value="{{ old('tanggal_mulai') }}"
                                    class="form-control"
                                    required
                                >

                            </div>


                            {{-- END --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Tanggal Selesai

                                    <span class="text-danger">*</span>

                                </label>

                                <input
                                    type="date"
                                    name="tanggal_selesai"
                                    value="{{ old('tanggal_selesai') }}"
                                    class="form-control"
                                    required
                                >

                            </div>


                            {{-- KETERANGAN --}}
                            <div class="col-12">

                                <label class="form-label fw-semibold">
                                    Keterangan
                                </label>

                                <textarea
                                    name="keterangan"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Opsional..."
                                >{{ old('keterangan') }}</textarea>

                            </div>

                        </div>


                        {{-- ==========================================
                            PRESET
                        =========================================== --}}
                        <div class="preset-box">

                            <div class="preset-title">
                                Pengaturan Cepat
                            </div>

                            <div class="preset-description">

                                Pilih pegawai terlebih dahulu,
                                lalu gunakan preset jika sesuai.
                                Setelah preset diterapkan,
                                setiap hari tetap bisa diedit manual.

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


                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    id="presetHoliday"
                                >
                                    Semua Hari Libur
                                </button>

                            </div>

                        </div>


                        {{-- ==========================================
                            DAYS
                        =========================================== --}}
                        <h6 class="fw-bold mb-3">
                            Pola Kerja Senin – Minggu
                        </h6>


                        @foreach(range(1, 7) as $hari)

                            @php

                                $oldStatus =
                                    old(
                                        "days.$hari.is_libur",
                                        '1'
                                    );

                                $oldMasuk =
                                    old(
                                        "days.$hari.jam_masuk",
                                        ''
                                    );

                                $oldPulang =
                                    old(
                                        "days.$hari.jam_pulang",
                                        ''
                                    );

                            @endphp


                            <div
                                class="day-card"
                                id="dayCard{{ $hari }}"
                            >

                                <div class="day-name">

                                    {{ $dayNames[$hari] }}

                                    <span
                                        class="day-state ms-2"
                                        id="dayState{{ $hari }}"
                                    ></span>

                                </div>


                                <input
                                    type="hidden"
                                    name="days[{{ $hari }}][hari]"
                                    value="{{ $hari }}"
                                >


                                <div class="row g-3">


                                    {{-- STATUS --}}
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
                                                    (string) $oldStatus
                                                    === '0'
                                                )
                                            >
                                                Hari Kerja
                                            </option>

                                            <option
                                                value="1"
                                                @selected(
                                                    (string) $oldStatus
                                                    === '1'
                                                )
                                            >
                                                Libur
                                            </option>

                                        </select>

                                    </div>


                                    {{-- MASUK --}}
                                    <div class="col-md-4">

                                        <label class="form-label">
                                            Jam Masuk
                                        </label>

                                        <input
                                            type="time"
                                            name="days[{{ $hari }}][jam_masuk]"
                                            id="dayStart{{ $hari }}"
                                            value="{{ $oldMasuk }}"
                                            class="form-control day-time"
                                            data-day="{{ $hari }}"
                                        >

                                    </div>


                                    {{-- PULANG --}}
                                    <div class="col-md-4">

                                        <label class="form-label">
                                            Jam Pulang
                                        </label>

                                        <input
                                            type="time"
                                            name="days[{{ $hari }}][jam_pulang]"
                                            id="dayEnd{{ $hari }}"
                                            value="{{ $oldPulang }}"
                                            class="form-control day-time"
                                            data-day="{{ $hari }}"
                                        >

                                    </div>

                                </div>

                            </div>

                        @endforeach


                        {{-- ACTION --}}
                        <div class="form-actions">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Simpan Jadwal Khusus
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

        </div>

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


        const employeeInfo =
            document.getElementById(
                'employeeInfo'
            );


        // =================================================
        // HELPERS
        // =================================================
        function getEmployee()
        {
            const id =
                employeeSelect.value;

            return employeeData[id]
                ?? null;
        }


        function setDay(
            day,
            isHoliday,
            start = '',
            end = ''
        ) {

            const status =
                document.getElementById(
                    'dayStatus' + day
                );

            const startInput =
                document.getElementById(
                    'dayStart' + day
                );

            const endInput =
                document.getElementById(
                    'dayEnd' + day
                );


            status.value =
                isHoliday
                    ? '1'
                    : '0';


            startInput.value =
                isHoliday
                    ? ''
                    : (start ?? '');


            endInput.value =
                isHoliday
                    ? ''
                    : (end ?? '');


            updateDayState(day);
        }


        function updateDayState(day)
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


            start.disabled = holiday;
            end.disabled = holiday;


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


        function copyBaseSchedule()
        {
            const employee =
                getEmployee();


            if (!employee) {

                alert(
                    'Pilih pegawai terlebih dahulu.'
                );

                return false;
            }


            if (
                !employee.template
                ||
                Object.keys(
                    employee.days
                ).length === 0
            ) {

                alert(
                    'Pegawai belum mempunyai template jadwal dasar.'
                );

                return false;
            }


            for (
                let day = 1;
                day <= 7;
                day++
            ) {

                const base =
                    employee.days[day]
                    ?? employee.days[
                        String(day)
                    ];


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


        function getBaseStart()
        {
            const employee =
                getEmployee();


            if (
                employee
                &&
                employee.days
            ) {

                for (
                    let day = 1;
                    day <= 7;
                    day++
                ) {

                    const base =
                        employee.days[day]
                        ?? employee.days[
                            String(day)
                        ];


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


        // =================================================
        // EMPLOYEE INFO
        // =================================================
        function updateEmployeeInfo()
        {
            const employee =
                getEmployee();


            if (!employee) {

                employeeInfo.classList.remove(
                    'active'
                );

                return;
            }


            document.getElementById(
                'infoEmployeeName'
            ).textContent =
                employee.nama;


            document.getElementById(
                'infoEmployeeUnit'
            ).textContent =
                employee.unit;


            document.getElementById(
                'infoEmployeeTemplate'
            ).textContent =
                employee.template
                ?? 'Belum ada';


            employeeInfo.classList.add(
                'active'
            );
        }


        employeeSelect.addEventListener(
            'change',
            updateEmployeeInfo
        );


        // =================================================
        // MANUAL STATUS CHANGE
        // =================================================
        document.querySelectorAll(
            '.day-status'
        ).forEach(
            function (select) {

                select.addEventListener(
                    'change',
                    function () {

                        updateDayState(
                            select.dataset.day
                        );
                    }
                );
            }
        );


        // =================================================
        // PRESET: COPY BASE
        // =================================================
        document.getElementById(
            'presetBase'
        ).addEventListener(
            'click',
            function () {

                if (
                    copyBaseSchedule()
                ) {

                    alert(
                        'Jadwal dasar berhasil disalin. Silakan sesuaikan jika diperlukan.'
                    );
                }
            }
        );


        // =================================================
        // PRESET: SATURDAY
        // =================================================
        document.getElementById(
            'presetSaturday'
        ).addEventListener(
            'click',
            function () {

                const employee =
                    getEmployee();


                if (!employee) {

                    alert(
                        'Pilih pegawai terlebih dahulu.'
                    );

                    return;
                }


                /*
                 * Mulai dari jadwal dasar.
                 */
                if (!copyBaseSchedule()) {
                    return;
                }


                setDay(
                    6,
                    false,
                    getBaseStart(),
                    '12:00'
                );
            }
        );


        // =================================================
        // PRESET: PART TIME SELASA KAMIS
        // =================================================
        document.getElementById(
            'presetPartTime'
        ).addEventListener(
            'click',
            function () {

                const employee =
                    getEmployee();


                if (!employee) {

                    alert(
                        'Pilih pegawai terlebih dahulu.'
                    );

                    return;
                }


                const start =
                    getBaseStart();


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


                // Selasa
                setDay(
                    2,
                    false,
                    start,
                    '12:00'
                );


                // Kamis
                setDay(
                    4,
                    false,
                    start,
                    '12:00'
                );
            }
        );


        // =================================================
        // PRESET: ALL HOLIDAY
        // =================================================
        document.getElementById(
            'presetHoliday'
        ).addEventListener(
            'click',
            function () {

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
            }
        );


        // =================================================
        // INITIAL
        // =================================================
        updateEmployeeInfo();


        for (
            let day = 1;
            day <= 7;
            day++
        ) {

            updateDayState(day);
        }

    }
);

</script>

@endsection