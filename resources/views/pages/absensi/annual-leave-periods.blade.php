@extends('layouts.admin')

@section('title', 'Tahun Ajaran Cuti')

@section('content')

<style>
    .leave-page {
        color: #263449;
        font-size: 15px;
    }

    .leave-page * {
        box-sizing: border-box;
    }

    .leave-page .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 22px;
    }

    .leave-page .page-title {
        margin: 0 0 6px;
        font-size: 24px;
        font-weight: 750;
        line-height: 1.35;
        color: #253650;
    }

    .leave-page .page-subtitle {
        margin: 0;
        color: #748197;
        font-size: 15px;
        line-height: 1.6;
    }

    .leave-page .page-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 54px;
        height: 54px;
        flex: 0 0 54px;
        border-radius: 14px;
        background: #eaf0ff;
        font-size: 27px;
    }

    .leave-page .panel {
        background: #fff;
        border: 1px solid #e4e9f1;
        border-radius: 12px;
        margin-bottom: 22px;
        box-shadow: 0 3px 12px rgba(35, 53, 83, .035);
        overflow: hidden;
    }

    .leave-page .panel-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 18px 22px;
        border-bottom: 1px solid #edf0f5;
    }

    .leave-page .panel-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border-radius: 10px;
        background: #f0f4ff;
        font-size: 21px;
    }

    .leave-page .panel-title {
        margin: 0;
        font-size: 18px;
        font-weight: 750;
        line-height: 1.4;
        color: #293a54;
    }

    .leave-page .panel-description {
        margin: 4px 0 0;
        font-size: 14px;
        color: #7a8799;
        line-height: 1.55;
    }

    .leave-page .panel-body {
        padding: 22px;
    }

    .leave-page .rule-box {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 15px 17px;
        margin-bottom: 22px;
        border: 1px solid #dbe6ff;
        border-radius: 10px;
        background: #f4f7ff;
    }

    .leave-page .rule-icon {
        font-size: 22px;
        line-height: 1.4;
    }

    .leave-page .rule-title {
        margin: 0 0 4px;
        color: #294d91;
        font-size: 15px;
        font-weight: 750;
    }

    .leave-page .rule-text {
        margin: 0;
        color: #536b91;
        font-size: 14px;
        line-height: 1.7;
    }

    .leave-page .form-group {
        margin-bottom: 20px;
    }

    .leave-page .form-label {
        display: block;
        margin-bottom: 8px;
        color: #35445a;
        font-size: 15px;
        font-weight: 700;
    }

    .leave-page .form-control {
        width: 100%;
        height: 44px;
        border: 1px solid #d8e0eb;
        border-radius: 8px;
        padding: 9px 12px;
        font-size: 15px;
        color: #293a50;
        box-shadow: none;
        transition: border-color .15s, box-shadow .15s;
    }

    .leave-page .form-control:focus {
        border-color: #5279ee;
        box-shadow: 0 0 0 3px rgba(82, 121, 238, .12);
    }

    .leave-page .form-help {
        display: block;
        margin-top: 6px;
        color: #8792a3;
        font-size: 13px;
        line-height: 1.55;
    }

    .leave-page .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 42px;
        padding: 9px 15px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        white-space: normal;
        line-height: 1.45;
    }

    .leave-page .btn-primary {
        background: #365fe8;
        border-color: #365fe8;
    }

    .leave-page .btn-primary:hover {
        background: #284dcc;
        border-color: #284dcc;
    }

    .leave-page .btn-outline-primary {
        color: #365fe8;
        border-color: #ccd7ff;
        background: #fff;
    }

    .leave-page .btn-outline-primary:hover {
        color: #fff;
        background: #365fe8;
        border-color: #365fe8;
    }

    .leave-page .btn-success {
        background: #e8f7ef;
        border-color: #d3efdf;
        color: #187449;
        cursor: default;
        opacity: 1;
    }

    .leave-page .btn:disabled {
        opacity: 1;
    }

    .leave-page .form-actions {
        display: flex;
        justify-content: flex-end;
        padding-top: 5px;
    }

    .leave-page .alert {
        border-radius: 9px;
        padding: 13px 16px;
        font-size: 14px;
        line-height: 1.65;
        margin-bottom: 18px;
    }

    .leave-page .alert ul {
        margin-bottom: 0;
        padding-left: 22px;
    }

    .leave-page .table-responsive {
        width: 100%;
    }

    .leave-page .period-table {
        width: 100%;
        margin: 0;
        font-size: 14px;
    }

    .leave-page .period-table thead th {
        padding: 14px 17px;
        background: #f7f9fc;
        border-top: 0;
        border-bottom: 1px solid #e7ecf3;
        color: #65738a;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .45px;
        text-transform: uppercase;
        vertical-align: middle;
        white-space: nowrap;
    }

    .leave-page .period-table tbody td {
        padding: 16px 17px;
        border-top: 1px solid #edf0f5;
        vertical-align: middle;
    }

    .leave-page .period-table tbody tr:first-child td {
        border-top: 0;
    }

    .leave-page .period-table tbody tr:hover {
        background: #fbfcff;
    }

    .leave-page .period-name {
        color: #2d3e57;
        font-size: 15px;
        font-weight: 750;
        margin-bottom: 4px;
    }

    .leave-page .period-id {
        color: #8994a5;
        font-size: 12px;
    }

    .leave-page .date-line {
        margin: 3px 0;
        color: #66758b;
        white-space: nowrap;
    }

    .leave-page .date-line strong {
        display: inline-block;
        min-width: 54px;
        color: #47566c;
        font-weight: 650;
    }

    .leave-page .status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 10px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 750;
        white-space: nowrap;
    }

    .leave-page .status-active {
        color: #176c43;
        background: #e2f6eb;
    }

    .leave-page .status-inactive {
        color: #657187;
        background: #eef1f5;
    }

    .leave-page .action-cell {
        min-width: 165px;
    }

    .leave-page .action-help {
        margin-top: 6px;
        color: #8994a5;
        font-size: 12px;
        line-height: 1.5;
    }

    .leave-page .empty-state {
        padding: 38px 18px !important;
        text-align: center;
    }

    .leave-page .empty-icon {
        margin-bottom: 10px;
        font-size: 34px;
    }

    .leave-page .empty-title {
        margin-bottom: 5px;
        color: #35445a;
        font-size: 16px;
        font-weight: 750;
    }

    .leave-page .empty-text {
        color: #8490a1;
        font-size: 14px;
    }

    .leave-page .quick-links {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .leave-page .quick-link {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 66px;
        padding: 13px 15px;
        border: 1px solid #e3e9f3;
        border-radius: 9px;
        color: #344661;
        background: #fff;
        text-decoration: none;
        transition: border-color .15s, background .15s;
    }

    .leave-page .quick-link:hover {
        border-color: #b9caff;
        background: #f8faff;
        color: #3159d5;
        text-decoration: none;
    }

    .leave-page .quick-link-icon {
        font-size: 24px;
    }

    .leave-page .quick-link-title {
        font-size: 14px;
        font-weight: 750;
        margin-bottom: 3px;
    }

    .leave-page .quick-link-desc {
        color: #8490a1;
        font-size: 12px;
        line-height: 1.5;
    }

    @media (max-width: 767px) {
        .leave-page .page-header {
            align-items: flex-start;
        }

        .leave-page .page-title {
            font-size: 21px;
        }

        .leave-page .page-icon {
            width: 45px;
            height: 45px;
            flex-basis: 45px;
            font-size: 23px;
        }

        .leave-page .panel-header {
            padding: 16px;
        }

        .leave-page .panel-body {
            padding: 16px;
        }

        .leave-page .form-actions {
            justify-content: stretch;
        }

        .leave-page .form-actions .btn {
            width: 100%;
        }

        .leave-page .quick-links {
            grid-template-columns: 1fr;
        }

        .leave-page .period-table thead th,
        .leave-page .period-table tbody td {
            padding: 13px 12px;
        }
    }
</style>

<div class="app-main">
    <div class="app-content-header">
    <div class="container-fluid">
        {{-- HEADER --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">Tahun Ajaran Cuti</h1>
                <p class="page-subtitle">
                    Kelola periode dan pengaturan tahun ajaran untuk Cuti Tahunan pegawai.
                </p>
            </div>

            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <a href="{{ route('annualLeaveAdmin.statistics') }}"
                class="btn btn-outline-primary"
                style="text-decoration:none; min-height:42px; display:inline-flex; align-items:center; gap:8px; border-radius:8px; padding:9px 15px; font-weight:700;">
                    ← Kembali ke Statistik
                </a>

                <div class="page-icon">📅</div>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <div class="leave-page">

    {{-- FLASH MESSAGE --}}
    @if (session('success'))
        <div class="alert alert-success">
            <strong>Berhasil.</strong> {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Periksa kembali data berikut:</strong>
            <ul class="mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ATURAN CUTI --}}
    <div class="rule-box">
        <div class="rule-icon">💡</div>
        <div>
            <h3 class="rule-title">Ketentuan Cuti Tahunan</h3>
            <p class="rule-text">
                Jatah cuti adalah <strong>17 hari kerja per tahun ajaran</strong>.
                Perhitungan mengikuti jadwal kerja dan kalender pegawai.
                Saldo yang tidak terpakai tidak otomatis dibawa ke periode berikutnya.
            </p>
        </div>
    </div>

    {{-- FORM TAMBAH PERIODE --}}
    <div class="panel">
        <div class="panel-header">
            <div class="panel-icon">➕</div>
            <div>
                <h2 class="panel-title">Buat Tahun Ajaran Baru</h2>
                <p class="panel-description">
                    Masukkan nama dan tanggal periode yang akan ditambahkan.
                </p>
            </div>
        </div>

        <div class="panel-body">
            <form
                action="{{ route('annualLeaveAdmin.periods.store') }}"
                method="POST"
            >
                @csrf

                <div class="form-group">
                    <label for="name" class="form-label">
                        Nama Tahun Ajaran
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}"
                        placeholder="Contoh: Tahun Ajaran 2027/2028"
                        maxlength="50"
                        required
                    >

                    @error('name')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror

                    <small class="form-help">
                        Contoh penamaan: Tahun Ajaran 2027/2028.
                    </small>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_date" class="form-label">
                                Tanggal Mulai
                            </label>

                            <input
                                type="date"
                                id="start_date"
                                name="start_date"
                                class="form-control @error('start_date') is-invalid @enderror"
                                value="{{ old('start_date') }}"
                                required
                            >

                            @error('start_date')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_date" class="form-label">
                                Tanggal Selesai
                            </label>

                            <input
                                type="date"
                                id="end_date"
                                name="end_date"
                                class="form-control @error('end_date') is-invalid @enderror"
                                value="{{ old('end_date') }}"
                                required
                            >

                            @error('end_date')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mb-3">
                    <strong>Perhatian:</strong>
                    Periode baru disimpan dalam status belum aktif.
                    Tanggal tidak boleh bertumpang tindih dengan periode yang sudah terdaftar.
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        💾 Simpan Tahun Ajaran
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- TABEL PERIODE --}}
    <div class="panel">
        <div class="panel-header">
            <div class="panel-icon">📚</div>
            <div>
                <h2 class="panel-title">Daftar Tahun Ajaran</h2>
                <p class="panel-description">
                    Lihat periode yang tersimpan dan pilih periode aktif.
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table period-table">
                <thead>
                    <tr>
                        <th style="width: 55px;">No.</th>
                        <th>Nama Periode</th>
                        <th>Rentang Tanggal</th>
                        <th>Status</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($periods as $period)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td>
                                <div class="period-name">
                                    {{ $period->name }}
                                </div>
                                <div class="period-id">
                                    ID Periode: {{ $period->id }}
                                </div>
                            </td>

                            <td>
                                <div class="date-line">
                                    <strong>Mulai</strong>
                                    {{ $period->start_date->format('d/m/Y') }}
                                </div>
                                <div class="date-line">
                                    <strong>Selesai</strong>
                                    {{ $period->end_date->format('d/m/Y') }}
                                </div>
                            </td>

                            <td>
                                @if ($period->is_active)
                                    <span class="status status-active">
                                        ● Aktif
                                    </span>
                                @else
                                    <span class="status status-inactive">
                                        ○ Belum Aktif
                                    </span>
                                @endif
                            </td>

                            <td class="action-cell">
                                @if ($period->is_active)
                                    <button
                                        type="button"
                                        class="btn btn-success"
                                        disabled
                                    >
                                        ✓ Periode Aktif
                                    </button>
                                @else
                                    <form
                                        action="{{ route('annualLeaveAdmin.periods.activate', $period) }}"
                                        method="POST"
                                        onsubmit="return confirm('Aktifkan periode {{ $period->name }}? Periode ini akan menggantikan periode aktif saat ini untuk pengaturan Cuti Tahunan.');"
                                    >
                                        @csrf

                                        <button type="submit" class="btn btn-outline-primary">
                                            Aktifkan Periode
                                        </button>
                                    </form>

                                    <div class="action-help">
                                        Mengganti periode aktif saat ini.
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">
                                <div class="empty-icon">📭</div>
                                <div class="empty-title">
                                    Belum Ada Tahun Ajaran
                                </div>
                                <div class="empty-text">
                                    Tambahkan tahun ajaran melalui formulir di atas.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MENU TERKAIT --}}
    <div class="panel">
        <div class="panel-header">
            <div class="panel-icon">🔗</div>
            <div>
                <h2 class="panel-title">Menu Pengelolaan Cuti</h2>
                <p class="panel-description">
                    Akses pengaturan hak cuti pegawai dan statistik saldo cuti.
                </p>
            </div>
        </div>

        <div class="panel-body">
            <div class="quick-links">
                <a
                    href="{{ route('annualLeaveAdmin.index') }}"
                    class="quick-link"
                >
                    <div class="quick-link-icon">👥</div>
                    <div>
                        <div class="quick-link-title">Hak Cuti Pegawai</div>
                        <div class="quick-link-desc">
                            Atur pegawai yang berhak mendapatkan cuti.
                        </div>
                    </div>
                </a>

                <a
                    href="{{ route('annualLeaveAdmin.statistics') }}"
                    class="quick-link"
                >
                    <div class="quick-link-icon">📊</div>
                    <div>
                        <div class="quick-link-title">Statistik Cuti</div>
                        <div class="quick-link-desc">
                            Lihat penggunaan dan sisa saldo cuti.
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

</div>
    </div>
</div>
</div>

@endsection