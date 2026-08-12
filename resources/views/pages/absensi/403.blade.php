<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Akses Ditolak | Sistem Absensi</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background:
                radial-gradient(circle at top left, rgba(79, 70, 229, 0.35), transparent 35%),
                radial-gradient(circle at bottom right, rgba(6, 182, 212, 0.30), transparent 35%),
                linear-gradient(135deg, #111827, #1e293b);
        }

        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .lock-icon {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center px-4 py-8">

    <div class="w-full max-w-lg">

        {{-- LOGO / HEADER --}}
        <div class="text-center text-white mb-6">

            <div class="inline-flex items-center justify-center
                        w-16 h-16 rounded-2xl
                        bg-white/10 backdrop-blur-md
                        border border-white/20
                        shadow-xl mb-4">

                <span class="text-3xl">
                    🔐
                </span>

            </div>

            <h1 class="text-2xl md:text-3xl font-bold">
                Sistem Absensi Digital
            </h1>

            <p class="text-sm text-white/70 mt-1">
                Formulir Pengajuan Izin
            </p>

        </div>


        {{-- CARD --}}
        <div class="glass rounded-3xl shadow-2xl p-7 md:p-9 text-center">

            {{-- ICON --}}
            <div class="lock-icon mx-auto mb-5
                        flex items-center justify-center
                        w-24 h-24 rounded-full
                        bg-red-50 border-8 border-red-100">

                <span class="text-5xl">
                    🚫
                </span>

            </div>


            {{-- CODE --}}
            <div class="text-6xl font-black text-gray-800 tracking-tight">
                403
            </div>

            <h2 class="mt-3 text-xl md:text-2xl font-bold text-gray-800">
                Akses Ditolak
            </h2>

            <p class="mt-3 text-gray-500 leading-relaxed">
                {{ $exception->getMessage() ?: 'Kamu tidak memiliki izin untuk mengakses halaman ini.' }}
            </p>


            {{-- SECURITY INFO --}}
            <div class="mt-6 p-4 rounded-2xl
                        bg-amber-50 border border-amber-200
                        text-left">

                <div class="flex gap-3">

                    <div class="text-xl">
                        🛡️
                    </div>

                    <div>

                        <p class="font-semibold text-amber-800 text-sm">
                            Demi keamanan sistem
                        </p>

                        <p class="text-xs text-amber-700 mt-1 leading-relaxed">
                            Formulir ini hanya dapat digunakan untuk
                            karyawan yang terdaftar pada unit yang
                            diberikan akses melalui tautan ini.
                        </p>

                    </div>

                </div>

            </div>


            {{-- WHAT TO DO --}}
            <div class="mt-5 text-left">

                <p class="text-sm font-semibold text-gray-700 mb-2">
                    💡 Apa yang harus dilakukan?
                </p>

                <ul class="text-sm text-gray-500 space-y-2">

                    <li class="flex gap-2">
                        <span>•</span>
                        <span>
                            Pastikan memilih nama karyawan dari unit
                            yang benar.
                        </span>
                    </li>

                    <li class="flex gap-2">
                        <span>•</span>
                        <span>
                            Jika kamu mendapatkan tautan yang salah,
                            hubungi administrator.
                        </span>
                    </li>

                </ul>

            </div>


            {{-- BUTTON --}}
            <div class="mt-7">

                <button
                    onclick="history.back()"
                    type="button"
                    class="w-full py-3.5 px-5
                           rounded-xl
                           bg-gradient-to-r from-indigo-600 to-cyan-500
                           text-white font-semibold
                           shadow-lg
                           hover:shadow-xl
                           hover:-translate-y-0.5
                           transition duration-200">

                    ← Kembali ke Form

                </button>

            </div>

        </div>


        {{-- FOOTER --}}
        <div class="text-center text-white/50 text-xs mt-5">

            <p>
                © {{ date('Y') }} Sistem Absensi Digital
            </p>

            <p class="mt-1">
                Akses aman • Data terlindungi
            </p>

        </div>

    </div>

</body>

</html>