@extends('layouts.admin')

@section('title')
    Data Pegawai
@endsection


@push('addon-style')

<link
    rel="stylesheet"
    href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"
>

<style>

    .employee-page {
        padding-bottom: 30px;
    }

    .employee-header {
        margin-bottom: 20px;
    }

    .employee-header h1 {
        font-weight: 700;
        color: #1f2937;
    }

    .employee-header p {
        color: #6b7280;
        margin-bottom: 0;
    }

    .employee-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0,0,0,.05);
        overflow: hidden;
    }

    .employee-card .card-header {
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        padding: 18px 20px;
    }

    .employee-card .card-body {
        padding: 20px;
    }

    .filter-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 20px;
    }

    #employeeTable thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        vertical-align: middle;
        white-space: nowrap;
    }

    #employeeTable tbody td {
        vertical-align: middle;
        padding-top: 12px;
        padding-bottom: 12px;
    }

    .employee-name {
        font-weight: 700;
        color: #1f2937;
    }

    .uid-badge {
        display: inline-flex;
        min-width: 45px;
        justify-content: center;
        padding: 5px 9px;
        background: #f1f5f9;
        border-radius: 7px;
        color: #475569;
        font-weight: 700;
        font-size: 12px;
    }

    .unit-text {
        font-weight: 600;
        color: #475569;
    }

    div.dataTables_wrapper div.dataTables_length select {
        min-width: 75px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
    }

    div.dataTables_wrapper div.dataTables_filter input {
        border-radius: 8px;
        border: 1px solid #d1d5db;
        padding: 6px 10px;
        margin-left: 8px;
    }

    div.dataTables_wrapper div.dataTables_info {
        color: #64748b;
        padding-top: 15px;
    }

    div.dataTables_wrapper div.dataTables_paginate {
        padding-top: 10px;
    }

    div.dataTables_wrapper .page-link {
        border-radius: 7px;
        margin: 0 2px;
    }

</style>

@endpush


@section('content')

<main class="app-main">

    {{-- HEADER --}}
    <div class="app-content-header">

        <div class="container-fluid">

            <div class="row">

                <div class="col-sm-7 employee-header">

                    <h1 class="mb-1 fs-3">
                        Data Pegawai
                    </h1>

                    <p>
                        Kelola dan lihat data seluruh pegawai DCS.
                    </p>

                </div>


                <div class="col-sm-5">

                    <nav aria-label="breadcrumb">

                        <ol class="breadcrumb float-sm-end">

                            <li class="breadcrumb-item">
                                Dashboard
                            </li>

                            <li
                                class="breadcrumb-item active"
                                aria-current="page"
                            >
                                Data Pegawai
                            </li>

                        </ol>

                    </nav>

                </div>

            </div>

        </div>

    </div>


    {{-- CONTENT --}}
    <div class="app-content">

        <div class="container-fluid">

            {{-- FLASH MESSAGE --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>

                    {{ session('success') }}

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close"
                    ></button>
                </div>
            @endif


            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>

                    {{ session('error') }}

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close"
                    ></button>
                </div>
            @endif


            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">

                    <div class="fw-bold mb-1">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Data belum dapat diproses
                    </div>

                    @foreach($errors->all() as $error)
                        <div>
                            • {{ $error }}
                        </div>
                    @endforeach

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close"
                    ></button>

                </div>
            @endif


            {{-- FILTER --}}
            <div class="filter-box">

                <div class="row g-3">


                    {{-- UNIT --}}
                    <div class="col-lg-4 col-md-6">

                        <label class="form-label fw-semibold">
                            Unit
                        </label>

                        <select
                            id="filterUnit"
                            class="form-select"
                        >

                            <option value="">
                                Semua Unit
                            </option>

                            @foreach($units as $unit)

                                <option value="{{ $unit->id }}">
                                    {{ $unit->nama }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- ROLE --}}
                    <div class="col-lg-3 col-md-6">

                        <label class="form-label fw-semibold">
                            Jabatan
                        </label>

                        <select
                            id="filterRole"
                            class="form-select"
                        >

                            <option value="">
                                Semua Jabatan
                            </option>

                            <option value="Direktur">
                                Direktur
                            </option>

                            <option value="Kepala Bidang">
                                Kepala Bidang
                            </option>

                            <option value="Kepala Sekolah">
                                Kepala Sekolah
                            </option>

                            <option value="Guru">
                                Guru
                            </option>

                            <option value="Staff">
                                Staff
                            </option>

                        </select>

                    </div>


                    {{-- SCHEDULE --}}
                    <div class="col-lg-3 col-md-6">

                        <label class="form-label fw-semibold">
                            Jadwal
                        </label>

                        <select
                            id="filterSchedule"
                            class="form-select"
                        >

                            <option value="">
                                Semua
                            </option>

                            <option value="assigned">
                                Sudah Ada Jadwal
                            </option>

                            <option value="unassigned">
                                Belum Ada Jadwal
                            </option>

                        </select>

                    </div>


                    {{-- RESET --}}
                    <div class="col-lg-2 col-md-6 d-flex align-items-end">

                        <button
                            type="button"
                            id="resetFilter"
                            class="btn btn-light border w-100"
                        >
                            Reset Filter
                        </button>

                    </div>

                </div>

            </div>


            {{-- CARD --}}
            <div class="card employee-card">

                <div class="card-header">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                        <div>

                            <h3 class="card-title fw-bold mb-1">
                                Daftar Pegawai
                            </h3>

                        </div>

                    </div>

                </div>


                <div class="card-body">

                    <div class="table-responsive">

                        <table
                            id="employeeTable"
                            class="table table-hover align-middle w-100"
                        >

                            <thead>

                                <tr>

                                    <th width="60">
                                        No
                                    </th>

                                    <th width="100">
                                        UID
                                    </th>

                                    <th>
                                        Nama Pegawai
                                    </th>

                                    <th>
                                        Unit
                                    </th>

                                    <th>
                                        Jabatan
                                    </th>

                                    <th>
                                        Jadwal
                                    </th>

                                    <th width="100">
                                        Aksi
                                    </th>

                                </tr>

                            </thead>

                            <tbody>
                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

</main>

{{-- MODAL NONAKTIFKAN / PEGAWAI KELUAR --}}
<div class="modal fade"
     id="deactivateEmployeeModal"
     tabindex="-1"
     aria-labelledby="deactivateEmployeeModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 shadow">

            <form
                id="deactivateEmployeeForm"
                method="POST"
                action=""
            >
                @csrf

                <div class="modal-header">

                    <h5
                        class="modal-title fw-bold"
                        id="deactivateEmployeeModalLabel"
                    >
                        <i class="bi bi-person-x me-2 text-danger"></i>
                        Nonaktifkan Pegawai
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>

                </div>


                <div class="modal-body">

                    <div class="alert alert-warning">

                        Pegawai:

                        <strong id="deactivateEmployeeName">
                            -
                        </strong>

                        <div class="small mt-1">
                            Data absensi dan riwayat pegawai tidak akan dihapus.
                        </div>

                    </div>


                    <div class="mb-3">

                        <label
                            for="tanggal_keluar"
                            class="form-label fw-semibold"
                        >
                            Tanggal Terakhir Bekerja
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="date"
                            name="tanggal_keluar"
                            id="tanggal_keluar"
                            class="form-control"
                            required
                        >

                        <div class="form-text">
                            Pegawai masih dihitung aktif sampai tanggal ini.
                        </div>

                    </div>


                    <div>

                        <label
                            for="keterangan_keluar"
                            class="form-label fw-semibold"
                        >
                            Keterangan
                        </label>

                        <textarea
                            name="keterangan"
                            id="keterangan_keluar"
                            class="form-control"
                            rows="3"
                            placeholder="Contoh: Resign, pensiun, pindah tempat kerja, kontrak selesai..."
                        ></textarea>

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
                        class="btn btn-danger"
                    >
                        <i class="bi bi-person-x me-1"></i>
                        Nonaktifkan Pegawai
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

{{-- MODAL PINDAH UNIT --}}
<div class="modal fade"
     id="transferUnitModal"
     tabindex="-1"
     aria-labelledby="transferUnitModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 shadow">

            <form
                id="transferUnitForm"
                method="POST"
                action=""
            >
                @csrf

                <div class="modal-header">

                    <h5
                        class="modal-title fw-bold"
                        id="transferUnitModalLabel"
                    >
                        <i class="bi bi-arrow-left-right me-2 text-success"></i>
                        Pindah Unit Pegawai
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>

                </div>


                <div class="modal-body">

                    <div class="alert alert-info">

                        Pegawai:

                        <strong id="transferEmployeeName">
                            -
                        </strong>

                        <div class="small mt-1">
                            Riwayat unit lama akan tetap disimpan.
                        </div>

                    </div>


                    {{-- UNIT TUJUAN --}}
                    <div class="mb-3">

                        <label
                            for="transfer_unit_id"
                            class="form-label fw-semibold"
                        >
                            Unit Tujuan
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="unit_id"
                            id="transfer_unit_id"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Pilih Unit Tujuan
                            </option>

                            @foreach($units as $unit)

                                <option value="{{ $unit->id }}">
                                    {{ $unit->nama }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    {{-- JADWAL BARU --}}
                    <div class="mb-3">

                        <label
                            for="transfer_work_schedule_id"
                            class="form-label fw-semibold"
                        >
                            Jadwal Baru
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="work_schedule_id"
                            id="transfer_work_schedule_id"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Pilih Jadwal Baru
                            </option>

                            @foreach($workSchedules as $schedule)

                                <option value="{{ $schedule->id }}">
                                    {{ $schedule->nama }}
                                </option>

                            @endforeach

                        </select>

                        <div class="form-text">
                            Jadwal ini mulai berlaku pada tanggal pindah unit.
                        </div>

                    </div>


                    {{-- TANGGAL PINDAH --}}
                    <div class="mb-3">

                        <label
                            for="tanggal_pindah"
                            class="form-label fw-semibold"
                        >
                            Mulai Pindah Unit
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="date"
                            name="tanggal_pindah"
                            id="tanggal_pindah"
                            class="form-control"
                            required
                        >

                        <div class="form-text">
                            Mulai tanggal ini pegawai dianggap berada di unit baru.
                        </div>

                    </div>


                    {{-- KETERANGAN --}}
                    <div>

                        <label
                            for="keterangan_pindah"
                            class="form-label fw-semibold"
                        >
                            Keterangan
                        </label>

                        <textarea
                            name="keterangan"
                            id="keterangan_pindah"
                            class="form-control"
                            rows="3"
                            placeholder="Contoh: Mutasi dari UM ke Elementary..."
                        ></textarea>

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
                        class="btn btn-success"
                    >
                        <i class="bi bi-arrow-left-right me-1"></i>
                        Pindahkan Unit
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection


@push('addon-script')

{{-- JQUERY --}}
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

{{-- DATATABLES --}}
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>


<script>

$(document).ready(function () {

    const table = $('#employeeTable').DataTable({

        processing: true,
        serverSide: true,

        responsive: false,
        autoWidth: false,

        pageLength: 25,

        lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100]
        ],

        order: [
            [2, 'asc']
        ],


        ajax: {

            url: "{{ route('employee.datatable') }}",

            type: "GET",

            data: function (d) {

                d.unit_id =
                    $('#filterUnit').val();

                d.role =
                    $('#filterRole').val();

                d.schedule_status =
                    $('#filterSchedule').val();
            },

            error: function (xhr) {

                console.error(
                    'Employee DataTables Error:',
                    xhr.responseText
                );
            }
        },


        columns: [

            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },

            {
                data: 'uid',
                name: 'uid',

                render: function (data) {

                    return `
                        <span class="uid-badge">
                            ${data ?? '-'}
                        </span>
                    `;
                }
            },

            {
                data: 'nama',
                name: 'nama',

                render: function (data) {

                    return `
                        <div class="employee-name">
                            ${data ?? '-'}
                        </div>
                    `;
                }
            },

            {
                data: 'unit',
                name: 'unit',
                orderable: false,
                searchable: false,

                render: function (data) {

                    return `
                        <span class="unit-text">
                            ${data ?? '-'}
                        </span>
                    `;
                }
            },

            {
                data: 'jabatan',
                name: 'role',
                orderable: true,
                searchable: true
            },

            {
                data: 'jadwal',
                name: 'jadwal',
                orderable: false,
                searchable: false
            },

            {
                data: 'aksi',
                name: 'aksi',
                orderable: false,
                searchable: false
            }

        ],


        language: {

            processing:
                'Memuat data pegawai...',

            search:
                'Cari:',

            searchPlaceholder:
                'Nama atau UID...',

            lengthMenu:
                'Tampilkan _MENU_ data',

            info:
                'Menampilkan _START_ - _END_ dari _TOTAL_ pegawai',

            infoEmpty:
                'Tidak ada data pegawai',

            infoFiltered:
                '(difilter dari _MAX_ pegawai)',

            zeroRecords:
                'Data pegawai tidak ditemukan',

            emptyTable:
                'Belum ada data pegawai',

            paginate: {

                first:
                    'Pertama',

                last:
                    'Terakhir',

                next:
                    'Berikutnya',

                previous:
                    'Sebelumnya'
            }
        }

    });


    // =====================================================
    // FILTER UNIT
    // =====================================================
    $('#filterUnit').on(
        'change',
        function () {

            table.ajax.reload();
        }
    );


    // =====================================================
    // FILTER JABATAN
    // =====================================================
    $('#filterRole').on(
        'change',
        function () {

            table.ajax.reload();
        }
    );


    // =====================================================
    // FILTER JADWAL
    // =====================================================
    $('#filterSchedule').on(
        'change',
        function () {

            table.ajax.reload();
        }
    );


    // =====================================================
    // RESET FILTER
    // =====================================================
    $('#resetFilter').on(
        'click',
        function () {

            $('#filterUnit')
                .val('');

            $('#filterRole')
                .val('');

            $('#filterSchedule')
                .val('');

            table.search('');

            table.ajax.reload();
        }
    );

    // =====================================================
    // NONAKTIFKAN / PEGAWAI KELUAR
    // =====================================================
    $(document).on(
        'click',
        '.btn-deactivate-employee',
        function () {

            const employeeId =
                $(this).data('id');

            const employeeName =
                $(this).data('nama');

            const urlTemplate =
                @json(
                    route(
                        'employee.deactivate',
                        ['employee' => '__EMPLOYEE_ID__']
                    )
                );

            const actionUrl =
                urlTemplate.replace(
                    '__EMPLOYEE_ID__',
                    employeeId
                );

            // Nama pegawai
            $('#deactivateEmployeeName')
                .text(employeeName);

            // Route form
            $('#deactivateEmployeeForm')
                .attr('action', actionUrl);

            // Reset input
            $('#tanggal_keluar')
                .val('');

            $('#keterangan_keluar')
                .val('');

            // Buka modal
            const modalElement =
                document.getElementById(
                    'deactivateEmployeeModal'
                );

            const modal =
                bootstrap.Modal.getOrCreateInstance(
                    modalElement
                );

            modal.show();
        }
    );

    // =====================================================
    // PINDAH UNIT
    // =====================================================
    $(document).on(
        'click',
        '.btn-transfer-unit',
        function () {

            const employeeId =
                $(this).data('id');

            const employeeName =
                $(this).data('nama');

            const currentUnitId =
                String($(this).data('unit-id'));

            const urlTemplate =
                @json(
                    route(
                        'employee.transferUnit',
                        ['employee' => '__EMPLOYEE_ID__']
                    )
                );

            const actionUrl =
                urlTemplate.replace(
                    '__EMPLOYEE_ID__',
                    employeeId
                );

            // Nama pegawai
            $('#transferEmployeeName')
                .text(employeeName);

            // Route form
            $('#transferUnitForm')
                .attr('action', actionUrl);

            // Reset form
            $('#transfer_unit_id')
                .val('');

            $('#tanggal_pindah')
                .val('');

            $('#keterangan_pindah')
                .val('');

            // Aktifkan semua pilihan unit dulu
            $('#transfer_unit_id option')
                .prop('disabled', false);

            // Unit saat ini tidak boleh dipilih
            $('#transfer_unit_id option[value="' + currentUnitId + '"]')
                .prop('disabled', true);

            // Buka modal
            const modalElement =
                document.getElementById(
                    'transferUnitModal'
                );

            const modal =
                bootstrap.Modal.getOrCreateInstance(
                    modalElement
                );

            modal.show();
        }
    );

});

</script>

@endpush