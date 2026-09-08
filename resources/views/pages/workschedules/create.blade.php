@extends('layouts.admin')

@section('title')
    Tambah Template Jadwal
@endsection


@section('content')

<main class="app-main">

    <div class="app-content-header">
        <div class="container-fluid">

            <div class="d-flex
                        justify-content-between
                        align-items-center">

                <div>
                    <h1 class="mb-1 fs-3">
                        Tambah Template Jadwal
                    </h1>

                    <div class="text-muted">
                        Buat template jadwal kerja baru
                        untuk pegawai.
                    </div>
                </div>

                <a
                    href="{{ route('workSchedule.index') }}"
                    class="btn btn-light border"
                >
                    Kembali
                </a>

            </div>

        </div>
    </div>


    <div class="app-content">
        <div class="container-fluid">

            @if ($errors->any())

                <div class="alert alert-danger">

                    <div class="fw-bold mb-2">
                        Data belum dapat disimpan
                    </div>

                    <ul class="mb-0">

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            <div class="card">

                <div class="card-header">

                    <h3 class="card-title">
                        Data Template Jadwal
                    </h3>

                </div>


                <form
                    action="{{ route('workSchedule.store') }}"
                    method="POST"
                >

                    @csrf


                    <div class="card-body">

                        {{-- NAMA --}}
                        <div class="mb-4">

                            <label
                                class="form-label fw-semibold"
                            >
                                Nama Template
                            </label>

                            <input
                                type="text"
                                name="nama"
                                class="form-control"
                                value="{{ old('nama') }}"
                                placeholder="Contoh: SHS Perpus"
                                required
                            >

                        </div>


                        <hr>


                        <h5 class="mb-3">
                            Jadwal Mingguan
                        </h5>


                        @php

                            $hariList = [
                                1 => 'Senin',
                                2 => 'Selasa',
                                3 => 'Rabu',
                                4 => 'Kamis',
                                5 => 'Jumat',
                                6 => 'Sabtu',
                                7 => 'Minggu',
                            ];

                        @endphp


                        @foreach($hariList as $no => $namaHari)

                            @php

                                $defaultLibur =
                                    in_array(
                                        $no,
                                        [6, 7]
                                    );

                            @endphp


                            <div
                                class="border rounded p-3 mb-3"
                            >

                                <input
                                    type="hidden"
                                    name="days[{{ $no }}][hari]"
                                    value="{{ $no }}"
                                >


                                <div class="row align-items-end">

                                    <div class="col-md-3">

                                        <div class="fw-bold mb-2">
                                            {{ $namaHari }}
                                        </div>

                                        <div class="form-check">

                                            <input
                                                type="hidden"
                                                name="days[{{ $no }}][is_libur]"
                                                value="0"
                                            >

                                            <input
                                                class="form-check-input libur-check"
                                                type="checkbox"
                                                name="days[{{ $no }}][is_libur]"
                                                value="1"
                                                data-day="{{ $no }}"
                                                id="libur{{ $no }}"
                                                @checked(
                                                    old(
                                                        "days.$no.is_libur",
                                                        $defaultLibur ? 1 : 0
                                                    ) == 1
                                                )
                                            >

                                            <label
                                                class="form-check-label"
                                                for="libur{{ $no }}"
                                            >
                                                Libur
                                            </label>

                                        </div>

                                    </div>


                                    <div class="col-md-4">

                                        <label class="form-label">
                                            Jam Masuk
                                        </label>

                                        <input
                                            type="time"
                                            name="days[{{ $no }}][jam_masuk]"
                                            id="masuk{{ $no }}"
                                            class="form-control"
                                            value="{{
                                                old(
                                                    "days.$no.jam_masuk",
                                                    !$defaultLibur
                                                        ? '07:00'
                                                        : ''
                                                )
                                            }}"
                                        >

                                    </div>


                                    <div class="col-md-4">

                                        <label class="form-label">
                                            Jam Pulang
                                        </label>

                                        <input
                                            type="time"
                                            name="days[{{ $no }}][jam_pulang]"
                                            id="pulang{{ $no }}"
                                            class="form-control"
                                            value="{{
                                                old(
                                                    "days.$no.jam_pulang",
                                                    !$defaultLibur
                                                        ? '15:30'
                                                        : ''
                                                )
                                            }}"
                                        >

                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>


                    <div class="card-footer">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Simpan Template
                        </button>

                        <a
                            href="{{ route('workSchedule.index') }}"
                            class="btn btn-light border"
                        >
                            Batal
                        </a>

                    </div>

                </form>

            </div>

        </div>
    </div>

</main>

@endsection


@push('addon-script')

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        document
            .querySelectorAll('.libur-check')
            .forEach(function (checkbox) {

                function updateInput() {

                    const day =
                        checkbox.dataset.day;

                    const masuk =
                        document.getElementById(
                            'masuk' + day
                        );

                    const pulang =
                        document.getElementById(
                            'pulang' + day
                        );


                    masuk.disabled =
                        checkbox.checked;

                    pulang.disabled =
                        checkbox.checked;


                    if (checkbox.checked) {

                        masuk.value = '';
                        pulang.value = '';

                    }

                }


                checkbox.addEventListener(
                    'change',
                    updateInput
                );

                updateInput();

            });

    }
);

</script>

@endpush