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
        Schema::table('attendance_permissions', function (Blueprint $table) {
            $table->foreignId('approver_user_id')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_permissions', function (Blueprint $table) {
            $table->dropForeign([
                'approver_user_id'
            ]);

            $table->dropColumn(
                'approver_user_id'
            );
        });
    }
};
