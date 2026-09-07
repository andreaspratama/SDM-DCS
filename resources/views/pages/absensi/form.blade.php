@extends('layouts.admin')

@section('title')
    Dashboard | Upload Kehadiran
@endsection

@section('content')

    <!--begin::App Main-->
      <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
          <!--begin::Container-->
          <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
              <div class="col-sm-6">
                <h1 class="mb-0 fs-3">Upload Data Absensi</h1>
              </div>
              <div class="col-sm-6">
                <nav aria-label="breadcrumb">
                  <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Upload Data Absensi</li>
                  </ol>
                </nav>
              </div>
            </div>
            <!--end::Row-->
          </div>
          <!--end::Container-->
        </div>
        <!--end::App Content Header-->
        <!--begin::App Content-->
        <div class="app-content">
          <!--begin::Container-->
          <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
              <div class="col-12">
                <!--begin::Card-->
                <div class="card">
                  <div class="card-header">

                      {{-- =========================
                          VALIDATION ERROR
                      ========================== --}}
                      @if ($errors->any())
                          <div class="alert alert-danger alert-dismissible fade show" role="alert">

                              <div class="fw-bold mb-2">
                                  Data belum dapat diproses
                              </div>

                              <ul class="mb-0">
                                  @foreach ($errors->all() as $error)
                                      <li>{{ $error }}</li>
                                  @endforeach
                              </ul>

                              <button
                                  type="button"
                                  class="btn-close"
                                  data-bs-dismiss="alert">
                              </button>

                          </div>
                      @endif


                      {{-- =========================
                          SUCCESS
                      ========================== --}}
                      @if(session('success'))

                          <div class="alert alert-success alert-dismissible fade show" role="alert">

                              <i class="fa-solid fa-circle-check me-2"></i>

                              {{ session('success') }}

                              <button
                                  type="button"
                                  class="btn-close"
                                  data-bs-dismiss="alert">
                              </button>

                          </div>

                      @endif


                      {{-- =========================
                          ERROR IMPORT
                      ========================== --}}
                      @if(session('error'))

                          <div class="alert alert-danger alert-dismissible fade show" role="alert">

                              <i class="fa-solid fa-circle-exclamation me-2"></i>

                              {{ session('error') }}

                              <button
                                  type="button"
                                  class="btn-close"
                                  data-bs-dismiss="alert">
                              </button>

                          </div>

                      @endif


                      {{-- =========================
                          UID TIDAK DITEMUKAN
                      ========================== --}}
                      @if(
                          session('missing_uids') &&
                          count(session('missing_uids')) > 0
                      )

                          <div class="alert alert-warning">

                              <div class="fw-bold mb-2">
                                  UID belum terdaftar di Master Employee
                              </div>

                              <div class="mb-1">
                                  {{ implode(', ', session('missing_uids')) }}
                              </div>

                              <small>
                                  Data dengan UID tersebut dilewati.
                                  Silakan cek Master Employee terlebih dahulu.
                              </small>

                          </div>

                      @endif


                      {{-- =========================
                          INFORMASI IMPORT
                      ========================== --}}
                      <div class="alert alert-info">

                          <div class="fw-bold mb-1">
                              Cara Kerja Import Absensi
                          </div>

                          <div class="small">

                              <div>
                                  <strong>UM, SHS dan PS Gama</strong>
                                  menyimpan raw scan terlebih dahulu dan setelah upload
                                  harus dilanjutkan dengan
                                  <strong>Process Data</strong>.
                              </div>

                              <div class="mt-1">
                                  <strong>Elementary, JHS dan PS Tama</strong>
                                  langsung menghasilkan data attendance dan tidak perlu
                                  Process Data.
                              </div>

                          </div>

                      </div>


                      {{-- =========================
                          FORM UPLOAD
                      ========================== --}}
                      <form
                          action="{{ route('absensi.upload') }}"
                          method="POST"
                          enctype="multipart/form-data"
                      >

                          @csrf


                          {{-- UNIT --}}
                          <div class="mb-3">

                              <label
                                  for="unit_id"
                                  class="form-label fw-semibold"
                              >
                                  Unit
                              </label>

                              <select
                                  name="unit_id"
                                  id="unit_id"
                                  required
                                  class="form-select @error('unit_id') is-invalid @enderror"
                              >

                                  <option value="">
                                      -- Pilih Unit --
                                  </option>

                                  @foreach($units as $unit)

                                      <option
                                          value="{{ $unit->id }}"
                                          @selected(
                                              old('unit_id') == $unit->id
                                          )
                                      >
                                          {{ $unit->nama }}
                                      </option>

                                  @endforeach

                              </select>

                              @error('unit_id')
                                  <div class="invalid-feedback">
                                      {{ $message }}
                                  </div>
                              @enderror

                          </div>


                          {{-- FILE --}}
                          <div class="mb-3">

                              <label
                                  for="file"
                                  class="form-label fw-semibold"
                              >
                                  File Absensi
                              </label>

                              <input
                                  type="file"
                                  name="file"
                                  id="file"
                                  class="form-control @error('file') is-invalid @enderror"
                                  accept=".csv,.txt,.xls,.xlsx"
                                  required
                              >

                              <div class="form-text">
                                  Format yang didukung:
                                  CSV, TXT, XLS dan XLSX.
                                  Sistem akan mendeteksi format mesin secara otomatis.
                              </div>

                              @error('file')
                                  <div class="invalid-feedback">
                                      {{ $message }}
                                  </div>
                              @enderror

                          </div>


                          {{-- BUTTON UPLOAD --}}
                          <button
                              type="submit"
                              class="btn btn-primary"
                          >
                              <i class="fa-solid fa-upload me-1"></i>
                              Upload Absensi
                          </button>

                      </form>


                      <hr>


                      {{-- =========================
                          PROCESS RAW SCAN
                      ========================== --}}
                      <a
                          href="{{ route('absensi.process') }}"
                          class="btn btn-success"
                      >
                          <i class="fa-solid fa-arrows-rotate me-1"></i>
                          Process Data
                      </a>


                      {{-- =========================
                          GENERATE ALPHA
                      ========================== --}}
                      <a
                          href="{{ route('absensi.alpha') }}"
                          class="btn btn-danger"
                      >
                          <i class="fa-solid fa-user-xmark me-1"></i>
                          Generate Alpha
                      </a>

                  </div>
                </div>
                <!--end::Card-->
              </div>
              <!-- /.col -->
            </div>
            <!--end::Row-->
          </div>
          <!--end::Container-->
        </div>
        <!--end::App Content-->
      </main>
      <!--end::App Main-->
@endsection