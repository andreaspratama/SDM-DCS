<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Izin</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-xl">

    {{-- HEADER --}}
    <div class="text-center text-white mb-6">
        <h1 class="text-3xl font-bold">Form Izin</h1>
        <p class="text-sm opacity-80">Isi data izin dengan lengkap</p>
    </div>

    {{-- CARD --}}
    <div class="backdrop-blur-xl bg-white/90 rounded-3xl shadow-2xl p-6 transition hover:scale-[1.01]">

        {{-- SUCCESS --}}
        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- ERROR --}}
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                <b>⚠️ Terjadi kesalahan:</b>
                <ul class="mt-2 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('formIzinStore') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Employee --}}
            <div>
                <label class="text-sm font-semibold text-gray-600">Nama Karyawan</label>
                <select name="employee_id" required
                    class="w-full mt-2 p-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-400 outline-none transition">
                    <option value="">Pilih karyawan</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->nama }}</option>
                    @endforeach
                </select>
            </div>

            {{-- DATE --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-gray-600">Tanggal Mulai</label>
                    <input type="date" name="date_start" required
                        class="w-full mt-2 p-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-400">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Tanggal Selesai</label>
                    <input type="date" name="date_end" required
                        class="w-full mt-2 p-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-400">
                </div>
            </div>

            {{-- TIME --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-gray-600">Jam Keluar</label>
                    <input type="time" name="time_start"
                        class="w-full mt-2 p-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-400">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Jam Kembali</label>
                    <input type="time" name="time_end"
                        class="w-full mt-2 p-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-400">
                </div>
            </div>

            {{-- TYPE --}}
            <div>
                <label class="text-sm text-gray-600">Jenis Izin</label>
                <select name="type"
                    class="w-full mt-2 p-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-400">
                    <option value="Izin Keluar">Izin Keluar</option>
                    <option value="Izin Terlambat">Izin Terlambat</option>
                    <option value="Keperluan Pribadi">Keperluan Pribadi</option>
                    <option value="Cuti">Cuti</option>
                    <option value="Sakit">Sakit</option>
                </select>
            </div>

            {{-- DESCRIPTION --}}
            <div>
                <label class="text-sm text-gray-600">Keterangan</label>
                <textarea name="description" rows="3"
                    class="w-full mt-2 p-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-400"
                    placeholder="Contoh: Ke bank, ke rumah sakit..."></textarea>
            </div>

            {{-- FILE --}}
            <div>
                <label class="text-sm text-gray-600">Lampiran</label>
                <div class="mt-2 border-2 border-dashed border-gray-300 rounded-xl p-4 text-center cursor-pointer hover:border-indigo-400 transition">
                    <input type="file" name="attachment" class="w-full text-sm">
                    <p class="text-xs text-gray-400 mt-2">Upload file jika ada</p>
                </div>
            </div>

            {{-- BUTTON --}}
            <button type="submit"
                class="w-full bg-gradient-to-r from-indigo-500 to-cyan-500 text-white font-semibold py-3 rounded-xl shadow-lg hover:scale-[1.02] hover:shadow-xl transition duration-200">
                🚀 Simpan Izin
            </button>

        </form>

    </div>

    {{-- FOOTER --}}
    <p class="text-center text-white text-xs mt-4 opacity-70">
        Sistem Absensi Digital
    </p>

</div>

</body>
</html>