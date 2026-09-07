@extends('layouts.admin')

@section('title', 'Struktur Organisasi Pegawai')

@section('content')

<style>
    .org-page {
        padding: 24px;
    }

    .org-header {
        margin-bottom: 24px;
    }

    .org-header h2 {
        font-weight: 700;
        margin-bottom: 5px;
        color: #1f2937;
    }

    .org-header p {
        margin: 0;
        color: #6b7280;
    }

    .org-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }

    .org-filter {
        padding: 20px;
        margin-bottom: 20px;
    }

    .bulk-panel {
        margin-bottom: 20px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        border-radius: 14px;
        padding: 18px 20px;
        display: none;
    }

    .bulk-panel.active {
        display: block;
    }

    .bulk-title {
        font-weight: 700;
        color: #1e3a8a;
        margin-bottom: 3px;
    }

    .bulk-subtitle {
        color: #64748b;
        font-size: 13px;
    }

    .selected-count {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        background: #dbeafe;
        color: #1d4ed8;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    .bulk-unit {
        font-size: 12px;
        color: #64748b;
        margin-top: 4px;
    }

    .org-table-header {
        padding: 18px 20px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }

    .org-table-header h5 {
        margin: 0;
        font-weight: 700;
        color: #1f2937;
    }

    .org-total {
        background: #f3f4f6;
        border-radius: 20px;
        padding: 6px 13px;
        font-size: 13px;
        color: #4b5563;
    }

    .org-table {
        margin-bottom: 0;
    }

    .org-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        border-bottom: 1px solid #e5e7eb;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .org-table tbody td {
        padding: 15px 16px;
        vertical-align: middle;
        border-color: #f1f5f9;
    }

    .org-table tbody tr:hover {
        background: #fafafa;
    }

    .org-table tbody tr.selected-row {
        background: #eff6ff;
    }

    .employee-box {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 220px;
    }

    .employee-avatar {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 12px;
        background: #eef2ff;
        color: #4338ca;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 16px;
    }

    .employee-name {
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 2px;
    }

    .employee-meta {
        color: #9ca3af;
        font-size: 12px;
    }

    .badge-role {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .role-director {
        background: #1f2937;
        color: white;
    }

    .role-kabid {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .role-kepsek {
        background: #ede9fe;
        color: #6d28d9;
    }

    .role-guru {
        background: #dcfce7;
        color: #15803d;
    }

    .role-staff {
        background: #f1f5f9;
        color: #475569;
    }

    .role-empty {
        background: #fef3c7;
        color: #92400e;
    }

    .unit-badge {
        display: inline-block;
        padding: 5px 9px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 12px;
        font-weight: 600;
    }

    .division-text {
        color: #374151;
        font-size: 13px;
    }

    .account-name {
        font-weight: 600;
        color: #374151;
    }

    .account-email {
        color: #9ca3af;
        font-size: 12px;
    }

    .account-empty {
        color: #9ca3af;
        font-size: 13px;
    }

    .approver-box {
        min-width: 170px;
    }

    .approver-name {
        font-weight: 700;
        color: #374151;
    }

    .approver-role {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 2px;
    }

    .approver-empty {
        color: #dc2626;
        font-size: 12px;
        font-weight: 600;
    }

    .top-structure {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 20px;
        background: #f3f4f6;
        color: #374151;
        font-size: 12px;
        font-weight: 700;
    }

    .btn-org-edit {
        border-radius: 9px;
        padding: 7px 13px;
        font-weight: 600;
        font-size: 13px;
    }

    .org-footer {
        border-top: 1px solid #e5e7eb;
        padding: 14px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .pagination-info {
        color: #6b7280;
        font-size: 13px;
    }

    .org-page-button {
        display: inline-block;
        padding: 7px 12px;
        border: 1px solid #d1d5db;
        background: white;
        color: #374151;
        border-radius: 8px;
        text-decoration: none;
        font-size: 13px;
    }

    .org-page-button:hover {
        background: #f8fafc;
        color: #111827;
    }

    .org-page-button.disabled {
        color: #cbd5e1;
        background: #f8fafc;
        pointer-events: none;
    }

    .employee-checkbox,
    #selectAll {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    @media(max-width: 768px) {
        .org-page {
            padding: 15px;
        }

        .org-filter {
            padding: 15px;
        }
    }
</style>


<div class="org-page">

    {{-- =========================================================
        HEADER
    ========================================================== --}}
    <div class="org-header">

        <h2>
            Struktur Organisasi Pegawai
        </h2>

        <p>
            Kelola jabatan, bidang, akun pimpinan, dan jalur approval pegawai.
        </p>

    </div>


    {{-- =========================================================
        SUCCESS
    ========================================================== --}}
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


    {{-- =========================================================
        VALIDATION ERROR
    ========================================================== --}}
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


    {{-- =========================================================
        FILTER
    ========================================================== --}}
    <div class="org-card org-filter">

        <form
            method="GET"
            action="{{ route('employeeOrganization.index') }}"
        >

            <div class="row g-3">

                <div class="col-lg-4 col-md-6">

                    <label class="form-label fw-semibold">
                        Cari Pegawai
                    </label>

                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        class="form-control"
                        placeholder="Cari nama atau UID..."
                    >

                </div>


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


                <div class="col-lg-3 col-md-6">

                    <label class="form-label fw-semibold">
                        Jabatan
                    </label>

                    <select
                        name="role"
                        class="form-select"
                    >

                        <option value="">
                            Semua Jabatan
                        </option>

                        @foreach($roles as $role)

                            <option
                                value="{{ $role }}"
                                @selected(
                                    request('role') === $role
                                )
                            >
                                {{ $role }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-lg-2 col-md-6 d-flex align-items-end gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('employeeOrganization.index') }}"
                        class="btn btn-light border"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>


    {{-- =========================================================
        BULK FORM
    ========================================================== --}}
    <form
        action="{{ route('employeeOrganization.bulkUpdate') }}"
        method="POST"
        id="bulkForm"
    >

        @csrf


        {{-- =====================================================
            PANEL BULK
        ====================================================== --}}
        <div
            class="bulk-panel"
            id="bulkPanel"
        >

            <div class="row g-3 align-items-end">

                {{-- INFO --}}
                <div class="col-lg-3">

                    <div class="bulk-title">
                        Atur Banyak Pegawai
                    </div>

                    <div class="bulk-subtitle">
                        Terapkan jabatan yang sama ke beberapa pegawai.
                    </div>

                    <div class="mt-2">

                        <span
                            class="selected-count"
                            id="selectedCount"
                        >
                            0 pegawai dipilih
                        </span>

                    </div>

                    <div
                        class="bulk-unit"
                        id="selectedUnitText"
                    >
                    </div>

                </div>


                {{-- ROLE --}}
                <div class="col-lg-3">

                    <label class="form-label fw-semibold">
                        Jabatan
                    </label>

                    <select
                        name="bulk_role"
                        id="bulkRole"
                        class="form-select"
                        required
                    >

                        <option value="">
                            -- Pilih Jabatan --
                        </option>

                        <option value="Guru">
                            Guru
                        </option>

                        <option value="Staff">
                            Staff
                        </option>

                    </select>

                    <div class="form-text">
                        Bulk hanya tersedia untuk Guru dan Staff.
                    </div>

                </div>


                {{-- DIVISION --}}
                <div
                    class="col-lg-4"
                    id="bulkDivisionWrapper"
                    style="display:none;"
                >

                    <label class="form-label fw-semibold">

                        Bidang / Divisi

                        <span class="text-danger">
                            *
                        </span>

                    </label>

                    <select
                        name="division_id"
                        id="bulkDivision"
                        class="form-select"
                    >

                        <option value="">
                            -- Pilih Bidang / Divisi --
                        </option>

                        @foreach($divisions as $division)

                            <option
                                value="{{ $division->id }}"
                                data-unit-id="{{ $division->unit_id }}"
                            >

                                {{ $division->nama }}

                            </option>

                        @endforeach

                    </select>

                    <div class="form-text">
                        Staff UM wajib memiliki bidang.
                    </div>

                </div>


                {{-- BUTTON --}}
                <div class="col-lg-2">

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                        id="bulkSubmit"
                    >
                        Terapkan
                    </button>

                </div>

            </div>

        </div>


        {{-- =====================================================
            TABLE
        ====================================================== --}}
        <div class="org-card">

            <div class="org-table-header">

                <div>

                    <h5>
                        Daftar Pegawai
                    </h5>

                    <div class="text-muted small mt-1">
                        Centang beberapa pegawai untuk melakukan pengaturan massal.
                    </div>

                </div>


                <div class="org-total">

                    Total
                    <strong>
                        {{ $employees->total() }}
                    </strong>
                    pegawai

                </div>

            </div>


            <div class="table-responsive">

                <table class="table org-table">

                    <thead>

                        <tr>

                            {{-- CHECK ALL --}}
                            <th style="width:50px">

                                <input
                                    type="checkbox"
                                    id="selectAll"
                                    title="Pilih semua pegawai pada halaman ini"
                                >

                            </th>

                            <th style="width:50px">
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
                                Bidang / Divisi
                            </th>

                            <th>
                                Akun Login
                            </th>

                            <th>
                                Atasan / Approver
                            </th>

                            <th style="width:100px">
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($employees as $employee)

                            @php

                                $approver =
                                    $approvers[$employee->id]
                                    ?? null;

                                $initial =
                                    mb_strtoupper(
                                        mb_substr(
                                            $employee->nama,
                                            0,
                                            1
                                        )
                                    );

                            @endphp


                            <tr
                                class="employee-row"
                                data-unit-id="{{ $employee->unit_id }}"
                                data-unit-name="{{ $employee->unit?->nama ?? '-' }}"
                            >

                                {{-- CHECKBOX --}}
                                <td>

                                    <input
                                        type="checkbox"
                                        name="employee_ids[]"
                                        value="{{ $employee->id }}"
                                        class="employee-checkbox"
                                        data-unit-id="{{ $employee->unit_id }}"
                                        data-unit-name="{{ $employee->unit?->nama ?? '-' }}"
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

                                    @if($employee->unit)

                                        <span class="unit-badge">

                                            {{ $employee->unit->nama }}

                                        </span>

                                    @else

                                        <span class="text-muted">
                                            -
                                        </span>

                                    @endif

                                </td>


                                {{-- ROLE --}}
                                <td>

                                    @switch($employee->role)

                                        @case('Direktur')

                                            <span class="badge-role role-director">
                                                Direktur
                                            </span>

                                            @break


                                        @case('Kepala Bidang')

                                            <span class="badge-role role-kabid">
                                                Kepala Bidang
                                            </span>

                                            @break


                                        @case('Kepala Sekolah')

                                            <span class="badge-role role-kepsek">
                                                Kepala Sekolah
                                            </span>

                                            @break


                                        @case('Waka Kurikulum')

                                            <span class="badge-role role-kepsek">
                                                Waka Kurikulum
                                            </span>

                                            @break


                                        @case('Waka Kesiswaan')

                                            <span class="badge-role role-kepsek">
                                                Waka Kesiswaan
                                            </span>

                                            @break


                                        @case('Guru')

                                            <span class="badge-role role-guru">
                                                Guru
                                            </span>

                                            @break


                                        @case('Staff')

                                            <span class="badge-role role-staff">
                                                Staff
                                            </span>

                                            @break


                                        @default

                                            <span class="badge-role role-empty">
                                                Belum Diatur
                                            </span>

                                    @endswitch

                                </td>


                                {{-- DIVISION --}}
                                <td>

                                    @if($employee->division)

                                        <div class="division-text">

                                            {{ $employee->division->nama }}

                                        </div>

                                    @else

                                        <span class="text-muted">
                                            -
                                        </span>

                                    @endif

                                </td>


                                {{-- ACCOUNT --}}
                                <td>

                                    @if($employee->user)

                                        <div class="account-name">

                                            {{ $employee->user->name }}

                                        </div>

                                        <div class="account-email">

                                            {{ $employee->user->email }}

                                        </div>

                                    @else

                                        <span class="account-empty">

                                            Belum ada akun

                                        </span>

                                    @endif

                                </td>


                                {{-- APPROVER --}}
                                <td>

                                    <div class="approver-box">

                                        @if(!$employee->role)

                                            <span class="text-muted small">

                                                Jabatan belum diatur

                                            </span>


                                        @elseif($employee->role === 'Direktur')

                                            <span class="top-structure">

                                                Puncak Struktur

                                            </span>


                                        @elseif($approver)

                                            <div class="approver-name">

                                                {{ $approver->name }}

                                            </div>

                                            <div class="approver-role">

                                                {{
                                                    $approver
                                                        ->employee
                                                        ?->role
                                                    ?? 'Pimpinan'
                                                }}

                                            </div>


                                        @else

                                            <div class="approver-empty">

                                                Atasan belum ditemukan

                                            </div>

                                        @endif

                                    </div>

                                </td>


                                {{-- ACTION --}}
                                <td>

                                    <a
                                        href="{{
                                            route(
                                                'employeeOrganization.edit',
                                                $employee->id
                                            )
                                        }}"
                                        class="btn btn-primary btn-sm btn-org-edit"
                                    >

                                        Atur

                                    </a>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="9"
                                    class="text-center py-5"
                                >

                                    <div class="fw-semibold text-muted">
                                        Data pegawai tidak ditemukan
                                    </div>

                                    <div class="small text-muted mt-1">
                                        Coba ubah filter pencarian.
                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- =================================================
                PAGINATION
            ================================================== --}}
            @if($employees->hasPages())

                <div class="org-footer">

                    <div class="pagination-info">

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

                            <span class="org-page-button disabled">

                                ← Sebelumnya

                            </span>

                        @else

                            <a
                                href="{{ $employees->previousPageUrl() }}"
                                class="org-page-button"
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
                                class="org-page-button"
                            >

                                Berikutnya →

                            </a>

                        @else

                            <span class="org-page-button disabled">

                                Berikutnya →

                            </span>

                        @endif

                    </div>

                </div>

            @else

                <div class="org-footer">

                    <div class="pagination-info">

                        Menampilkan

                        <strong>
                            {{ $employees->count() }}
                        </strong>

                        pegawai

                    </div>

                </div>

            @endif

        </div>

    </form>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const selectAll =
        document.getElementById('selectAll');

    const checkboxes =
        Array.from(
            document.querySelectorAll(
                '.employee-checkbox'
            )
        );

    const bulkPanel =
        document.getElementById('bulkPanel');

    const selectedCount =
        document.getElementById('selectedCount');

    const selectedUnitText =
        document.getElementById('selectedUnitText');

    const bulkRole =
        document.getElementById('bulkRole');

    const bulkDivisionWrapper =
        document.getElementById(
            'bulkDivisionWrapper'
        );

    const bulkDivision =
        document.getElementById(
            'bulkDivision'
        );

    const bulkForm =
        document.getElementById('bulkForm');


    // =========================================================
    // AMBIL CHECKBOX YANG DIPILIH
    // =========================================================
    function getSelected()
    {
        return checkboxes.filter(
            checkbox => checkbox.checked
        );
    }


    // =========================================================
    // UPDATE TAMPILAN ROW
    // =========================================================
    function updateRowStyles()
    {
        checkboxes.forEach(function (checkbox) {

            const row =
                checkbox.closest(
                    '.employee-row'
                );

            if (!row) {
                return;
            }

            if (checkbox.checked) {

                row.classList.add(
                    'selected-row'
                );

            } else {

                row.classList.remove(
                    'selected-row'
                );
            }
        });
    }


    // =========================================================
    // FILTER DIVISION SESUAI UNIT
    // =========================================================
    function filterDivisions(unitId)
    {
        Array.from(
            bulkDivision.options
        ).forEach(function (option) {

            if (!option.value) {
                option.hidden = false;
                return;
            }

            const optionUnit =
                option.dataset.unitId;

            option.hidden =
                String(optionUnit)
                !==
                String(unitId);
        });


        /*
         * Kalau division yang sedang terpilih
         * ternyata bukan milik unit terpilih,
         * reset.
         */
        const selectedOption =
            bulkDivision.options[
                bulkDivision.selectedIndex
            ];

        if (
            selectedOption
            &&
            selectedOption.value
            &&
            String(
                selectedOption.dataset.unitId
            )
            !==
            String(unitId)
        ) {

            bulkDivision.value = '';
        }
    }


    // =========================================================
    // TOGGLE BIDANG
    // =========================================================
    function updateDivisionVisibility()
    {
        const selected =
            getSelected();

        if (selected.length === 0) {

            bulkDivisionWrapper.style.display =
                'none';

            return;
        }


        const unitId =
            selected[0].dataset.unitId;

        const role =
            bulkRole.value;


        /*
         * Saat ini Staff UM memakai division.
         * Unit UM = ID 1.
         */
        const needDivision =
            role === 'Staff'
            &&
            String(unitId) === '1';


        if (needDivision) {

            bulkDivisionWrapper.style.display =
                'block';

            bulkDivision.required = true;

            filterDivisions(unitId);

        } else {

            bulkDivisionWrapper.style.display =
                'none';

            bulkDivision.required = false;

            bulkDivision.value = '';
        }
    }


    // =========================================================
    // UPDATE BULK PANEL
    // =========================================================
    function refreshBulkPanel()
    {
        const selected =
            getSelected();


        if (selected.length === 0) {

            bulkPanel.classList.remove(
                'active'
            );

            selectedCount.textContent =
                '0 pegawai dipilih';

            selectedUnitText.textContent =
                '';

            selectAll.checked = false;

            selectAll.indeterminate =
                false;

            updateRowStyles();

            return;
        }


        bulkPanel.classList.add(
            'active'
        );


        selectedCount.textContent =
            selected.length
            +
            ' pegawai dipilih';


        const units =
            [
                ...new Set(
                    selected.map(
                        checkbox =>
                            checkbox.dataset.unitId
                    )
                )
            ];


        if (units.length === 1) {

            const unitName =
                selected[0]
                    .dataset
                    .unitName;

            selectedUnitText.textContent =
                'Unit: '
                +
                unitName;

        } else {

            selectedUnitText.textContent =
                '⚠ Pegawai berasal dari beberapa unit. Pilih satu unit saja.';
        }


        // Status select all
        if (
            selected.length ===
            checkboxes.length
        ) {

            selectAll.checked = true;
            selectAll.indeterminate = false;

        } else {

            selectAll.checked = false;
            selectAll.indeterminate = true;
        }


        updateRowStyles();
        updateDivisionVisibility();
    }


    // =========================================================
    // SELECT ALL
    // =========================================================
    selectAll.addEventListener(
        'change',
        function () {

            /*
             * Select all hanya realistis jika data yang
             * sedang ditampilkan berasal dari satu unit.
             */

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
                    'Pilih filter Unit terlebih dahulu sebelum menggunakan Pilih Semua.'
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


            refreshBulkPanel();
        }
    );


    // =========================================================
    // CHECKBOX SATU-SATU
    // =========================================================
    checkboxes.forEach(
        function (checkbox) {

            checkbox.addEventListener(
                'change',
                function () {

                    const selected =
                        getSelected();


                    /*
                     * Mencegah beda unit langsung dari UI.
                     */
                    if (selected.length > 1) {

                        const firstUnit =
                            selected[0]
                                .dataset
                                .unitId;

                        const invalid =
                            selected.some(
                                item =>
                                    item.dataset.unitId
                                    !==
                                    firstUnit
                            );


                        if (invalid) {

                            checkbox.checked =
                                false;

                            alert(
                                'Bulk pengaturan hanya dapat dilakukan untuk pegawai dalam unit yang sama.'
                            );
                        }
                    }


                    refreshBulkPanel();
                }
            );
        }
    );


    // =========================================================
    // ROLE CHANGE
    // =========================================================
    bulkRole.addEventListener(
        'change',
        updateDivisionVisibility
    );


    // =========================================================
    // VALIDASI SEBELUM SUBMIT
    // =========================================================
    bulkForm.addEventListener(
        'submit',
        function (event) {

            const selected =
                getSelected();


            if (selected.length === 0) {

                event.preventDefault();

                alert(
                    'Pilih minimal satu pegawai.'
                );

                return;
            }


            if (!bulkRole.value) {

                event.preventDefault();

                alert(
                    'Pilih jabatan yang akan diterapkan.'
                );

                return;
            }


            const unitIds =
                [
                    ...new Set(
                        selected.map(
                            item =>
                                item.dataset.unitId
                        )
                    )
                ];


            if (unitIds.length > 1) {

                event.preventDefault();

                alert(
                    'Pegawai yang dipilih harus berada dalam unit yang sama.'
                );

                return;
            }


            if (
                bulkRole.value === 'Staff'
                &&
                String(unitIds[0]) === '1'
                &&
                !bulkDivision.value
            ) {

                event.preventDefault();

                alert(
                    'Staff UM wajib mempunyai Bidang / Divisi.'
                );

                return;
            }


            const role =
                bulkRole.value;


            const total =
                selected.length;


            const yakin =
                confirm(
                    'Terapkan jabatan '
                    +
                    role
                    +
                    ' ke '
                    +
                    total
                    +
                    ' pegawai terpilih?'
                );


            if (!yakin) {

                event.preventDefault();
            }
        }
    );


    refreshBulkPanel();

});

</script>

@endsection