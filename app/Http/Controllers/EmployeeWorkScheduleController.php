<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeWorkSchedule;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeWorkScheduleController extends Controller
{
    // =====================================================
    // DAFTAR JADWAL KHUSUS
    // =====================================================
    public function index(Request $request)
    {
        $this->ensureAdmin();

        $query = EmployeeWorkSchedule::with([
            'employee.unit',
            'days' => function ($q) {
                $q->orderBy('hari');
            },
        ])
        ->orderByDesc('tanggal_mulai')
        ->orderByDesc('id');


        // ================================================
        // FILTER UNIT
        // ================================================
        if ($request->filled('unit_id')) {

            $query->whereHas(
                'employee',
                function ($q) use ($request) {

                    $q->where(
                        'unit_id',
                        $request->unit_id
                    );
                }
            );
        }


        // ================================================
        // SEARCH PEGAWAI / NAMA JADWAL
        // ================================================
        if ($request->filled('q')) {

            $keyword = $request->q;

            $query->where(
                function ($q) use ($keyword) {

                    $q->where(
                        'nama',
                        'like',
                        '%' . $keyword . '%'
                    )
                    ->orWhereHas(
                        'employee',
                        function ($employeeQuery) use ($keyword) {

                            $employeeQuery->where(
                                'nama',
                                'like',
                                '%' . $keyword . '%'
                            );
                        }
                    );
                }
            );
        }


        $specialSchedules = $query
            ->paginate(20)
            ->withQueryString();


        $employees = Employee::with('unit')
            ->orderBy('nama')
            ->get();


        $units = Unit::orderBy('nama')
            ->get();


        return view(
            'pages.employeeworkschedules.index',
            compact(
                'specialSchedules',
                'employees',
                'units'
            )
        );
    }


    // =====================================================
    // FORM TAMBAH
    // =====================================================
    public function create()
    {
        $this->ensureAdmin();

        $employees = Employee::with([
                'unit',
                'workSchedule.days',
            ])
            ->orderBy('nama')
            ->get();

        return view(
            'pages.employeeworkschedules.create',
            compact('employees')
        );
    }


    // =====================================================
    // SIMPAN JADWAL KHUSUS
    // =====================================================
    public function store(Request $request)
    {
        $this->ensureAdmin();


        $validated = $this->validateSchedule(
            $request
        );


        $employee = Employee::findOrFail(
            $validated['employee_id']
        );


        // ================================================
        // CEK JADWAL KHUSUS BENTROK
        // ================================================
        $overlap = EmployeeWorkSchedule::where(
                'employee_id',
                $employee->id
            )
            ->whereDate(
                'tanggal_mulai',
                '<=',
                $validated['tanggal_selesai']
            )
            ->whereDate(
                'tanggal_selesai',
                '>=',
                $validated['tanggal_mulai']
            )
            ->exists();


        if ($overlap) {

            return back()
                ->withInput()
                ->withErrors([
                    'tanggal_mulai' =>
                        'Pegawai sudah mempunyai jadwal khusus pada periode yang bertabrakan.',
                ]);
        }


        DB::transaction(
            function () use (
                $validated
            ) {

                $schedule =
                    EmployeeWorkSchedule::create([
                        'employee_id' =>
                            $validated['employee_id'],

                        'tanggal_mulai' =>
                            $validated['tanggal_mulai'],

                        'tanggal_selesai' =>
                            $validated['tanggal_selesai'],

                        'nama' =>
                            $validated['nama'],

                        'keterangan' =>
                            $validated['keterangan'] ?? null,
                    ]);


                $this->saveDays(
                    $schedule,
                    $validated['days']
                );
            }
        );


        return redirect()
            ->route(
                'employeeWorkSchedule.index'
            )
            ->with(
                'success',
                'Jadwal khusus pegawai berhasil dibuat.'
            );
    }


    // =====================================================
    // EDIT
    // =====================================================
    public function edit(
    EmployeeWorkSchedule $employeeWorkSchedule
    ) {
        $this->ensureAdmin();

        $employeeWorkSchedule->load([
            'employee.unit',
            'employee.workSchedule.days',

            'days' => function ($q) {
                $q->orderBy('hari');
            },
        ]);


        $employees = Employee::with([
                'unit',
                'workSchedule.days',
            ])
            ->orderBy('nama')
            ->get();


        return view(
            'pages.employeeworkschedules.edit',
            compact(
                'employeeWorkSchedule',
                'employees'
            )
        );
    }


    // =====================================================
    // UPDATE
    // =====================================================
    public function update(
        Request $request,
        EmployeeWorkSchedule $employeeWorkSchedule
    ) {
        $this->ensureAdmin();


        $validated = $this->validateSchedule(
            $request
        );


        // ================================================
        // CEK OVERLAP KECUALI DIRINYA SENDIRI
        // ================================================
        $overlap = EmployeeWorkSchedule::where(
                'employee_id',
                $validated['employee_id']
            )
            ->where(
                'id',
                '!=',
                $employeeWorkSchedule->id
            )
            ->whereDate(
                'tanggal_mulai',
                '<=',
                $validated['tanggal_selesai']
            )
            ->whereDate(
                'tanggal_selesai',
                '>=',
                $validated['tanggal_mulai']
            )
            ->exists();


        if ($overlap) {

            return back()
                ->withInput()
                ->withErrors([
                    'tanggal_mulai' =>
                        'Pegawai sudah mempunyai jadwal khusus lain pada periode yang bertabrakan.',
                ]);
        }


        DB::transaction(
            function () use (
                $validated,
                $employeeWorkSchedule
            ) {

                $employeeWorkSchedule->update([
                    'employee_id' =>
                        $validated['employee_id'],

                    'tanggal_mulai' =>
                        $validated['tanggal_mulai'],

                    'tanggal_selesai' =>
                        $validated['tanggal_selesai'],

                    'nama' =>
                        $validated['nama'],

                    'keterangan' =>
                        $validated['keterangan'] ?? null,
                ]);


                $this->saveDays(
                    $employeeWorkSchedule,
                    $validated['days']
                );
            }
        );


        return redirect()
            ->route(
                'employeeWorkSchedule.index'
            )
            ->with(
                'success',
                'Jadwal khusus pegawai berhasil diperbarui.'
            );
    }


    // =====================================================
    // DELETE
    // =====================================================
    public function destroy(
        EmployeeWorkSchedule $employeeWorkSchedule
    ) {
        $this->ensureAdmin();


        DB::transaction(
            function () use (
                $employeeWorkSchedule
            ) {

                $employeeWorkSchedule
                    ->days()
                    ->delete();


                $employeeWorkSchedule
                    ->delete();
            }
        );


        return redirect()
            ->route(
                'employeeWorkSchedule.index'
            )
            ->with(
                'success',
                'Jadwal khusus pegawai berhasil dihapus.'
            );
    }


    // =====================================================
    // VALIDASI JADWAL
    // =====================================================
    private function validateSchedule(
        Request $request
    ): array {

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'exists:employees,id',
            ],

            'nama' => [
                'required',
                'string',
                'max:255',
            ],

            'tanggal_mulai' => [
                'required',
                'date',
            ],

            'tanggal_selesai' => [
                'required',
                'date',
                'after_or_equal:tanggal_mulai',
            ],

            'keterangan' => [
                'nullable',
                'string',
            ],

            'days' => [
                'required',
                'array',
                'size:7',
            ],

            'days.*.hari' => [
                'required',
                'integer',
                'between:1,7',
            ],

            'days.*.is_libur' => [
                'required',
                Rule::in([
                    '0',
                    '1',
                    0,
                    1,
                ]),
            ],

            'days.*.jam_masuk' => [
                'nullable',
                'date_format:H:i',
            ],

            'days.*.jam_pulang' => [
                'nullable',
                'date_format:H:i',
            ],
        ], [
            'employee_id.required' =>
                'Pegawai wajib dipilih.',

            'nama.required' =>
                'Nama jadwal khusus wajib diisi.',

            'tanggal_mulai.required' =>
                'Tanggal mulai wajib diisi.',

            'tanggal_selesai.required' =>
                'Tanggal selesai wajib diisi.',

            'tanggal_selesai.after_or_equal' =>
                'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);


        // ================================================
        // PENGAMAN RANGE
        // ================================================
        $start = Carbon::parse(
            $validated['tanggal_mulai']
        );

        $end = Carbon::parse(
            $validated['tanggal_selesai']
        );


        if ($start->diffInDays($end) > 370) {

            throw ValidationException::withMessages([
                'tanggal_selesai' =>
                    'Periode jadwal khusus maksimal 371 hari.',
            ]);
        }


        // ================================================
        // VALIDASI JAM SETIAP HARI
        // ================================================
        foreach (
            $validated['days']
            as $day
        ) {

            $isLibur =
                (bool) $day['is_libur'];


            if (!$isLibur) {

                if (
                    empty($day['jam_masuk'])
                    ||
                    empty($day['jam_pulang'])
                ) {

                    throw ValidationException::withMessages([
                        'days' =>
                            'Jam masuk dan jam pulang wajib diisi pada hari kerja.',
                    ]);
                }


                if (
                    $day['jam_pulang']
                    <=
                    $day['jam_masuk']
                ) {

                    throw ValidationException::withMessages([
                        'days' =>
                            'Jam pulang harus lebih besar dari jam masuk.',
                    ]);
                }
            }
        }


        return $validated;
    }


    // =====================================================
    // SIMPAN DETAIL SENIN - MINGGU
    // =====================================================
    private function saveDays(
        EmployeeWorkSchedule $schedule,
        array $days
    ): void {

        foreach ($days as $day) {

            $isLibur =
                (bool) $day['is_libur'];


            $schedule
                ->days()
                ->updateOrCreate(
                    [
                        'hari' =>
                            $day['hari'],
                    ],
                    [
                        'jam_masuk' =>
                            $isLibur
                                ? null
                                : $day['jam_masuk'],

                        'jam_pulang' =>
                            $isLibur
                                ? null
                                : $day['jam_pulang'],

                        'is_libur' =>
                            $isLibur,
                    ]
                );
        }
    }


    // =====================================================
    // SECURITY
    // =====================================================
    private function ensureAdmin(): void
    {
        $user = auth()->user();

        abort_unless(
            $user &&
            $user->isAdmin(),
            403
        );
    }
}