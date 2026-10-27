@extends('layouts.admin')

@section('title', 'Statistik Cuti Tahunan')

@section('content')
<main class="app-main annual-leave-page">

    {{-- HEADER --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="leave-eyebrow">
                        MANAJEMEN KEPEGAWAIAN
                    </div>
                    <h1 class="leave-page-title">Statistik Cuti Tahunan</h1>
                    <p class="leave-page-subtitle">
                        Pantau hak cuti, penggunaan, dan sisa saldo cuti tahunan pegawai.
                    </p>
                </div>

                <a href="{{ route('annualLeaveAdmin.index') }}"
                   class="leave-back-button">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M15 18l-6-6 6-6"
                              stroke="currentColor" stroke-width="2"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Pengaturan Hak Cuti
                </a>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">


            {{-- PILIH PERIODE STATISTIK --}}
            <div style="background:#fff; padding:20px; margin-bottom:20px; border:1px solid #e2e8f0; border-radius:14px;">
                <label for="statistics_period_id"
                    style="display:block; font-weight:700; margin-bottom:10px; color:#172554;">
                    📅 Pilih Tahun Ajaran untuk Statistik
                </label>

                <form method="GET"
                    action="{{ route('annualLeaveAdmin.statistics') }}"
                    style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">

                    <select name="period_id"
                            id="statistics_period_id"
                            required
                            style="flex:1; min-width:220px; max-width:420px; height:44px; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; background:#fff;">

                        @foreach ($periods as $item)
                            <option value="{{ $item->id }}"
                                {{ (int) $selectedPeriodId === (int) $item->id ? 'selected' : '' }}>
                                {{ $item->name }}{{ $item->is_active ? ' (TA Aktif)' : '' }}
                            </option>
                        @endforeach

                    </select>

                    <button type="submit"
                            style="height:44px; padding:0 18px; border:0; border-radius:8px; background:#1d4ed8; color:#fff; font-weight:700; cursor:pointer;">
                        Tampilkan Statistik
                    </button>
                </form>

                <p style="margin:10px 0 0; color:#64748b; font-size:13px;">
                    Pilih tahun ajaran untuk melihat rincian saldo dan penggunaan cuti pada periode tersebut.
                </p>
            </div>


            {{-- PERIODE AKTIF --}}
            @if ($period)
                <section class="leave-period-card">
                    <div class="leave-period-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="16" rx="2"
                                  stroke="currentColor" stroke-width="1.8"/>
                            <path d="M16 3v4M8 3v4M3 10h18"
                                  stroke="currentColor" stroke-width="1.8"
                                  stroke-linecap="round"/>
                            <path d="M8 14h3M8 17h6"
                                  stroke="currentColor" stroke-width="1.8"
                                  stroke-linecap="round"/>
                        </svg>
                    </div>

                    <div class="leave-period-content">
                        <div class="leave-period-label">PERIODE CUTI AKTIF</div>

                        <h2 class="leave-period-title">
                            {{ $period->name }}
                        </h2>

                        <p class="leave-period-description">
                            {{ \Carbon\Carbon::parse($period->start_date)->format('d-m-Y') }}
                            sampai
                            {{ \Carbon\Carbon::parse($period->end_date)->format('d-m-Y') }}
                        </p>
                    </div>

                    <div class="leave-entitlement">
                        <span>Jatah standar</span>
                        <strong>17 <small>hari kerja</small></strong>
                    </div>
                </section>

                {{-- TABEL PEGAWAI --}}
                <section class="leave-table-card">
                    <div class="leave-table-header">
                        <div>
                            <h2 class="leave-table-title">Rincian Saldo Pegawai</h2>
                            <p class="leave-table-subtitle">
                                Rincian jatah, penggunaan, dan sisa cuti setiap pegawai.
                            </p>
                        </div>

                        <div class="leave-total-badge">
                            {{ number_format($employees->count(), 0, ',', '.') }}
                            pegawai
                        </div>
                    </div>

                    {{-- PENCARIAN --}}
                    <div class="leave-search-area">
                        <div class="leave-search-box">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="10.8" cy="10.8" r="6.8"
                                        stroke="currentColor" stroke-width="1.8"/>
                                <path d="m16 16 4.5 4.5"
                                      stroke="currentColor" stroke-width="1.8"
                                      stroke-linecap="round"/>
                            </svg>

                            <input type="text"
                                   id="leaveEmployeeSearch"
                                   placeholder="Cari nama, UID, atau unit pegawai..."
                                   autocomplete="off"
                                   aria-label="Cari pegawai">

                            <button type="button"
                                    id="clearLeaveSearch"
                                    class="leave-clear-search"
                                    aria-label="Hapus pencarian"
                                    title="Hapus pencarian">
                                &times;
                            </button>
                        </div>

                        <div class="leave-search-hint">
                            Gunakan pencarian untuk menemukan pegawai dengan cepat.
                        </div>
                    </div>

                    <div class="leave-table-wrapper">
                        <table class="leave-table" id="leaveStatisticsTable">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 65px;">No.</th>
                                    <th>Nama Pegawai</th>
                                    <th>Unit</th>
                                    <th class="text-center">Jatah</th>
                                    <th class="text-center">Terpakai</th>
                                    <th class="text-center">Sisa</th>
                                    <th style="min-width: 190px;">Penggunaan</th>
                                </tr>
                            </thead>

                            <tbody id="leaveStatisticsBody">
                                @forelse ($employees as $employee)
                                    @php
                                        $allowance = (int) $employee->allowance_days;
                                        $used = (int) $employee->total_used;
                                        $remaining = (int) $employee->remaining;

                                        $percentage = $allowance > 0
                                            ? min(100, max(0, ($used / $allowance) * 100))
                                            : 0;

                                        if ($remaining <= 0) {
                                            $balanceClass = 'balance-empty';
                                            $balanceLabel = 'Habis';
                                        } elseif ($remaining <= 3) {
                                            $balanceClass = 'balance-low';
                                            $balanceLabel = 'Menipis';
                                        } else {
                                            $balanceClass = 'balance-good';
                                            $balanceLabel = 'Tersedia';
                                        }

                                        if ($percentage >= 100) {
                                            $progressClass = 'progress-full';
                                        } elseif ($percentage >= 70) {
                                            $progressClass = 'progress-high';
                                        } else {
                                            $progressClass = 'progress-normal';
                                        }
                                    @endphp

                                    <tr class="leave-employee-row">
                                        <td class="text-center leave-row-number">
                                            {{ $loop->iteration }}
                                        </td>

                                        <td>
                                            <div class="leave-employee-name">
                                                {{ $employee->nama }}
                                            </div>
                                            <div class="leave-employee-uid">
                                                UID: {{ $employee->uid ?: '-' }}
                                            </div>
                                        </td>

                                        <td>
                                            <span class="leave-unit-badge">
                                                {{ $employee->nama ?: '-' }}
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <span class="leave-number leave-number-allowance">
                                                {{ number_format($allowance, 0, ',', '.') }}
                                            </span>
                                            <span class="leave-day-label">hari</span>
                                        </td>

                                        <td class="text-center">
                                            <span class="leave-number leave-number-used">
                                                {{ number_format($used, 0, ',', '.') }}
                                            </span>
                                            <span class="leave-day-label">hari</span>
                                        </td>

                                        <td class="text-center">
                                            <span class="leave-balance {{ $balanceClass }}">
                                                {{ number_format($remaining, 0, ',', '.') }}
                                            </span>
                                            <div class="leave-balance-label">
                                                {{ $balanceLabel }}
                                            </div>
                                        </td>

                                        <td>
                                            <div class="leave-progress-info">
                                                <span>Terpakai</span>
                                                <strong>{{ number_format($percentage, 0, ',', '.') }}%</strong>
                                            </div>

                                            <div class="leave-progress-track"
                                                 role="progressbar"
                                                 aria-label="Persentase penggunaan cuti {{ $employee->nama }}"
                                                 aria-valuenow="{{ (int) round($percentage) }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100">
                                                <div class="leave-progress-bar {{ $progressClass }}"
                                                     style="width: {{ $percentage }}%;">
                                                </div>
                                            </div>

                                            <div class="leave-progress-caption">
                                                {{ number_format($used, 0, ',', '.') }}
                                                dari
                                                {{ number_format($allowance, 0, ',', '.') }}
                                                hari
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="leaveEmptyRow">
                                        <td colspan="7">
                                            <div class="leave-empty-state">
                                                <div class="leave-empty-icon">
                                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                        <circle cx="11" cy="11" r="7"
                                                                stroke="currentColor" stroke-width="1.8"/>
                                                        <path d="m16 16 4 4"
                                                              stroke="currentColor" stroke-width="1.8"
                                                              stroke-linecap="round"/>
                                                    </svg>
                                                </div>
                                                <h3>Belum Ada Data Pegawai</h3>
                                                <p>
                                                    Belum ada pegawai yang ditetapkan sebagai penerima
                                                    hak cuti untuk periode ini.
                                                </p>
                                                <a href="{{ route('annualLeaveAdmin.index') }}"
                                                   class="leave-empty-button">
                                                    Atur Hak Cuti Pegawai
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div id="leaveSearchNoResults" class="leave-no-results" hidden>
                            <div class="leave-empty-icon">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7"
                                            stroke="currentColor" stroke-width="1.8"/>
                                    <path d="m16 16 4 4"
                                          stroke="currentColor" stroke-width="1.8"
                                          stroke-linecap="round"/>
                                </svg>
                            </div>
                            <h3>Pegawai Tidak Ditemukan</h3>
                            <p>Coba gunakan nama, UID, atau unit yang berbeda.</p>
                        </div>
                    </div>

                    <div class="leave-table-footer">
                        <span id="leaveVisibleCount">
                            Menampilkan {{ $employees->count() }} pegawai
                        </span>
                        <span>Periode {{ $period->name }}</span>
                    </div>
                </section>

            @else
                {{-- BELUM ADA PERIODE AKTIF --}}
                <section class="leave-empty-period">
                    <div class="leave-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="16" rx="2"
                                  stroke="currentColor" stroke-width="1.8"/>
                            <path d="M16 3v4M8 3v4M3 10h18"
                                  stroke="currentColor" stroke-width="1.8"
                                  stroke-linecap="round"/>
                        </svg>
                    </div>

                    <h2>Belum Ada Periode Cuti Aktif</h2>
                    <p>
                        Aktifkan periode cuti tahunan terlebih dahulu agar statistik
                        pegawai dapat ditampilkan.
                    </p>

                    <a href="{{ route('annualLeaveAdmin.index') }}"
                       class="leave-empty-button">
                        Buka Pengaturan Hak Cuti
                    </a>
                </section>
            @endif

        </div>
    </div>
</main>
@endsection

@push('addon-style')
<style>
    .annual-leave-page {
        --leave-text: #172033;
        --leave-muted: #64748b;
        --leave-border: #e2e8f0;
        --leave-primary: #2563eb;
        --leave-radius: 18px;
        color: var(--leave-text);
        font-size: 16px;
        padding-bottom: 30px;
    }

    .annual-leave-page *,
    .annual-leave-page *::before,
    .annual-leave-page *::after {
        box-sizing: border-box;
    }

    .annual-leave-page svg {
        display: block;
        width: 25px;
        height: 25px;
        flex-shrink: 0;
    }

    .annual-leave-page .app-content-header {
        padding-top: 24px;
        padding-bottom: 12px;
    }

    .leave-eyebrow {
        color: #2563eb;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 1.7px;
        margin-bottom: 8px;
    }

    .leave-page-title {
        margin: 0;
        font-size: 32px;
        font-weight: 850;
        line-height: 1.3;
        letter-spacing: -0.8px;
        color: var(--leave-text);
    }

    .leave-page-subtitle {
        color: var(--leave-muted);
        font-size: 16px;
        line-height: 1.7;
        margin: 8px 0 0;
    }

    .leave-back-button {
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 11px 17px;
        border: 1px solid #dbe4f0;
        border-radius: 12px;
        background: #fff;
        color: #334155;
        font-size: 15px;
        font-weight: 750;
        text-decoration: none;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .035);
        transition: .2s ease;
    }

    .leave-back-button svg {
        width: 19px;
        height: 19px;
    }

    .leave-back-button:hover {
        color: #1d4ed8;
        border-color: #93c5fd;
        background: #f8fbff;
        transform: translateY(-1px);
    }

    /* PERIODE */

    .leave-period-card {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 23px 25px;
        margin: 14px 0 24px;
        background: linear-gradient(115deg, #eff6ff 0%, #f8fbff 65%, #fff 100%);
        border: 1px solid #dbeafe;
        border-radius: var(--leave-radius);
        box-shadow: 0 5px 18px rgba(37, 99, 235, .045);
    }

    .leave-period-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #dbeafe;
        color: #1d4ed8;
        flex-shrink: 0;
    }

    .leave-period-icon svg {
        width: 30px;
        height: 30px;
    }

    .leave-period-content {
        flex: 1;
        min-width: 0;
    }

    .leave-period-label {
        color: #2563eb;
        font-size: 12px;
        font-weight: 850;
        letter-spacing: 1.3px;
        margin-bottom: 5px;
    }

    .leave-period-title {
        margin: 0;
        font-size: 23px;
        font-weight: 850;
        line-height: 1.4;
        color: #172554;
    }

    .leave-period-description {
        margin: 5px 0 0;
        font-size: 15px;
        color: #64748b;
    }

    .leave-entitlement {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4px;
        padding-left: 22px;
        border-left: 1px solid #bfdbfe;
        flex-shrink: 0;
    }

    .leave-entitlement span {
        color: #64748b;
        font-size: 14px;
        font-weight: 650;
    }

    .leave-entitlement strong {
        color: #1d4ed8;
        font-size: 28px;
        font-weight: 850;
        line-height: 1.3;
    }

    .leave-entitlement small {
        font-size: 14px;
        font-weight: 700;
    }

    /* KARTU STATISTIK */

    .leave-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 26px;
    }

    .leave-stat-card {
        min-width: 0;
        padding: 23px;
        border: 1px solid var(--leave-border);
        border-radius: var(--leave-radius);
        background: #fff;
        box-shadow: 0 5px 18px rgba(15, 23, 42, .035);
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .leave-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, .075);
    }

    .leave-stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 21px;
    }

    .leave-stat-icon {
        width: 54px;
        height: 54px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .leave-stat-icon svg {
        width: 29px;
        height: 29px;
    }

    .leave-stat-tag {
        font-size: 10px;
        font-weight: 850;
        letter-spacing: .8px;
        padding: 6px 9px;
        border-radius: 7px;
        white-space: nowrap;
    }

    .stat-employees .leave-stat-icon {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .stat-employees .leave-stat-tag {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .stat-allowance .leave-stat-icon {
        background: #ede9fe;
        color: #6d28d9;
    }

    .stat-allowance .leave-stat-tag {
        background: #f5f3ff;
        color: #6d28d9;
    }

    .stat-used .leave-stat-icon {
        background: #ffedd5;
        color: #c2410c;
    }

    .stat-used .leave-stat-tag {
        background: #fff7ed;
        color: #c2410c;
    }

    .stat-remaining .leave-stat-icon {
        background: #dcfce7;
        color: #15803d;
    }

    .stat-remaining .leave-stat-tag {
        background: #f0fdf4;
        color: #15803d;
    }

    .leave-stat-label {
        color: #475569;
        font-size: 15px;
        font-weight: 750;
        line-height: 1.5;
        margin-bottom: 8px;
    }

    .leave-stat-value {
        color: #172033;
        font-size: 35px;
        font-weight: 900;
        letter-spacing: -1px;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    .leave-stat-unit {
        color: #64748b;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: 0;
    }

    .leave-stat-description {
        color: #8491a5;
        font-size: 13px;
        line-height: 1.65;
        margin-top: 11px;
    }

    /* TABEL */

    .leave-table-card {
        overflow: hidden;
        border: 1px solid var(--leave-border);
        border-radius: var(--leave-radius);
        background: #fff;
        box-shadow: 0 5px 20px rgba(15, 23, 42, .035);
    }

    .leave-table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
        padding: 24px 25px;
        border-bottom: 1px solid #edf1f6;
    }

    .leave-table-title {
        margin: 0;
        color: #172033;
        font-size: 22px;
        font-weight: 850;
        line-height: 1.4;
    }

    .leave-table-subtitle {
        margin: 6px 0 0;
        color: #64748b;
        font-size: 15px;
        line-height: 1.6;
    }

    .leave-total-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 14px;
        color: #1d4ed8;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 800;
        white-space: nowrap;
    }

    .leave-search-area {
        padding: 20px 25px;
        background: #fbfcfe;
        border-bottom: 1px solid #edf1f6;
    }

    .leave-search-box {
        position: relative;
        display: flex;
        align-items: center;
        max-width: 620px;
    }

    .leave-search-box > svg {
        position: absolute;
        left: 15px;
        width: 22px;
        height: 22px;
        color: #94a3b8;
        pointer-events: none;
    }

    .leave-search-box input {
        width: 100%;
        height: 50px;
        padding: 12px 46px 12px 48px;
        color: #172033;
        background: #fff;
        border: 1px solid #dbe3ee;
        border-radius: 12px;
        outline: none;
        font-family: inherit;
        font-size: 15px;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .leave-search-box input::placeholder {
        color: #94a3b8;
    }

    .leave-search-box input:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, .11);
    }

    .leave-clear-search {
        position: absolute;
        right: 10px;
        display: none;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border: 0;
        border-radius: 8px;
        color: #64748b;
        background: #f1f5f9;
        font-size: 24px;
        line-height: 1;
        cursor: pointer;
    }

    .leave-clear-search:hover {
        color: #1d4ed8;
        background: #dbeafe;
    }

    .leave-search-hint {
        color: #94a3b8;
        font-size: 13px;
        margin-top: 9px;
    }

    .leave-table-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .leave-table {
        width: 100%;
        min-width: 930px;
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
        color: #334155;
        font-size: 15px;
    }

    .leave-table thead th {
        padding: 17px 16px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        color: #475569;
        font-size: 12px;
        font-weight: 850;
        letter-spacing: .45px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .leave-table tbody td {
        padding: 18px 16px;
        border-bottom: 1px solid #edf1f6;
        vertical-align: middle;
    }

    .leave-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .leave-table tbody tr.leave-employee-row:hover {
        background: #f8fbff;
    }

    .leave-row-number {
        color: #94a3b8;
        font-size: 14px;
        font-weight: 750;
    }

    .leave-employee-name {
        max-width: 300px;
        color: #172033;
        font-size: 16px;
        font-weight: 800;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .leave-employee-uid {
        margin-top: 4px;
        color: #94a3b8;
        font-size: 13px;
    }

    .leave-unit-badge {
        display: inline-block;
        padding: 7px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        color: #475569;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }

    .leave-number {
        display: block;
        font-size: 19px;
        font-weight: 850;
        line-height: 1.4;
    }

    .leave-number-allowance {
        color: #334155;
    }

    .leave-number-used {
        color: #c2410c;
    }

    .leave-day-label {
        display: block;
        margin-top: 3px;
        color: #94a3b8;
        font-size: 12px;
    }

    .leave-balance {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 45px;
        padding: 6px 10px;
        border-radius: 9px;
        font-size: 18px;
        font-weight: 900;
    }

    .balance-good {
        color: #15803d;
        background: #dcfce7;
    }

    .balance-low {
        color: #a16207;
        background: #fef3c7;
    }

    .balance-empty {
        color: #b91c1c;
        background: #fee2e2;
    }

    .leave-balance-label {
        margin-top: 5px;
        color: #64748b;
        font-size: 12px;
        font-weight: 650;
    }

    .leave-progress-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-bottom: 9px;
        color: #64748b;
        font-size: 13px;
    }

    .leave-progress-info strong {
        color: #334155;
        font-size: 14px;
        font-weight: 850;
    }

    .leave-progress-track {
        height: 9px;
        overflow: hidden;
        border-radius: 99px;
        background: #e9eef5;
    }

    .leave-progress-bar {
        height: 100%;
        border-radius: inherit;
        transition: width .35s ease;
    }

    .progress-normal {
        background: #3b82f6;
    }

    .progress-high {
        background: #f59e0b;
    }

    .progress-full {
        background: #ef4444;
    }

    .leave-progress-caption {
        margin-top: 7px;
        color: #94a3b8;
        font-size: 12px;
    }

    .leave-table-footer {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 10px;
        padding: 16px 25px;
        background: #fbfcfe;
        border-top: 1px solid #edf1f6;
        color: #64748b;
        font-size: 13px;
        font-weight: 650;
    }

    /* EMPTY STATE */

    .leave-empty-state,
    .leave-empty-period,
    .leave-no-results {
        text-align: center;
        padding: 48px 22px;
    }

    .leave-empty-period {
        margin-top: 20px;
        padding: 60px 25px;
        border: 1px solid var(--leave-border);
        border-radius: var(--leave-radius);
        background: #fff;
        box-shadow: 0 5px 20px rgba(15, 23, 42, .035);
    }

    .leave-empty-icon {
        width: 68px;
        height: 68px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 18px;
        border-radius: 20px;
        color: #64748b;
        background: #f1f5f9;
    }

    .leave-empty-icon svg {
        width: 32px;
        height: 32px;
    }

    .leave-empty-state h3,
    .leave-empty-period h2,
    .leave-no-results h3 {
        margin: 0 0 10px;
        color: #172033;
        font-size: 21px;
        font-weight: 850;
    }

    .leave-empty-state p,
    .leave-empty-period p,
    .leave-no-results p {
        max-width: 550px;
        margin: 0 auto 22px;
        color: #64748b;
        font-size: 15px;
        line-height: 1.8;
    }

    .leave-empty-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 45px;
        padding: 11px 18px;
        border: 0;
        border-radius: 11px;
        color: #fff !important;
        background: #2563eb;
        font-size: 15px;
        font-weight: 800;
        text-decoration: none !important;
        transition: background .2s ease, transform .2s ease;
    }

    .leave-empty-button:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
    }

    .leave-no-results[hidden] {
        display: none !important;
    }

    /* RESPONSIVE */

    @media (max-width: 1200px) {
        .leave-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .annual-leave-page .app-content-header {
            padding-top: 18px;
        }

        .leave-page-title {
            font-size: 27px;
            letter-spacing: -.4px;
        }

        .leave-page-subtitle {
            font-size: 15px;
        }

        .leave-back-button {
            width: 100%;
        }

        .leave-period-card {
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 14px;
            padding: 19px;
        }

        .leave-period-icon {
            width: 49px;
            height: 49px;
            border-radius: 13px;
        }

        .leave-period-icon svg {
            width: 25px;
            height: 25px;
        }

        .leave-period-title {
            font-size: 20px;
        }

        .leave-period-description {
            font-size: 14px;
            line-height: 1.7;
        }

        .leave-entitlement {
            width: 100%;
            align-items: flex-start;
            padding: 13px 0 0;
            border-left: 0;
            border-top: 1px solid #bfdbfe;
        }

        .leave-entitlement strong {
            font-size: 25px;
        }

        .leave-stats-grid {
            grid-template-columns: 1fr;
            gap: 13px;
        }

        .leave-stat-card {
            padding: 20px;
        }

        .leave-stat-top {
            margin-bottom: 15px;
        }

        .leave-stat-value {
            font-size: 32px;
        }

        .leave-table-header {
            align-items: flex-start;
            flex-direction: column;
            padding: 20px;
        }

        .leave-table-title {
            font-size: 20px;
        }

        .leave-search-area {
            padding: 18px 20px;
        }

        .leave-search-box input {
            font-size: 14px;
            height: 48px;
            padding-left: 44px;
        }

        .leave-table-footer {
            padding: 15px 20px;
        }
    }

    @media (min-width: 576px) and (max-width: 768px) {
        .leave-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>
@endpush

@push('prepend-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('leaveEmployeeSearch');
    const clearButton = document.getElementById('clearLeaveSearch');
    const tableBody = document.getElementById('leaveStatisticsBody');
    const noResults = document.getElementById('leaveSearchNoResults');
    const visibleCount = document.getElementById('leaveVisibleCount');

    if (!searchInput || !tableBody) {
        return;
    }

    const rows = Array.from(
        tableBody.querySelectorAll('tr.leave-employee-row')
    );

    function filterEmployees() {
        const query = searchInput.value.trim().toLocaleLowerCase('id-ID');
        let visible = 0;

        rows.forEach(function (row) {
            const text = row.textContent.toLocaleLowerCase('id-ID');
            const matches = text.includes(query);

            row.style.display = matches ? '' : 'none';

            if (matches) {
                visible++;
            }
        });

        if (clearButton) {
            clearButton.style.display = searchInput.value ? 'flex' : 'none';
        }

        if (noResults) {
            noResults.hidden = !(rows.length > 0 && visible === 0);
        }

        if (visibleCount) {
            visibleCount.textContent =
                'Menampilkan ' + visible.toLocaleString('id-ID') +
                ' dari ' + rows.length.toLocaleString('id-ID') +
                ' pegawai';
        }
    }

    searchInput.addEventListener('input', filterEmployees);

    if (clearButton) {
        clearButton.addEventListener('click', function () {
            searchInput.value = '';
            filterEmployees();
            searchInput.focus();
        });
    }

    filterEmployees();
});
</script>
@endpush