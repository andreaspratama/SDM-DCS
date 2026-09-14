<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('is_active')
                ->default(true)
                ->after('role');

            $table->date('tanggal_masuk')
                ->nullable()
                ->after('is_active');

            $table->date('tanggal_keluar')
                ->nullable()
                ->after('tanggal_masuk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'tanggal_masuk',
                'tanggal_keluar',
            ]);
        });
    }
};
