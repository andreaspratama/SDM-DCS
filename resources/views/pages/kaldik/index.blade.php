@extends('layouts.admin')

@section('content')

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Kalender Pendidikan</h3>
            <p class="text-muted mb-0">
                Pengaturan hari efektif dan hari libur sekolah
            </p>
        </div>

        <a href="{{ route('school-calendar.create') }}"
           class="btn btn-primary">
            + Tambah Kalender
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Periode</th>
                            <th>Nama</th>
                            <th>Jenis</th>
                            <th>Hari Kerja</th>
                            <th>Keterangan</th>
                            <th width="160">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($calendars as $calendar)

                            <tr>

                                <td>
                                    {{ $loop->iteration }}
                                </td>

                                <td>
                                    {{ $calendar->tanggal_mulai->format('d/m/Y') }}
                                    -
                                    {{ $calendar->tanggal_selesai->format('d/m/Y') }}
                                </td>

                                <td>
                                    {{ $calendar->nama }}
                                </td>

                                <td>
                                    {{ ucwords(str_replace('_', ' ', $calendar->jenis)) }}
                                </td>

                                <td>
                                    @if($calendar->is_hari_kerja)
                                        <span class="badge bg-success">
                                            Ya
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            Tidak
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $calendar->keterangan ?? '-' }}
                                </td>

                                <td>

                                    <a href="{{ route('school-calendar.edit', $calendar) }}"
                                       class="btn btn-sm btn-warning">
                                        Edit
                                    </a>

                                    <form action="{{ route('school-calendar.destroy', $calendar) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Hapus data kalender ini?')">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-sm btn-danger">
                                            Hapus
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="7"
                                    class="text-center text-muted py-4">

                                    Belum ada data kalender pendidikan.

                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>

@endsection