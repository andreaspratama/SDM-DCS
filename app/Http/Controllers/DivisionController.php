<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DivisionController extends Controller
{
    // =====================================================
    // DAFTAR BIDANG / DIVISI
    // =====================================================
    public function index()
    {
        $this->ensureAdmin();

        $divisions = Division::with('unit')
            ->withCount('employees')
            ->orderBy('unit_id')
            ->orderBy('nama')
            ->get();

        $units = Unit::orderBy('nama')->get();

        return view(
            'pages.divisi.index',
            compact(
                'divisions',
                'units'
            )
        );
    }


    // =====================================================
    // TAMBAH BIDANG
    // =====================================================
    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'unit_id' => [
                'required',
                'exists:units,id',
            ],

            'nama' => [
                'required',
                'string',
                'max:255',

                Rule::unique('divisions', 'nama')
                    ->where(
                        fn ($query) =>
                        $query->where(
                            'unit_id',
                            $request->unit_id
                        )
                    ),
            ],
        ], [
            'unit_id.required' => 'Unit wajib dipilih.',
            'unit_id.exists' => 'Unit tidak valid.',

            'nama.required' => 'Nama bidang wajib diisi.',
            'nama.unique' => 'Bidang tersebut sudah ada pada unit yang dipilih.',
        ]);


        Division::create([
            'unit_id' => $validated['unit_id'],
            'nama' => $validated['nama'],
        ]);


        return redirect()
            ->route('division.index')
            ->with(
                'success',
                'Bidang / divisi berhasil ditambahkan.'
            );
    }


    // =====================================================
    // UPDATE BIDANG
    // =====================================================
    public function update(
        Request $request,
        Division $division
    ) {
        $this->ensureAdmin();

        $validated = $request->validate([
            'unit_id' => [
                'required',
                'exists:units,id',
            ],

            'nama' => [
                'required',
                'string',
                'max:255',

                Rule::unique('divisions', 'nama')
                    ->where(
                        fn ($query) =>
                        $query->where(
                            'unit_id',
                            $request->unit_id
                        )
                    )
                    ->ignore($division->id),
            ],
        ], [
            'unit_id.required' => 'Unit wajib dipilih.',
            'unit_id.exists' => 'Unit tidak valid.',

            'nama.required' => 'Nama bidang wajib diisi.',
            'nama.unique' => 'Bidang tersebut sudah ada pada unit yang dipilih.',
        ]);


        // =================================================
        // JANGAN PINDAH UNIT JIKA SUDAH ADA PEGAWAI
        // =================================================
        if (
            (int) $division->unit_id !==
            (int) $validated['unit_id']
            &&
            $division->employees()->exists()
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'unit_id' =>
                        'Unit tidak dapat diubah karena bidang ini sudah mempunyai pegawai.',
                ]);
        }


        $division->update([
            'unit_id' => $validated['unit_id'],
            'nama' => $validated['nama'],
        ]);


        return redirect()
            ->route('division.index')
            ->with(
                'success',
                'Bidang / divisi berhasil diperbarui.'
            );
    }


    // =====================================================
    // HAPUS BIDANG
    // =====================================================
    public function destroy(Division $division)
    {
        $this->ensureAdmin();


        // Jangan hapus jika masih digunakan pegawai
        if ($division->employees()->exists()) {

            return redirect()
                ->route('division.index')
                ->with(
                    'error',
                    'Bidang tidak dapat dihapus karena masih digunakan oleh pegawai.'
                );
        }


        $division->delete();


        return redirect()
            ->route('division.index')
            ->with(
                'success',
                'Bidang / divisi berhasil dihapus.'
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