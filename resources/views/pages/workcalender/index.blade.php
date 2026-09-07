@extends('layouts.admin')

@section('title', 'Kalender Kerja / Kaldik')

@section('content')

<style>
    .calendar-page {
        padding: 24px;
    }

    .calendar-header {
        margin-bottom: 24px;
    }

    .calendar-header h2 {
        margin-bottom: 5px;
        font-weight: 700;
        color: #1f2937;
    }

    .calendar-header p {
        margin: 0;
        color: #6b7280;
    }

    .calendar-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,0,0,.04);
        overflow: hidden;
    }

    .calendar-filter {
        padding: 20px;
        margin-bottom: 20px;
    }

    .calendar-card-header {
        padding: 18px 20px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .calendar-card-header h5 {
        margin: 0;
        font-weight: 700;
        color: #1f2937;
    }

    .calendar-total {
        display: inline-flex;
        padding: 6px 12px;
        border-radius: 20px;
        background: #f3f4f6;
        color: #4b5563;
        font-size: 13px;
    }

    .calendar-table {
        margin-bottom: 0;
    }

    .calendar-table thead th {
        padding: 14px 16px;
        background: #f8fafc;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        white-space: nowrap;
    }

    .calendar-table tbody td {
        padding: 15px 16px;
        vertical-align: middle;
        border-color: #f1f5f9;
    }

    .calendar-table tbody tr:hover {
        background: #fafafa;
    }

    .date-box {
        min-width: 120px;
    }

    .date-main {
        font-weight: 700;
        color: #1f2937;
    }

    .date-day {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 2px;
    }

    .status-badge {
        display: inline-flex;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .status-workday {
        background: #dcfce7;
        color: #15803d;
    }

    .status-holiday {
        background: #fee2e2;
        color: #b91c1c;
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

    .unit-global {
        background: #eef2ff;
        color: #4338ca;
        border-color: #c7d2fe;
    }

    .type-badge {
        display: inline-flex;
        padding: 5px 9px;
        border-radius: 8px;
        background: #f1f5f9;
        color: #475569;
        font-size: 12px;
        font-weight: 600;
    }

    .calendar-name {
        font-weight: 700;
        color: #374151;
    }

    .calendar-description {
        color: #94a3b8;
        font-size: 12px;
        margin-top: 3px;
        max-width: 320px;
    }

    .btn-action {
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        padding: 6px 10px;
    }

    .calendar-footer {
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
        background: white;
        color: #374151;
        border-radius: 8px;
        text-decoration: none;
        font-size: 13px;
    }

    .page-button:hover {
        background: #f8fafc;
        color: #111827;
    }

    .page-button.disabled {
        color: #cbd5e1;
        background: #f8fafc;
        pointer-events: none;
    }

    .calendar-info {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 15px 16px;
        color: #1e40af;
        font-size: 13px;
        margin-bottom: 20px;
    }

    .modal-content {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
    }

    .modal-header,
    .modal-footer {
        border-color: #e5e7eb;
    }

    @media(max-width:768px) {
        .calendar-page {
            padding: 15px;
        }
    }
</style>


<div class="calendar-page">

    {{-- =====================================================
        HEADER
    ====================================================== --}}
    <div class="calendar-header">

        <h2>
            Kalender Kerja / Kaldik
        </h2>

        <p>
            Kelola hari kerja, hari libur, dan override kalender yang digunakan oleh sistem absensi.
        </p>

    </div>


    {{-- =====================================================
        INFO
    ====================================================== --}}
    <div class="calendar-info">

        <strong>Catatan:</strong>

        Kalender khusus unit mempunyai prioritas lebih tinggi
        daripada kalender yang berlaku untuk semua unit.

    </div>


    {{-- =====================================================
        SUCCESS
    ====================================================== --}}
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


    {{-- =====================================================
        VALIDATION ERROR
    ====================================================== --}}
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


    {{-- =====================================================
        FILTER
    ====================================================== --}}
    <div class="calendar-card calendar-filter">

        <form
            action="{{ route('workCalendar.index') }}"
            method="GET"
        >

            <div class="row g-3">

                {{-- TAHUN AJARAN --}}
                <div class="col-lg-3 col-md-6">

                    <label class="form-label fw-semibold">
                        Tahun Ajaran
                    </label>

                    <select
                        name="academic_year"
                        class="form-select"
                    >

                        <option value="">
                            Semua Tahun Ajaran
                        </option>

                        @foreach($academicYears as $year)

                            <option
                                value="{{ $year }}"
                                @selected(
                                    request('academic_year') === $year
                                )
                            >
                                {{ $year }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- BULAN --}}
                <div class="col-lg-2 col-md-6">

                    <label class="form-label fw-semibold">
                        Bulan
                    </label>

                    <input
                        type="month"
                        name="month"
                        value="{{ request('month') }}"
                        class="form-control"
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
                            Semua Scope
                        </option>

                        <option
                            value="global"
                            @selected(
                                request('unit_id') === 'global'
                            )
                        >
                            Semua Unit / Global
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


                {{-- STATUS --}}
                <div class="col-lg-2 col-md-6">

                    <label class="form-label fw-semibold">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            Semua Status
                        </option>

                        <option
                            value="workday"
                            @selected(
                                request('status') === 'workday'
                            )
                        >
                            Hari Kerja
                        </option>

                        <option
                            value="holiday"
                            @selected(
                                request('status') === 'holiday'
                            )
                        >
                            Libur
                        </option>

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
                        href="{{ route('workCalendar.index') }}"
                        class="btn btn-light border"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>


    {{-- =====================================================
        TABLE
    ====================================================== --}}
    <div class="calendar-card">

        <div class="calendar-card-header">

            <div>

                <h5>
                    Daftar Kalender Kerja
                </h5>

                <div class="small text-muted mt-1">
                    Data pada halaman ini digunakan langsung oleh mesin perhitungan absensi.
                </div>

            </div>


            <div class="d-flex align-items-center gap-2">

                <div class="calendar-total">

                    Total:
                    &nbsp;

                    <strong>
                        {{ $calendars->total() }}
                    </strong>

                </div>


                <button
                    type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#addCalendarModal"
                >
                    + Tambah Kalender
                </button>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table calendar-table">

                <thead>

                    <tr>

                        <th width="60">
                            No
                        </th>

                        <th>
                            Tanggal
                        </th>

                        <th>
                            Tahun Ajaran
                        </th>

                        <th>
                            Scope Unit
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Jenis
                        </th>

                        <th>
                            Keterangan
                        </th>

                        <th width="170">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($calendars as $calendar)

                        @php

                            $typeLabel = match($calendar->type) {
                                'libur_gk'
                                    => 'Libur GK',

                                'libur_nasional'
                                    => 'Libur Nasional',

                                'libur_khusus'
                                    => 'Libur Khusus',

                                'hari_kerja_khusus'
                                    => 'Hari Kerja Khusus',

                                default
                                    => $calendar->type
                                        ?: '-',
                            };

                        @endphp


                        <tr>

                            {{-- NO --}}
                            <td class="text-muted">

                                {{
                                    $calendars->firstItem()
                                    + $loop->index
                                }}

                            </td>


                            {{-- DATE --}}
                            <td>

                                <div class="date-box">

                                    <div class="date-main">

                                        {{
                                            $calendar->date
                                                ->format('d/m/Y')
                                        }}

                                    </div>

                                    <div class="date-day">

                                        {{
                                            $calendar->date
                                                ->locale('id')
                                                ->translatedFormat('l')
                                        }}

                                    </div>

                                </div>

                            </td>


                            {{-- ACADEMIC YEAR --}}
                            <td>

                                <span class="unit-badge">

                                    {{ $calendar->academic_year }}

                                </span>

                            </td>


                            {{-- UNIT --}}
                            <td>

                                @if($calendar->unit)

                                    <span class="unit-badge">

                                        {{ $calendar->unit->nama }}

                                    </span>

                                @else

                                    <span class="unit-badge unit-global">

                                        Semua Unit

                                    </span>

                                @endif

                            </td>


                            {{-- STATUS --}}
                            <td>

                                @if($calendar->is_workday)

                                    <span class="status-badge status-workday">

                                        Hari Kerja

                                    </span>

                                @else

                                    <span class="status-badge status-holiday">

                                        Libur

                                    </span>

                                @endif

                            </td>


                            {{-- TYPE --}}
                            <td>

                                <span class="type-badge">

                                    {{ $typeLabel }}

                                </span>

                            </td>


                            {{-- NAME --}}
                            <td>

                                <div class="calendar-name">

                                    {{ $calendar->name }}

                                </div>

                                @if($calendar->description)

                                    <div class="calendar-description">

                                        {{ $calendar->description }}

                                    </div>

                                @endif

                            </td>


                            {{-- ACTION --}}
                            <td>

                                <div class="d-flex gap-2">

                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm btn-action"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editCalendarModal{{ $calendar->id }}"
                                    >
                                        Edit
                                    </button>


                                    <form
                                        action="{{
                                            route(
                                                'workCalendar.destroy',
                                                $calendar->id
                                            )
                                        }}"
                                        method="POST"
                                        onsubmit="return confirm('Hapus override kalender tanggal ini?')"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm btn-action"
                                        >
                                            Hapus
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="8"
                                class="text-center py-5"
                            >

                                <div class="fw-semibold text-muted">

                                    Tidak ada data kalender kerja.

                                </div>

                                <div class="small text-muted mt-1">

                                    Coba ubah filter atau tambahkan kalender baru.

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- =================================================
            PAGINATION CUSTOM
        ================================================== --}}
        @if($calendars->hasPages())

            <div class="calendar-footer">

                <div class="page-info">

                    Menampilkan

                    <strong>
                        {{ $calendars->firstItem() }}
                    </strong>

                    -

                    <strong>
                        {{ $calendars->lastItem() }}
                    </strong>

                    dari

                    <strong>
                        {{ $calendars->total() }}
                    </strong>

                    data

                </div>


                <div class="d-flex align-items-center gap-2">

                    @if($calendars->onFirstPage())

                        <span class="page-button disabled">

                            ← Sebelumnya

                        </span>

                    @else

                        <a
                            href="{{ $calendars->previousPageUrl() }}"
                            class="page-button"
                        >
                            ← Sebelumnya
                        </a>

                    @endif


                    <span class="small text-muted px-2">

                        Halaman

                        <strong>
                            {{ $calendars->currentPage() }}
                        </strong>

                        dari

                        <strong>
                            {{ $calendars->lastPage() }}
                        </strong>

                    </span>


                    @if($calendars->hasMorePages())

                        <a
                            href="{{ $calendars->nextPageUrl() }}"
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

</div>


{{-- =========================================================
    MODAL TAMBAH
========================================================== --}}
<div
    class="modal fade"
    id="addCalendarModal"
    tabindex="-1"
>

    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content">

            <form
                action="{{ route('workCalendar.store') }}"
                method="POST"
            >

                @csrf

                <input
                    type="hidden"
                    name="form_context"
                    value="add"
                >


                <div class="modal-header">

                    <h5 class="modal-title">
                        Tambah Kalender Kerja
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body">

                    <div class="row g-3">


                        {{-- TAHUN AJARAN --}}
                        <div class="col-md-6">

                            <label class="form-label fw-semibold">

                                Tahun Ajaran

                                <span class="text-danger">*</span>

                            </label>

                            <input
                                type="text"
                                name="academic_year"
                                value="{{
                                    old(
                                        'academic_year',
                                        request(
                                            'academic_year',
                                            $academicYears->first()
                                            ?? '2026/2027'
                                        )
                                    )
                                }}"
                                class="form-control"
                                placeholder="2026/2027"
                                required
                            >

                        </div>


                        {{-- UNIT --}}
                        <div class="col-md-6">

                            <label class="form-label fw-semibold">

                                Berlaku Untuk

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
                                            old('unit_id') == $unit->id
                                        )
                                    >
                                        {{ $unit->nama }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- DATE START --}}
                        <div class="col-md-6">

                            <label class="form-label fw-semibold">

                                Tanggal Mulai

                                <span class="text-danger">*</span>

                            </label>

                            <input
                                type="date"
                                name="date_start"
                                value="{{ old('date_start') }}"
                                class="form-control"
                                required
                            >

                        </div>


                        {{-- DATE END --}}
                        <div class="col-md-6">

                            <label class="form-label fw-semibold">

                                Tanggal Selesai

                                <span class="text-danger">*</span>

                            </label>

                            <input
                                type="date"
                                name="date_end"
                                value="{{ old('date_end') }}"
                                class="form-control"
                                required
                            >

                            <div class="form-text">

                                Untuk satu hari, isi tanggal mulai
                                dan selesai dengan tanggal yang sama.

                            </div>

                        </div>


                        {{-- STATUS --}}
                        <div class="col-md-6">

                            <label class="form-label fw-semibold">

                                Status Kalender

                                <span class="text-danger">*</span>

                            </label>

                            <select
                                name="is_workday"
                                id="addIsWorkday"
                                class="form-select"
                                required
                            >

                                <option
                                    value="0"
                                    @selected(
                                        (string) old(
                                            'is_workday',
                                            '0'
                                        ) === '0'
                                    )
                                >
                                    Libur
                                </option>

                                <option
                                    value="1"
                                    @selected(
                                        (string) old(
                                            'is_workday'
                                        ) === '1'
                                    )
                                >
                                    Hari Kerja
                                </option>

                            </select>

                        </div>


                        {{-- TYPE --}}
                        <div class="col-md-6">

                            <label class="form-label fw-semibold">

                                Jenis

                            </label>

                            <select
                                name="type"
                                id="addCalendarType"
                                class="form-select"
                            >

                                <option value="">
                                    -- Pilih Jenis --
                                </option>

                                <option
                                    value="libur_gk"
                                    @selected(
                                        old('type') === 'libur_gk'
                                    )
                                >
                                    Libur Guru / Karyawan
                                </option>

                                <option
                                    value="libur_nasional"
                                    @selected(
                                        old('type') === 'libur_nasional'
                                    )
                                >
                                    Libur Nasional
                                </option>

                                <option
                                    value="libur_khusus"
                                    @selected(
                                        old('type') === 'libur_khusus'
                                    )
                                >
                                    Libur Khusus
                                </option>

                                <option
                                    value="hari_kerja_khusus"
                                    @selected(
                                        old('type') === 'hari_kerja_khusus'
                                    )
                                >
                                    Hari Kerja Khusus
                                </option>

                            </select>

                        </div>


                        {{-- NAME --}}
                        <div class="col-12">

                            <label class="form-label fw-semibold">

                                Nama / Keterangan Kalender

                                <span class="text-danger">*</span>

                            </label>

                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                class="form-control"
                                placeholder="Contoh: Libur Guru/Karyawan"
                                required
                                maxlength="255"
                            >

                        </div>


                        {{-- DESCRIPTION --}}
                        <div class="col-12">

                            <label class="form-label fw-semibold">

                                Catatan Tambahan

                            </label>

                            <textarea
                                name="description"
                                class="form-control"
                                rows="3"
                                placeholder="Opsional"
                            >{{ old('description') }}</textarea>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light border"
                        data-bs-dismiss="modal"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Simpan Kalender
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


{{-- =========================================================
    MODAL EDIT
========================================================== --}}
@foreach($calendars as $calendar)

    <div
        class="modal fade"
        id="editCalendarModal{{ $calendar->id }}"
        tabindex="-1"
    >

        <div class="modal-dialog modal-dialog-centered modal-lg">

            <div class="modal-content">

                <form
                    action="{{
                        route(
                            'workCalendar.update',
                            $calendar->id
                        )
                    }}"
                    method="POST"
                >

                    @csrf
                    @method('PUT')

                    <input
                        type="hidden"
                        name="form_context"
                        value="edit-{{ $calendar->id }}"
                    >


                    <div class="modal-header">

                        <h5 class="modal-title">
                            Edit Kalender Kerja
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                        </button>

                    </div>


                    <div class="modal-body">

                        <div class="row g-3">


                            {{-- ACADEMIC YEAR --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Tahun Ajaran

                                </label>

                                <input
                                    type="text"
                                    name="academic_year"
                                    class="form-control"
                                    value="{{
                                        old('form_context')
                                            ===
                                            'edit-'.$calendar->id

                                            ? old(
                                                'academic_year'
                                            )

                                            : $calendar
                                                ->academic_year
                                    }}"
                                    required
                                >

                            </div>


                            {{-- DATE --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Tanggal

                                </label>

                                <input
                                    type="date"
                                    name="date"
                                    class="form-control"
                                    value="{{
                                        old('form_context')
                                            ===
                                            'edit-'.$calendar->id

                                            ? old('date')

                                            : $calendar
                                                ->date
                                                ->format('Y-m-d')
                                    }}"
                                    required
                                >

                            </div>


                            {{-- UNIT --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Berlaku Untuk

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
                                                (
                                                    old('form_context')
                                                    ===
                                                    'edit-'.$calendar->id

                                                    ? old('unit_id')

                                                    : $calendar
                                                        ->unit_id
                                                )
                                                == $unit->id
                                            )
                                        >
                                            {{ $unit->nama }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            {{-- STATUS --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Status

                                </label>

                                @php

                                    $editWorkday =
                                        old('form_context')
                                        ===
                                        'edit-'.$calendar->id

                                        ? old('is_workday')

                                        : (
                                            $calendar->is_workday
                                                ? '1'
                                                : '0'
                                        );

                                @endphp

                                <select
                                    name="is_workday"
                                    class="form-select"
                                    required
                                >

                                    <option
                                        value="0"
                                        @selected(
                                            (string) $editWorkday
                                            === '0'
                                        )
                                    >
                                        Libur
                                    </option>

                                    <option
                                        value="1"
                                        @selected(
                                            (string) $editWorkday
                                            === '1'
                                        )
                                    >
                                        Hari Kerja
                                    </option>

                                </select>

                            </div>


                            {{-- TYPE --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Jenis

                                </label>

                                @php

                                    $editType =
                                        old('form_context')
                                        ===
                                        'edit-'.$calendar->id

                                        ? old('type')

                                        : $calendar->type;

                                @endphp

                                <select
                                    name="type"
                                    class="form-select"
                                >

                                    <option value="">
                                        -- Pilih Jenis --
                                    </option>

                                    <option
                                        value="libur_gk"
                                        @selected(
                                            $editType === 'libur_gk'
                                        )
                                    >
                                        Libur Guru / Karyawan
                                    </option>

                                    <option
                                        value="libur_nasional"
                                        @selected(
                                            $editType === 'libur_nasional'
                                        )
                                    >
                                        Libur Nasional
                                    </option>

                                    <option
                                        value="libur_khusus"
                                        @selected(
                                            $editType === 'libur_khusus'
                                        )
                                    >
                                        Libur Khusus
                                    </option>

                                    <option
                                        value="hari_kerja_khusus"
                                        @selected(
                                            $editType === 'hari_kerja_khusus'
                                        )
                                    >
                                        Hari Kerja Khusus
                                    </option>

                                </select>

                            </div>


                            {{-- NAME --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Nama Kalender

                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    class="form-control"
                                    value="{{
                                        old('form_context')
                                            ===
                                            'edit-'.$calendar->id

                                            ? old('name')

                                            : $calendar->name
                                    }}"
                                    required
                                >

                            </div>


                            {{-- DESCRIPTION --}}
                            <div class="col-12">

                                <label class="form-label fw-semibold">

                                    Catatan

                                </label>

                                <textarea
                                    name="description"
                                    class="form-control"
                                    rows="3"
                                >{{ old('form_context') === 'edit-'.$calendar->id ? old('description') : $calendar->description }}</textarea>

                            </div>

                        </div>

                    </div>


                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-light border"
                            data-bs-dismiss="modal"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Simpan Perubahan
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endforeach


<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | BUKA KEMBALI MODAL KALAU VALIDASI ERROR
    |--------------------------------------------------------------------------
    */

    const context =
        @json(old('form_context'));


    if (context === 'add') {

        const element =
            document.getElementById(
                'addCalendarModal'
            );

        if (element) {

            const modal =
                new bootstrap.Modal(
                    element
                );

            modal.show();
        }

    } else if (
        context
        &&
        context.startsWith('edit-')
    ) {

        const id =
            context.replace(
                'edit-',
                ''
            );

        const element =
            document.getElementById(
                'editCalendarModal'
                +
                id
            );

        if (element) {

            const modal =
                new bootstrap.Modal(
                    element
                );

            modal.show();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BANTU STATUS ↔ TYPE PADA FORM TAMBAH
    |--------------------------------------------------------------------------
    */

    const status =
        document.getElementById(
            'addIsWorkday'
        );

    const type =
        document.getElementById(
            'addCalendarType'
        );


    if (status && type) {

        status.addEventListener(
            'change',
            function () {

                if (
                    status.value === '1'
                ) {

                    type.value =
                        'hari_kerja_khusus';

                } else if (
                    type.value
                    ===
                    'hari_kerja_khusus'
                ) {

                    type.value = '';
                }
            }
        );
    }

});

</script>

@endsection