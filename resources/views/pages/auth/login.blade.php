<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login Administrator | SDM Absensi</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background:
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, .35), transparent 30%),
                radial-gradient(circle at 90% 80%, rgba(6, 182, 212, .35), transparent 30%),
                linear-gradient(135deg, #0f172a, #1e1b4b, #0f172a);
        }

        .glass {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .glow {
            box-shadow:
                0 0 40px rgba(99, 102, 241, .25),
                0 25px 60px rgba(0, 0, 0, .35);
        }

        .floating {
            animation: floating 5s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .fade-in {
            animation: fadeIn .8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.4);
            border-top-color: white;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center px-5 py-10">

    <div class="w-full max-w-md fade-in">

        {{-- LOGO / BRAND --}}
        <div class="text-center mb-8">

            <div class="floating inline-flex items-center justify-center
                        w-24 h-24 rounded-3xl
                        bg-white/95
                        shadow-2xl
                        mb-5">

                <div class="text-5xl">
                    🛡️
                </div>

            </div>

            <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight">
                SDM Absensi
            </h1>

            <p class="mt-2 text-sm text-slate-300">
                Sistem Manajemen Kehadiran Digital
            </p>

        </div>


        {{-- LOGIN CARD --}}
        <div class="glass rounded-[2rem] p-7 md:p-9 glow">

            {{-- CARD HEADER --}}
            <div class="mb-7">

                <div class="flex items-center gap-3 mb-2">

                    <div class="w-10 h-10 rounded-xl
                                bg-indigo-500/20
                                border border-indigo-400/20
                                flex items-center justify-center">

                        🔐

                    </div>

                    <div>
                        <h2 class="text-xl font-bold text-white">
                            Login Administrator
                        </h2>

                        <p class="text-xs text-slate-400">
                            Silakan masuk untuk melanjutkan
                        </p>
                    </div>

                </div>

            </div>


            {{-- ERROR --}}
            @if ($errors->any())

                <div class="mb-6 rounded-2xl
                            bg-red-500/10
                            border border-red-400/20
                            p-4
                            text-red-200">

                    <div class="flex items-start gap-3">

                        <div class="text-xl">
                            ⚠️
                        </div>

                        <div>

                            <p class="font-semibold text-sm">
                                Login gagal
                            </p>

                            <div class="mt-1 text-xs text-red-300 space-y-1">

                                @foreach ($errors->all() as $error)

                                    <div>
                                        {{ $error }}
                                    </div>

                                @endforeach

                            </div>

                        </div>

                    </div>

                </div>

            @endif


            {{-- SUCCESS --}}
            @if (session('success'))

                <div class="mb-6 rounded-2xl
                            bg-emerald-500/10
                            border border-emerald-400/20
                            p-4
                            text-emerald-200">

                    <div class="flex items-center gap-3">

                        <span class="text-xl">
                            ✅
                        </span>

                        <span class="text-sm">
                            {{ session('success') }}
                        </span>

                    </div>

                </div>

            @endif


            {{-- FORM --}}
            <form
                action="{{ route('login.process') }}"
                method="POST"
                id="loginForm"
            >

                @csrf


                {{-- EMAIL --}}
                <div class="mb-5">

                    <label
                        for="email"
                        class="block text-sm font-semibold text-slate-200 mb-2"
                    >
                        📧 Email Administrator
                    </label>

                    <div class="relative">

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="Masukkan email administrator"

                            class="w-full
                                   px-4 py-3.5
                                   rounded-2xl
                                   bg-white/10
                                   border border-white/10
                                   text-white
                                   placeholder-slate-500
                                   outline-none
                                   transition

                                   focus:bg-white/15
                                   focus:border-indigo-400
                                   focus:ring-2
                                   focus:ring-indigo-500/20"
                        >

                    </div>

                </div>


                {{-- PASSWORD --}}
                <div class="mb-6">

                    <label
                        for="password"
                        class="block text-sm font-semibold text-slate-200 mb-2"
                    >
                        🔑 Password
                    </label>

                    <div class="relative">

                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan password"

                            class="w-full
                                   px-4 py-3.5 pr-14
                                   rounded-2xl
                                   bg-white/10
                                   border border-white/10
                                   text-white
                                   placeholder-slate-500
                                   outline-none
                                   transition

                                   focus:bg-white/15
                                   focus:border-indigo-400
                                   focus:ring-2
                                   focus:ring-indigo-500/20"
                        >

                        {{-- SHOW PASSWORD --}}
                        <button
                            type="button"
                            onclick="togglePassword()"
                            class="absolute
                                   right-3
                                   top-1/2
                                   -translate-y-1/2
                                   w-9 h-9
                                   rounded-xl
                                   text-slate-400
                                   hover:text-white
                                   hover:bg-white/10
                                   transition"
                            title="Tampilkan password"
                        >
                            👁️
                        </button>

                    </div>

                </div>


                {{-- LOGIN BUTTON --}}
                <button
                    type="submit"
                    id="loginButton"

                    class="w-full
                           py-3.5
                           rounded-2xl

                           bg-gradient-to-r
                           from-indigo-500
                           via-blue-500
                           to-cyan-500

                           text-white
                           font-bold

                           shadow-lg
                           shadow-indigo-500/20

                           hover:shadow-indigo-500/40
                           hover:-translate-y-0.5

                           active:translate-y-0

                           transition
                           duration-200"

                >

                    <span id="buttonText">
                        🔓 Masuk ke Dashboard
                    </span>

                    <span
                        id="buttonLoading"
                        class="hidden items-center justify-center gap-2"
                    >

                        <span class="spinner"></span>

                        Memverifikasi...

                    </span>

                </button>

            </form>


            {{-- SECURITY INFO --}}
            <div class="mt-6 pt-5 border-t border-white/10">

                <div class="flex items-center justify-center gap-2">

                    <span class="text-emerald-400">
                        🛡️
                    </span>

                    <span class="text-xs text-slate-400">
                        Area administrator terlindungi
                    </span>

                </div>

            </div>

        </div>


        {{-- FOOTER --}}
        <div class="text-center mt-6">

            <p class="text-xs text-slate-500">
                © {{ date('Y') }} SDM Absensi
            </p>

            <p class="text-[10px] text-slate-600 mt-1">
                Sistem Manajemen Kehadiran Digital
            </p>

        </div>

    </div>


    {{-- JAVASCRIPT --}}
    <script>

        function togglePassword() {

            const password = document.getElementById('password');

            if (password.type === 'password') {

                password.type = 'text';

            } else {

                password.type = 'password';

            }

        }


        document.getElementById('loginForm').addEventListener('submit', function () {

            const button = document.getElementById('loginButton');

            const text = document.getElementById('buttonText');

            const loading = document.getElementById('buttonLoading');

            button.disabled = true;

            button.classList.add('opacity-80', 'cursor-not-allowed');

            text.classList.add('hidden');

            loading.classList.remove('hidden');

            loading.classList.add('flex');

        });

    </script>

</body>

</html>