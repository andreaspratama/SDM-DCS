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
        Schema::create('employee_unit_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();

            $table->date('tanggal_mulai');

            $table->date('tanggal_selesai')
                ->nullable();

            $table->string('keterangan')
                ->nullable();

            $table->index(
                ['employee_id', 'tanggal_mulai', 'tanggal_selesai'],
                'emp_unit_hist_period_idx'
            );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_unit_histories');
    }
};
