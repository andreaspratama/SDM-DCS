@extends('layouts.admin')

@section('title', 'Approval Izin')

@push('prepend-style')
<link rel="stylesheet"
      href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.css">
@endpush

@section('content')

<main class="app-main">

    <div class="app-content-header">
        <div class="container-fluid">

            <h1 class="mb-1">
                Approval Izin Pegawai
            </h1>

            <div class="text-muted">
                Daftar pengajuan izin pegawai
            </div>

        </div>
    </div>


    <div class="app-content">

        <div class="container-fluid">

            @if(session('message'))

                <div class="alert alert-success alert-dismissible fade show">

                    {{ session('message') }}

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert">
                    </button>

                </div>

            @endif


            <div class="card shadow-sm">

                <div class="card-header bg-white">

                    <strong>
                        📄 Daftar Pengajuan Izin
                    </strong>

                </div>


                <div class="card-body">


                    {{-- =====================================================
                        FILTER
                    ====================================================== --}}

                    <div class="row g-3 mb-4">

                        <div class="col-md-4">

                            <label class="form-label fw-semibold">
                                Dari Tanggal
                            </label>

                            <input type="date"
                                   id="date_from"
                                   class="form-control"
                                   value="{{ $dateFrom ?? '' }}">

                        </div>


                        <div class="col-md-4">

                            <label class="form-label fw-semibold">
                                Sampai Tanggal
                            </label>

                            <input type="date"
                                   id="date_to"
                                   class="form-control"
                                   value="{{ $dateTo ?? '' }}">

                        </div>


                        <div class="col-md-4 d-flex align-items-end gap-2">

                            <button type="button"
                                    id="btnFilter"
                                    class="btn btn-primary">

                                <i class="fa-solid fa-filter me-1"></i>
                                Filter

                            </button>


                            <button type="button"
                                    id="btnReset"
                                    class="btn btn-outline-secondary">

                                <i class="fa-solid fa-rotate-left me-1"></i>
                                Reset

                            </button>

                        </div>

                    </div>


                    {{-- =====================================================
                        TABLE
                    ====================================================== --}}

                    <div class="table-responsive">

                        <table id="permissionTable"
                               class="table table-bordered table-hover align-middle w-100">

                            <thead class="table-light">

                                <tr>

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
                                        Tanggal
                                    </th>

                                    <th>
                                        Jam
                                    </th>

                                    <th>
                                        Jenis
                                    </th>

                                    <th>
                                        Keterangan
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Lampiran
                                    </th>

                                    @if(!$isTU)

                                        <th width="260">
                                            Aksi
                                        </th>

                                    @endif

                                </tr>

                            </thead>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

</main>

@endsection


@push('addon-script')

{{-- =====================================================
    JQUERY
===================================================== --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


{{-- =====================================================
    DATATABLES
===================================================== --}}
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap5.js"></script>


<script>

$(document).ready(function () {

    const table = $('#permissionTable').DataTable({

        processing: true,

        serverSide: true,

        ajax: {

            url: "{{ route('attendancePermission.datatable') }}",

            type: "GET",

            data: function (d) {

                d.date_from = $('#date_from').val();

                d.date_to = $('#date_to').val();

            },

            error: function (xhr) {

                console.error(
                    'DataTables AJAX Error:',
                    xhr.status,
                    xhr.responseText
                );

            }

        },

        columns: [

            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false,
                className: 'text-center'
            },

            {
                data: 'pegawai',
                name: 'pegawai',
                orderable: false,
                searchable: true
            },

            {
                data: 'unit',
                name: 'unit',
                orderable: false,
                searchable: false
            },

            {
                data: 'tanggal',
                name: 'date_start',
                orderable: true,
                searchable: false,
                className: 'text-nowrap'
            },

            {
                data: 'jam',
                name: 'time_start',
                orderable: false,
                searchable: false
            },

            {
                data: 'type',
                name: 'type',
                orderable: true,
                searchable: true
            },

            {
                data: 'description',
                name: 'description',
                orderable: true,
                searchable: true
            },

            {
                data: 'status',
                name: 'status',
                orderable: true,
                searchable: true,
                className: 'text-center'
            },

            {
                data: 'attachment',
                name: 'attachment',
                orderable: false,
                searchable: false,
                className: 'text-center'
            }

            @if(!$isTU)
            ,

            {
                data: 'aksi',
                name: 'aksi',
                orderable: false,
                searchable: false,
                className: 'text-center'
            }
            @endif

        ],

        order: [
            [3, 'desc']
        ],

        pageLength: 10,

        lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100]
        ],

        language: {

            processing:
                'Memuat data...',

            search:
                'Cari:',

            searchPlaceholder:
                'Nama pegawai / jenis izin...',

            lengthMenu:
                'Tampilkan _MENU_ data',

            info:
                'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',

            infoEmpty:
                'Tidak ada data',

            infoFiltered:
                '(difilter dari _MAX_ total data)',

            zeroRecords:
                'Data tidak ditemukan',

            emptyTable:
                'Belum ada pengajuan izin',

            paginate: {

                first:
                    'Awal',

                last:
                    'Akhir',

                next:
                    '›',

                previous:
                    '‹'

            }

        }

    });


    // =====================================================
    // FILTER
    // =====================================================

    $('#btnFilter').on('click', function () {

        table.ajax.reload();

    });


    // =====================================================
    // RESET
    // =====================================================

    $('#btnReset').on('click', function () {

        $('#date_from').val('');

        $('#date_to').val('');

        table.ajax.reload();

    });


    // =====================================================
    // ENTER
    // =====================================================

    $('#date_from, #date_to').on('keypress', function (e) {

        if (e.which === 13) {

            e.preventDefault();

            table.ajax.reload();

        }

    });

});

</script>

@endpush