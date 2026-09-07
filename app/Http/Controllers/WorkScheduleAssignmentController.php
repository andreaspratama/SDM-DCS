<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Unit;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkScheduleAssignmentController extends Controller
{
    // =====================================================
    // DAFTAR PEGAWAI + JADWAL
    // =====================================================
    public function index(Request $request)
    {
        $this->ensureAdmin();

        $query = Employee::with([
            'unit',
            'workSchedule',
        ])
        ->orderBy('nama');


        // =================================================
        // SEARCH
        // =================================================
        if ($request->filled('q')) {

            $keyword = $request->q;

            $query->where(function ($q) use ($keyword) {

                $q->where(
                    'nama',
                    'like',
                    '%' . $keyword . '%'
                )
                ->orWhere(
                    'uid',
                    'like',
                    '%' . $keyword . '%'
                );
            });
        }


        // =================================================
        // FILTER UNIT
        // =================================================
        if ($request->filled('unit_id')) {

            $query->where(
                'unit_id',
                $request->unit_id
            );
        }


        // =================================================
        // FILTER TEMPLATE
        // =================================================
        if ($request->filled('work_schedule_id')) {

            if (
                $request->work_schedule_id
                === 'none'
            ) {

                $query->whereNull(
                    'work_schedule_id'
                );

            } else {

                $query->where(
                    'work_schedule_id',
                    $request->work_schedule_id
                );
            }
        }


        $employees = $query
            ->paginate(25)
            ->withQueryString();


        $units = Unit::orderBy('nama')
            ->get();


        $schedules = WorkSchedule::with([
                'days' => function ($query) {
                    $query->orderBy('hari');
                },
            ])
            ->orderBy('nama')
            ->get();


        return view(
            'pages.workscheduleassignment.index',
            compact(
                'employees',
                'units',
                'schedules'
            )
        );
    }


    // =====================================================
    // BULK ASSIGN TEMPLATE
    // =====================================================
    public function bulkUpdate(Request $request)
    {
        $this->ensureAdmin();


        $validated = $request->validate([
            'employee_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'employee_ids.*' => [
                'required',
                'integer',
                'exists:employees,id',
            ],

            'work_schedule_id' => [
                'required',
                'integer',
                'exists:work_schedules,id',
            ],
        ], [
            'employee_ids.required' =>
                'Pilih minimal satu pegawai.',

            'employee_ids.min' =>
                'Pilih minimal satu pegawai.',

            'work_schedule_id.required' =>
                'Pilih template jadwal kerja.',

            'work_schedule_id.exists' =>
                'Template jadwal kerja tidak valid.',
        ]);


        $employees = Employee::whereIn(
            'id',
            $validated['employee_ids']
        )->get();


        if ($employees->isEmpty()) {

            return back()
                ->withInput()
                ->withErrors([
                    'employee_ids' =>
                        'Pegawai yang dipilih tidak ditemukan.',
                ]);
        }


        // =================================================
        // PENGAMAN:
        // BULK HANYA DALAM SATU UNIT
        // =================================================
        $unitIds = $employees
            ->pluck('unit_id')
            ->unique()
            ->values();


        if ($unitIds->count() > 1) {

            return back()
                ->withInput()
                ->withErrors([
                    'employee_ids' =>
                        'Plotting jadwal massal hanya dapat dilakukan untuk pegawai dalam satu unit yang sama.',
                ]);
        }


        $schedule = WorkSchedule::findOrFail(
            $validated['work_schedule_id']
        );


        DB::transaction(
            function () use (
                $employees,
                $schedule
            ) {

                foreach ($employees as $employee) {

                    $employee->work_schedule_id =
                        $schedule->id;

                    $employee->save();
                }
            }
        );


        return redirect()
            ->route(
                'workScheduleAssignment.index'
            )
            ->with(
                'success',
                $employees->count()
                . ' pegawai berhasil menggunakan template jadwal '
                . $schedule->nama
                . '.'
            );
    }


    // =====================================================
    // HAPUS TEMPLATE DARI EMPLOYEE
    // =====================================================
    public function clear(Request $request)
    {
        $this->ensureAdmin();


        $validated = $request->validate([
            'employee_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'employee_ids.*' => [
                'required',
                'integer',
                'exists:employees,id',
            ],
        ]);


        Employee::whereIn(
            'id',
            $validated['employee_ids']
        )->update([
            'work_schedule_id' => null,
        ]);


        return redirect()
            ->route(
                'workScheduleAssignment.index'
            )
            ->with(
                'success',
                count($validated['employee_ids'])
                . ' pegawai berhasil dilepas dari template jadwal.'
            );
    }


    // =====================================================
    // HANYA ADMIN
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