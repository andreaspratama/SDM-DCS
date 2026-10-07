<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    // =====================================================
    // DAFTAR USER
    // =====================================================
    public function index()
    {
        $this->ensureAdmin();

        // ================================================
        // SEMUA USER
        // ================================================
        $users = User::with([
            'employee.unit',
            'employee.division',
        ])
        ->orderBy('name')
        ->get();


        // ================================================
        // EMPLOYEE YANG BELUM PUNYA AKUN
        // Untuk form TAMBAH
        // ================================================
        $availableEmployees = Employee::with([
                'unit',
                'division',
            ])
            ->whereDoesntHave('user')
            ->whereIn('role', [
                'Direktur',
                'Sekretaris',
                'Kepala Bidang',
                'Kepala Sekolah',
            ])
            ->orderBy('nama')
            ->get();


        // ================================================
        // SEMUA PIMPINAN
        // Untuk form EDIT
        // Employee yang sudah punya akun tetap harus muncul
        // ================================================
        $leadershipEmployees = Employee::with([
                'unit',
                'division',
            ])
            ->whereIn('role', [
                'Direktur',
                'Sekretaris',
                'Kepala Bidang',
                'Kepala Sekolah',
            ])
            ->orderBy('nama')
            ->get();


        return view(
            'pages.user.index',
            compact(
                'users',
                'availableEmployees',
                'leadershipEmployees'
            )
        );
    }

    // =====================================================
    // TAMBAH USER
    // =====================================================
    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'role' => [
                'required',
                Rule::in([
                    'Admin',
                    'Pimpinan',
                    'TU',
                ]),
            ],

            'unit_id' => [
                Rule::requiredIf(
                    fn () =>
                        $request->role === 'TU'
                ),

                'nullable',

                Rule::exists(
                    'units',
                    'id'
                ),
            ],

            'employee_id' => [
                Rule::requiredIf(
                    fn () =>
                        $request->role === 'Pimpinan'
                ),

                'nullable',

                Rule::exists(
                    'employees',
                    'id'
                ),

                Rule::unique(
                    'users',
                    'employee_id'
                ),
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ], [
            'employee_id.required' =>
                'Akun Pimpinan wajib dihubungkan dengan pegawai.',

            'employee_id.unique' =>
                'Pegawai tersebut sudah mempunyai akun login.',

            'email.unique' =>
                'Email tersebut sudah digunakan.',

            'password.confirmed' =>
                'Konfirmasi password tidak sama.',

            'password.min' =>
                'Password minimal 8 karakter.',
        ]);


        $employee = null;


        // =================================================
        // JIKA USER TERHUBUNG KE EMPLOYEE
        // =================================================
        if (!empty($validated['employee_id'])) {

            $employee = Employee::findOrFail(
                $validated['employee_id']
            );
        }


        // =================================================
        // VALIDASI KHUSUS PIMPINAN
        // =================================================
        if ($validated['role'] === 'Pimpinan') {

            $allowedLeadershipRoles = [
                'Direktur',
                'Sekretaris',
                'Kepala Bidang',
                'Kepala Sekolah',
            ];


            if (
                !$employee
                ||
                !in_array(
                    $employee->role,
                    $allowedLeadershipRoles,
                    true
                )
            ) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'employee_id' =>
                            'Akun Pimpinan hanya dapat diberikan kepada Direktur, Kepala Bidang, atau Kepala Sekolah.',
                    ]);
            }
        }


        User::create([
            'name' => $validated['name'],

            'email' => $validated['email'],

            'password' => Hash::make(
                $validated['password']
            ),

            'role' => $validated['role'],

            'unit_id' =>
                $validated['role'] === 'TU'
                    ? $validated['unit_id']
                    : $employee?->unit_id,

            'employee_id' =>
                $employee?->id,
        ]);


        return redirect()
            ->route('userManagement.index')
            ->with(
                'success',
                'Akun pengguna berhasil dibuat.'
            );
    }


    // =====================================================
    // UPDATE USER
    // =====================================================
    public function update(
        Request $request,
        User $user
    ) {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',

                Rule::unique(
                    'users',
                    'email'
                )->ignore($user->id),
            ],

            'role' => [
                'required',

                Rule::in([
                    'Admin',
                    'Pimpinan',
                    'TU',
                ]),
            ],

            // =================================================
            // UNIT
            // Wajib hanya untuk TU
            // =================================================
            'unit_id' => [
                Rule::requiredIf(
                    fn () =>
                        $request->role === 'TU'
                ),

                'nullable',

                Rule::exists(
                    'units',
                    'id'
                ),
            ],

            // =================================================
            // EMPLOYEE
            // Wajib hanya untuk Pimpinan
            // =================================================
            'employee_id' => [
                Rule::requiredIf(
                    fn () =>
                        $request->role === 'Pimpinan'
                ),

                'nullable',

                Rule::exists(
                    'employees',
                    'id'
                ),

                Rule::unique(
                    'users',
                    'employee_id'
                )->ignore($user->id),
            ],

            // =================================================
            // PASSWORD
            // Kosong = password lama tetap
            // =================================================
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],

        ], [

            'employee_id.required' =>
                'Akun Pimpinan wajib dihubungkan dengan pegawai.',

            'employee_id.unique' =>
                'Pegawai tersebut sudah mempunyai akun login lain.',

            'unit_id.required' =>
                'Unit wajib dipilih untuk akun TU.',

            'email.unique' =>
                'Email tersebut sudah digunakan.',

            'password.confirmed' =>
                'Konfirmasi password tidak sama.',

            'password.min' =>
                'Password minimal 8 karakter.',
        ]);


        // =====================================================
        // AMBIL EMPLOYEE
        // Hanya jika ada employee_id
        // =====================================================
        $employee = null;

        if (!empty($validated['employee_id'])) {

            $employee = Employee::findOrFail(
                $validated['employee_id']
            );
        }


        // =====================================================
        // VALIDASI KHUSUS PIMPINAN
        // =====================================================
        if ($validated['role'] === 'Pimpinan') {

            $allowedLeadershipRoles = [
                'Direktur',
                'Sekretaris',
                'Kepala Bidang',
                'Kepala Sekolah',
            ];

            if (
                !$employee
                ||
                !in_array(
                    $employee->role,
                    $allowedLeadershipRoles,
                    true
                )
            ) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'employee_id' =>
                            'Akun Pimpinan hanya dapat diberikan kepada Direktur, Kepala Bidang, atau Kepala Sekolah.',
                    ]);
            }
        }


        // =====================================================
        // JANGAN BIARKAN ADMIN MENGHILANGKAN
        // ROLE ADMIN DARI AKUN SENDIRI
        // =====================================================
        if (
            auth()->id() === $user->id
            &&
            $validated['role'] !== 'Admin'
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'role' =>
                        'Akun yang sedang digunakan tidak dapat menghapus hak akses Admin miliknya sendiri.',
                ]);
        }


        // =====================================================
        // TENTUKAN UNIT
        // =====================================================

        if ($validated['role'] === 'TU') {

            // TU mengambil unit dari form
            $unitId = $validated['unit_id'];

        } else {

            // Admin / Pimpinan
            // Unit mengikuti employee
            $unitId = $employee?->unit_id;
        }


        // =====================================================
        // DATA UPDATE
        // =====================================================

        $data = [
            'name' => $validated['name'],

            'email' => $validated['email'],

            'role' => $validated['role'],

            'unit_id' => $unitId,

            // Hanya Pimpinan yang mempunyai employee
            'employee_id' =>
                $validated['role'] === 'Pimpinan'
                    ? $employee?->id
                    : null,
        ];


        // =====================================================
        // PASSWORD
        // Hanya update jika diisi
        // =====================================================

        if (!empty($validated['password'])) {

            $data['password'] = Hash::make(
                $validated['password']
            );
        }


        // =====================================================
        // UPDATE
        // =====================================================

        $user->update($data);


        return redirect()
            ->route('userManagement.index')
            ->with(
                'success',
                'Akun pengguna berhasil diperbarui.'
            );
    }


    // =====================================================
    // HAPUS USER
    // =====================================================
    public function destroy(User $user)
    {
        $this->ensureAdmin();


        // Tidak boleh hapus akun sendiri
        if (auth()->id() === $user->id) {

            return redirect()
                ->route('userManagement.index')
                ->with(
                    'error',
                    'Anda tidak dapat menghapus akun yang sedang digunakan.'
                );
        }


        /*
         * Jangan hapus user apabila masih menjadi
         * approver izin pending.
         */
        if (
            $user->permissionsToApprove()
                ->where(
                    'status',
                    'pending'
                )
                ->exists()
        ) {

            return redirect()
                ->route('userManagement.index')
                ->with(
                    'error',
                    'Akun tidak dapat dihapus karena masih mempunyai pengajuan izin yang menunggu approval.'
                );
        }


        $user->delete();


        return redirect()
            ->route('userManagement.index')
            ->with(
                'success',
                'Akun pengguna berhasil dihapus.'
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