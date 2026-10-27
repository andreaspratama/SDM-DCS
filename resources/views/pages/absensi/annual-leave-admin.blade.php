@extends('layouts.admin')

@section('title', 'Pengaturan Pegawai Cuti')

@section('content')
<main class="app-main annual-setting-page">

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="setting-heading">
                <div>
                    <div class="setting-eyebrow">MANAJEMEN KEPEGAWAIAN</div>
                    <h1 class="setting-title">Pengaturan Cuti Tahunan</h1>
                    <p class="setting-subtitle">
                        Tentukan pegawai yang berhak memperoleh cuti tahunan pada periode aktif.
                    </p>
                </div>

                <div class="display: flex; gap: 8px; flex-wrap: wrap;">
                    <a href="{{ route('annualLeaveAdmin.statistics') }}"
                    class="setting-secondary-button">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 19V5M4 19h17"
                                stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round"/>
                            <path d="M8 15v-4M13 15V7M18 15V9"
                                stroke="currentColor" stroke-width="2"
                                stroke-linecap="round"/>
                        </svg>
                        Statistik Cuti
                    </a>
                    <a href="{{ route('annualLeaveAdmin.periods') }}"
                    class="setting-secondary-button">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 19V5M4 19h17"
                                stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round"/>
                            <path d="M8 15v-4M13 15V7M18 15V9"
                                stroke="currentColor" stroke-width="2"
                                stroke-linecap="round"/>
                        </svg>
                        Kelola Tahun Ajaran Cuti
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            @if (session('success'))
                <div class="setting-alert setting-alert-success">
                    <span class="setting-alert-icon">✓</span>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="setting-alert setting-alert-danger">
                    <span class="setting-alert-icon">!</span>
                    <div>
                        <strong>Pengaturan belum berhasil disimpan.</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if (!$period)

                <section class="setting-empty-period">
                    <div class="setting-empty-icon">
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
                        Aktifkan periode cuti tahunan terlebih dahulu sebelum mengatur
                        pegawai yang berhak.
                    </p>
                </section>

            @else

                @php
                    $eligibleIds = $eligibilities
                        ->filter(fn ($item) => $item->is_eligible)
                        ->keys()
                        ->map(fn ($id) => (string) $id)
                        ->all();

                    $checkedIds = collect(old('employee_ids', $eligibleIds))
                        ->map(fn ($id) => (string) $id)
                        ->all();

                    $eligibleCount = count($checkedIds);
                    $employeeCount = $employees->count();
                @endphp


                {{-- PILIH TAHUN AJARAN --}}
                <section class="setting-period-card" style="display:block;">
                    <label for="period_id"
                        style="display:block; margin-bottom:10px; font-size:15px; font-weight:800; color:#172554;">
                        📅 Pilih Tahun Ajaran
                    </label>

                    <form method="GET"
                        action="{{ route('annualLeaveAdmin.index') }}"
                        style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">

                        <select name="period_id"
                                id="period_id"
                                required
                                style="flex:1; min-width:220px; height:46px; padding:10px 12px; border:1px solid #bfdbfe; border-radius:10px; background:#fff; color:#172033; font-size:15px;">

                            @foreach ($periods as $item)
                                <option value="{{ $item->id }}"
                                    {{ (int) $selectedPeriodId === (int) $item->id ? 'selected' : '' }}>
                                    {{ $item->name }}
                                    {{ $item->is_active ? '(TA Aktif)' : '' }}
                                </option>
                            @endforeach

                        </select>

                        <button type="submit"
                                class="setting-secondary-button"
                                style="cursor:pointer;">
                            Tampilkan Data
                        </button>
                    </form>

                    <p style="margin:10px 0 0; color:#64748b; font-size:13px; line-height:1.6;">
                        Pilih tahun ajaran untuk melihat pengaturan hak cuti pegawai pada periode tersebut.
                        Periode lama tetap tersimpan.
                    </p>
                </section>


                {{-- INFORMASI PERIODE --}}
                <section class="setting-period-card">
                    <div class="setting-period-icon">
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

                    <div class="setting-period-content">
                        <div class="setting-period-label">PERIODE AKTIF</div>
                        <h2>{{ $period->name }}</h2>
                        <p>
                            {{ \Carbon\Carbon::parse($period->start_date)->format('d M Y') }}
                            — 
                            {{ \Carbon\Carbon::parse($period->end_date)->format('d M Y') }}
                        </p>
                    </div>

                    <div class="setting-entitlement">
                        <span>Jatah per pegawai</span>
                        <strong>17 <small>hari kerja</small></strong>
                    </div>
                </section>

                {{-- RINGKASAN --}}
                <section class="setting-summary-grid">
                    <div class="setting-summary-card">
                        <div class="setting-summary-icon summary-blue">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="9" cy="8" r="3.5"
                                        stroke="currentColor" stroke-width="1.8"/>
                                <path d="M2.5 20v-1.5A5.5 5.5 0 0 1 8 13h2a5.5 5.5 0 0 1 5.5 5.5V20"
                                      stroke="currentColor" stroke-width="1.8"
                                      stroke-linecap="round"/>
                                <path d="M16 5a3.5 3.5 0 0 1 0 6.5M18 14a5.5 5.5 0 0 1 3.5 5.1V20"
                                      stroke="currentColor" stroke-width="1.8"
                                      stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div>
                            <div class="setting-summary-label">Total Pegawai</div>
                            <div class="setting-summary-value">
                                {{ number_format($employeeCount, 0, ',', '.') }}
                            </div>
                            <div class="setting-summary-note">Terdaftar dalam sistem</div>
                        </div>
                    </div>

                    <div class="setting-summary-card">
                        <div class="setting-summary-icon summary-green">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"
                                        stroke="currentColor" stroke-width="1.8"/>
                                <path d="m7.5 12.5 3 3 6-7"
                                      stroke="currentColor" stroke-width="2"
                                      stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <div class="setting-summary-label">Berhak Cuti</div>
                            <div class="setting-summary-value" id="eligibleCount">
                                {{ number_format($eligibleCount, 0, ',', '.') }}
                            </div>
                            <div class="setting-summary-note">Pegawai terpilih</div>
                        </div>
                    </div>

                    <div class="setting-summary-card">
                        <div class="setting-summary-icon summary-orange">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="17" rx="2"
                                      stroke="currentColor" stroke-width="1.8"/>
                                <path d="M7 2.5v4M17 2.5v4M3 9h18"
                                      stroke="currentColor" stroke-width="1.8"
                                      stroke-linecap="round"/>
                                <path d="M8 13h3M8 16.5h7"
                                      stroke="currentColor" stroke-width="1.8"
                                      stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div>
                            <div class="setting-summary-label">Jatah Cuti</div>
                            <div class="setting-summary-value">17 <small>hari</small></div>
                            <div class="setting-summary-note">Per periode tahun ajaran</div>
                        </div>
                    </div>
                </section>

                {{-- FORM PENGATURAN --}}
                <form method="POST"
                    action="{{ route('annualLeaveAdmin.update') }}"
                    id="annualLeaveSettingForm">
                    @csrf

                    <input type="hidden"
                        name="period_id"
                        value="{{ $selectedPeriodId }}">

                    <section class="setting-table-card">

                        <div class="setting-table-heading">
                            <div>
                                <h2>Daftar Pegawai</h2>
                                <p>Pilih pegawai yang berhak menerima cuti tahunan.</p>
                            </div>

                            <span class="setting-total-badge">
                                {{ number_format($employeeCount, 0, ',', '.') }} pegawai
                            </span>
                        </div>

                        {{-- TOOLBAR --}}
                        <div class="setting-toolbar">
                            <div class="setting-search">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="10.8" cy="10.8" r="6.8"
                                            stroke="currentColor" stroke-width="1.8"/>
                                    <path d="m16 16 4.5 4.5"
                                          stroke="currentColor" stroke-width="1.8"
                                          stroke-linecap="round"/>
                                </svg>

                                <input type="text"
                                       id="employeeSearch"
                                       placeholder="Cari nama, UID, atau unit..."
                                       autocomplete="off"
                                       aria-label="Cari pegawai">

                                <button type="button"
                                        id="clearEmployeeSearch"
                                        aria-label="Hapus pencarian"
                                        title="Hapus pencarian">
                                    &times;
                                </button>
                            </div>

                            <div class="setting-selection-actions">
                                <button type="button"
                                        class="setting-select-button"
                                        id="selectVisible">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m5 12 4 4L19 6"
                                              stroke="currentColor" stroke-width="2"
                                              stroke-linecap="round"
                                              stroke-linejoin="round"/>
                                    </svg>
                                    Pilih yang tampil
                                </button>

                                <button type="button"
                                        class="setting-unselect-button"
                                        id="unselectVisible">
                                    Batalkan pilihan
                                </button>
                            </div>
                        </div>

                        {{-- INFO PILIHAN --}}
                        <div class="setting-selection-info">
                            <span>
                                <strong id="selectedCount">{{ $eligibleCount }}</strong>
                                pegawai dipilih
                            </span>
                            <span class="setting-info-tip">
                                Centang kotak untuk menentukan hak cuti.
                            </span>
                        </div>

                        {{-- TABEL SCROLL INTERNAL --}}
                        <div class="setting-table-scroll">
                            <table class="setting-table" id="employeeTable">
                                <thead>
                                    <tr>
                                        <th class="setting-check-column">
                                            <input type="checkbox"
                                                   id="selectAllVisible"
                                                   aria-label="Pilih semua pegawai yang tampil">
                                        </th>
                                        <th>Nama Pegawai</th>
                                        <th>UID</th>
                                        <th>Unit</th>
                                        <th>Status Hak Cuti</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($employees as $employee)
                                        @php
                                            $eligibility = $eligibilities->get($employee->id);
                                            $isChecked = in_array(
                                                (string) $employee->id,
                                                $checkedIds,
                                                true
                                            );
                                        @endphp

                                        <tr class="setting-employee-row">
                                            <td class="setting-check-column">
                                                <input type="checkbox"
                                                       class="employee-checkbox"
                                                       name="employee_ids[]"
                                                       value="{{ $employee->id }}"
                                                       {{ $isChecked ? 'checked' : '' }}>
                                            </td>

                                            <td>
                                                <div class="setting-employee-name">
                                                    {{ $employee->nama }}
                                                </div>
                                                <div class="setting-employee-caption">
                                                    ID Pegawai: {{ $employee->id }}
                                                </div>
                                            </td>

                                            <td>
                                                <span class="setting-uid">
                                                    {{ $employee->uid ?: '-' }}
                                                </span>
                                            </td>

                                            <td>
                                                <span class="setting-unit">
                                                    {{ $employee->unit_id ?: '-' }}
                                                </span>
                                            </td>

                                            <td>
                                                <span class="setting-status {{ $isChecked ? 'status-eligible' : 'status-ineligible' }}"
                                                      data-status-for="{{ $employee->id }}">
                                                    <span class="setting-status-dot"></span>
                                                    {{ $isChecked ? 'Berhak Cuti' : 'Belum Berhak' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5">
                                                <div class="setting-empty-table">
                                                    <h3>Data pegawai belum tersedia</h3>
                                                    <p>Belum ada data pegawai yang dapat ditampilkan.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <div id="noEmployeeSearchResults"
                                 class="setting-no-results"
                                 hidden>
                                <div class="setting-no-results-icon">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle cx="11" cy="11" r="7"
                                                stroke="currentColor" stroke-width="1.8"/>
                                        <path d="m16 16 4 4"
                                              stroke="currentColor" stroke-width="1.8"
                                              stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <strong>Pegawai tidak ditemukan</strong>
                                <p>Coba kata kunci nama, UID, atau unit yang lain.</p>
                            </div>
                        </div>

                        {{-- FOOTER FORM --}}
                        <div class="setting-form-footer">
                            <div class="setting-warning">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 3 22 20H2L12 3Z"
                                          stroke="currentColor" stroke-width="1.8"
                                          stroke-linejoin="round"/>
                                    <path d="M12 9v5M12 17h.01"
                                          stroke="currentColor" stroke-width="2"
                                          stroke-linecap="round"/>
                                </svg>
                                <span>
                                    Pegawai yang tidak dicentang akan menjadi tidak berhak
                                    pada periode ini. Riwayat transaksi cuti tidak dihapus.
                                </span>
                            </div>

                            <div class="setting-submit-row">
                                <span class="setting-submit-hint">
                                    Pastikan pilihan pegawai sudah benar.
                                </span>

                                <button type="submit"
                                        class="setting-save-button"
                                        id="saveSettingButton">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M5 3h12l4 4v14H3V3h2Z"
                                              stroke="currentColor" stroke-width="1.8"
                                              stroke-linejoin="round"/>
                                        <path d="M7 3v6h10V3M7 21v-7h10v7"
                                              stroke="currentColor" stroke-width="1.8"
                                              stroke-linejoin="round"/>
                                    </svg>
                                    Simpan Pengaturan
                                </button>
                            </div>
                        </div>

                    </section>
                </form>

            @endif
        </div>
    </div>
</main>
@endsection

@push('addon-style')
<style>
    .annual-setting-page {
        --setting-text: #172033;
        --setting-muted: #64748b;
        --setting-border: #e2e8f0;
        color: var(--setting-text);
        font-size: 16px;
        padding-bottom: 28px;
    }

    .annual-setting-page *,
    .annual-setting-page *::before,
    .annual-setting-page *::after {
        box-sizing: border-box;
    }

    .annual-setting-page svg {
        width: 23px;
        height: 23px;
        display: block;
        flex-shrink: 0;
    }

    .setting-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 18px;
        padding: 8px 0 15px;
    }

    .setting-eyebrow {
        color: #2563eb;
        font-size: 12px;
        font-weight: 850;
        letter-spacing: 1.5px;
        margin-bottom: 7px;
    }

    .setting-title {
        margin: 0;
        color: var(--setting-text);
        font-size: 31px;
        font-weight: 850;
        line-height: 1.3;
        letter-spacing: -.6px;
    }

    .setting-subtitle {
        margin: 8px 0 0;
        color: var(--setting-muted);
        font-size: 15px;
        line-height: 1.7;
    }

    .setting-secondary-button {
        min-height: 45px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 10px 16px;
        color: #1d4ed8 !important;
        background: #fff;
        border: 1px solid #bfdbfe;
        border-radius: 11px;
        font-size: 14px;
        font-weight: 800;
        text-decoration: none !important;
        transition: .2s ease;
    }

    .setting-secondary-button:hover {
        background: #eff6ff;
        border-color: #93c5fd;
    }

    .setting-secondary-button svg {
        width: 20px;
        height: 20px;
    }

    /* ALERT */

    .setting-alert {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 15px 18px;
        margin-bottom: 18px;
        border-radius: 12px;
        font-size: 15px;
        line-height: 1.7;
    }

    .setting-alert-success {
        color: #166534;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .setting-alert-danger {
        color: #991b1b;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    .setting-alert-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 25px;
        height: 25px;
        flex-shrink: 0;
        border-radius: 50%;
        background: rgba(255,255,255,.7);
        font-weight: 900;
    }

    .setting-alert ul {
        margin: 7px 0 0;
        padding-left: 20px;
    }

    /* PERIODE */

    .setting-period-card {
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 20px 23px;
        margin: 5px 0 20px;
        border: 1px solid #dbeafe;
        border-radius: 16px;
        background: linear-gradient(110deg, #eff6ff, #f8fbff 65%, #fff);
    }

    .setting-period-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 55px;
        height: 55px;
        flex-shrink: 0;
        color: #1d4ed8;
        background: #dbeafe;
        border-radius: 15px;
    }

    .setting-period-icon svg {
        width: 29px;
        height: 29px;
    }

    .setting-period-content {
        flex: 1;
        min-width: 0;
    }

    .setting-period-label {
        color: #2563eb;
        font-size: 11px;
        font-weight: 850;
        letter-spacing: 1.3px;
        margin-bottom: 4px;
    }

    .setting-period-content h2 {
        margin: 0;
        color: #172554;
        font-size: 21px;
        font-weight: 850;
        line-height: 1.4;
    }

    .setting-period-content p {
        margin: 5px 0 0;
        color: #64748b;
        font-size: 14px;
    }

    .setting-entitlement {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4px;
        padding-left: 20px;
        border-left: 1px solid #bfdbfe;
        white-space: nowrap;
    }

    .setting-entitlement span {
        color: #64748b;
        font-size: 13px;
        font-weight: 650;
    }

    .setting-entitlement strong {
        color: #1d4ed8;
        font-size: 26px;
        font-weight: 900;
    }

    .setting-entitlement small {
        font-size: 13px;
        font-weight: 750;
    }

    /* RINGKASAN */

    .setting-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .setting-summary-card {
        display: flex;
        align-items: center;
        gap: 15px;
        min-width: 0;
        padding: 19px;
        background: #fff;
        border: 1px solid var(--setting-border);
        border-radius: 15px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, .03);
    }

    .setting-summary-icon {
        width: 49px;
        height: 49px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border-radius: 14px;
    }

    .setting-summary-icon svg {
        width: 27px;
        height: 27px;
    }

    .summary-blue {
        color: #1d4ed8;
        background: #dbeafe;
    }

    .summary-green {
        color: #15803d;
        background: #dcfce7;
    }

    .summary-orange {
        color: #c2410c;
        background: #ffedd5;
    }

    .setting-summary-label {
        color: #64748b;
        font-size: 13px;
        font-weight: 750;
    }

    .setting-summary-value {
        margin-top: 3px;
        color: #172033;
        font-size: 27px;
        font-weight: 900;
        line-height: 1.3;
    }

    .setting-summary-value small {
        color: #64748b;
        font-size: 13px;
        font-weight: 750;
    }

    .setting-summary-note {
        margin-top: 3px;
        color: #94a3b8;
        font-size: 12px;
        line-height: 1.5;
    }

    /* DAFTAR PEGAWAI */

    .setting-table-card {
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--setting-border);
        border-radius: 16px;
        box-shadow: 0 5px 18px rgba(15, 23, 42, .035);
    }

    .setting-table-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        padding: 21px 23px;
        border-bottom: 1px solid #edf1f6;
    }

    .setting-table-heading h2 {
        margin: 0;
        color: #172033;
        font-size: 21px;
        font-weight: 850;
    }

    .setting-table-heading p {
        margin: 6px 0 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
    }

    .setting-total-badge {
        padding: 8px 12px;
        color: #1d4ed8;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 850;
        white-space: nowrap;
    }

    .setting-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        padding: 17px 23px;
        background: #fbfcfe;
        border-bottom: 1px solid #edf1f6;
    }

    .setting-search {
        position: relative;
        flex: 1 1 270px;
        max-width: 440px;
    }

    .setting-search > svg {
        position: absolute;
        top: 50%;
        left: 13px;
        width: 21px;
        height: 21px;
        color: #94a3b8;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .setting-search input {
        width: 100%;
        height: 44px;
        padding: 10px 42px;
        color: #172033;
        background: #fff;
        border: 1px solid #dbe3ee;
        border-radius: 10px;
        outline: none;
        font-family: inherit;
        font-size: 14px;
        transition: .2s ease;
    }

    .setting-search input:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 3px rgba(59,130,246,.1);
    }

    .setting-search input::placeholder {
        color: #94a3b8;
    }

    #clearEmployeeSearch {
        position: absolute;
        top: 50%;
        right: 8px;
        display: none;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border: 0;
        border-radius: 7px;
        color: #64748b;
        background: #f1f5f9;
        font-size: 22px;
        line-height: 1;
        transform: translateY(-50%);
        cursor: pointer;
    }

    .setting-selection-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .setting-select-button,
    .setting-unselect-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 40px;
        padding: 9px 12px;
        border-radius: 9px;
        font-family: inherit;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        transition: .2s ease;
    }

    .setting-select-button {
        color: #1d4ed8;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }

    .setting-select-button:hover {
        background: #dbeafe;
    }

    .setting-select-button svg {
        width: 17px;
        height: 17px;
    }

    .setting-unselect-button {
        color: #475569;
        background: #fff;
        border: 1px solid #cbd5e1;
    }

    .setting-unselect-button:hover {
        background: #f1f5f9;
    }

    .setting-selection-info {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
        padding: 12px 23px;
        color: #475569;
        background: #fff;
        border-bottom: 1px solid #edf1f6;
        font-size: 13px;
    }

    .setting-selection-info strong {
        color: #15803d;
        font-size: 15px;
        font-weight: 900;
    }

    .setting-info-tip {
        color: #94a3b8;
    }

    /* TABEL SCROLL DI DALAM KARTU */

    .setting-table-scroll {
        max-height: 440px;
        overflow: auto;
        overscroll-behavior: contain;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f8fafc;
    }

    .setting-table-scroll::-webkit-scrollbar {
        width: 9px;
        height: 9px;
    }

    .setting-table-scroll::-webkit-scrollbar-track {
        background: #f8fafc;
    }

    .setting-table-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border: 2px solid #f8fafc;
        border-radius: 99px;
    }

    .setting-table {
        width: 100%;
        min-width: 740px;
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 14px;
    }

    .setting-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        padding: 14px 16px;
        color: #475569;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        font-size: 11px;
        font-weight: 850;
        letter-spacing: .6px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .setting-table tbody td {
        padding: 13px 16px;
        color: #334155;
        background: #fff;
        border-bottom: 1px solid #edf1f6;
        vertical-align: middle;
    }

    .setting-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .setting-table tbody tr:hover td {
        background: #f8fbff;
    }

    .setting-check-column {
        width: 54px;
        min-width: 54px;
        text-align: center !important;
    }

    .setting-table input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin: 0;
        accent-color: #2563eb;
        cursor: pointer;
        vertical-align: middle;
    }

    .setting-employee-name {
        color: #172033;
        font-size: 14px;
        font-weight: 800;
        line-height: 1.5;
    }

    .setting-employee-caption {
        margin-top: 3px;
        color: #94a3b8;
        font-size: 11px;
    }

    .setting-uid {
        color: #475569;
        font-size: 13px;
        font-weight: 650;
    }

    .setting-unit {
        display: inline-block;
        padding: 5px 8px;
        color: #475569;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 800;
    }

    .setting-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 9px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .setting-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .status-eligible {
        color: #166534;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .status-eligible .setting-status-dot {
        background: #22c55e;
    }

    .status-ineligible {
        color: #64748b;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .status-ineligible .setting-status-dot {
        background: #94a3b8;
    }

    .setting-form-footer {
        padding: 18px 23px 21px;
        border-top: 1px solid #edf1f6;
        background: #fff;
    }

    .setting-warning {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 13px 15px;
        color: #92400e;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 10px;
        font-size: 13px;
        line-height: 1.7;
    }

    .setting-warning svg {
        width: 20px;
        height: 20px;
        margin-top: 1px;
        color: #d97706;
    }

    .setting-submit-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 13px;
        margin-top: 17px;
    }

    .setting-submit-hint {
        color: #94a3b8;
        font-size: 13px;
    }

    .setting-save-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        min-height: 46px;
        padding: 12px 20px;
        color: #fff;
        background: #2563eb;
        border: 1px solid #2563eb;
        border-radius: 11px;
        font-family: inherit;
        font-size: 14px;
        font-weight: 850;
        box-shadow: 0 4px 10px rgba(37,99,235,.15);
        cursor: pointer;
        transition: .2s ease;
    }

    .setting-save-button:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        transform: translateY(-1px);
    }

    .setting-save-button svg {
        width: 19px;
        height: 19px;
    }

    .setting-save-button:disabled {
        opacity: .7;
        cursor: wait;
        transform: none;
    }

    .setting-empty-table {
        padding: 40px 15px;
        text-align: center;
    }

    .setting-empty-table h3 {
        margin: 0 0 8px;
        color: #172033;
        font-size: 18px;
        font-weight: 850;
    }

    .setting-empty-table p {
        margin: 0;
        color: #64748b;
        font-size: 14px;
    }

    .setting-no-results[hidden] {
        display: none !important;
    }

    .setting-no-results {
        padding: 38px 20px;
        text-align: center;
    }

    .setting-no-results-icon,
    .setting-empty-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 58px;
        height: 58px;
        margin: 0 auto 14px;
        color: #64748b;
        background: #f1f5f9;
        border-radius: 16px;
    }

    .setting-no-results-icon svg,
    .setting-empty-icon svg {
        width: 28px;
        height: 28px;
    }

    .setting-no-results strong,
    .setting-empty-period h2 {
        display: block;
        margin: 0 0 8px;
        color: #172033;
        font-size: 20px;
        font-weight: 850;
    }

    .setting-no-results p,
    .setting-empty-period p {
        margin: 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.8;
    }

    .setting-empty-period {
        padding: 55px 24px;
        text-align: center;
        background: #fff;
        border: 1px solid var(--setting-border);
        border-radius: 16px;
    }

    .setting-empty-period p {
        max-width: 480px;
        margin: 0 auto;
    }

    @media (max-width: 1000px) {
        .setting-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .setting-title {
            font-size: 26px;
        }

        .setting-subtitle {
            font-size: 14px;
        }

        .setting-secondary-button {
            width: 100%;
        }

        .setting-period-card {
            align-items: flex-start;
            flex-wrap: wrap;
            padding: 17px;
            gap: 13px;
        }

        .setting-period-icon {
            width: 46px;
            height: 46px;
        }

        .setting-period-content h2 {
            font-size: 18px;
        }

        .setting-period-content p {
            font-size: 13px;
            line-height: 1.7;
        }

        .setting-entitlement {
            width: 100%;
            align-items: flex-start;
            padding: 12px 0 0;
            border-left: 0;
            border-top: 1px solid #bfdbfe;
        }

        .setting-summary-grid {
            grid-template-columns: 1fr;
            gap: 11px;
        }

        .setting-summary-card {
            padding: 16px;
        }

        .setting-table-heading {
            padding: 18px;
        }

        .setting-toolbar {
            padding: 15px 18px;
        }

        .setting-search {
            flex-basis: 100%;
            max-width: none;
        }

        .setting-selection-actions {
            width: 100%;
        }

        .setting-select-button,
        .setting-unselect-button {
            flex: 1;
        }

        .setting-selection-info {
            padding: 12px 18px;
        }

        .setting-info-tip {
            width: 100%;
        }

        .setting-table-scroll {
            max-height: 390px;
        }

        .setting-form-footer {
            padding: 16px 18px 18px;
        }

        .setting-submit-row {
            align-items: stretch;
            flex-direction: column;
        }

        .setting-save-button {
            width: 100%;
        }
    }

    @media (min-width: 576px) and (max-width: 768px) {
        .setting-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>
@endpush

@push('prepend-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('employeeSearch');
    const clearSearch = document.getElementById('clearEmployeeSearch');
    const table = document.getElementById('employeeTable');
    const selectVisible = document.getElementById('selectVisible');
    const unselectVisible = document.getElementById('unselectVisible');
    const selectAllVisible = document.getElementById('selectAllVisible');
    const selectedCount = document.getElementById('selectedCount');
    const eligibleCount = document.getElementById('eligibleCount');
    const noResults = document.getElementById('noEmployeeSearchResults');
    const saveButton = document.getElementById('saveSettingButton');

    if (!table) {
        return;
    }

    const rows = Array.from(
        table.querySelectorAll('tbody tr.setting-employee-row')
    );

    function visibleRows() {
        return rows.filter(function (row) {
            return row.style.display !== 'none';
        });
    }

    function updateCounts() {
        const checked = table.querySelectorAll(
            'tbody .employee-checkbox:checked'
        ).length;

        if (selectedCount) {
            selectedCount.textContent = checked.toLocaleString('id-ID');
        }

        if (eligibleCount) {
            eligibleCount.textContent = checked.toLocaleString('id-ID');
        }
    }

    function updateRowStatus(checkbox) {
        const row = checkbox.closest('tr');
        if (!row) return;

        const status = row.querySelector('.setting-status');
        if (!status) return;

        if (checkbox.checked) {
            status.classList.remove('status-ineligible');
            status.classList.add('status-eligible');
            status.innerHTML =
                '<span class="setting-status-dot"></span>Berhak Cuti';
        } else {
            status.classList.remove('status-eligible');
            status.classList.add('status-ineligible');
            status.innerHTML =
                '<span class="setting-status-dot"></span>Belum Berhak';
        }
    }

    function updateSelectAllState() {
        if (!selectAllVisible) return;

        const visible = visibleRows();
        const checked = visible.filter(function (row) {
            const checkbox = row.querySelector('.employee-checkbox');
            return checkbox && checkbox.checked;
        });

        selectAllVisible.checked =
            visible.length > 0 && checked.length === visible.length;

        selectAllVisible.indeterminate =
            checked.length > 0 && checked.length < visible.length;
    }

    function filterEmployees() {
        const keyword = search
            ? search.value.trim().toLocaleLowerCase('id-ID')
            : '';

        let visibleCount = 0;

        rows.forEach(function (row) {
            const matches = row.textContent
                .toLocaleLowerCase('id-ID')
                .includes(keyword);

            row.style.display = matches ? '' : 'none';

            if (matches) visibleCount++;
        });

        if (clearSearch) {
            clearSearch.style.display =
                search && search.value ? 'flex' : 'none';
        }

        if (noResults) {
            noResults.hidden = !(rows.length > 0 && visibleCount === 0);
        }

        updateSelectAllState();
    }

    function setVisibleCheckboxes(checked) {
        visibleRows().forEach(function (row) {
            const checkbox = row.querySelector('.employee-checkbox');

            if (checkbox && !checkbox.disabled) {
                checkbox.checked = checked;
                updateRowStatus(checkbox);
            }
        });

        updateCounts();
        updateSelectAllState();
    }

    if (search) {
        search.addEventListener('input', filterEmployees);
    }

    if (clearSearch) {
        clearSearch.addEventListener('click', function () {
            if (search) {
                search.value = '';
                filterEmployees();
                search.focus();
            }
        });
    }

    if (selectVisible) {
        selectVisible.addEventListener('click', function () {
            setVisibleCheckboxes(true);
        });
    }

    if (unselectVisible) {
        unselectVisible.addEventListener('click', function () {
            setVisibleCheckboxes(false);
        });
    }

    if (selectAllVisible) {
        selectAllVisible.addEventListener('change', function () {
            setVisibleCheckboxes(this.checked);
        });
    }

    table.querySelectorAll('.employee-checkbox').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            updateRowStatus(this);
            updateCounts();
            updateSelectAllState();
        });

        updateRowStatus(checkbox);
    });

    if (saveButton) {
        const form = document.getElementById('annualLeaveSettingForm');

        if (form) {
            form.addEventListener('submit', function () {
                saveButton.disabled = true;
                saveButton.innerHTML =
                    '<span>Menyimpan pengaturan...</span>';
            });
        }
    }

    filterEmployees();
    updateCounts();
});
</script>
@endpush