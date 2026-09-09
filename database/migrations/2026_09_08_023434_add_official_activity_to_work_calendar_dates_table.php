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
        Schema::table('work_calendar_dates', function (Blueprint $table) {
            $table->boolean('has_official_activity')
                ->default(false)
                ->after('is_workday');

            $table->string('official_activity_name')
                ->nullable()
                ->after('has_official_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_calendar_dates', function (Blueprint $table) {
            $table->dropColumn([
                'has_official_activity',
                'official_activity_name',
            ]);
        });
    }
};
