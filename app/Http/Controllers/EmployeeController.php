<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Unit;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    public function index()
    {
        $units = Unit::orderBy('nama')->get();
        return view('pages.employee.index', compact('units'));
    }

    public function datatable(Request $request)
    {
        $query = Employee::query()
            ->with([
                'unit:id,nama',
                'workSchedule:id,nama',
            ])
            ->select([
                'id',
                'uid',
                'nama',
                'unit_id',
                'role',
                'work_schedule_id',
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
                return $row->unit?->nama ?? '-';
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

                return '
                    <a href="' .
                        route('employeeOrganization.edit', $row->id) .
                    '"
                    class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil-square"></i>
                        Atur
                    </a>
                ';
            })

            ->rawColumns([
                'jabatan',
                'jadwal',
                'aksi',
            ])

            ->toJson();
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
            $unit = Unit::where(
                'nama',
                $unitName
            )->first();


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
