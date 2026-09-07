<?php

namespace App\Http\Controllers;

use App\Models\AttendancePermission;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Unit;
use App\Services\ApprovalResolverService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class EmployeeOrganizationController extends Controller
{
    // =====================================================
    // DAFTAR STRUKTUR ORGANISASI
    // =====================================================
    public function index(
        Request $request,
        ApprovalResolverService $approvalResolver
    ) {
        $this->ensureAdmin();

        // =================================================
        // AMBIL EMPLOYEE
        // =================================================
        $query = Employee::with([
            'unit',
            'division',
            'user',
        ])
        ->orderBy('nama');


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
        // FILTER JABATAN
        // =================================================
        if ($request->filled('role')) {

            $query->where(
                'role',
                $request->role
            );
        }


        // =================================================
        // SEARCH NAMA / UID
        // =================================================
        if ($request->filled('q')) {

            $keyword = $request->q;

            $query->where(
                function ($q) use ($keyword) {

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
                }
            );
        }


        // =================================================
        // PAGINATION
        // =================================================
        $employees = $query
            ->paginate(25)
            ->withQueryString();


        // =================================================
        // UNIT
        // =================================================
        $units = Unit::orderBy('nama')
            ->get();
        
        $divisions = Division::with('unit')
            ->orderBy('unit_id')
            ->orderBy('nama')
            ->get();


        // =================================================
        // DAFTAR JABATAN
        // =================================================
        $roles = [
            'Direktur',
            'Kepala Bidang',
            'Kepala Sekolah',
            'Guru',
            'Staff',
        ];


        // =================================================
        // CARI ATASAN MASING-MASING PEGAWAI
        // =================================================
        $approvers = [];

        foreach ($employees as $employee) {

            if (!$employee->role) {

                $approvers[$employee->id] = null;

                continue;
            }

            $approvers[$employee->id] =
                $approvalResolver->resolve(
                    $employee
                );
        }


        return view(
            'pages.employeorganisasi.index',
            compact(
                'employees',
                'units',
                'roles',
                'approvers',
                'divisions'
            )
        );
    }


    // =====================================================
    // FORM EDIT STRUKTUR EMPLOYEE
    // =====================================================
    public function edit(Employee $employee)
    {
        $this->ensureAdmin();

        $employee->load([
            'unit',
            'division',
            'user',
        ]);


        // Hanya division milik unit employee
        $divisions = Division::where(
                'unit_id',
                $employee->unit_id
            )
            ->orderBy('nama')
            ->get();


        $roles = [
            'Direktur',
            'Kepala Bidang',
            'Kepala Sekolah',
            'Waka Kurikulum',
            'Waka Kesiswaan',
            'Guru',
            'Staff',
        ];


        return view(
            'pages.employeorganisasi.edit',
            compact(
                'employee',
                'divisions',
                'roles'
            )
        );
    }


    // =====================================================
    // UPDATE STRUKTUR EMPLOYEE
    // =====================================================
    public function update(
        Request $request,
        Employee $employee,
        ApprovalResolverService $approvalResolver
    ) {
        $this->ensureAdmin();


        $roles = [
            'Direktur',
            'Kepala Bidang',
            'Kepala Sekolah',
            'Waka Kurikulum',
            'Waka Kesiswaan',
            'Guru',
            'Staff',
        ];


        // =================================================
        // VALIDASI
        // =================================================
        $request->validate([

            'role' => [
                'nullable',
                Rule::in($roles),
            ],

            'division_id' => [

                'nullable',

                Rule::requiredIf(
                    function () use (
                        $request,
                        $employee
                    ) {

                        // Kepala Bidang wajib punya bidang
                        if (
                            $request->role ===
                            'Kepala Bidang'
                        ) {
                            return true;
                        }

                        // Staff UM wajib punya bidang
                        if (
                            $request->role === 'Staff'
                            &&
                            (int) $employee->unit_id === 1
                        ) {
                            return true;
                        }

                        return false;
                    }
                ),

                Rule::exists(
                    'divisions',
                    'id'
                )->where(
                    function ($query) use ($employee) {

                        $query->where(
                            'unit_id',
                            $employee->unit_id
                        );
                    }
                ),
            ],
        ]);


        // =================================================
        // SIMPAN JABATAN
        // =================================================
        $employee->role =
            $request->role ?: null;


        // =================================================
        // SIMPAN BIDANG
        // =================================================
        if (
            in_array(
                $employee->role,
                [
                    'Staff',
                    'Kepala Bidang',
                ],
                true
            )
        ) {

            $employee->division_id =
                $request->division_id ?: null;

        } else {

            // Direktur / KS / Guru
            // tidak perlu division
            $employee->division_id = null;
        }


        $employee->save();


        // =================================================
        // UPDATE ULANG APPROVER IZIN YANG MASIH PENDING
        // =================================================
        $pendingPermissions =
            AttendancePermission::with('employee')
                ->where(
                    'status',
                    AttendancePermission::STATUS_PENDING
                )
                ->get();


        foreach ($pendingPermissions as $permission) {

            if (!$permission->employee) {
                continue;
            }


            $approver =
                $approvalResolver->resolve(
                    $permission->employee
                );


            $newApproverId =
                $approver?->id;


            if (
                $permission->approver_user_id
                !=
                $newApproverId
            ) {

                $permission->approver_user_id =
                    $newApproverId;

                $permission->save();
            }
        }


        return redirect()
            ->route(
                'employeeOrganization.index'
            )
            ->with(
                'success',
                'Struktur organisasi pegawai berhasil diperbarui.'
            );
    }

    // =====================================================
    // BULK UPDATE STRUKTUR PEGAWAI
    // =====================================================
    public function bulkUpdate(
        Request $request,
        ApprovalResolverService $approvalResolver
    ) {
        $this->ensureAdmin();


        // =================================================
        // VALIDASI DASAR
        // =================================================
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

            /*
            * Demi keamanan, bulk hanya untuk
            * jabatan yang jumlahnya banyak.
            *
            * Pimpinan tetap diatur satu per satu.
            */
            'bulk_role' => [
                'required',
                Rule::in([
                    'Guru',
                    'Staff',
                ]),
            ],

            'division_id' => [
                'nullable',
                'integer',
                'exists:divisions,id',
            ],
        ], [
            'employee_ids.required' =>
                'Pilih minimal satu pegawai.',

            'employee_ids.min' =>
                'Pilih minimal satu pegawai.',

            'bulk_role.required' =>
                'Pilih jabatan yang akan diterapkan.',

            'bulk_role.in' =>
                'Bulk hanya tersedia untuk Guru dan Staff.',
        ]);


        // =================================================
        // AMBIL EMPLOYEE TERPILIH
        // =================================================
        $employees = Employee::whereIn(
            'id',
            $validated['employee_ids']
        )->get();


        if ($employees->isEmpty()) {

            return back()->withErrors([
                'employee_ids' =>
                    'Pegawai yang dipilih tidak ditemukan.',
            ]);
        }


        // =================================================
        // BULK HARUS DALAM SATU UNIT
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
                        'Bulk pengaturan hanya dapat dilakukan untuk pegawai dalam satu unit yang sama.',
                ]);
        }


        $unitId = (int) $unitIds->first();

        $role = $validated['bulk_role'];

        $divisionId =
            $validated['division_id'] ?? null;


        // =================================================
        // STAFF UM WAJIB PUNYA BIDANG
        // =================================================
        if (
            $role === 'Staff'
            &&
            $unitId === 1
        ) {

            if (!$divisionId) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'division_id' =>
                            'Staff UM wajib mempunyai Bidang / Divisi.',
                    ]);
            }


            // Pastikan division memang milik UM
            $divisionValid = Division::where(
                    'id',
                    $divisionId
                )
                ->where(
                    'unit_id',
                    $unitId
                )
                ->exists();


            if (!$divisionValid) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'division_id' =>
                            'Bidang yang dipilih tidak sesuai dengan unit pegawai.',
                    ]);
            }
        }


        DB::transaction(
            function () use (
                $employees,
                $role,
                $unitId,
                $divisionId,
                $approvalResolver
            ) {

                // =============================================
                // UPDATE EMPLOYEE
                // =============================================
                foreach ($employees as $employee) {

                    $employee->role = $role;


                    /*
                    * Guru tidak memakai division.
                    */
                    if ($role === 'Guru') {

                        $employee->division_id = null;

                    }

                    /*
                    * Staff UM memakai division.
                    */
                    elseif (
                        $role === 'Staff'
                        &&
                        $unitId === 1
                    ) {

                        $employee->division_id =
                            $divisionId;

                    }

                    /*
                    * Staff unit selain UM saat ini
                    * tidak memakai division.
                    */
                    else {

                        $employee->division_id = null;
                    }


                    $employee->save();
                }


                // =============================================
                // HITUNG ULANG APPROVER IZIN PENDING
                // HANYA PEGAWAI YANG BARU DIUBAH
                // =============================================
                $pendingPermissions =
                    AttendancePermission::with('employee')
                        ->whereIn(
                            'employee_id',
                            $employees->pluck('id')
                        )
                        ->where(
                            'status',
                            AttendancePermission::STATUS_PENDING
                        )
                        ->get();


                foreach (
                    $pendingPermissions
                    as $permission
                ) {

                    if (!$permission->employee) {
                        continue;
                    }


                    $approver =
                        $approvalResolver->resolve(
                            $permission->employee
                        );


                    $permission->approver_user_id =
                        $approver?->id;

                    $permission->save();
                }
            }
        );


        return redirect()
            ->route(
                'employeeOrganization.index'
            )
            ->with(
                'success',
                $employees->count()
                . ' pegawai berhasil diperbarui menjadi '
                . $role
                . '.'
            );
    }

    // =====================================================
    // SECURITY
    // HANYA ADMINISTRATOR
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