<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAnnualLeave;
use App\Models\LeavePeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class AnnualLeaveAdminController extends Controller
{
    
    public function index(Request $request)
    {
        $this->ensureAdmin();

        $periods = LeavePeriod::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $activePeriod = LeavePeriod::where('is_active', true)->first();

        // Gunakan TA yang dipilih admin.
        // Jika belum memilih, tampilkan TA aktif.
        $selectedPeriodId = $request->query('period_id');

        $period = $selectedPeriodId
            ? $periods->firstWhere('id', (int) $selectedPeriodId)
            : $activePeriod;

        // Jika ID tidak valid, kembali ke TA aktif.
        if (!$period) {
            $period = $activePeriod;
        }

        $selectedPeriodId = $period?->id;

        $employees = Employee::where('is_active', true)
            ->orderBy('nama')
            ->get();

        $eligibilities = $period
            ? EmployeeAnnualLeave::where(
                'leave_period_id',
                $period->id
            )->get()->keyBy('employee_id')
            : collect();

        return view(
            'pages.absensi.annual-leave-admin',
            compact(
                'period',
                'periods',
                'selectedPeriodId',
                'activePeriod',
                'employees',
                'eligibilities'
            )
        );
    }
    
    public function update(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'period_id' => [
                'required',
                'integer',
                'exists:leave_periods,id',
            ],
            'employee_ids' => [
                'nullable',
                'array',
            ],
            'employee_ids.*' => [
                'integer',
                'distinct',
                'exists:employees,id',
            ],
        ]);

        $period = LeavePeriod::findOrFail($validated['period_id']);

        $employeeIds = collect($validated['employee_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($period, $employeeIds) {
            // Nonaktifkan hak cuti yang tidak dipilih,
            // hanya pada periode yang sedang diedit.
            EmployeeAnnualLeave::where(
                'leave_period_id',
                $period->id
            )->update([
                'is_eligible' => false,
            ]);

            // Aktifkan hak cuti untuk pegawai yang dipilih.
            foreach ($employeeIds as $employeeId) {
                $employee = Employee::where('is_active', true)
                    ->find($employeeId);

                // Abaikan pegawai yang sudah tidak aktif.
                if (!$employee) {
                    continue;
                }

                EmployeeAnnualLeave::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_period_id' => $period->id,
                    ],
                    [
                        'allowance_days' => 17,
                        'is_eligible' => true,
                    ]
                );
            }
        });

        return redirect()
            ->route('annualLeaveAdmin.index', [
                'period_id' => $period->id,
            ])
            ->with(
                'success',
                'Pengaturan hak cuti pegawai untuk ' .
                $period->name .
                ' berhasil disimpan.'
            );
    }

    public function periods()
    {
        $this->ensureAdmin();

        $periods = LeavePeriod::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        return view(
            'pages.absensi.annual-leave-periods',
            compact('periods')
        );
    }

    public function storePeriod(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                'unique:leave_periods,name',
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],
        ], [
            'name.required' => 'Nama tahun ajaran wajib diisi.',
            'name.unique' => 'Nama tahun ajaran tersebut sudah terdaftar.',
            'name.max' => 'Nama tahun ajaran maksimal 50 karakter.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus sama dengan atau setelah tanggal mulai.',
        ]);

        // Cegah periode tahun ajaran yang tanggalnya tumpang tindih.
        $overlap = LeavePeriod::query()
            ->whereDate('start_date', '<=', $validated['end_date'])
            ->whereDate('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($overlap) {
            return back()
                ->withInput()
                ->withErrors([
                    'start_date' => 'Rentang tanggal bertumpang tindih dengan periode tahun ajaran yang sudah ada.',
                ]);
        }

        // Periode baru selalu dibuat nonaktif.
        LeavePeriod::create([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => false,
        ]);

        return redirect()
            ->route('annualLeaveAdmin.periods')
            ->with(
                'success',
                'Tahun ajaran berhasil dibuat. Periode belum aktif; aktifkan saat sudah siap digunakan.'
            );
    }

    public function activatePeriod(LeavePeriod $period)
    {
        $this->ensureAdmin();

        DB::transaction(function () use ($period) {
            // Kunci periode yang akan diaktifkan.
            $target = LeavePeriod::query()
                ->whereKey($period->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Pastikan hanya satu periode yang aktif.
            LeavePeriod::query()
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $target->update([
                'is_active' => true,
            ]);
        });

        return redirect()
            ->route('annualLeaveAdmin.periods')
            ->with(
                'success',
                'Periode ' . $period->name . ' berhasil diaktifkan. Periode sebelumnya tetap tersimpan.'
            );
    }

    private function ensureAdmin(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->isAdmin(),
            403,
            'Hanya Admin yang dapat mengatur Cuti Tahunan.'
        );
    }

    
    public function statistics(Request $request)
    {
        $this->ensureAdmin();

        // Ambil semua periode, termasuk tahun ajaran lama.
        $periods = LeavePeriod::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $activePeriod = LeavePeriod::where('is_active', true)->first();

        // Periode dari dropdown, atau periode aktif jika belum dipilih.
        $requestedPeriodId = $request->query('period_id');

        $period = $requestedPeriodId
            ? $periods->firstWhere('id', (int) $requestedPeriodId)
            : $activePeriod;

        // Jika periode yang diminta tidak valid, gunakan periode aktif.
        if (!$period) {
            $period = $activePeriod;
        }

        $selectedPeriodId = $period?->id;

        $employees = collect();

        $stats = [
            'total_employees' => 0,
            'total_allowance' => 0,
            'total_used' => 0,
            'total_remaining' => 0,
        ];

        if ($period) {
            $employees = DB::table('employee_annual_leaves as ea')
                ->join('employees as e', 'e.id', '=', 'ea.employee_id')

                // Ambil nama unit pegawai
                ->leftJoin('units as u', 'u.id', '=', 'e.unit_id')

                ->leftJoin(
                    'annual_leave_transactions as t',
                    't.employee_annual_leave_id',
                    '=',
                    'ea.id'
                )
                ->where('ea.leave_period_id', $period->id)
                ->where('ea.is_eligible', true)
                ->select(
                    'ea.id as eligibility_id',
                    'e.id as employee_id',
                    'e.nama',
                    'e.uid',
                    'e.unit_id',
                    'u.nama as nama_unit',
                    'ea.allowance_days'
                )
                ->selectRaw("
                    COALESCE(SUM(
                        CASE
                            WHEN t.type = 'debit' THEN t.days
                            WHEN t.type = 'credit' THEN -t.days
                            ELSE 0
                        END
                    ), 0) AS total_used
                ")
                ->groupBy(
                    'ea.id',
                    'e.id',
                    'e.nama',
                    'e.uid',
                    'e.unit_id',
                    'u.nama',
                    'ea.allowance_days'
                )
                ->orderBy('e.nama')
                ->get()
                ->map(function ($employee) {
                    $employee->allowance_days = (int) $employee->allowance_days;
                    $employee->total_used = (int) $employee->total_used;

                    $employee->remaining = $employee->allowance_days
                        - $employee->total_used;

                    return $employee;
                });

            $stats = [
                'total_employees' => $employees->count(),
                'total_allowance' => (int) $employees->sum('allowance_days'),
                'total_used' => (int) $employees->sum('total_used'),
                'total_remaining' => (int) $employees->sum('remaining'),
            ];
        }

        return view(
            'pages.absensi.annual-leave-statistics',
            compact(
                'period',
                'periods',
                'selectedPeriodId',
                'activePeriod',
                'employees',
                'stats'
            )
        );
    }
}