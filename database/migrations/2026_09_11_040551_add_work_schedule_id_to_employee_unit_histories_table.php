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
        Schema::table('employee_unit_histories', function (Blueprint $table) {
            $table->foreignId('work_schedule_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('work_schedules')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_unit_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_schedule_id');
        });
    }
};
