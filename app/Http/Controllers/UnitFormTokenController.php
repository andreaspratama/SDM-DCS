<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\UnitFormToken;
use Illuminate\Support\Str;

class UnitFormTokenController extends Controller
{
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
            'token_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);

        // Untuk sementara kita tampilkan token asli
        return response()->json([
            'unit' => $unit->nama,
            'token' => $token,
        ]);
    }
}
