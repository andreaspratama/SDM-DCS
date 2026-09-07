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
        Schema::create('work_calendar_dates', function (Blueprint $table) {
            $table->id();
            // Tahun ajaran, contoh: 2026/2027
            $table->string('academic_year', 9);

            // Tanggal kalender
            $table->date('date');

            /*
            |--------------------------------------------------------------------------
            | UNIT
            |--------------------------------------------------------------------------
            |
            | NULL = berlaku untuk seluruh unit.
            |
            | Kalau nanti ada libur khusus unit tertentu,
            | bisa diisi unit_id.
            |
            */
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | STATUS HARI KERJA
            |--------------------------------------------------------------------------
            |
            | true  = hari kerja
            | false = libur
            |
            */
            $table->boolean('is_workday');

            /*
            |--------------------------------------------------------------------------
            | JENIS
            |--------------------------------------------------------------------------
            |
            | Contoh:
            | libur_gk
            | libur_nasional
            | libur_khusus
            | hari_kerja_khusus
            |
            */
            $table->string('type', 50)->nullable();

            // Nama / alasan kalender
            $table->string('name')->nullable();

            // Catatan tambahan kalau diperlukan
            $table->text('description')->nullable();
            $table->timestamps();
            // Biar pencarian tanggal cepat
            $table->index('date');

            $table->index([
                'academic_year',
                'date'
            ]);

            $table->index([
                'unit_id',
                'date'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_calendar_dates');
    }
};
