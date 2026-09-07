@extends('layouts.admin')

@section('content')

<div class="container py-4">

    <div class="mb-4">
        <h3>Tambah Kalender Pendidikan</h3>
        <p class="text-muted">
            Tambahkan hari efektif atau periode libur sekolah.
        </p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="{{ route('school-calendar.store') }}"
                  method="POST">

                @csrf

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Tanggal Mulai
                        </label>

                        <input type="date"
                               name="tanggal_mulai"
                               value="{{ old('tanggal_mulai') }}"
                               class="form-control @error('tanggal_mulai') is-invalid @enderror"
                               required>

                        @error('tanggal_mulai')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Tanggal Selesai
                        </label>

                        <input type="date"
                               name="tanggal_selesai"
                               value="{{ old('tanggal_selesai') }}"
                               class="form-control @error('tanggal_selesai') is-invalid @enderror"
                               required>

                        @error('tanggal_selesai')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Nama
                    </label>

                    <input type="text"
                           name="nama"
                           value="{{ old('nama') }}"
                           class="form-control @error('nama') is-invalid @enderror"
                           placeholder="Contoh: Libur Semester Ganjil"
                           required>

                    @error('nama')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Jenis
                    </label>

                    <select name="jenis"
                            class="form-select"
                            required>

                        <option value="">-- Pilih Jenis --</option>

                        <option value="efektif"
                            {{ old('jenis') == 'efektif' ? 'selected' : '' }}>
                            Hari Efektif
                        </option>

                        <option value="libur_semester"
                            {{ old('jenis') == 'libur_semester' ? 'selected' : '' }}>
                            Libur Semester
                        </option>

                        <option value="libur_nasional"
                            {{ old('jenis') == 'libur_nasional' ? 'selected' : '' }}>
                            Libur Nasional
                        </option>

                        <option value="libur_khusus"
                            {{ old('jenis') == 'libur_khusus' ? 'selected' : '' }}>
                            Libur Khusus
                        </option>

                        <option value="kegiatan_sekolah"
                            {{ old('jenis') == 'kegiatan_sekolah' ? 'selected' : '' }}>
                            Kegiatan Sekolah
                        </option>

                        <option value="lainnya"
                            {{ old('jenis') == 'lainnya' ? 'selected' : '' }}>
                            Lainnya
                        </option>

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Apakah Hari Kerja?
                    </label>

                    <select name="is_hari_kerja"
                            class="form-select"
                            required>

                        <option value="1"
                            {{ old('is_hari_kerja', '1') == '1' ? 'selected' : '' }}>
                            Ya
                        </option>

                        <option value="0"
                            {{ old('is_hari_kerja') === '0' ? 'selected' : '' }}>
                            Tidak
                        </option>

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Keterangan
                    </label>

                    <textarea name="keterangan"
                              class="form-control"
                              rows="3"
                              placeholder="Keterangan tambahan...">{{ old('keterangan') }}</textarea>

                </div>

                <div class="d-flex gap-2">

                    <a href="{{ route('school-calendar.index') }}"
                       class="btn btn-secondary">
                        Kembali
                    </a>

                    <button type="submit"
                            class="btn btn-primary">
                        Simpan
                    </button>

                </div>

            </form>

        </div>
    </div>

</div>

@endsection