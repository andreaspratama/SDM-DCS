@extends('layouts.admin')

@section('title')
    Token Form Izin
@endsection

@section('content')

<main class="app-main">

    {{-- HEADER --}}
    <div class="app-content-header">
        <div class="container-fluid">

            <div class="row align-items-center">

                <div class="col-sm-6">
                    <h1 class="mb-0 fs-3">
                        🔐 Token Form Izin
                    </h1>

                    <p class="text-muted mb-0 mt-1">
                        Kelola akses form izin untuk setiap unit.
                    </p>
                </div>

                <div class="col-sm-6">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboard') }}">
                                    Dashboard
                                </a>
                            </li>

                            <li class="breadcrumb-item active">
                                Token Form Izin
                            </li>
                        </ol>
                    </nav>
                </div>

            </div>

        </div>
    </div>


    {{-- CONTENT --}}
    <div class="app-content">
        @if(session('generated_token'))

            <div class="alert alert-success shadow-sm border-0 mb-4">

                <div class="d-flex align-items-start">

                    <div class="fs-2 me-3">
                        ✅
                    </div>

                    <div class="flex-grow-1">

                        <h5 class="fw-bold mb-1">
                            Token {{ session('generated_token.unit') }} berhasil dibuat!
                        </h5>

                        <p class="small text-muted mb-3">
                            Bagikan link berikut kepada unit
                            <strong>{{ session('generated_token.unit') }}</strong>.
                            Token lama otomatis tidak berlaku.
                        </p>

                        <div class="input-group">

                            <input
                                type="text"
                                id="generatedTokenLink"
                                class="form-control"
                                value="{{ session('generated_token.link') }}"
                                readonly
                            >

                            <button
                                type="button"
                                class="btn btn-primary"
                                onclick="copyGeneratedLink()"
                            >
                                📋 Copy Link
                            </button>

                        </div>

                        <div
                            id="copySuccess"
                            class="text-success small fw-semibold mt-2"
                            style="display:none;"
                        >
                            ✅ Link berhasil disalin!
                        </div>

                    </div>

                </div>

            </div>

        @endif

        <div class="container-fluid">

            {{-- INFO --}}
            <div class="alert alert-info border-0 shadow-sm">

                <div class="d-flex align-items-start">

                    <div class="fs-3 me-3">
                        💡
                    </div>

                    <div>

                        <h6 class="fw-bold mb-1">
                            Tentang Token
                        </h6>

                        <div class="small">
                            Setiap unit memiliki token sendiri untuk mengakses
                            Form Izin. Jika token di-regenerate, token lama
                            otomatis tidak dapat digunakan lagi.
                        </div>

                    </div>

                </div>

            </div>


            {{-- CARD --}}
            <div class="card border-0 shadow-sm">

                <div class="card-header bg-white py-3">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>
                            <h5 class="mb-1 fw-bold">
                                🔑 Token Unit
                            </h5>

                            <small class="text-muted">
                                Daftar token aktif setiap unit
                            </small>
                        </div>

                    </div>

                </div>


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-hover align-middle mb-0">

                            <thead class="table-light">

                                <tr>

                                    <th class="px-4" style="width: 70px;">
                                        #
                                    </th>

                                    <th>
                                        Unit
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Link Form
                                    </th>

                                    <th class="text-center" style="width: 180px;">
                                        Aksi
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @forelse($units as $index => $unit)

                                    @php
                                        $tokenData = $tokens->get($unit->id);
                                    @endphp

                                    <tr>

                                        {{-- NOMOR --}}
                                        <td class="px-4 fw-semibold text-muted">
                                            {{ $index + 1 }}
                                        </td>


                                        {{-- UNIT --}}
                                        <td>

                                            <div class="fw-bold">
                                                {{ $unit->nama }}
                                            </div>

                                            <small class="text-muted">
                                                Unit ID: {{ $unit->id }}
                                            </small>

                                        </td>


                                        {{-- STATUS --}}
                                        <td>

                                            @if($tokenData)

                                                <span class="badge bg-success-subtle text-success px-3 py-2">
                                                    🟢 Aktif
                                                </span>

                                            @else

                                                <span class="badge bg-secondary-subtle text-secondary px-3 py-2">
                                                    ⚪ Belum ada token
                                                </span>

                                            @endif

                                        </td>


                                        {{-- LINK --}}
                                        <td>

                                            @if($tokenData)

                                                <div class="text-muted small">
                                                    Token aktif
                                                </div>

                                                <div class="text-success small fw-semibold">
                                                    🔒 Token tersimpan aman
                                                </div>

                                            @else

                                                <span class="text-muted">
                                                    Belum tersedia
                                                </span>

                                            @endif

                                        </td>


                                        {{-- AKSI --}}
                                        <td class="text-center">

                                            @if($tokenData)

                                                @php
                                                    $link = url('/form-izin/' . $tokenData->token);
                                                @endphp

                                                <button
                                                    type="button"
                                                    class="btn btn-outline-warning btn-sm mb-2"
                                                    onclick="copyLink('{{ $link }}', this)"
                                                >
                                                    📋 Copy Link
                                                </button>

                                                <form
                                                    action="{{ route('unitFormToken.generate', $unit) }}"
                                                    method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Regenerate token {{ $unit->nama }}? Token lama akan langsung tidak berlaku.');"
                                                >
                                                    @csrf

                                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                                        🔄 Regenerate
                                                    </button>
                                                </form>

                                            @else

                                                <form
                                                    action="{{ route('unitFormToken.generate', $unit) }}"
                                                    method="POST"
                                                    class="d-inline"
                                                >
                                                    @csrf

                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        🔑 Generate
                                                    </button>
                                                </form>

                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="5"
                                            class="text-center py-5 text-muted">

                                            Belum ada data unit.

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

<script>
function copyGeneratedLink() {

    const input = document.getElementById('generatedTokenLink');
    const success = document.getElementById('copySuccess');

    navigator.clipboard.writeText(input.value)
        .then(function () {

            success.style.display = 'block';

            setTimeout(function () {
                success.style.display = 'none';
            }, 2500);

        })
        .catch(function () {

            input.select();
            document.execCommand('copy');

            success.style.display = 'block';

            setTimeout(function () {
                success.style.display = 'none';
            }, 2500);

        });
}

function copyLink(link, button) {

    navigator.clipboard.writeText(link)
        .then(function () {

            const originalText = button.innerHTML;

            button.innerHTML = '✅ Copied!';

            setTimeout(function () {
                button.innerHTML = originalText;
            }, 2000);

        })
        .catch(function () {

            const textarea = document.createElement('textarea');

            textarea.value = link;
            document.body.appendChild(textarea);

            textarea.select();
            document.execCommand('copy');

            document.body.removeChild(textarea);

            const originalText = button.innerHTML;

            button.innerHTML = '✅ Copied!';

            setTimeout(function () {
                button.innerHTML = originalText;
            }, 2000);

        });
}
</script>

@endsection