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
        Schema::create('employee_work_schedule_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_work_schedule_id')
                ->constrained('employee_work_schedules')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('hari');

            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();

            $table->boolean('is_libur')->default(false);
            $table->timestamps();
            $table->unique(
                ['employee_work_schedule_id', 'hari'],
                'ews_days_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_work_schedule_days');
    }
};
