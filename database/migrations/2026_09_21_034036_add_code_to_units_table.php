<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah identitas lama untuk kompatibilitas import
        Schema::table('units', function (Blueprint $table) {
            $table->string('code')
                ->nullable()
                ->unique()
                ->after('nama');
        });

        // Simpan nama lama sebagai CODE,
        // lalu ubah NAMA menjadi nama tampilan baru.
        $mapping = [
            'Elementary' => 'EL',
            'JHS'        => 'JH',
            'PS Gama'    => 'GM',
            'PS Tama'    => 'TM',
            'SHS'        => 'SH',
            'UM'         => 'UM',
        ];

        foreach ($mapping as $namaLama => $namaBaru) {
            DB::table('units')
                ->where('nama', $namaLama)
                ->update([
                    'code' => $namaLama,
                    'nama' => $namaBaru,
                ]);
        }
    }

    public function down(): void
    {
        // Kembalikan nama lama
        $mapping = [
            'EL' => 'Elementary',
            'JH' => 'JHS',
            'GM' => 'PS Gama',
            'TM' => 'PS Tama',
            'SH' => 'SHS',
            'UM' => 'UM',
        ];

        foreach ($mapping as $namaBaru => $namaLama) {
            DB::table('units')
                ->where('nama', $namaBaru)
                ->update([
                    'nama' => $namaLama,
                ]);
        }

        Schema::table('units', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};