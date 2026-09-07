@extends('layouts.admin')

@section('title', 'Atur Struktur Pegawai')

@section('content')

<style>
    .org-edit-page {
        padding: 24px;
    }

    .org-edit-header {
        margin-bottom: 24px;
    }

    .org-edit-header h2 {
        font-weight: 700;
        margin-bottom: 5px;
        color: #1f2937;
    }

    .org-edit-header p {
        color: #6b7280;
        margin: 0;
    }

    .org-edit-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,0,0,.04);
        overflow: hidden;
    }

    .org-edit-card-header {
        padding: 20px 22px;
        border-bottom: 1px solid #e5e7eb;
        background: #ffffff;
    }

    .org-edit-card-header h5 {
        margin: 0;
        font-weight: 700;
        color: #1f2937;
    }

    .org-edit-card-body {
        padding: 24px;
    }

    .employee-summary {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        margin-bottom: 24px;
    }

    .employee-avatar {
        width: 52px;
        height: 52px;
        min-width: 52px;
        border-radius: 14px;
        background: #eef2ff;
        color: #4338ca;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 20px;
    }

    .employee-name {
        font-size: 17px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 3px;
    }

    .employee-meta {
        color: #6b7280;
        font-size: 13px;
    }

    .form-section-title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: #64748b;
        margin-bottom: 14px;
    }

    .info-box {
        padding: 14px 16px;
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        border-radius: 10px;
    }

    .info-label {
        font-size: 12px;
        color: #94a3b8;
        margin-bottom: 3px;
    }

    .info-value {
        color: #334155;
        font-weight: 600;
    }

    .login-box {
        border-radius: 10px;
        padding: 15px 16px;
    }

    .login-box.success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .login-box.warning {
        background: #fffbeb;
        border: 1px solid #fde68a;
    }

    .login-name {
        font-weight: 700;
        color: #374151;
    }

    .login-email {
        color: #6b7280;
        font-size: 13px;
        margin-top: 2px;
    }

    .org-help {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 16px;
        color: #1e40af;
        font-size: 13px;
        line-height: 1.6;
    }

    .org-actions {
        border-top: 1px solid #e5e7eb;
        padding-top: 20px;
        margin-top: 25px;
        display: flex;
        gap: 10px;
    }

    .org-actions .btn {
        border-radius: 9px;
        padding: 9px 18px;
        font-weight: 600;
    }

    .required-star {
        color: #dc2626;
    }

    @media(max-width: 768px) {
        .org-edit-page {
            padding: 15px;
        }

        .org-edit-card-body {
            padding: 18px;
        }
    }
</style>


@php

    $initial = mb_strtoupper(
        mb_substr($employee->nama, 0, 1)
    );

@endphp


<div class="org-edit-page">

    {{-- =====================================================
        HEADER
    ====================================================== --}}
    <div class="org-edit-header">

        <h2>
            Atur Struktur Pegawai
        </h2>

        <p>
            Tentukan jabatan dan bidang untuk membentuk jalur approval otomatis.
        </p>

    </div>


    <div class="row">

        {{-- =================================================
            FORM UTAMA
        ================================================== --}}
        <div class="col-lg-8">

            {{-- ERROR VALIDATION --}}
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


            <div class="org-edit-card">

                <div class="org-edit-card-header">

                    <h5>
                        Data Struktur Pegawai
                    </h5>

                </div>


                <div class="org-edit-card-body">

                    {{-- =========================================
                        EMPLOYEE SUMMARY
                    ========================================== --}}
                    <div class="employee-summary">

                        <div class="employee-avatar">
                            {{ $initial }}
                        </div>

                        <div>

                            <div class="employee-name">
                                {{ $employee->nama }}
                            </div>

                            <div class="employee-meta">

                                UID Fingerprint:
                                {{ $employee->uid }}

                            </div>

                        </div>

                    </div>


                    {{-- =========================================
                        DATA DASAR
                    ========================================== --}}
                    <div class="form-section-title">
                        Informasi Pegawai
                    </div>


                    <div class="row g-3 mb-4">

                        {{-- UNIT --}}
                        <div class="col-md-6">

                            <div class="info-box">

                                <div class="info-label">
                                    Unit
                                </div>

                                <div class="info-value">

                                    {{ $employee->unit?->nama ?? '-' }}

                                </div>

                            </div>

                        </div>


                        {{-- UID --}}
                        <div class="col-md-6">

                            <div class="info-box">

                                <div class="info-label">
                                    UID Fingerprint
                                </div>

                                <div class="info-value">

                                    {{ $employee->uid }}

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- =========================================
                        FORM
                    ========================================== --}}
                    <form
                        action="{{ route(
                            'employeeOrganization.update',
                            $employee->id
                        ) }}"
                        method="POST"
                    >

                        @csrf
                        @method('PUT')


                        <div class="form-section-title">
                            Struktur Organisasi
                        </div>


                        {{-- =====================================
                            JABATAN
                        ====================================== --}}
                        <div class="mb-4">

                            <label
                                for="role"
                                class="form-label fw-semibold"
                            >
                                Jabatan
                                <span class="required-star">
                                    *
                                </span>
                            </label>


                            <select
                                name="role"
                                id="role"
                                class="form-select @error('role') is-invalid @enderror"
                            >

                                <option value="">
                                    -- Belum Ditentukan --
                                </option>


                                @foreach($roles as $role)

                                    <option
                                        value="{{ $role }}"
                                        @selected(
                                            old(
                                                'role',
                                                $employee->role
                                            ) === $role
                                        )
                                    >

                                        {{ $role }}

                                    </option>

                                @endforeach

                            </select>


                            @error('role')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror


                            <div class="form-text">

                                Jabatan menentukan ke mana pengajuan
                                izin pegawai akan diarahkan.

                            </div>

                        </div>


                        {{-- =====================================
                            BIDANG / DIVISI
                        ====================================== --}}
                        <div
                            class="mb-4"
                            id="divisionWrapper"
                        >

                            <label
                                for="division_id"
                                class="form-label fw-semibold"
                            >

                                Bidang / Divisi

                                <span
                                    id="divisionRequired"
                                    class="required-star"
                                >
                                    *
                                </span>

                            </label>


                            <select
                                name="division_id"
                                id="division_id"
                                class="form-select @error('division_id') is-invalid @enderror"
                            >

                                <option value="">
                                    -- Pilih Bidang / Divisi --
                                </option>


                                @foreach($divisions as $division)

                                    <option
                                        value="{{ $division->id }}"
                                        @selected(
                                            old(
                                                'division_id',
                                                $employee->division_id
                                            ) == $division->id
                                        )
                                    >

                                        {{ $division->nama }}

                                    </option>

                                @endforeach

                            </select>


                            @error('division_id')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror


                            <div class="form-text">

                                Bidang digunakan untuk menentukan
                                Kepala Bidang yang menjadi approver.

                            </div>


                            @if($divisions->isEmpty())

                                <div
                                    class="alert alert-warning mt-3 mb-0"
                                    id="noDivisionWarning"
                                >

                                    Belum ada Bidang / Divisi pada unit
                                    <strong>
                                        {{ $employee->unit?->nama }}
                                    </strong>.

                                    Nanti bidang dapat dibuat melalui
                                    menu Master Bidang.

                                </div>

                            @endif

                        </div>


                        {{-- =====================================
                            AKUN LOGIN
                        ====================================== --}}
                        <div class="mb-4">

                            <label class="form-label fw-semibold">

                                Akun Login Pimpinan

                            </label>


                            @if($employee->user)

                                <div class="login-box success">

                                    <div class="login-name">

                                        {{ $employee->user->name }}

                                    </div>

                                    <div class="login-email">

                                        {{ $employee->user->email }}

                                    </div>

                                    <div class="small text-success mt-2">

                                        Akun sudah terhubung dengan pegawai ini.

                                    </div>

                                </div>

                            @else

                                <div class="login-box warning">

                                    <div class="fw-semibold">

                                        Belum mempunyai akun login

                                    </div>

                                    <div class="small text-muted mt-1">

                                        Pegawai biasa tidak wajib mempunyai
                                        akun. Kepala Sekolah, Kepala Bidang,
                                        dan Direktur membutuhkan akun agar
                                        dapat melakukan approval.

                                    </div>

                                </div>

                            @endif

                        </div>


                        {{-- =====================================
                            BUTTON
                        ====================================== --}}
                        <div class="org-actions">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Simpan Perubahan
                            </button>


                            <a
                                href="{{ route(
                                    'employeeOrganization.index'
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


        {{-- =================================================
            PENJELASAN ALUR
        ================================================== --}}
        <div class="col-lg-4 mt-4 mt-lg-0">

            <div class="org-edit-card">

                <div class="org-edit-card-header">

                    <h5>
                        Cara Kerja Approval
                    </h5>

                </div>


                <div class="org-edit-card-body">

                    <div class="org-help">

                        <div class="fw-bold mb-2">
                            Jalur approval otomatis
                        </div>

                        <div class="mb-3">

                            <strong>Guru</strong>
                            <br>
                            ↓
                            <br>
                            Kepala Sekolah pada unit yang sama

                        </div>


                        <div class="mb-3">

                            <strong>Staff UM</strong>
                            <br>
                            ↓
                            <br>
                            Kepala Bidang pada bidang yang sama

                        </div>


                        <div class="mb-3">

                            <strong>Kepala Sekolah</strong>
                            <br>
                            ↓
                            <br>
                            Direktur

                        </div>


                        <div class="mb-3">

                            <strong>Kepala Bidang</strong>
                            <br>
                            ↓
                            <br>
                            Direktur

                        </div>


                        <div>

                            <strong>Direktur</strong>
                            <br>
                            ↓
                            <br>
                            Puncak struktur

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
    JAVASCRIPT
========================================================== --}}
<script>

    document.addEventListener('DOMContentLoaded', function () {

        const roleSelect =
            document.getElementById('role');

        const divisionWrapper =
            document.getElementById('divisionWrapper');

        const divisionSelect =
            document.getElementById('division_id');

        const divisionRequired =
            document.getElementById('divisionRequired');

        const employeeUnitId =
            {{ (int) $employee->unit_id }};


        function toggleDivision()
        {
            const role = roleSelect.value;


            /*
             * Bidang diperlukan untuk:
             *
             * 1. Kepala Bidang
             * 2. Staff UM
             */
            const isKepalaBidang =
                role === 'Kepala Bidang';

            const isStaffUM =
                role === 'Staff'
                &&
                employeeUnitId === 1;

            const needDivision =
                isKepalaBidang || isStaffUM;


            if (needDivision) {

                divisionWrapper.style.display = 'block';

                divisionRequired.style.display = 'inline';

            } else {

                divisionWrapper.style.display = 'none';

                divisionRequired.style.display = 'none';

                /*
                 * Jangan kosongkan saat pertama load
                 * sebelum user mengganti role.
                 *
                 * Controller tetap menjadi sumber
                 * kebenaran ketika disimpan.
                 */
            }
        }


        roleSelect.addEventListener(
            'change',
            function () {

                const role = roleSelect.value;

                const needDivision =
                    role === 'Kepala Bidang'
                    ||
                    (
                        role === 'Staff'
                        &&
                        employeeUnitId === 1
                    );


                if (!needDivision) {

                    divisionSelect.value = '';

                }


                toggleDivision();
            }
        );


        toggleDivision();

    });

</script>

@endsection