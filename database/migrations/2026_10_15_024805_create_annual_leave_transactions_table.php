
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_leave_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_annual_leave_id')
                ->constrained('employee_annual_leaves');

            $table->foreignId('attendance_permission_id')
                ->constrained('attendance_permissions');

            // debit  = pemotongan saldo
            // credit = pengembalian saldo
            $table->enum('type', [
                'debit',
                'credit',
            ]);

            // Jumlah hari kerja yang dipotong/dikembalikan
            $table->unsignedSmallInteger('days');

            $table->string('description')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Mencegah debit atau credit ganda
            // untuk satu pengajuan dan jenis transaksi.
            $table->unique(
                ['attendance_permission_id', 'type'],
                'annual_leave_permission_type_unique'
            );

            $table->index([
                'employee_annual_leave_id',
                'type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_leave_transactions');
    }
};
