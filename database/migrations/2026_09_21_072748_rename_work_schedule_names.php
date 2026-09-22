<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mapping = [
            'TKGAMA'     => 'GM',
            'TKTAMA'     => 'TM',
            'JHS'        => 'JH',
            'SD'         => 'EL',
            'SHS Guru'   => 'SH Guru',
            'SHS Admin'  => 'SH Admin',
            'SHS Perpus' => 'SH Perpus',
            'UM'         => 'UM',
        ];

        foreach ($mapping as $namaLama => $namaBaru) {
            DB::table('work_schedules')
                ->where('nama', $namaLama)
                ->update([
                    'nama' => $namaBaru,
                ]);
        }
    }

    public function down(): void
    {
        $mapping = [
            'GM'        => 'TKGAMA',
            'TM'        => 'TKTAMA',
            'JH'        => 'JHS',
            'EL'        => 'SD',
            'SH Guru'   => 'SHS Guru',
            'SH Admin'  => 'SHS Admin',
            'SH Perpus' => 'SHS Perpus',
            'UM'        => 'UM',
        ];

        foreach ($mapping as $namaBaru => $namaLama) {
            DB::table('work_schedules')
                ->where('nama', $namaBaru)
                ->update([
                    'nama' => $namaLama,
                ]);
        }
    }
};