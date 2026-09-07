@extends('layouts.admin')

@section('title', 'Manajemen User')

@section('content')

<style>
    .user-page {
        padding: 24px;
    }

    .user-header {
        margin-bottom: 24px;
    }

    .user-header h2 {
        margin-bottom: 5px;
        font-weight: 700;
        color: #1f2937;
    }

    .user-header p {
        margin: 0;
        color: #6b7280;
    }

    .user-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,0,0,.04);
        overflow: hidden;
    }

    .user-card-header {
        padding: 18px 20px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .user-card-header h5 {
        margin: 0;
        font-weight: 700;
        color: #1f2937;
    }

    .user-table {
        margin-bottom: 0;
    }

    .user-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .user-table tbody td {
        padding: 15px 16px;
        vertical-align: middle;
        border-color: #f1f5f9;
    }

    .user-table tbody tr:hover {
        background: #fafafa;
    }

    .user-box {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .user-avatar {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 12px;
        background: #eef2ff;
        color: #4338ca;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .user-name {
        font-weight: 700;
        color: #1f2937;
    }

    .user-email {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 2px;
    }

    .role-badge {
        display: inline-flex;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    .role-admin {
        background: #fee2e2;
        color: #b91c1c;
    }

    .role-pimpinan {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .position-badge {
        display: inline-flex;
        padding: 5px 9px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: 12px;
        color: #475569;
        font-weight: 600;
    }

    .employee-name {
        font-weight: 600;
        color: #374151;
    }

    .employee-info {
        color: #94a3b8;
        font-size: 12px;
        margin-top: 2px;
    }

    .btn-action {
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        padding: 6px 11px;
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

    .account-note {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        padding: 12px 14px;
        border-radius: 10px;
        font-size: 13px;
        color: #1e40af;
    }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #94a3b8;
    }

    @media(max-width:768px) {
        .user-page {
            padding: 15px;
        }
    }
</style>


<div class="user-page">

    {{-- =====================================================
        HEADER
    ====================================================== --}}
    <div class="user-header">

        <h2>
            Manajemen User / Akun
        </h2>

        <p>
            Kelola akun Administrator dan Pimpinan yang dapat masuk ke sistem.
        </p>

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
        ERROR
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
        VALIDATION
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


    {{-- =====================================================
        CARD
    ====================================================== --}}
    <div class="user-card">

        <div class="user-card-header">

            <div>

                <h5>
                    Daftar Akun
                </h5>

                <div class="small text-muted mt-1">
                    Pimpinan harus terhubung dengan data pegawai.
                </div>

            </div>


            <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#addUserModal"
            >
                + Buat Akun
            </button>

        </div>


        <div class="table-responsive">

            <table class="table user-table">

                <thead>

                    <tr>

                        <th width="60">
                            No
                        </th>

                        <th>
                            Akun
                        </th>

                        <th>
                            Hak Akses
                        </th>

                        <th>
                            Terhubung Pegawai
                        </th>

                        <th>
                            Jabatan
                        </th>

                        <th>
                            Unit / Bidang
                        </th>

                        <th width="180">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($users as $user)

                        @php
                            $initial = mb_strtoupper(
                                mb_substr($user->name, 0, 1)
                            );
                        @endphp


                        <tr>

                            {{-- NO --}}
                            <td class="text-muted">
                                {{ $loop->iteration }}
                            </td>


                            {{-- ACCOUNT --}}
                            <td>

                                <div class="user-box">

                                    <div class="user-avatar">
                                        {{ $initial }}
                                    </div>

                                    <div>

                                        <div class="user-name">
                                            {{ $user->name }}
                                        </div>

                                        <div class="user-email">
                                            {{ $user->email }}
                                        </div>

                                    </div>

                                </div>

                            </td>


                            {{-- ROLE LOGIN --}}
                            <td>

                                @if($user->role === 'Admin')

                                    <span class="role-badge role-admin">
                                        Administrator
                                    </span>

                                @else

                                    <span class="role-badge role-pimpinan">
                                        Pimpinan
                                    </span>

                                @endif

                            </td>


                            {{-- EMPLOYEE --}}
                            <td>

                                @if($user->employee)

                                    <div class="employee-name">
                                        {{ $user->employee->nama }}
                                    </div>

                                    <div class="employee-info">
                                        UID: {{ $user->employee->uid }}
                                    </div>

                                @else

                                    <span class="text-muted">
                                        Tidak terhubung
                                    </span>

                                @endif

                            </td>


                            {{-- POSITION --}}
                            <td>

                                @if($user->employee?->role)

                                    <span class="position-badge">
                                        {{ $user->employee->role }}
                                    </span>

                                @else

                                    <span class="text-muted">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- UNIT --}}
                            <td>

                                @if($user->employee)

                                    <div class="fw-semibold">

                                        {{ $user->employee->unit?->nama ?? '-' }}

                                    </div>

                                    @if($user->employee->division)

                                        <div class="small text-muted mt-1">

                                            {{ $user->employee->division->nama }}

                                        </div>

                                    @endif

                                @else

                                    <span class="text-muted">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- ACTION --}}
                            <td>

                                <div class="d-flex gap-2">

                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm btn-action"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUserModal{{ $user->id }}"
                                    >
                                        Edit
                                    </button>


                                    @if(auth()->id() !== $user->id)

                                        <form
                                            action="{{ route(
                                                'userManagement.destroy',
                                                $user->id
                                            ) }}"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus akun ini?')"
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

                                    @else

                                        <span
                                            class="badge bg-light text-dark border"
                                        >
                                            Akun aktif
                                        </span>

                                    @endif

                                </div>

                            </td>

                        </tr>


                        {{-- ==========================================
                            MODAL EDIT
                        =========================================== --}}
                        <div
                            class="modal fade"
                            id="editUserModal{{ $user->id }}"
                            tabindex="-1"
                        >

                            <div class="modal-dialog modal-dialog-centered">

                                <div class="modal-content">

                                    <form
                                        action="{{ route(
                                            'userManagement.update',
                                            $user->id
                                        ) }}"
                                        method="POST"
                                    >

                                        @csrf
                                        @method('PUT')

                                        <input
                                            type="hidden"
                                            name="form_context"
                                            value="edit-{{ $user->id }}"
                                        >


                                        <div class="modal-header">

                                            <h5 class="modal-title">
                                                Edit Akun
                                            </h5>

                                            <button
                                                type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal">
                                            </button>

                                        </div>


                                        <div class="modal-body">

                                            {{-- NAME --}}
                                            <div class="mb-3">

                                                <label class="form-label fw-semibold">
                                                    Nama Akun
                                                </label>

                                                <input
                                                    type="text"
                                                    name="name"
                                                    class="form-control"
                                                    value="{{ old(
                                                        'form_context'
                                                    ) === 'edit-'.$user->id
                                                        ? old('name')
                                                        : $user->name }}"
                                                    required
                                                >

                                            </div>


                                            {{-- EMAIL --}}
                                            <div class="mb-3">

                                                <label class="form-label fw-semibold">
                                                    Email
                                                </label>

                                                <input
                                                    type="email"
                                                    name="email"
                                                    class="form-control"
                                                    value="{{ old(
                                                        'form_context'
                                                    ) === 'edit-'.$user->id
                                                        ? old('email')
                                                        : $user->email }}"
                                                    required
                                                >

                                            </div>


                                            {{-- ROLE --}}
                                            <div class="mb-3">

                                                <label class="form-label fw-semibold">
                                                    Hak Akses
                                                </label>

                                                <select
                                                    name="role"
                                                    class="form-select edit-role"
                                                    data-user="{{ $user->id }}"
                                                    required
                                                >

                                                    <option
                                                        value="Admin"
                                                        @selected(
                                                            $user->role === 'Admin'
                                                        )
                                                    >
                                                        Administrator
                                                    </option>

                                                    <option
                                                        value="Pimpinan"
                                                        @selected(
                                                            $user->role === 'Pimpinan'
                                                        )
                                                    >
                                                        Pimpinan
                                                    </option>

                                                </select>

                                            </div>


                                            {{-- EMPLOYEE --}}
                                            <div
                                                class="mb-3 edit-employee-wrapper"
                                                id="editEmployeeWrapper{{ $user->id }}"
                                            >

                                                <label class="form-label fw-semibold">

                                                    Hubungkan dengan Pegawai

                                                </label>


                                                <select
                                                    name="employee_id"
                                                    class="form-select"
                                                >

                                                    <option value="">
                                                        -- Tidak Terhubung --
                                                    </option>


                                                    @foreach($leadershipEmployees as $employee)

                                                        <option
                                                            value="{{ $employee->id }}"
                                                            @selected(
                                                                $user->employee_id
                                                                == $employee->id
                                                            )
                                                        >

                                                            {{ $employee->nama }}
                                                            —
                                                            {{ $employee->role }}
                                                            —
                                                            {{ $employee->unit?->nama }}

                                                        </option>

                                                    @endforeach

                                                </select>

                                            </div>


                                            {{-- PASSWORD --}}
                                            <div class="mb-3">

                                                <label class="form-label fw-semibold">
                                                    Password Baru
                                                </label>

                                                <input
                                                    type="password"
                                                    name="password"
                                                    class="form-control"
                                                    autocomplete="new-password"
                                                >

                                                <div class="form-text">
                                                    Kosongkan jika password tidak ingin diganti.
                                                </div>

                                            </div>


                                            {{-- CONFIRM --}}
                                            <div>

                                                <label class="form-label fw-semibold">
                                                    Konfirmasi Password Baru
                                                </label>

                                                <input
                                                    type="password"
                                                    name="password_confirmation"
                                                    class="form-control"
                                                    autocomplete="new-password"
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
                                colspan="7"
                                class="empty-state"
                            >
                                Belum ada akun pengguna.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>


{{-- =========================================================
    MODAL TAMBAH USER
========================================================== --}}
<div
    class="modal fade"
    id="addUserModal"
    tabindex="-1"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form
                action="{{ route('userManagement.store') }}"
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
                        Buat Akun Baru
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body">

                    <div class="account-note mb-3">

                        Untuk akun <strong>Pimpinan</strong>,
                        hubungkan akun dengan Direktur,
                        Kepala Bidang, atau Kepala Sekolah.

                    </div>


                    {{-- NAME --}}
                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Nama Akun
                        </label>

                        <input
                            type="text"
                            name="name"
                            id="addUserName"
                            value="{{ old('name') }}"
                            class="form-control"
                            required
                        >

                    </div>


                    {{-- EMAIL --}}
                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Email Login
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-control"
                            placeholder="nama@admin.com"
                            required
                        >

                    </div>


                    {{-- ROLE --}}
                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Hak Akses
                        </label>

                        <select
                            name="role"
                            id="addUserRole"
                            class="form-select"
                            required
                        >

                            <option value="Pimpinan">
                                Pimpinan
                            </option>

                            <option value="Admin">
                                Administrator
                            </option>

                        </select>

                    </div>


                    {{-- EMPLOYEE --}}
                    <div
                        class="mb-3"
                        id="addEmployeeWrapper"
                    >

                        <label class="form-label fw-semibold">

                            Hubungkan dengan Pegawai

                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <select
                            name="employee_id"
                            id="addEmployee"
                            class="form-select"
                        >

                            <option value="">
                                -- Pilih Pegawai --
                            </option>


                            @foreach($availableEmployees as $employee)

                                <option
                                    value="{{ $employee->id }}"
                                    data-name="{{ $employee->nama }}"
                                >

                                    {{ $employee->nama }}
                                    —
                                    {{ $employee->role }}
                                    —
                                    {{ $employee->unit?->nama }}

                                    @if($employee->division)
                                        —
                                        {{ $employee->division->nama }}
                                    @endif

                                </option>

                            @endforeach

                        </select>


                        @if($availableEmployees->isEmpty())

                            <div class="form-text text-warning">

                                Semua pimpinan yang sudah dikonfigurasi
                                telah mempunyai akun.

                            </div>

                        @endif

                    </div>


                    {{-- PASSWORD --}}
                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            autocomplete="new-password"
                            required
                        >

                        <div class="form-text">
                            Minimal 8 karakter.
                        </div>

                    </div>


                    {{-- PASSWORD CONFIRM --}}
                    <div>

                        <label class="form-label fw-semibold">
                            Konfirmasi Password
                        </label>

                        <input
                            type="password"
                            name="password_confirmation"
                            class="form-control"
                            autocomplete="new-password"
                            required
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
                        Buat Akun
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | FORM TAMBAH
    |--------------------------------------------------------------------------
    */

    const addRole =
        document.getElementById('addUserRole');

    const addEmployeeWrapper =
        document.getElementById('addEmployeeWrapper');

    const addEmployee =
        document.getElementById('addEmployee');

    const addUserName =
        document.getElementById('addUserName');


    function toggleAddEmployee()
    {
        const isPimpinan =
            addRole.value === 'Pimpinan';

        addEmployeeWrapper.style.display =
            isPimpinan ? 'block' : 'none';

        if (!isPimpinan) {
            addEmployee.value = '';
        }
    }


    addRole.addEventListener(
        'change',
        toggleAddEmployee
    );


    addEmployee.addEventListener(
        'change',
        function () {

            const option =
                addEmployee.options[
                    addEmployee.selectedIndex
                ];

            const employeeName =
                option.dataset.name;

            if (
                employeeName
                &&
                !addUserName.value.trim()
            ) {
                addUserName.value =
                    employeeName;
            }
        }
    );


    toggleAddEmployee();


    /*
    |--------------------------------------------------------------------------
    | FORM EDIT
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.edit-role')
        .forEach(function (select) {

            function toggle()
            {
                const userId =
                    select.dataset.user;

                const wrapper =
                    document.getElementById(
                        'editEmployeeWrapper' + userId
                    );

                if (!wrapper) {
                    return;
                }

                wrapper.style.display =
                    select.value === 'Pimpinan'
                        ? 'block'
                        : 'none';
            }


            select.addEventListener(
                'change',
                toggle
            );

            toggle();
        });


    /*
    |--------------------------------------------------------------------------
    | BUKA KEMBALI MODAL JIKA VALIDATION ERROR
    |--------------------------------------------------------------------------
    */

    const context =
        @json(old('form_context'));

    if (context === 'add') {

        const modalElement =
            document.getElementById(
                'addUserModal'
            );

        if (modalElement) {

            const modal =
                new bootstrap.Modal(
                    modalElement
                );

            modal.show();
        }

    } else if (
        context
        &&
        context.startsWith('edit-')
    ) {

        const userId =
            context.replace(
                'edit-',
                ''
            );

        const modalElement =
            document.getElementById(
                'editUserModal' + userId
            );

        if (modalElement) {

            const modal =
                new bootstrap.Modal(
                    modalElement
                );

            modal.show();
        }
    }

});
</script>

@endsection