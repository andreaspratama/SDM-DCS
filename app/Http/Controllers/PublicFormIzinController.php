<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\UnitFormToken;

class PublicFormIzinController extends Controller
{
    public function create(string $token)
    {
        // Hash token dari URL
        $tokenHash = hash('sha256', $token);

        // Cari token yang masih aktif
        $formToken = UnitFormToken::where('token_hash', $tokenHash)
            ->where('is_active', true)
            ->with('unit')
            ->first();

        // Token tidak ditemukan / sudah tidak aktif
        if (!$formToken) {
            abort(404);
        }

        // Ambil employee HANYA dari unit token
        $employees = Employee::where('unit_id', $formToken->unit_id)
            ->orderBy('nama')
            ->get();

        return view('pages.absensi.formIzin', [
            'employees' => $employees,
            'unit' => $formToken->unit,
            'token' => $token,
        ]);
    }
}
