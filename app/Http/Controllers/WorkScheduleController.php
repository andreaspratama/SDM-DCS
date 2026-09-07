<?php

namespace App\Http\Controllers;

use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkScheduleController extends Controller
{
    // =====================================================
    // DAFTAR TEMPLATE JADWAL
    // =====================================================
    public function index()
    {
        $this->ensureAdmin();

        $schedules = WorkSchedule::with([
                'days' => function ($query) {
                    $query->orderBy('hari');
                },
            ])
            ->withCount('employees')
            ->orderBy('id')
            ->get();

        return view(
            'pages.workschedules.index',
            compact('schedules')
        );
    }


    // =====================================================
    // FORM EDIT TEMPLATE
    // =====================================================
    public function edit(WorkSchedule $workSchedule)
    {
        $this->ensureAdmin();

        $workSchedule->load([
            'days' => function ($query) {
                $query->orderBy('hari');
            },
        ]);

        return view(
            'pages.workschedules.edit',
            compact('workSchedule')
        );
    }


    // =====================================================
    // UPDATE TEMPLATE
    // =====================================================
    public function update(
        Request $request,
        WorkSchedule $workSchedule
    ) {
        $this->ensureAdmin();

        $validated = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:255',

                Rule::unique(
                    'work_schedules',
                    'nama'
                )->ignore($workSchedule->id),
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
            'nama.required' =>
                'Nama jadwal wajib diisi.',

            'nama.unique' =>
                'Nama jadwal tersebut sudah digunakan.',

            'days.required' =>
                'Pengaturan hari wajib diisi.',
        ]);


        /*
         * Validasi jam untuk setiap hari kerja.
         */
        foreach ($validated['days'] as $day) {

            $isLibur =
                (bool) $day['is_libur'];

            if (!$isLibur) {

                if (
                    empty($day['jam_masuk'])
                    ||
                    empty($day['jam_pulang'])
                ) {

                    return back()
                        ->withInput()
                        ->withErrors([
                            'days' =>
                                'Jam masuk dan jam pulang wajib diisi pada hari kerja.',
                        ]);
                }


                if (
                    $day['jam_pulang']
                    <=
                    $day['jam_masuk']
                ) {

                    return back()
                        ->withInput()
                        ->withErrors([
                            'days' =>
                                'Jam pulang harus lebih besar dari jam masuk.',
                        ]);
                }
            }
        }


        DB::transaction(
            function () use (
                $validated,
                $workSchedule
            ) {

                // =========================================
                // SIMPAN SETIAP HARI
                // =========================================
                foreach ($validated['days'] as $day) {

                    $isLibur =
                        (bool) $day['is_libur'];


                    $workSchedule
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


                /*
                 * work_schedules masih memiliki
                 * kolom jam_masuk / jam_pulang lama.
                 *
                 * Kita sinkronkan dengan hari kerja
                 * pertama supaya data lama tetap konsisten.
                 */
                $firstWorkday =
                    collect($validated['days'])
                        ->first(
                            fn ($day) =>
                                !(bool) $day['is_libur']
                        );


                $workSchedule->update([
                    'nama' =>
                        $validated['nama'],

                    'jam_masuk' =>
                        $firstWorkday['jam_masuk']
                        ?? null,

                    'jam_pulang' =>
                        $firstWorkday['jam_pulang']
                        ?? null,
                ]);
            }
        );


        return redirect()
            ->route('workSchedule.index')
            ->with(
                'success',
                'Template jadwal kerja berhasil diperbarui.'
            );
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