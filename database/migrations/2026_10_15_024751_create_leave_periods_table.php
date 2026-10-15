<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_periods', function (Blueprint $table) {
            $table->id();

            // Contoh: Tahun Ajaran 2026/2027
            $table->string('name', 50)->unique();

            $table->date('start_date');
            $table->date('end_date');

            // Periode yang sedang digunakan
            $table->boolean('is_active')->default(false);

            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_periods');
    }
};
