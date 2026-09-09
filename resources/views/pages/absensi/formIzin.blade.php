<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Form Izin</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>

        body {
            background:
                linear-gradient(
                    135deg,
                    #4f46e5,
                    #06b6d4
                );
        }

    </style>

</head>


<body class="min-h-screen flex items-center justify-center p-4">


<div class="w-full max-w-xl">


    {{-- =====================================================
         HEADER
    ====================================================== --}}
    <div class="text-center text-white mb-6">

        <h1 class="text-3xl font-bold">
            Form Izin
        </h1>

        @if(isset($unit))

            <p class="mt-1 font-semibold">
                {{ $unit->nama }}
            </p>

        @endif

        <p class="text-sm opacity-80">
            Isi data pengajuan izin dengan lengkap
        </p>

    </div>


    {{-- =====================================================
         CARD
    ====================================================== --}}
    <div class="
        backdrop-blur-xl
        bg-white/95
        rounded-3xl
        shadow-2xl
        p-6
    ">


        {{-- =================================================
             SUCCESS
        ================================================== --}}
        @if(session('success'))

            <div class="
                mb-5
                p-4
                bg-green-100
                border
                border-green-200
                text-green-700
                rounded-xl
                text-sm
            ">

                {{ session('success') }}

            </div>

        @endif


        {{-- =================================================
             ERROR
        ================================================== --}}
        @if($errors->any())

            <div class="
                mb-5
                p-4
                bg-red-100
                border
                border-red-200
                text-red-700
                rounded-xl
                text-sm
            ">

                <div class="font-bold mb-2">
                    ⚠️ Pengajuan belum dapat disimpan
                </div>

                <ul class="list-disc list-inside space-y-1">

                    @foreach($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        {{-- =================================================
             FORM
        ================================================== --}}
        <form
            action="{{ route('formIzinStore', ['token' => $token]) }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-5"
            id="formIzin"
        >

            @csrf


            {{-- =================================================
                 EMPLOYEE
            ================================================== --}}
            <div>

                <label
                    class="
                        text-sm
                        font-semibold
                        text-gray-600
                    "
                >
                    Nama Karyawan
                </label>


                <select
                    name="employee_id"
                    required
                    class="
                        w-full
                        mt-2
                        p-3
                        rounded-xl
                        border
                        border-gray-200
                        focus:ring-2
                        focus:ring-indigo-400
                        outline-none
                    "
                >

                    <option value="">
                        Pilih karyawan
                    </option>


                    @foreach($employees as $emp)

                        <option
                            value="{{ $emp->id }}"
                            @selected(
                                old('employee_id')
                                == $emp->id
                            )
                        >

                            {{ $emp->nama }}

                        </option>

                    @endforeach

                </select>

            </div>


            {{-- =================================================
                 JENIS IZIN
            ================================================== --}}
            <div>

                <label
                    class="
                        text-sm
                        font-semibold
                        text-gray-600
                    "
                >
                    Jenis Izin
                </label>


                <select
                    name="type"
                    id="type"
                    required
                    class="
                        w-full
                        mt-2
                        p-3
                        rounded-xl
                        border
                        border-gray-200
                        focus:ring-2
                        focus:ring-indigo-400
                        outline-none
                    "
                >

                    <option value="">
                        Pilih jenis izin
                    </option>


                    {{-- =====================================
                         IZIN OPERASIONAL
                    ====================================== --}}
                    <optgroup label="Izin Operasional">

                        <option
                            value="Izin Keluar Sementara"
                            @selected(
                                old('type')
                                === 'Izin Keluar Sementara'
                            )
                        >
                            Izin Keluar Sementara
                        </option>


                        <option
                            value="Izin Terlambat"
                            @selected(
                                old('type')
                                === 'Izin Terlambat'
                            )
                        >
                            Izin Terlambat
                        </option>


                        <option
                            value="Izin Pulang Awal"
                            @selected(
                                old('type')
                                === 'Izin Pulang Awal'
                            )
                        >
                            Izin Pulang Awal
                        </option>


                        <option
                            value="Izin Tidak Masuk"
                            @selected(
                                old('type')
                                === 'Izin Tidak Masuk'
                            )
                        >
                            Izin Tidak Masuk
                        </option>


                        <option
                            value="Sakit"
                            @selected(
                                old('type')
                                === 'Sakit'
                            )
                        >
                            Sakit
                        </option>

                    </optgroup>


                    {{-- =====================================
                         IZIN KHUSUS KEPEGAWAIAN
                    ====================================== --}}
                    <optgroup label="Izin Khusus / Kepegawaian">

                        <option
                            value="Pernikahan Pegawai"
                            @selected(
                                old('type')
                                === 'Pernikahan Pegawai'
                            )
                        >
                            Pernikahan Pegawai
                        </option>


                        <option
                            value="Pernikahan Anak Pegawai"
                            @selected(
                                old('type')
                                === 'Pernikahan Anak Pegawai'
                            )
                        >
                            Pernikahan Anak Pegawai
                        </option>


                        <option
                            value="Istri Pegawai Melahirkan / Gugur Kandungan"
                            @selected(
                                old('type')
                                ===
                                'Istri Pegawai Melahirkan / Gugur Kandungan'
                            )
                        >
                            Istri Pegawai Melahirkan /
                            Gugur Kandungan
                        </option>


                        <option
                            value="Suami/istri/anak/orangtua/mertua Pegawai masuk RS"
                            @selected(
                                old('type')
                                ===
                                'Suami/istri/anak/orangtua/mertua Pegawai masuk RS'
                            )
                        >
                            Suami/istri/anak/orangtua/mertua
                            Pegawai masuk RS
                        </option>


                        <option
                            value="Izin Lainnya"
                            @selected(
                                old('type')
                                ===
                                'Izin Lainnya'
                            )
                        >
                            Izin Lainnya
                        </option>

                    </optgroup>

                </select>

            </div>


            {{-- =================================================
                 INFO JENIS IZIN
            ================================================== --}}
            <div
                id="permissionInfo"
                class="
                    hidden
                    p-3
                    bg-blue-50
                    text-blue-700
                    border
                    border-blue-100
                    rounded-xl
                    text-sm
                "
            >
            </div>


            {{-- =================================================
                 TANGGAL
            ================================================== --}}
            <div
                class="
                    grid
                    grid-cols-1
                    md:grid-cols-2
                    gap-4
                "
            >


                {{-- TANGGAL MULAI --}}
                <div>

                    <label
                        class="
                            text-sm
                            text-gray-600
                        "
                        id="dateStartLabel"
                    >
                        Tanggal Mulai
                    </label>


                    <input
                        type="date"
                        name="date_start"
                        id="date_start"
                        value="{{ old('date_start') }}"
                        required
                        class="
                            w-full
                            mt-2
                            p-3
                            rounded-xl
                            border
                            border-gray-200
                            focus:ring-2
                            focus:ring-indigo-400
                        "
                    >

                </div>


                {{-- TANGGAL SELESAI --}}
                <div id="dateEndWrapper">

                    <label
                        class="
                            text-sm
                            text-gray-600
                        "
                    >
                        Tanggal Selesai
                    </label>


                    <input
                        type="date"
                        name="date_end"
                        id="date_end"
                        value="{{ old('date_end') }}"
                        class="
                            w-full
                            mt-2
                            p-3
                            rounded-xl
                            border
                            border-gray-200
                            focus:ring-2
                            focus:ring-indigo-400
                        "
                    >

                </div>

            </div>


            {{-- =================================================
                 JAM
            ================================================== --}}
            <div
                id="timeSection"
                class="
                    hidden
                    grid
                    grid-cols-1
                    md:grid-cols-2
                    gap-4
                "
            >


                {{-- JAM AWAL --}}
                <div id="timeStartWrapper">

                    <label
                        id="timeStartLabel"
                        class="
                            text-sm
                            text-gray-600
                        "
                    >
                        Jam Awal
                    </label>


                    <input
                        type="time"
                        name="time_start"
                        id="time_start"
                        value="{{ old('time_start') }}"
                        class="
                            w-full
                            mt-2
                            p-3
                            rounded-xl
                            border
                            border-gray-200
                            focus:ring-2
                            focus:ring-indigo-400
                        "
                    >

                </div>


                {{-- JAM AKHIR --}}
                <div id="timeEndWrapper">

                    <label
                        id="timeEndLabel"
                        class="
                            text-sm
                            text-gray-600
                        "
                    >
                        Jam Akhir
                    </label>


                    <input
                        type="time"
                        name="time_end"
                        id="time_end"
                        value="{{ old('time_end') }}"
                        class="
                            w-full
                            mt-2
                            p-3
                            rounded-xl
                            border
                            border-gray-200
                            focus:ring-2
                            focus:ring-indigo-400
                        "
                    >

                </div>

            </div>


            {{-- =================================================
                 DESCRIPTION
            ================================================== --}}
            <div>

                <label
                    class="
                        text-sm
                        text-gray-600
                    "
                >
                    Keterangan
                </label>


                <textarea
                    name="description"
                    rows="3"
                    class="
                        w-full
                        mt-2
                        p-3
                        rounded-xl
                        border
                        border-gray-200
                        focus:ring-2
                        focus:ring-indigo-400
                    "
                    placeholder="Tuliskan keterangan izin..."
                >{{ old('description') }}</textarea>

            </div>


            {{-- =================================================
                 ATTACHMENT
            ================================================== --}}
            <div>

                <label
                    class="
                        text-sm
                        text-gray-600
                    "
                >
                    Lampiran
                    <span class="text-gray-400">
                        (opsional)
                    </span>
                </label>


                <div class="
                    mt-2
                    border-2
                    border-dashed
                    border-gray-300
                    rounded-xl
                    p-4
                    hover:border-indigo-400
                    transition
                ">

                    <input
                        type="file"
                        name="attachment"
                        accept=".jpg,.jpeg,.png,.pdf"
                        class="
                            w-full
                            text-sm
                            text-gray-600
                        "
                    >

                    <p class="
                        text-xs
                        text-gray-400
                        mt-2
                    ">
                        JPG, PNG atau PDF.
                        Maksimal 2 MB.
                    </p>

                </div>

            </div>


            {{-- =================================================
                 BUTTON
            ================================================== --}}
            <button
                type="submit"
                class="
                    w-full
                    bg-gradient-to-r
                    from-indigo-500
                    to-cyan-500
                    text-white
                    font-semibold
                    py-3
                    rounded-xl
                    shadow-lg
                    hover:scale-[1.01]
                    hover:shadow-xl
                    transition
                    duration-200
                "
            >

                🚀 Kirim Pengajuan Izin

            </button>

        </form>

    </div>


    {{-- =====================================================
         FOOTER
    ====================================================== --}}
    <p class="
        text-center
        text-white
        text-xs
        mt-4
        opacity-70
    ">
        Sistem Absensi Digital
    </p>

</div>


{{-- =========================================================
     JAVASCRIPT
========================================================== --}}
<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const type =
            document.getElementById('type');

        const dateStart =
            document.getElementById('date_start');

        const dateEnd =
            document.getElementById('date_end');

        const dateEndWrapper =
            document.getElementById(
                'dateEndWrapper'
            );

        const dateStartLabel =
            document.getElementById(
                'dateStartLabel'
            );

        const timeSection =
            document.getElementById(
                'timeSection'
            );

        const timeStartWrapper =
            document.getElementById(
                'timeStartWrapper'
            );

        const timeEndWrapper =
            document.getElementById(
                'timeEndWrapper'
            );

        const timeStart =
            document.getElementById(
                'time_start'
            );

        const timeEnd =
            document.getElementById(
                'time_end'
            );

        const timeStartLabel =
            document.getElementById(
                'timeStartLabel'
            );

        const timeEndLabel =
            document.getElementById(
                'timeEndLabel'
            );

        const permissionInfo =
            document.getElementById(
                'permissionInfo'
            );


        function updateForm() {

            const value =
                type.value;


            const isKeluar =
                value === 'Izin Keluar Sementara';


            const isTerlambat =
                value === 'Izin Terlambat';

            const isPulangAwal =
                value === 'Izin Pulang Awal';


            const isPartial =
                isKeluar
                ||
                isTerlambat
                ||
                isPulangAwal;


            // =============================================
            // DEFAULT
            // =============================================
            dateStartLabel.textContent =
                'Tanggal Mulai';

            dateEndWrapper.classList.remove(
                'hidden'
            );

            dateEnd.required =
                true;

            timeSection.classList.add(
                'hidden'
            );

            timeStart.required =
                false;

            timeEnd.required =
                false;

            timeStartWrapper.classList.remove(
                'hidden'
            );

            timeEndWrapper.classList.remove(
                'hidden'
            );

            permissionInfo.classList.add(
                'hidden'
            );


            // =============================================
            // PARTIAL
            // =============================================
            if (isPartial) {

                dateStartLabel.textContent =
                    'Tanggal';

                dateEndWrapper.classList.add(
                    'hidden'
                );

                dateEnd.required =
                    false;

                dateEnd.value =
                    dateStart.value;
            }


            // =============================================
            // IZIN KELUAR /
            // KEPERLUAN PRIBADI
            // =============================================
            if (isKeluar) {

                timeSection.classList.remove(
                    'hidden'
                );

                timeStartLabel.textContent =
                    'Jam Keluar';

                timeEndLabel.textContent =
                    'Jam Kembali';

                timeStart.required =
                    true;

                timeEnd.required =
                    true;


                permissionInfo.textContent =
                    'Isi jam keluar dan jam kembali. '
                    + 'Izin ini berlaku untuk satu tanggal.';

                permissionInfo.classList.remove(
                    'hidden'
                );

            }


            // =============================================
            // IZIN TERLAMBAT
            // =============================================
            else if (isTerlambat) {

                timeSection.classList.remove(
                    'hidden'
                );

                timeStartLabel.textContent =
                    'Jam Perkiraan Datang';

                timeStart.required =
                    true;


                timeEndWrapper.classList.add(
                    'hidden'
                );

                timeEnd.required =
                    false;

                timeEnd.value =
                    '';


                permissionInfo.textContent =
                    'Masukkan jam perkiraan datang. '
                    + 'Izin terlambat berlaku untuk satu tanggal.';

                permissionInfo.classList.remove(
                    'hidden'
                );

            }

            // PULANG AWAL
            else if (isPulangAwal) {

                timeSection.classList.remove(
                    'hidden'
                );

                timeStartLabel.textContent =
                    'Jam Pulang';

                timeStart.required =
                    true;


                // Jam akhir tidak diperlukan
                timeEndWrapper.classList.add(
                    'hidden'
                );

                timeEnd.required =
                    false;

                timeEnd.value =
                    '';


                permissionInfo.textContent =
                    'Masukkan jam izin pulang. '
                    + 'Izin pulang awal berlaku untuk satu tanggal.';

                permissionInfo.classList.remove(
                    'hidden'
                );
            }


            // =============================================
            // FULL DAY
            // =============================================
            else {

                timeStart.required =
                    false;

                timeEnd.required =
                    false;

                timeStart.value =
                    '';

                timeEnd.value =
                    '';

                if (value) {

                    permissionInfo.textContent =
                        'Jenis izin ini menggunakan '
                        + 'rentang tanggal dan tidak '
                        + 'memerlukan jam.';

                    permissionInfo.classList.remove(
                        'hidden'
                    );
                }
            }
        }


        // =================================================
        // SAAT TYPE BERUBAH
        // =================================================
        type.addEventListener(
            'change',
            updateForm
        );


        // =================================================
        // SYNC TANGGAL PARTIAL
        // =================================================
        dateStart.addEventListener(
            'change',
            function () {

                const value =
                    type.value;

                const isPartial =
                    value === 'Izin Keluar Sementara'
                    ||
                    value === 'Izin Terlambat'
                    ||
                    value === 'Izin Pulang Awal';


                if (isPartial) {

                    dateEnd.value =
                        dateStart.value;
                }
            }
        );


        // =================================================
        // FIRST LOAD
        // =================================================
        updateForm();

    }
);

</script>


</body>
</html>