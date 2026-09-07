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
        Schema::create('school_calendars', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');

            $table->string('nama');

            $table->enum('jenis', [
                'efektif',
                'libur_semester',
                'libur_nasional',
                'libur_khusus',
                'kegiatan_sekolah',
                'lainnya',
            ])->default('efektif');

            $table->boolean('is_hari_kerja')->default(true);

            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_calendars');
    }
};
