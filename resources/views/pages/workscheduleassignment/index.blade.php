@extends('layouts.admin')

@section('title', 'Plotting Jadwal Pegawai')

@section('content')

<style>
    .assignment-page {
        padding: 24px;
    }

    .assignment-header {
        margin-bottom: 24px;
    }

    .assignment-header h2 {
        font-weight: 700;
        margin-bottom: 5px;
        color: #1f2937;
    }

    .assignment-header p {
        margin: 0;
        color: #6b7280;
    }

    .assignment-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,0,0,.04);
        overflow: hidden;
    }

    .assignment-filter {
        padding: 20px;
        margin-bottom: 20px;
    }

    .assignment-info {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
        border-radius: 12px;
        padding: 15px 16px;
        margin-bottom: 20px;
        font-size: 13px;
    }

    .bulk-panel {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 14px;
        padding: 18px 20px;
        margin-bottom: 20px;
        display: none;
    }

    .bulk-panel.active {
        display: block;
    }

    .bulk-title {
        font-weight: 700;
        color: #166534;
    }

    .bulk-subtitle {
        font-size: 13px;
        color: #64748b;
        margin-top: 3px;
    }

    .selected-badge {
        display: inline-flex;
        padding: 5px 10px;
        border-radius: 20px;
        background: #dcfce7;
        color: #15803d;
        font-size: 12px;
        font-weight: 700;
        margin-top: 8px;
    }

    .assignment-card-header {
        padding: 18px 20px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .assignment-card-header h5 {
        margin: 0;
        font-weight: 700;
        color: #1f2937;
    }

    .total-badge {
        display: inline-flex;
        padding: 6px 12px;
        border-radius: 20px;
        background: #f3f4f6;
        color: #4b5563;
        font-size: 13px;
    }

    .assignment-table {
        margin-bottom: 0;
    }

    .assignment-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .assignment-table tbody td {
        padding: 15px 16px;
        vertical-align: middle;
        border-color: #f1f5f9;
    }

    .assignment-table tbody tr:hover {
        background: #fafafa;
    }

    .assignment-table tbody tr.selected-row {
        background: #f0fdf4;
    }

    .employee-checkbox,
    #selectAll {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .employee-box {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 230px;
    }

    .employee-avatar {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 12px;
        background: #eef2ff;
        color: #4338ca;
        font-weight: 800;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .employee-name {
        font-weight: 700;
        color: #1f2937;
    }

    .employee-meta {
        color: #94a3b8;
        font-size: 12px;
        margin-top: 2px;
    }

    .unit-badge {
        display: inline-flex;
        padding: 5px 9px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 12px;
        font-weight: 600;
    }

    .schedule-badge {
        display: inline-flex;
        padding: 6px 10px;
        border-radius: 20px;
        background: #dcfce7;
        color: #15803d;
        font-size: 12px;
        font-weight: 700;
    }

    .schedule-empty {
        display: inline-flex;
        padding: 6px 10px;
        border-radius: 20px;
        background: #fef3c7;
        color: #92400e;
        font-size: 12px;
        font-weight: 700;
    }

    .role-text {
        color: #475569;
        font-size: 13px;
    }

    .assignment-footer {
        border-top: 1px solid #e5e7eb;
        padding: 14px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .page-info {
        color: #6b7280;
        font-size: 13px;
    }

    .page-button {
        display: inline-block;
        padding: 7px 12px;
        border: 1px solid #d1d5db;
        background: #fff;
        color: #374151;
        border-radius: 8px;
        text-decoration: none;
        font-size: 13px;
    }

    .page-button.disabled {
        background: #f8fafc;
        color: #cbd5e1;
        pointer-events: none;
    }

    @media(max-width:768px) {
        .assignment-page {
            padding: 15px;
        }
    }
</style>


<div class="assignment-page">

    {{-- HEADER --}}
    <div class="assignment-header">

        <h2>
            Plotting Jadwal Pegawai
        </h2>

        <p>
            Tentukan template jadwal dasar yang digunakan masing-masing pegawai.
        </p>

    </div>


    {{-- INFO --}}
    <div class="assignment-info">

        <strong>Catatan:</strong>

        Template ini merupakan jadwal dasar pegawai.
        Jika pegawai mempunyai jadwal khusus pada periode tertentu,
        jadwal khusus tetap mempunyai prioritas lebih tinggi.

    </div>


    {{-- SUCCESS --}}
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


    {{-- ERROR --}}
    @if($errors->any())

        <div class="alert alert-danger">

            <div class="fw-bold mb-2">
                Data belum dapat diproses:
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


    {{-- FILTER --}}
    <div class="assignment-card assignment-filter">

        <form
            method="GET"
            action="{{ route('workScheduleAssignment.index') }}"
        >

            <div class="row g-3">

                {{-- SEARCH --}}
                <div class="col-lg-4 col-md-6">

                    <label class="form-label fw-semibold">
                        Cari Pegawai
                    </label>

                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        class="form-control"
                        placeholder="Nama atau UID..."
                    >

                </div>


                {{-- UNIT --}}
                <div class="col-lg-3 col-md-6">

                    <label class="form-label fw-semibold">
                        Unit
                    </label>

                    <select
                        name="unit_id"
                        class="form-select"
                    >

                        <option value="">
                            Semua Unit
                        </option>

                        @foreach($units as $unit)

                            <option
                                value="{{ $unit->id }}"
                                @selected(
                                    request('unit_id') == $unit->id
                                )
                            >
                                {{ $unit->nama }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- TEMPLATE --}}
                <div class="col-lg-3 col-md-6">

                    <label class="form-label fw-semibold">
                        Template Jadwal
                    </label>

                    <select
                        name="work_schedule_id"
                        class="form-select"
                    >

                        <option value="">
                            Semua Template
                        </option>

                        <option
                            value="none"
                            @selected(
                                request('work_schedule_id') === 'none'
                            )
                        >
                            Belum Ada Jadwal
                        </option>

                        @foreach($schedules as $schedule)

                            <option
                                value="{{ $schedule->id }}"
                                @selected(
                                    request('work_schedule_id') == $schedule->id
                                )
                            >
                                {{ $schedule->nama }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- BUTTON --}}
                <div class="col-lg-2 col-md-6 d-flex align-items-end gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('workScheduleAssignment.index') }}"
                        class="btn btn-light border"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>


    {{-- FORM BULK --}}
    <form
        method="POST"
        action="{{ route('workScheduleAssignment.bulkUpdate') }}"
        id="assignmentForm"
    >

        @csrf


        {{-- BULK PANEL --}}
        <div
            class="bulk-panel"
            id="bulkPanel"
        >

            <div class="row g-3 align-items-end">

                <div class="col-lg-4">

                    <div class="bulk-title">
                        Atur Template Jadwal
                    </div>

                    <div class="bulk-subtitle">
                        Template akan diterapkan ke seluruh pegawai yang dicentang.
                    </div>

                    <span
                        class="selected-badge"
                        id="selectedCount"
                    >
                        0 pegawai dipilih
                    </span>

                    <div
                        class="small text-muted mt-2"
                        id="selectedUnit"
                    >
                    </div>

                </div>


                {{-- TEMPLATE --}}
                <div class="col-lg-4">

                    <label class="form-label fw-semibold">
                        Template Jadwal
                    </label>

                    <select
                        name="work_schedule_id"
                        id="bulkSchedule"
                        class="form-select"
                    >

                        <option value="">
                            -- Pilih Template Jadwal --
                        </option>

                        @foreach($schedules as $schedule)

                            <option
                                value="{{ $schedule->id }}"
                            >
                                {{ $schedule->nama }}

                                @if($schedule->jam_masuk && $schedule->jam_pulang)
                                    —
                                    {{
                                        substr(
                                            $schedule->jam_masuk,
                                            0,
                                            5
                                        )
                                    }}
                                    -
                                    {{
                                        substr(
                                            $schedule->jam_pulang,
                                            0,
                                            5
                                        )
                                    }}
                                @endif
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- APPLY --}}
                <div class="col-lg-2">

                    <button
                        type="submit"
                        class="btn btn-success w-100"
                        formaction="{{ route('workScheduleAssignment.bulkUpdate') }}"
                        id="applyButton"
                    >
                        Terapkan
                    </button>

                </div>


                {{-- CLEAR --}}
                <div class="col-lg-2">

                    <button
                        type="submit"
                        class="btn btn-outline-danger w-100"
                        formaction="{{ route('workScheduleAssignment.clear') }}"
                        id="clearButton"
                    >
                        Lepas Jadwal
                    </button>

                </div>

            </div>

        </div>


        {{-- TABLE --}}
        <div class="assignment-card">

            <div class="assignment-card-header">

                <div>

                    <h5>
                        Daftar Pegawai
                    </h5>

                    <div class="small text-muted mt-1">
                        Centang pegawai untuk melakukan plotting jadwal secara massal.
                    </div>

                </div>


                <div class="total-badge">

                    Total:&nbsp;

                    <strong>
                        {{ $employees->total() }}
                    </strong>

                    &nbsp;pegawai

                </div>

            </div>


            <div class="table-responsive">

                <table class="table assignment-table">

                    <thead>

                        <tr>

                            <th width="50">

                                <input
                                    type="checkbox"
                                    id="selectAll"
                                >

                            </th>

                            <th width="50">
                                No
                            </th>

                            <th>
                                Pegawai
                            </th>

                            <th>
                                Unit
                            </th>

                            <th>
                                Jabatan
                            </th>

                            <th>
                                Template Jadwal
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($employees as $employee)

                            @php

                                $initial =
                                    mb_strtoupper(
                                        mb_substr(
                                            $employee->nama,
                                            0,
                                            1
                                        )
                                    );

                                $oldIds =
                                    old(
                                        'employee_ids',
                                        []
                                    );

                            @endphp


                            <tr class="employee-row">

                                {{-- CHECKBOX --}}
                                <td>

                                    <input
                                        type="checkbox"
                                        name="employee_ids[]"
                                        value="{{ $employee->id }}"
                                        class="employee-checkbox"
                                        data-unit-id="{{ $employee->unit_id }}"
                                        data-unit-name="{{ $employee->unit?->nama ?? '-' }}"
                                        @checked(
                                            in_array(
                                                $employee->id,
                                                $oldIds
                                            )
                                        )
                                    >

                                </td>


                                {{-- NO --}}
                                <td class="text-muted">

                                    {{
                                        $employees->firstItem()
                                        + $loop->index
                                    }}

                                </td>


                                {{-- EMPLOYEE --}}
                                <td>

                                    <div class="employee-box">

                                        <div class="employee-avatar">

                                            {{ $initial }}

                                        </div>

                                        <div>

                                            <div class="employee-name">

                                                {{ $employee->nama }}

                                            </div>

                                            <div class="employee-meta">

                                                UID:
                                                {{ $employee->uid }}

                                            </div>

                                        </div>

                                    </div>

                                </td>


                                {{-- UNIT --}}
                                <td>

                                    <span class="unit-badge">

                                        {{ $employee->unit?->nama ?? '-' }}

                                    </span>

                                </td>


                                {{-- ROLE --}}
                                <td>

                                    <span class="role-text">

                                        {{ $employee->role ?? 'Belum Diatur' }}

                                    </span>

                                </td>


                                {{-- SCHEDULE --}}
                                <td>

                                    @if($employee->workSchedule)

                                        <span class="schedule-badge">

                                            {{ $employee->workSchedule->nama }}

                                        </span>

                                    @else

                                        <span class="schedule-empty">

                                            Belum Ada Jadwal

                                        </span>

                                    @endif

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="6"
                                    class="text-center py-5"
                                >

                                    <div class="fw-semibold text-muted">

                                        Data pegawai tidak ditemukan.

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- PAGINATION --}}
            @if($employees->hasPages())

                <div class="assignment-footer">

                    <div class="page-info">

                        Menampilkan

                        <strong>
                            {{ $employees->firstItem() }}
                        </strong>

                        -

                        <strong>
                            {{ $employees->lastItem() }}
                        </strong>

                        dari

                        <strong>
                            {{ $employees->total() }}
                        </strong>

                        pegawai

                    </div>


                    <div class="d-flex align-items-center gap-2">

                        @if($employees->onFirstPage())

                            <span class="page-button disabled">

                                ← Sebelumnya

                            </span>

                        @else

                            <a
                                href="{{ $employees->previousPageUrl() }}"
                                class="page-button"
                            >

                                ← Sebelumnya

                            </a>

                        @endif


                        <span class="small text-muted px-2">

                            Halaman

                            <strong>
                                {{ $employees->currentPage() }}
                            </strong>

                            dari

                            <strong>
                                {{ $employees->lastPage() }}
                            </strong>

                        </span>


                        @if($employees->hasMorePages())

                            <a
                                href="{{ $employees->nextPageUrl() }}"
                                class="page-button"
                            >

                                Berikutnya →

                            </a>

                        @else

                            <span class="page-button disabled">

                                Berikutnya →

                            </span>

                        @endif

                    </div>

                </div>

            @endif

        </div>

    </form>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const selectAll =
            document.getElementById(
                'selectAll'
            );

        const checkboxes =
            Array.from(
                document.querySelectorAll(
                    '.employee-checkbox'
                )
            );

        const bulkPanel =
            document.getElementById(
                'bulkPanel'
            );

        const selectedCount =
            document.getElementById(
                'selectedCount'
            );

        const selectedUnit =
            document.getElementById(
                'selectedUnit'
            );

        const schedule =
            document.getElementById(
                'bulkSchedule'
            );

        const applyButton =
            document.getElementById(
                'applyButton'
            );

        const clearButton =
            document.getElementById(
                'clearButton'
            );


        function getSelected()
        {
            return checkboxes.filter(
                checkbox =>
                    checkbox.checked
            );
        }


        function updateRows()
        {
            checkboxes.forEach(
                function (checkbox) {

                    const row =
                        checkbox.closest('tr');

                    if (!row) {
                        return;
                    }

                    row.classList.toggle(
                        'selected-row',
                        checkbox.checked
                    );
                }
            );
        }


        function refreshPanel()
        {
            const selected =
                getSelected();


            if (selected.length === 0) {

                bulkPanel.classList.remove(
                    'active'
                );

                selectedCount.textContent =
                    '0 pegawai dipilih';

                selectedUnit.textContent =
                    '';

                selectAll.checked = false;

                selectAll.indeterminate =
                    false;

                updateRows();

                return;
            }


            bulkPanel.classList.add(
                'active'
            );


            selectedCount.textContent =
                selected.length
                +
                ' pegawai dipilih';


            const unitIds =
                [
                    ...new Set(
                        selected.map(
                            checkbox =>
                                checkbox.dataset.unitId
                        )
                    )
                ];


            if (unitIds.length === 1) {

                selectedUnit.textContent =
                    'Unit: '
                    +
                    selected[0]
                        .dataset
                        .unitName;

            } else {

                selectedUnit.innerHTML =
                    '<span class="text-danger">'
                    +
                    'Pegawai berasal dari unit berbeda.'
                    +
                    '</span>';
            }


            if (
                selected.length ===
                checkboxes.length
            ) {

                selectAll.checked =
                    true;

                selectAll.indeterminate =
                    false;

            } else {

                selectAll.checked =
                    false;

                selectAll.indeterminate =
                    true;
            }


            updateRows();
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK SATU-SATU
        |--------------------------------------------------------------------------
        */
        checkboxes.forEach(
            function (checkbox) {

                checkbox.addEventListener(
                    'change',
                    function () {

                        const selected =
                            getSelected();


                        if (selected.length > 1) {

                            const firstUnit =
                                selected[0]
                                    .dataset
                                    .unitId;


                            const bedaUnit =
                                selected.some(
                                    item =>
                                        item.dataset.unitId
                                        !==
                                        firstUnit
                                );


                            if (bedaUnit) {

                                checkbox.checked =
                                    false;

                                alert(
                                    'Plotting jadwal massal hanya dapat dilakukan untuk pegawai dalam unit yang sama.'
                                );
                            }
                        }


                        refreshPanel();
                    }
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | SELECT ALL
        |--------------------------------------------------------------------------
        */
        selectAll.addEventListener(
            'change',
            function () {

                const unitIds =
                    [
                        ...new Set(
                            checkboxes.map(
                                checkbox =>
                                    checkbox.dataset.unitId
                            )
                        )
                    ];


                if (
                    selectAll.checked
                    &&
                    unitIds.length > 1
                ) {

                    alert(
                        'Gunakan Filter Unit terlebih dahulu sebelum memilih semua pegawai.'
                    );

                    selectAll.checked =
                        false;

                    return;
                }


                checkboxes.forEach(
                    function (checkbox) {

                        checkbox.checked =
                            selectAll.checked;
                    }
                );


                refreshPanel();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | APPLY
        |--------------------------------------------------------------------------
        */
        applyButton.addEventListener(
            'click',
            function (event) {

                const selected =
                    getSelected();


                if (
                    selected.length === 0
                ) {

                    event.preventDefault();

                    alert(
                        'Pilih minimal satu pegawai.'
                    );

                    return;
                }


                if (!schedule.value) {

                    event.preventDefault();

                    alert(
                        'Pilih template jadwal kerja.'
                    );

                    return;
                }


                const scheduleName =
                    schedule.options[
                        schedule.selectedIndex
                    ].text.trim();


                const yakin =
                    confirm(
                        'Terapkan template "'
                        +
                        scheduleName
                        +
                        '" ke '
                        +
                        selected.length
                        +
                        ' pegawai terpilih?'
                    );


                if (!yakin) {

                    event.preventDefault();
                }
            }
        );


        /*
        |--------------------------------------------------------------------------
        | CLEAR
        |--------------------------------------------------------------------------
        */
        clearButton.addEventListener(
            'click',
            function (event) {

                const selected =
                    getSelected();


                if (
                    selected.length === 0
                ) {

                    event.preventDefault();

                    alert(
                        'Pilih minimal satu pegawai.'
                    );

                    return;
                }


                const yakin =
                    confirm(
                        'Lepas template jadwal dari '
                        +
                        selected.length
                        +
                        ' pegawai terpilih?'
                    );


                if (!yakin) {

                    event.preventDefault();
                }
            }
        );


        refreshPanel();

    }
);

</script>

@endsection