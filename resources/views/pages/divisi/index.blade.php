@extends('layouts.admin')

@section('title', 'Master Bidang / Divisi')

@section('content')

<style>
    .division-page {
        padding: 24px;
    }

    .division-header {
        margin-bottom: 24px;
    }

    .division-header h2 {
        margin-bottom: 5px;
        font-weight: 700;
        color: #1f2937;
    }

    .division-header p {
        margin: 0;
        color: #6b7280;
    }

    .division-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,0,0,.04);
        overflow: hidden;
    }

    .division-card-header {
        padding: 18px 20px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }

    .division-card-header h5 {
        margin: 0;
        font-weight: 700;
        color: #1f2937;
    }

    .division-table {
        margin: 0;
    }

    .division-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .division-table tbody td {
        padding: 15px 16px;
        vertical-align: middle;
        border-color: #f1f5f9;
    }

    .division-table tbody tr:hover {
        background: #fafafa;
    }

    .unit-badge {
        display: inline-flex;
        padding: 5px 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
    }

    .division-name {
        font-weight: 700;
        color: #1f2937;
    }

    .employee-count {
        display: inline-flex;
        padding: 5px 9px;
        background: #eef2ff;
        color: #4338ca;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    .btn-action {
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        padding: 6px 11px;
    }

    .empty-box {
        padding: 50px 20px;
        text-align: center;
        color: #9ca3af;
    }

    .modal-content {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
    }

    .modal-header {
        border-bottom: 1px solid #e5e7eb;
    }

    .modal-footer {
        border-top: 1px solid #e5e7eb;
    }

    @media(max-width: 768px) {
        .division-page {
            padding: 15px;
        }
    }
</style>


<div class="division-page">

    {{-- =====================================================
        HEADER
    ====================================================== --}}
    <div class="division-header">

        <h2>
            Master Bidang / Divisi
        </h2>

        <p>
            Kelola bidang kerja yang digunakan untuk menentukan struktur dan jalur approval pegawai.
        </p>

    </div>


    {{-- =====================================================
        ALERT SUCCESS
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
        ALERT ERROR
    ====================================================== --}}
    @if(session('error'))

        <div class="alert alert-danger alert-dismissible fade show">

            {{ session('error') }}

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


    <div class="division-card">

        {{-- =================================================
            CARD HEADER
        ================================================== --}}
        <div class="division-card-header">

            <div>

                <h5>
                    Daftar Bidang / Divisi
                </h5>

                <div class="small text-muted mt-1">
                    Bidang akan digunakan untuk menghubungkan Staff dengan Kepala Bidang.
                </div>

            </div>


            <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#addDivisionModal"
            >
                + Tambah Bidang
            </button>

        </div>


        {{-- =================================================
            TABLE
        ================================================== --}}
        <div class="table-responsive">

            <table class="table division-table">

                <thead>

                    <tr>

                        <th width="60">
                            No
                        </th>

                        <th>
                            Unit
                        </th>

                        <th>
                            Nama Bidang / Divisi
                        </th>

                        <th>
                            Jumlah Pegawai
                        </th>

                        <th width="180">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($divisions as $division)

                        <tr>

                            {{-- NO --}}
                            <td class="text-muted">
                                {{ $loop->iteration }}
                            </td>


                            {{-- UNIT --}}
                            <td>

                                <span class="unit-badge">

                                    {{ $division->unit?->nama ?? '-' }}

                                </span>

                            </td>


                            {{-- NAMA --}}
                            <td>

                                <div class="division-name">

                                    {{ $division->nama }}

                                </div>

                            </td>


                            {{-- JUMLAH EMPLOYEE --}}
                            <td>

                                <span class="employee-count">

                                    {{ $division->employees_count }}
                                    pegawai

                                </span>

                            </td>


                            {{-- AKSI --}}
                            <td>

                                <div class="d-flex gap-2">

                                    {{-- EDIT --}}
                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm btn-action"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editDivisionModal{{ $division->id }}"
                                    >
                                        Edit
                                    </button>


                                    {{-- DELETE --}}
                                    <form
                                        action="{{ route(
                                            'division.destroy',
                                            $division->id
                                        ) }}"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus bidang ini?')"
                                    >

                                        @csrf
                                        @method('DELETE')


                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm btn-action"
                                            {{ $division->employees_count > 0 ? 'disabled' : '' }}
                                        >
                                            Hapus
                                        </button>

                                    </form>

                                </div>


                                @if($division->employees_count > 0)

                                    <div class="small text-muted mt-2">

                                        Tidak dapat dihapus karena masih digunakan.

                                    </div>

                                @endif

                            </td>

                        </tr>


                        {{-- =========================================
                            MODAL EDIT
                        ========================================== --}}
                        <div
                            class="modal fade"
                            id="editDivisionModal{{ $division->id }}"
                            tabindex="-1"
                            aria-hidden="true"
                        >

                            <div class="modal-dialog modal-dialog-centered">

                                <div class="modal-content">

                                    <form
                                        action="{{ route(
                                            'division.update',
                                            $division->id
                                        ) }}"
                                        method="POST"
                                    >

                                        @csrf
                                        @method('PUT')


                                        <div class="modal-header">

                                            <h5 class="modal-title">

                                                Edit Bidang / Divisi

                                            </h5>

                                            <button
                                                type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"
                                            >
                                            </button>

                                        </div>


                                        <div class="modal-body">

                                            {{-- UNIT --}}
                                            <div class="mb-3">

                                                <label class="form-label fw-semibold">

                                                    Unit

                                                    <span class="text-danger">
                                                        *
                                                    </span>

                                                </label>


                                                <select
                                                    name="unit_id"
                                                    class="form-select"
                                                    required
                                                >

                                                    @foreach($units as $unit)

                                                        <option
                                                            value="{{ $unit->id }}"
                                                            @selected(
                                                                $division->unit_id
                                                                == $unit->id
                                                            )
                                                        >

                                                            {{ $unit->nama }}

                                                        </option>

                                                    @endforeach

                                                </select>


                                                @if($division->employees_count > 0)

                                                    <div class="form-text">

                                                        Unit tidak dapat dipindahkan jika bidang sudah mempunyai pegawai.

                                                    </div>

                                                @endif

                                            </div>


                                            {{-- NAMA --}}
                                            <div>

                                                <label class="form-label fw-semibold">

                                                    Nama Bidang / Divisi

                                                    <span class="text-danger">
                                                        *
                                                    </span>

                                                </label>


                                                <input
                                                    type="text"
                                                    name="nama"
                                                    value="{{ $division->nama }}"
                                                    class="form-control"
                                                    required
                                                    maxlength="255"
                                                >

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


                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="empty-box"
                            >

                                <div class="fw-semibold">
                                    Belum ada Bidang / Divisi
                                </div>

                                <div class="small mt-1">
                                    Klik tombol "Tambah Bidang" untuk membuat bidang baru.
                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>


{{-- =========================================================
    MODAL TAMBAH
========================================================== --}}
<div
    class="modal fade"
    id="addDivisionModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form
                action="{{ route('division.store') }}"
                method="POST"
            >

                @csrf


                <div class="modal-header">

                    <h5 class="modal-title">

                        Tambah Bidang / Divisi

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    >
                    </button>

                </div>


                <div class="modal-body">

                    {{-- UNIT --}}
                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Unit

                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <select
                            name="unit_id"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- Pilih Unit --
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


                    {{-- NAMA --}}
                    <div>

                        <label class="form-label fw-semibold">

                            Nama Bidang / Divisi

                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            name="nama"
                            value="{{ old('nama') }}"
                            class="form-control"
                            placeholder="Contoh: Pendidikan dan Pengembangan"
                            required
                            maxlength="255"
                        >


                        <div class="form-text">

                            Nama bidang tidak boleh sama dalam unit yang sama.

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
                        Tambah Bidang
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection