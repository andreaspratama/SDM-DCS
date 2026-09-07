<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\WorkCalendarDate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkCalendarController extends Controller
{
    // =====================================================
    // INDEX
    // =====================================================
    public function index(Request $request)
    {
        $this->ensureAdmin();

        $query = WorkCalendarDate::with('unit')
            ->orderBy('date');


        // ================================================
        // FILTER TAHUN AJARAN
        // ================================================
        if ($request->filled('academic_year')) {

            $query->where(
                'academic_year',
                $request->academic_year
            );
        }


        // ================================================
        // FILTER BULAN
        // Format: YYYY-MM
        // ================================================
        if ($request->filled('month')) {

            try {

                $month = Carbon::createFromFormat(
                    'Y-m',
                    $request->month
                );

                $query
                    ->whereYear(
                        'date',
                        $month->year
                    )
                    ->whereMonth(
                        'date',
                        $month->month
                    );

            } catch (\Throwable $e) {
                //
            }
        }


        // ================================================
        // FILTER UNIT
        // "global" = semua unit / unit_id NULL
        // ================================================
        if ($request->filled('unit_id')) {

            if ($request->unit_id === 'global') {

                $query->whereNull('unit_id');

            } else {

                $query->where(
                    'unit_id',
                    $request->unit_id
                );
            }
        }


        // ================================================
        // FILTER STATUS
        // ================================================
        if ($request->filled('status')) {

            if ($request->status === 'workday') {

                $query->where(
                    'is_workday',
                    true
                );

            } elseif ($request->status === 'holiday') {

                $query->where(
                    'is_workday',
                    false
                );
            }
        }


        $calendars = $query
            ->paginate(31)
            ->withQueryString();


        $units = Unit::orderBy('nama')
            ->get();


        // ================================================
        // DAFTAR TAHUN AJARAN YANG SUDAH ADA
        // ================================================
        $academicYears = WorkCalendarDate::query()
            ->select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');


        return view(
            'pages.workcalender.index',
            compact(
                'calendars',
                'units',
                'academicYears'
            )
        );
    }


    // =====================================================
    // STORE
    // Bisa satu tanggal atau range tanggal
    // =====================================================
    public function store(Request $request)
    {
        $this->ensureAdmin();


        $validated = $request->validate([
            'academic_year' => [
                'required',
                'regex:/^\d{4}\/\d{4}$/',
            ],

            'date_start' => [
                'required',
                'date',
            ],

            'date_end' => [
                'required',
                'date',
                'after_or_equal:date_start',
            ],

            'unit_id' => [
                'nullable',
                'exists:units,id',
            ],

            'is_workday' => [
                'required',
                Rule::in([
                    '0',
                    '1',
                    0,
                    1,
                ]),
            ],

            'type' => [
                'nullable',
                Rule::in([
                    'libur_gk',
                    'libur_nasional',
                    'libur_khusus',
                    'hari_kerja_khusus',
                ]),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ], [
            'academic_year.required' =>
                'Tahun ajaran wajib diisi.',

            'academic_year.regex' =>
                'Format tahun ajaran harus seperti 2026/2027.',

            'date_start.required' =>
                'Tanggal mulai wajib diisi.',

            'date_end.required' =>
                'Tanggal selesai wajib diisi.',

            'date_end.after_or_equal' =>
                'Tanggal selesai tidak boleh sebelum tanggal mulai.',

            'is_workday.required' =>
                'Status hari kerja wajib dipilih.',

            'name.required' =>
                'Nama / keterangan kalender wajib diisi.',
        ]);


        $start = Carbon::parse(
            $validated['date_start']
        )->startOfDay();


        $end = Carbon::parse(
            $validated['date_end']
        )->startOfDay();


        /*
         * Pengaman supaya Admin tidak salah membuat
         * range ribuan hari.
         */
        if (
            $start->diffInDays($end) > 370
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'date_end' =>
                        'Rentang kalender maksimal 371 hari.',
                ]);
        }


        $unitId =
            $validated['unit_id'] ?? null;


        DB::transaction(
            function () use (
                $validated,
                $start,
                $end,
                $unitId
            ) {

                $period = CarbonPeriod::create(
                    $start,
                    $end
                );


                foreach ($period as $date) {

                    /*
                     * Satu tanggal + satu scope unit
                     * hanya boleh memiliki satu override.
                     *
                     * Kalau sudah ada, kita UPDATE,
                     * bukan membuat duplikat.
                     */
                    $query =
                        WorkCalendarDate::whereDate(
                            'date',
                            $date->toDateString()
                        );


                    if ($unitId) {

                        $query->where(
                            'unit_id',
                            $unitId
                        );

                    } else {

                        $query->whereNull(
                            'unit_id'
                        );
                    }


                    $calendar =
                        $query->first();


                    $data = [
                        'academic_year' =>
                            $validated['academic_year'],

                        'date' =>
                            $date->toDateString(),

                        'unit_id' =>
                            $unitId,

                        'is_workday' =>
                            (bool) $validated['is_workday'],

                        'type' =>
                            $validated['type'] ?? null,

                        'name' =>
                            $validated['name'],

                        'description' =>
                            $validated['description'] ?? null,
                    ];


                    if ($calendar) {

                        $calendar->update(
                            $data
                        );

                    } else {

                        WorkCalendarDate::create(
                            $data
                        );
                    }
                }
            }
        );


        $totalDays =
            $start->diffInDays($end) + 1;


        return redirect()
            ->route('workCalendar.index')
            ->with(
                'success',
                $totalDays
                . ' tanggal kalender kerja berhasil disimpan.'
            );
    }


    // =====================================================
    // UPDATE SATU TANGGAL
    // =====================================================
    public function update(
        Request $request,
        WorkCalendarDate $workCalendar
    ) {
        $this->ensureAdmin();


        $validated = $request->validate([
            'academic_year' => [
                'required',
                'regex:/^\d{4}\/\d{4}$/',
            ],

            'date' => [
                'required',
                'date',
            ],

            'unit_id' => [
                'nullable',
                'exists:units,id',
            ],

            'is_workday' => [
                'required',
                Rule::in([
                    '0',
                    '1',
                    0,
                    1,
                ]),
            ],

            'type' => [
                'nullable',
                Rule::in([
                    'libur_gk',
                    'libur_nasional',
                    'libur_khusus',
                    'hari_kerja_khusus',
                ]),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ]);


        $unitId =
            $validated['unit_id'] ?? null;


        // ================================================
        // CEK DUPLIKAT TANGGAL + SCOPE UNIT
        // ================================================
        $duplicate =
            WorkCalendarDate::whereDate(
                'date',
                $validated['date']
            )
            ->where(
                'id',
                '!=',
                $workCalendar->id
            );


        if ($unitId) {

            $duplicate->where(
                'unit_id',
                $unitId
            );

        } else {

            $duplicate->whereNull(
                'unit_id'
            );
        }


        if ($duplicate->exists()) {

            return back()
                ->withInput()
                ->withErrors([
                    'date' =>
                        'Tanggal tersebut sudah mempunyai kalender pada scope unit yang sama.',
                ]);
        }


        $workCalendar->update([
            'academic_year' =>
                $validated['academic_year'],

            'date' =>
                $validated['date'],

            'unit_id' =>
                $unitId,

            'is_workday' =>
                (bool) $validated['is_workday'],

            'type' =>
                $validated['type'] ?? null,

            'name' =>
                $validated['name'],

            'description' =>
                $validated['description'] ?? null,
        ]);


        return redirect()
            ->route('workCalendar.index')
            ->with(
                'success',
                'Kalender kerja berhasil diperbarui.'
            );
    }


    // =====================================================
    // DELETE SATU TANGGAL
    // =====================================================
    public function destroy(
        WorkCalendarDate $workCalendar
    ) {
        $this->ensureAdmin();

        $workCalendar->delete();


        return redirect()
            ->route('workCalendar.index')
            ->with(
                'success',
                'Override kalender kerja berhasil dihapus.'
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