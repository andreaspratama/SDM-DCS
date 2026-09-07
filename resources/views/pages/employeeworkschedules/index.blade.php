@extends('layouts.admin')

@section('title', 'Jadwal Khusus Pegawai')

@section('content')

<style>
    .special-page {
        padding: 24px;
    }

    .special-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 22px;
    }

    .special-header h2 {
        margin: 0 0 5px;
        font-weight: 700;
        color: #1f2937;
    }

    .special-header p {
        margin: 0;
        color: #6b7280;
    }

    .special-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,0,0,.04);
        overflow: hidden;
    }

    .filter-card {
        padding: 20px;
        margin-bottom: 20px;
    }

    .info-box {
        padding: 15px 16px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
        border-radius: 12px;
        margin-bottom: 20px;
        font-size: 13px;
    }

    .special-table {
        margin: 0;
    }

    .special-table th {
        background: #f8fafc;
        color: #64748b;
        font-size: 12px;
        text-transform: uppercase;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .special-table td {
        padding: 15px 16px;
        vertical-align: middle;
        border-color: #f1f5f9;
    }

    .employee-name {
        font-weight: 700;
        color: #1f2937;
    }

    .employee-unit {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 2px;
    }

    .schedule-name {
        font-weight: 700;
        color: #334155;
    }

    .schedule-note {
        color: #94a3b8;
        font-size: 12px;
        margin-top: 3px;
    }

    .period-badge {
        display: inline-flex;
        padding: 6px 10px;
        border-radius: 8px;
        background: #f1f5f9;
        color: #475569;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .day-badge {
        display: inline-block;
        padding: 4px 7px;
        margin: 2px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
    }

    .day-work {
        background: #dcfce7;
        color: #15803d;
    }

    .day-holiday {
        background: #fee2e2;
        color: #b91c1c;
    }

    .footer-pagination {
        padding: 14px 20px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .page-button {
        display: inline-block;
        padding: 7px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        color: #374151;
        text-decoration: none;
        background: white;
        font-size: 13px;
    }

    .page-button.disabled {
        color: #cbd5e1;
        background: #f8fafc;
        pointer-events: none;
    }

    @media(max-width:768px) {
        .special-page {
            padding: 15px;
        }
    }
</style>

@php
    $dayNames = [
        1 => 'Sen',
        2 => 'Sel',
        3 => 'Rab',
        4 => 'Kam',
        5 => 'Jum',
        6 => 'Sab',
        7 => 'Min',
    ];
@endphp


<div class="special-page">

    <div class="special-header">

        <div>
            <h2>Jadwal Khusus Pegawai</h2>

            <p>
                Kelola perubahan jadwal sementara, part-time,
                Sabtu masuk, dan pola kerja khusus lainnya.
            </p>
        </div>

        <a
            href="{{ route('employeeWorkSchedule.create') }}"
            class="btn btn-primary"
        >
            + Tambah Jadwal Khusus
        </a>

    </div>


    <div class="info-box">

        <strong>Prioritas:</strong>

        Jadwal khusus hanya berlaku pada periode yang ditentukan.
        Di luar periode tersebut, pegawai kembali menggunakan
        template jadwal dasarnya.

    </div>


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


    {{-- FILTER --}}
    <div class="special-card filter-card">

        <form
            method="GET"
            action="{{ route('employeeWorkSchedule.index') }}"
        >

            <div class="row g-3">

                <div class="col-md-5">

                    <label class="form-label fw-semibold">
                        Cari
                    </label>

                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        class="form-control"
                        placeholder="Nama pegawai atau nama jadwal..."
                    >

                </div>


                <div class="col-md-4">

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


                <div class="col-md-3 d-flex align-items-end gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('employeeWorkSchedule.index') }}"
                        class="btn btn-light border"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>


    {{-- TABLE --}}
    <div class="special-card">

        <div class="table-responsive">

            <table class="table special-table">

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Pegawai</th>
                        <th>Nama Jadwal</th>
                        <th>Periode</th>
                        <th>Pola Hari</th>
                        <th width="170">Aksi</th>
                    </tr>
                </thead>


                <tbody>

                    @forelse($specialSchedules as $schedule)

                        <tr>

                            <td class="text-muted">

                                {{
                                    $specialSchedules->firstItem()
                                    + $loop->index
                                }}

                            </td>


                            <td>

                                <div class="employee-name">
                                    {{ $schedule->employee?->nama ?? '-' }}
                                </div>

                                <div class="employee-unit">
                                    {{ $schedule->employee?->unit?->nama ?? '-' }}
                                </div>

                            </td>


                            <td>

                                <div class="schedule-name">
                                    {{ $schedule->nama }}
                                </div>

                                @if($schedule->keterangan)

                                    <div class="schedule-note">
                                        {{ $schedule->keterangan }}
                                    </div>

                                @endif

                            </td>


                            <td>

                                <span class="period-badge">

                                    {{
                                        $schedule->tanggal_mulai
                                            ->format('d/m/Y')
                                    }}

                                    &nbsp;–&nbsp;

                                    {{
                                        $schedule->tanggal_selesai
                                            ->format('d/m/Y')
                                    }}

                                </span>

                            </td>


                            <td>

                                @foreach(range(1, 7) as $hari)

                                    @php
                                        $day = $schedule
                                            ->days
                                            ->firstWhere(
                                                'hari',
                                                $hari
                                            );
                                    @endphp


                                    @if($day && !$day->is_libur)

                                        <span
                                            class="day-badge day-work"
                                            title="{{
                                                substr(
                                                    $day->jam_masuk,
                                                    0,
                                                    5
                                                )
                                                .' - '.
                                                substr(
                                                    $day->jam_pulang,
                                                    0,
                                                    5
                                                )
                                            }}"
                                        >
                                            {{ $dayNames[$hari] }}
                                        </span>

                                    @else

                                        <span
                                            class="day-badge day-holiday"
                                            title="Libur"
                                        >
                                            {{ $dayNames[$hari] }}
                                        </span>

                                    @endif

                                @endforeach

                            </td>


                            <td>

                                <div class="d-flex gap-2">

                                    <a
                                        href="{{
                                            route(
                                                'employeeWorkSchedule.edit',
                                                $schedule->id
                                            )
                                        }}"
                                        class="btn btn-warning btn-sm"
                                    >
                                        Edit
                                    </a>


                                    <form
                                        action="{{
                                            route(
                                                'employeeWorkSchedule.destroy',
                                                $schedule->id
                                            )
                                        }}"
                                        method="POST"
                                        onsubmit="return confirm('Hapus jadwal khusus ini?')"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm"
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
                                colspan="6"
                                class="text-center py-5 text-muted"
                            >
                                Belum ada jadwal khusus pegawai.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($specialSchedules->hasPages())

            <div class="footer-pagination">

                <div class="small text-muted">

                    Menampilkan

                    <strong>
                        {{ $specialSchedules->firstItem() }}
                    </strong>

                    -

                    <strong>
                        {{ $specialSchedules->lastItem() }}
                    </strong>

                    dari

                    <strong>
                        {{ $specialSchedules->total() }}
                    </strong>

                    jadwal

                </div>


                <div class="d-flex gap-2 align-items-center">

                    @if($specialSchedules->onFirstPage())

                        <span class="page-button disabled">
                            ← Sebelumnya
                        </span>

                    @else

                        <a
                            href="{{ $specialSchedules->previousPageUrl() }}"
                            class="page-button"
                        >
                            ← Sebelumnya
                        </a>

                    @endif


                    <span class="small text-muted px-2">

                        Halaman

                        <strong>
                            {{ $specialSchedules->currentPage() }}
                        </strong>

                        dari

                        <strong>
                            {{ $specialSchedules->lastPage() }}
                        </strong>

                    </span>


                    @if($specialSchedules->hasMorePages())

                        <a
                            href="{{ $specialSchedules->nextPageUrl() }}"
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

@endsection