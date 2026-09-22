<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Unit;
use App\Models\WorkSchedule;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    public function index()
    {
        $units = Unit::orderBy('nama')->get();

        $workSchedules = WorkSchedule::orderBy('nama')->get();

        return view(
            'pages.employee.index',
            compact(
                'units',
                'workSchedules'
            )
        );
    }

    public function datatable(Request $request)
    {
        $query = Employee::query()
            ->with([
                'unit:id,nama',
                'workSchedule:id,nama',
                'unitHistories.unit:id,nama',
            ])
            ->select([
                'id',
                'uid',
                'nama',
                'unit_id',
                'role',
                'work_schedule_id',
                'is_active',
                'tanggal_keluar',
            ]);


        // =============================
        // FILTER UNIT
        // =============================
        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }


        // =============================
        // FILTER JABATAN
        // =============================
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }


        // =============================
        // FILTER JADWAL
        // =============================
        if ($request->filled('schedule_status')) {

            if ($request->schedule_status === 'assigned') {
                $query->whereNotNull('work_schedule_id');
            }

            if ($request->schedule_status === 'unassigned') {
                $query->whereNull('work_schedule_id');
            }
        }


        return DataTables::eloquent($query)

            ->addIndexColumn()

            ->editColumn('uid', function ($row) {
                return $row->uid ?: '-';
            })

            ->addColumn('unit', function ($row) {

                $unitAktif =
                    $row->unit?->nama ?? '-';

                $mutasiTerjadwal =
                    $row->unitHistories
                        ->filter(function ($history) {

                            return
                                $history->tanggal_mulai
                                && $history->tanggal_mulai->isFuture()
                                && is_null($history->tanggal_selesai);
                        })
                        ->sortBy('tanggal_mulai')
                        ->first();


                if (!$mutasiTerjadwal) {

                    return '
                        <span class="unit-text">'
                            . e($unitAktif) .
                        '</span>
                    ';
                }


                $unitTujuan =
                    $mutasiTerjadwal->unit?->nama
                    ?? '-';

                $tanggalEfektif =
                    $mutasiTerjadwal
                        ->tanggal_mulai
                        ->format('d/m/Y');


                return '
                    <div>
                        <div class="unit-text">
                            ' . e($unitAktif) . '
                        </div>

                        <div class="mt-1">
                            <span class="badge text-bg-info">
                                <i class="bi bi-arrow-right me-1"></i>
                                ' . e($unitTujuan) . '
                                mulai ' . e($tanggalEfektif) . '
                            </span>
                        </div>
                    </div>
                ';
            })

            ->addColumn('jabatan', function ($row) {

                if (!$row->role) {
                    return '<span class="badge text-bg-secondary">
                                Belum Diatur
                            </span>';
                }

                return '<span class="badge text-bg-primary">'
                        . e($row->role) .
                    '</span>';
            })

            ->addColumn('jadwal', function ($row) {

                if (!$row->workSchedule) {
                    return '<span class="badge text-bg-warning">
                                Belum Ada Jadwal
                            </span>';
                }

                return '<span class="badge text-bg-success">'
                        . e($row->workSchedule->nama) .
                    '</span>';
            })

            ->addColumn('aksi', function ($row) {

                $atur = '
                    <a href="' . route('employeeOrganization.edit', $row->id) . '"
                    class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil-square"></i>
                        Atur
                    </a>
                ';

                // Pegawai sudah nonaktif
                if (!$row->is_active) {

                    $tanggalKeluar = $row->tanggal_keluar
                        ? \Carbon\Carbon::parse($row->tanggal_keluar)->format('d/m/Y')
                        : '-';

                    return '
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            ' . $atur . '

                            <span
                                class="badge text-bg-secondary"
                                title="Tanggal keluar: ' . e($tanggalKeluar) . '"
                            >
                                Nonaktif
                            </span>
                        </div>
                    ';
                }

                // Pegawai masih aktif
                return '
                    <div class="d-flex align-items-center gap-1 flex-wrap">

                        ' . $atur . '

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-success btn-transfer-unit"
                            data-id="' . $row->id . '"
                            data-nama="' . e($row->nama) . '"
                            data-unit-id="' . $row->unit_id . '"
                        >
                            <i class="bi bi-arrow-left-right"></i>
                            Pindah Unit
                        </button>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger btn-deactivate-employee"
                            data-id="' . $row->id . '"
                            data-nama="' . e($row->nama) . '"
                        >
                            <i class="bi bi-person-x"></i>
                            Keluar
                        </button>

                    </div>
                ';
            })

            ->rawColumns([
                'unit',
                'jabatan',
                'jadwal',
                'aksi',
            ])

            ->toJson();
    }

    public function deactivate(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'tanggal_keluar' => 'required|date|before_or_equal:today',
            'keterangan'     => 'nullable|string|max:255',
        ]);

        if (!$employee->is_active) {
            return back()->with(
                'error',
                'Pegawai ini sudah berstatus nonaktif.'
            );
        }

        $tanggalKeluar = \Carbon\Carbon::parse(
            $validated['tanggal_keluar']
        )->toDateString();


        \Illuminate\Support\Facades\DB::transaction(
            function () use (
                $employee,
                $validated,
                $tanggalKeluar
            ) {

                // =====================================================
                // 1. HAPUS MUTASI UNIT YANG BELUM BERLAKU
                // =====================================================
                $employee->unitHistories()
                    ->whereDate(
                        'tanggal_mulai',
                        '>',
                        $tanggalKeluar
                    )
                    ->delete();


                // =====================================================
                // 2. CARI RIWAYAT UNIT YANG BERLAKU SAAT PEGAWAI KELUAR
                // =====================================================
                $riwayatAktif =
                    $employee->unitHistories()
                        ->whereDate(
                            'tanggal_mulai',
                            '<=',
                            $tanggalKeluar
                        )
                        ->where(function ($query) use ($tanggalKeluar) {

                            $query
                                ->whereNull('tanggal_selesai')
                                ->orWhereDate(
                                    'tanggal_selesai',
                                    '>=',
                                    $tanggalKeluar
                                );
                        })
                        ->orderByDesc('tanggal_mulai')
                        ->first();


                // =====================================================
                // 3. TUTUP RIWAYAT UNIT PADA TANGGAL KELUAR
                // =====================================================
                if ($riwayatAktif) {

                    $keteranganKeluar =
                        $validated['keterangan']
                        ?? 'Pegawai keluar / nonaktif';


                    $riwayatAktif->update([
                        'tanggal_selesai' =>
                            $tanggalKeluar,

                        'keterangan' =>
                            trim(
                                (
                                    $riwayatAktif->keterangan
                                        ? $riwayatAktif->keterangan . ' | '
                                        : ''
                                )
                                . $keteranganKeluar
                            ),
                    ]);
                }


                // =====================================================
                // 4. NONAKTIFKAN PEGAWAI
                // =====================================================
                $employee->update([
                    'is_active' =>
                        false,

                    'tanggal_keluar' =>
                        $tanggalKeluar,
                ]);
            }
        );


        return back()->with(
            'success',
            $employee->nama .
            ' berhasil dinonaktifkan per ' .
            \Carbon\Carbon::parse($tanggalKeluar)
                ->format('d/m/Y') .
            '. Mutasi unit yang belum berlaku telah dibatalkan.'
        );
    }

    public function transferUnit(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'unit_id'          => 'required|exists:units,id',
            'work_schedule_id' => 'required|exists:work_schedules,id',
            'tanggal_pindah'   => 'required|date',
            'keterangan'       => 'nullable|string|max:255',
        ]);

        // Pegawai nonaktif tidak boleh dipindahkan
        if (!$employee->is_active) {
            return back()->with(
                'error',
                'Pegawai nonaktif tidak dapat dipindahkan unit.'
            );
        }

        $unitBaru = Unit::findOrFail(
            $validated['unit_id']
        );

        // Tidak boleh pindah ke unit yang sama
        if ((int) $employee->unit_id === (int) $unitBaru->id) {
            return back()->with(
                'error',
                'Unit tujuan sama dengan unit pegawai saat ini.'
            );
        }

        $tanggalPindah = \Carbon\Carbon::parse(
            $validated['tanggal_pindah']
        )->startOfDay();

        $today = \Carbon\Carbon::today();

        // Tanggal pindah tidak boleh sebelum tanggal masuk
        if (
            $employee->tanggal_masuk &&
            $tanggalPindah->lt(
                $employee->tanggal_masuk->copy()->startOfDay()
            )
        ) {
            return back()->with(
                'error',
                'Tanggal pindah tidak boleh sebelum tanggal masuk pegawai.'
            );
        }


        // =====================================================
        // CEK APAKAH SUDAH ADA PINDAH UNIT TERJADWAL
        // =====================================================
        $scheduledTransfer =
            $employee->unitHistories()
                ->whereDate(
                    'tanggal_mulai',
                    '>',
                    $today->toDateString()
                )
                ->whereNull('tanggal_selesai')
                ->first();

        if ($scheduledTransfer) {

            return back()->with(
                'error',
                'Pegawai ini sudah memiliki pindah unit terjadwal.'
            );
        }


        \Illuminate\Support\Facades\DB::transaction(
            function () use (
                $employee,
                $unitBaru,
                $tanggalPindah,
                $today,
                $validated
            ) {

                $unitLamaId =
                    $employee->unit_id;

                $tanggalSelesaiUnitLama =
                    $tanggalPindah
                        ->copy()
                        ->subDay()
                        ->toDateString();


                // =====================================================
                // CARI RIWAYAT UNIT AKTIF / TERKINI
                // =====================================================
                $riwayatAktif =
                    $employee->unitHistories()
                        ->whereNull('tanggal_selesai')
                        ->whereDate(
                            'tanggal_mulai',
                            '<=',
                            $today->toDateString()
                        )
                        ->orderByDesc('tanggal_mulai')
                        ->first();


                // =====================================================
                // SUDAH PUNYA RIWAYAT UNIT
                // =====================================================
                if ($riwayatAktif) {

                    if (
                        $tanggalPindah->lte(
                            $riwayatAktif
                                ->tanggal_mulai
                                ->copy()
                                ->startOfDay()
                        )
                    ) {

                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'tanggal_pindah' =>
                                'Tanggal pindah harus setelah tanggal mulai unit saat ini.',
                        ]);
                    }


                    $riwayatAktif->update([
                        'tanggal_selesai' =>
                            $tanggalSelesaiUnitLama,

                        'keterangan' =>
                            'Pindah ke ' .
                            $unitBaru->nama .
                            (
                                !empty($validated['keterangan'])
                                    ? ' - ' . $validated['keterangan']
                                    : ''
                            ),
                    ]);

                } else {

                    // =================================================
                    // PEGAWAI LAMA BELUM PUNYA RIWAYAT UNIT
                    // =================================================

                    $tanggalAwal =
                        $employee->tanggal_masuk
                            ? $employee->tanggal_masuk->toDateString()
                            : \App\Models\Attendance::where(
                                'employee_id',
                                $employee->id
                            )->min('date');


                    if (
                        !$tanggalAwal &&
                        $employee->created_at
                    ) {

                        $tanggalAwal =
                            $employee->created_at
                                ->toDateString();
                    }


                    if (
                        $tanggalAwal &&
                        $tanggalAwal <=
                            $tanggalSelesaiUnitLama
                    ) {

                        $employee
                            ->unitHistories()
                            ->create([
                                'unit_id' =>
                                    $unitLamaId,
                                
                                'work_schedule_id' =>
                                    $employee->work_schedule_id,

                                'tanggal_mulai' =>
                                    $tanggalAwal,

                                'tanggal_selesai' =>
                                    $tanggalSelesaiUnitLama,

                                'keterangan' =>
                                    'Riwayat unit sebelum pindah ke ' .
                                    $unitBaru->nama,
                            ]);
                    }
                }


                // =====================================================
                // BUAT UNIT BARU
                // =====================================================
                $employee
                    ->unitHistories()
                    ->create([
                        'unit_id' =>
                            $unitBaru->id,
                        
                        'work_schedule_id' =>
                            $validated['work_schedule_id'],

                        'tanggal_mulai' =>
                            $tanggalPindah
                                ->toDateString(),

                        'tanggal_selesai' =>
                            null,

                        'keterangan' =>
                            $validated['keterangan']
                                ?? 'Pindah unit',
                    ]);


                // =====================================================
                // JIKA TANGGAL PINDAH SUDAH BERLAKU
                // =====================================================
                if ($tanggalPindah->lte($today)) {

                    $employee->update([
                        'unit_id' =>
                            $unitBaru->id,

                        'work_schedule_id' =>
                            $validated['work_schedule_id'],
                    ]);
                }

                // Kalau tanggal pindah masih di masa depan:
                // employees.unit_id TIDAK diubah dulu.
            }
        );


        if ($tanggalPindah->isFuture()) {

            return back()->with(
                'success',
                $employee->nama .
                ' dijadwalkan pindah ke unit ' .
                $unitBaru->nama .
                ' mulai ' .
                $tanggalPindah->format('d/m/Y') .
                '.'
            );
        }


        return back()->with(
            'success',
            $employee->nama .
            ' berhasil dipindahkan ke unit ' .
            $unitBaru->nama .
            '.'
        );
    }

    public function formUpload()
    {
        return view('pages.employee.form');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $path = $request->file('file')->getRealPath();

        $file = fopen($path, 'r');

        if (!$file) {
            return back()->with(
                'error',
                'File employee tidak dapat dibaca.'
            );
        }


        // =========================================================
        // DETEKSI DELIMITER DARI HEADER
        // Mendukung:
        // ;
        // ,
        // TAB
        // =========================================================
        $firstLine = fgets($file);

        if ($firstLine === false) {

            fclose($file);

            return back()->with(
                'error',
                'File employee kosong.'
            );
        }


        // Hapus UTF-8 BOM
        $firstLine = preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            $firstLine
        );


        $delimiterCounts = [
            ';'  => substr_count($firstLine, ';'),
            ','  => substr_count($firstLine, ','),
            "\t" => substr_count($firstLine, "\t"),
        ];


        arsort($delimiterCounts);

        $delimiter = array_key_first(
            $delimiterCounts
        );


        // Kalau delimiter tidak ketemu
        if (($delimiterCounts[$delimiter] ?? 0) === 0) {

            fclose($file);

            return back()->with(
                'error',
                'Format file tidak dikenali. Gunakan CSV dengan kolom UID, Nama, Unit.'
            );
        }


        // Kembali ke awal file
        rewind($file);


        $first = true;

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        $unitTidakDitemukan = [];


        while (
            ($data = fgetcsv(
                $file,
                0,
                $delimiter
            )) !== false
        ) {

            // =====================================================
            // SKIP HEADER
            // =====================================================
            if ($first) {

                $first = false;

                continue;
            }


            // =====================================================
            // DATA
            // =====================================================
            $uid = trim(
                (string) ($data[0] ?? '')
            );

            $nama = trim(
                (string) ($data[1] ?? '')
            );

            $unitName = trim(
                (string) ($data[2] ?? '')
            );


            // Hapus BOM kalau ternyata ikut UID
            $uid = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $uid
            );


            if (
                $uid === '' ||
                $nama === '' ||
                $unitName === ''
            ) {

                $skipped++;

                continue;
            }


            // =====================================================
            // CARI UNIT
            // =====================================================
            $unit = Unit::where('nama', $unitName)
                ->orWhere('code', $unitName)
                ->first();


            if (!$unit) {

                $unitTidakDitemukan[
                    $unitName
                ] = true;

                $skipped++;

                continue;
            }


            // =====================================================
            // UID + UNIT HARUS UNIK
            //
            // Penting untuk SMA:
            // "01" tetap berbeda dengan "1"
            // =====================================================
            $employee = Employee::where(
                    'uid',
                    $uid
                )
                ->where(
                    'unit_id',
                    $unit->id
                )
                ->first();


            if ($employee) {

                $employee->update([
                    'nama' => $nama,
                ]);

                $updated++;

            } else {

                Employee::create([
                    'uid' => $uid,
                    'nama' => $nama,
                    'unit_id' => $unit->id,
                ]);

                $inserted++;
            }
        }


        fclose($file);


        $message =
            'Upload Employee berhasil. ' .
            'Baru: ' . $inserted . '. ' .
            'Update: ' . $updated . '. ' .
            'Dilewati: ' . $skipped . '.';


        if (!empty($unitTidakDitemukan)) {

            $message .=
                ' Unit tidak ditemukan: ' .
                implode(
                    ', ',
                    array_keys($unitTidakDitemukan)
                ) .
                '.';
        }


        return redirect()
            ->route('employee.index')
            ->with(
                'success',
                $message
            );
    }
}
