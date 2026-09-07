<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\UnitFormToken;
use Illuminate\Support\Str;

class UnitFormTokenController extends Controller
{
    public function index()
    {
        $units = Unit::with('formToken')->orderBy('nama')->get();

        $tokens = UnitFormToken::with('unit')
            ->where('is_active', true)
            ->get()
            ->keyBy('unit_id');

        return view('pages.absensi.unit-form-token', compact(
            'units',
            'tokens'
        ));
    }

    public function generate(Unit $unit)
    {
        // Nonaktifkan token lama
        UnitFormToken::where('unit_id', $unit->id)
            ->update([
                'is_active' => false
            ]);

        // Buat token random
        $token = Str::random(64);

        // Simpan hash token ke database
        UnitFormToken::create([
            'unit_id' => $unit->id,
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);

        // URL asli untuk dibagikan ke unit
        $link = url('/form-izin/' . $token);

        return redirect()
            ->route('unitFormToken.index')
            ->with('generated_token', [
                'unit' => $unit->nama,
                'link' => $link,
            ]);
    }
}
