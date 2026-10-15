
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_annual_leaves', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees');

            $table->foreignId('leave_period_id')
                ->constrained('leave_periods');

            // Jatah cuti per pegawai per periode
            $table->unsignedSmallInteger('allowance_days')
                ->default(12);

            // Admin menentukan pegawai yang berhak cuti
            $table->boolean('is_eligible')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();

            // Satu pegawai hanya memiliki satu hak cuti
            // untuk setiap periode tahun ajaran.
            $table->unique(
                ['employee_id', 'leave_period_id'],
                'employee_annual_leave_employee_period_unique'
            );

            $table->index([
                'leave_period_id',
                'is_eligible',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_annual_leaves');
    }
};
