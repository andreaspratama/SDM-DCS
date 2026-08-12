<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Halaman Tidak Ditemukan</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background:
                radial-gradient(circle at top left, #4f46e5, transparent 35%),
                radial-gradient(circle at bottom right, #06b6d4, transparent 35%),
                #f8fafc;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-lg">

        <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl p-8 text-center">

            {{-- ICON --}}
            <div class="mx-auto mb-6 flex items-center justify-center
                        w-24 h-24 rounded-full bg-indigo-100">

                <span class="text-5xl">🔍</span>

            </div>

            {{-- CODE --}}
            <h1 class="text-7xl font-black text-indigo-600">
                404
            </h1>

            <h2 class="mt-4 text-2xl font-bold text-gray-800">
                Halaman Tidak Ditemukan
            </h2>

            <p class="mt-3 text-gray-500 leading-relaxed">
                Maaf, halaman yang kamu cari tidak ditemukan atau
                alamat yang digunakan sudah tidak tersedia.
            </p>

            {{-- INFO --}}
            <div class="mt-6 p-4 rounded-2xl bg-indigo-50 border border-indigo-100 text-left">

                <div class="flex gap-3">

                    <div class="text-xl">
                        🛡️
                    </div>

                    <div>
                        <p class="font-semibold text-indigo-800">
                            Sistem Absensi Digital
                        </p>

                        <p class="text-sm text-indigo-600 mt-1">
                            Pastikan link formulir yang kamu gunakan
                            benar dan masih aktif.
                        </p>
                    </div>

                </div>

            </div>

            {{-- BUTTON --}}
            <div class="mt-7">

                <button
                    onclick="history.back()"
                    class="inline-flex items-center justify-center
                           px-6 py-3 rounded-xl
                           bg-gradient-to-r from-indigo-500 to-cyan-500
                           text-white font-semibold
                           shadow-lg
                           hover:scale-105
                           transition duration-200">

                    ← Kembali

                </button>

            </div>

            {{-- FOOTER --}}
            <p class="mt-8 text-xs text-gray-400">
                Sistem Absensi Digital
            </p>

        </div>

    </div>

</body>
</html>