@extends('layouts.admin')

@section('title')
    Dashboard | Data Kehadiran
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
                <h1 class="mb-0 fs-3">Data Kehadiran</h1>
              </div>
              <div class="col-sm-6">
                <nav aria-label="breadcrumb">
                  <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Data Kehadiran</li>
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
              <div class="col-md-12">
                <div class="card mb-4">
                  <div class="card-header">
                    <h3 class="card-title">Data Kehadiran</h3>
                  </div>
                  <div class="card mb-3 mt-3">
                      <div class="card-body">
                          <div class="row g-3 align-items-end">
                                {{-- UNIT --}}
                                <div class="col-md-3">
                                    <label for="unit_id" class="form-label fw-semibold">
                                        🏢 Unit
                                    </label>

                                    <select id="unit_id" class="form-select">
                                        <option value="">Semua Unit</option>

                                        @foreach(\App\Models\Unit::orderBy('nama')->get() as $unit)
                                            <option value="{{ $unit->id }}">
                                                {{ $unit->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                              {{-- Tanggal Mulai --}}
                              <div class="col-md-3">
                                  <label for="start_date" class="form-label fw-semibold">
                                      📅 Tanggal Mulai
                                  </label>

                                  <input
                                      type="date"
                                      id="start_date"
                                      class="form-control"
                                  >
                              </div>


                              {{-- Tanggal Akhir --}}
                              <div class="col-md-3">
                                  <label for="end_date" class="form-label fw-semibold">
                                      📅 Tanggal Akhir
                                  </label>

                                  <input
                                      type="date"
                                      id="end_date"
                                      class="form-control"
                                  >
                              </div>


                              {{-- Tombol --}}
                                <div class="col-md-4    ">
                                    <div class="d-flex justify-content-end gap-2">

                                        <button
                                            type="button"
                                            onclick="reloadTable()"
                                            class="btn btn-primary px-4"
                                        >
                                            🔍 Tampilkan Data
                                        </button>

                                        <button
                                            type="button"
                                            onclick="resetFilter()"
                                            class="btn btn-outline-secondary px-4"
                                        >
                                            🔄 Reset
                                        </button>

                                        <button
                                            type="button"
                                            onclick="exportExcel()"
                                            class="btn btn-success px-4"
                                        >
                                            📥 Export Excel
                                        </button>

                                    </div>
                                </div>

                          </div>
                      </div>
                  </div>
                  <!-- /.card-header -->
                  <div class="card-body p-3">
                    <table class="table table-sm" id="tableAbsensi">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Total Hadir</th>
                                <th>Tidak Masuk</th>
                                <th>Telat</th>
                                <th>Pulang Cepat</th>
                                <th>Tanpa Ket</th>
                                <th>Keluar Tanpa Izin</th>
                                <th>Total Jam</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody></tbody>
                    </table>
                  </div>
                  <!-- /.card-body -->
                </div>
                <!-- /.card -->
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

@push('prepend-style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endpush

@push('addon-script')
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <script>
      $(document).ready(function(){

          let today = new Date();

          function formatDateLocal(date) {
              let year = date.getFullYear();
              let month = String(date.getMonth() + 1).padStart(2, '0');
              let day = String(date.getDate()).padStart(2, '0');

              return `${year}-${month}-${day}`;
          }

          // ==========================
          // DEFAULT BULAN BERJALAN
          // ==========================

          let firstDay = new Date(
              today.getFullYear(),
              today.getMonth(),
              1
          );

          let lastDay = new Date(
              today.getFullYear(),
              today.getMonth() + 1,
              0
          );

          $('#start_date').val(formatDateLocal(firstDay));
          $('#end_date').val(formatDateLocal(lastDay));


          // ==========================
          // DATATABLE
          // ==========================

          let table = $('#tableAbsensi').DataTable({
              processing: true,
              serverSide: true,

              ajax: {
                  url: "{{ route('absensi.datatable') }}",

                  data: function(d){
                        d.unit_id   = $('#unit_id').val();
                        d.start_date = $('#start_date').val();
                        d.end_date   = $('#end_date').val();
                  },

                  error: function(xhr){
                      console.log(xhr.responseText);
                  }
              },

              columns: [
                  { data:'nama', name:'nama' },
                  { data:'total_hadir', name:'total_hadir' },
                  { data:'tidak_masuk', name:'tidak_masuk' },
                  { data:'total_telat', name:'total_telat' },
                  { data:'pulang_cepat', name:'pulang_cepat' },
                  { data:'tanpa_keterangan', name:'tanpa_keterangan' },
                  { data:'keluar_tanpa_izin', name:'keluar_tanpa_izin' },
                  { data:'total_jam', name:'total_jam' },
                  {
                      data:'aksi',
                      name:'aksi',
                      orderable:false,
                      searchable:false
                  }
              ]
          });


          // ==========================
          // FILTER
          // ==========================

          window.reloadTable = function(){

              let start = $('#start_date').val();
              let end   = $('#end_date').val();

              if (!start || !end) {
                  alert('Tanggal mulai dan tanggal akhir harus diisi.');
                  return;
              }

              if (start > end) {
                  alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir.');
                  return;
              }

              table.ajax.reload();
          };


          // ==========================
          // RESET FILTER
          // ==========================

            window.resetFilter = function(){

                let today = new Date();

                // ==========================
                // RESET UNIT
                // ==========================
                $('#unit_id').val('');

                // ==========================
                // RESET TANGGAL
                // ==========================
                let firstDay = new Date(
                    today.getFullYear(),
                    today.getMonth(),
                    1
                );

                let lastDay = new Date(
                    today.getFullYear(),
                    today.getMonth() + 1,
                    0
                );

                $('#start_date').val(formatDateLocal(firstDay));
                $('#end_date').val(formatDateLocal(lastDay));

                // ==========================
                // RELOAD TABLE
                // ==========================
                table.ajax.reload();
            };

          // ==========================
          // DOWNLOAD ABSENSI
          // ==========================

            window.exportExcel = function(){

                let unit  = $('#unit_id').val();
                let start = $('#start_date').val();
                let end   = $('#end_date').val();

                // ==========================
                // VALIDASI TANGGAL
                // ==========================
                if (!start || !end) {
                    alert('Tanggal mulai dan tanggal akhir harus diisi.');
                    return;
                }

                if (start > end) {
                    alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir.');
                    return;
                }

                // ==========================
                // BUAT URL EXPORT
                // ==========================
                let url = "{{ route('absensi.export') }}"
                    + "?unit_id=" + encodeURIComponent(unit)
                    + "&start_date=" + encodeURIComponent(start)
                    + "&end_date=" + encodeURIComponent(end);

                // ==========================
                // DOWNLOAD EXCEL
                // ==========================
                window.location.href = url;
            };

      });
    </script>
@endpush