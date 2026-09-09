@extends('layouts.admin')

@section('title')
    Approval Izin
@endsection

@section('content')

<main class="app-main">

    <div class="app-content-header">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1">
                        Approval Izin Pegawai
                    </h1>

                    <div class="text-muted">
                        Daftar pengajuan izin pegawai
                    </div>
                </div>
            </div>

        </div>
    </div>


    <div class="app-content">
        <div class="container-fluid">


            {{-- ALERT --}}
            @if(session('message'))
                <div class="alert alert-success">
                    {{ session('message') }}
                </div>
            @endif


            <div class="card shadow-sm">

                <div class="card-header bg-white">
                    <b>📄 Daftar Pengajuan Izin</b>
                </div>


                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead class="table-light">
                                <tr>
                                    <th width="50">No</th>
                                    <th>Pegawai</th>
                                    <th>Unit</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Jenis</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                    <th>Lampiran</th>
                                    <th width="260">Aksi</th>
                                </tr>
                            </thead>


                            <tbody>

                                @forelse($permissions as $permission)

                                    <tr>

                                        {{-- NO --}}
                                        <td>
                                            {{ $loop->iteration }}
                                        </td>


                                        {{-- PEGAWAI --}}
                                        <td>
                                            <b>
                                                {{ $permission->employee->nama ?? '-' }}
                                            </b>
                                        </td>


                                        {{-- UNIT --}}
                                        <td>
                                            {{ $permission->employee->unit->nama ?? '-' }}
                                        </td>


                                        {{-- TANGGAL --}}
                                        <td>

                                            {{ optional($permission->date_start)->format('d-m-Y') }}

                                            @if(
                                                $permission->date_end &&
                                                $permission->date_start &&
                                                $permission->date_end->format('Y-m-d')
                                                !==
                                                $permission->date_start->format('Y-m-d')
                                            )

                                                <br>

                                                <span class="text-muted">
                                                    s/d
                                                    {{ $permission->date_end->format('d-m-Y') }}
                                                </span>

                                            @endif

                                        </td>


                                        {{-- JAM --}}
                                        <td>

                                            {{-- ================================================
                                                IZIN KELUAR SEMENTARA
                                            ================================================= --}}
                                            @if(
                                                in_array(
                                                    $permission->type,
                                                    [
                                                        'Izin Keluar Sementara',
                                                        'Keperluan Pribadi'
                                                    ],
                                                    true
                                                )
                                            )

                                                @if($permission->time_start && $permission->time_end)

                                                    <span style="font-weight:600;">

                                                        {{ substr($permission->time_start, 0, 5) }}

                                                        <br>

                                                        <span style="font-weight:400;">
                                                            s/d
                                                        </span>

                                                        <br>

                                                        {{ substr($permission->time_end, 0, 5) }}

                                                    </span>

                                                @else

                                                    <span class="text-muted">
                                                        -
                                                    </span>

                                                @endif


                                            {{-- ================================================
                                                IZIN TERLAMBAT
                                            ================================================= --}}
                                            @elseif($permission->type === 'Izin Terlambat')

                                                @if($permission->time_start)

                                                    <div style="color:#dc3545; font-weight:600;">

                                                        <i class="fa-solid fa-clock me-1"></i>

                                                        Datang:
                                                        {{ substr($permission->time_start, 0, 5) }}

                                                    </div>

                                                @else

                                                    <span class="text-muted">
                                                        Jam belum diisi
                                                    </span>

                                                @endif


                                            {{-- ================================================
                                                IZIN PULANG AWAL
                                            ================================================= --}}
                                            @elseif($permission->type === 'Izin Pulang Awal')

                                                @if($permission->time_start)

                                                    <div style="color:#fd7e14; font-weight:600;">

                                                        <i class="fa-solid fa-person-walking-arrow-right me-1"></i>

                                                        Pulang:
                                                        {{ substr($permission->time_start, 0, 5) }}

                                                    </div>

                                                @else

                                                    <span class="text-muted">
                                                        Jam belum diisi
                                                    </span>

                                                @endif


                                            {{-- ================================================
                                                IZIN FULL DAY
                                            ================================================= --}}
                                            @else

                                                <span style="
                                                    color:#6c757d;
                                                    font-weight:600;
                                                ">

                                                    <i class="fa-solid fa-calendar-day me-1"></i>
                                                    Full Day

                                                </span>

                                            @endif

                                        </td>


                                        {{-- JENIS --}}
                                        <td>
                                            <b>
                                                {{ $permission->type }}
                                            </b>
                                        </td>


                                        {{-- KETERANGAN --}}
                                        <td style="min-width:200px;">
                                            {{ $permission->description ?? '-' }}
                                        </td>


                                        {{-- STATUS --}}
                                        <td>

                                            @if($permission->status === 'pending')

                                                <span class="badge bg-warning text-dark">
                                                    ⏳ Pending
                                                </span>

                                            @elseif($permission->status === 'approved')

                                                <span class="badge bg-success">
                                                    ✔ Approved
                                                </span>

                                            @elseif($permission->status === 'rejected')

                                                <span class="badge bg-danger">
                                                    ✖ Rejected
                                                </span>

                                            @else

                                                <span class="badge bg-secondary">
                                                    {{ $permission->status }}
                                                </span>

                                            @endif


                                            @if($permission->approved_at)

                                                <div
                                                    class="small text-muted mt-1"
                                                >
                                                    {{ $permission->approved_at->format('d-m-Y H:i') }}
                                                </div>

                                            @endif

                                        </td>


                                        {{-- LAMPIRAN --}}
                                        <td>

                                            @if($permission->attachment)

                                                <a
                                                    href="{{ asset('storage/'.$permission->attachment) }}"
                                                    target="_blank"
                                                    class="btn btn-sm btn-outline-primary"
                                                >
                                                    📎 Lihat
                                                </a>

                                            @else

                                                <span class="text-muted">
                                                    -
                                                </span>

                                            @endif

                                        </td>


                                        {{-- AKSI --}}
                                        <td>

                                            @if($permission->status === 'pending')

                                                <div class="d-flex gap-2 flex-wrap">


                                                    {{-- APPROVE --}}
                                                    <form
                                                        action="{{ route('attendancePermission.approve', $permission->id) }}"
                                                        method="POST"
                                                    >

                                                        @csrf

                                                        <button
                                                            type="submit"
                                                            class="btn btn-success btn-sm"
                                                            onclick="return confirm('Setujui izin ini?')"
                                                        >
                                                            ✔ Setujui
                                                        </button>

                                                    </form>


                                                    {{-- REJECT --}}
                                                    <form
                                                        action="{{ route('attendancePermission.reject', $permission->id) }}"
                                                        method="POST"
                                                    >

                                                        @csrf

                                                        <button
                                                            type="submit"
                                                            class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Tolak izin ini?')"
                                                        >
                                                            ✖ Tolak
                                                        </button>

                                                    </form>

                                                </div>

                                            @else

                                                <div class="small text-muted">

                                                    Sudah diproses

                                                    @if($permission->approvedBy)

                                                        <br>

                                                        Oleh:
                                                        <b>
                                                            {{ $permission->approvedBy->name ?? '-' }}
                                                        </b>

                                                    @endif

                                                </div>

                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td
                                            colspan="10"
                                            class="text-center py-5 text-muted"
                                        >

                                            📭 Belum ada pengajuan izin

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>
    </div>

</main>

@endsection