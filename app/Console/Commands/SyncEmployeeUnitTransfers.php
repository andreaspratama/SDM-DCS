<?php

namespace App\Console\Commands;

use App\Models\EmployeeUnitHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncEmployeeUnitTransfers extends Command
{
    /**
     * Nama command.
     */
    protected $signature = 'employee:sync-unit-transfers';

    /**
     * Deskripsi command.
     */
    protected $description =
        'Mengaktifkan perpindahan unit pegawai yang sudah mencapai tanggal efektif';


    public function handle(): int
    {
        $today = now()->toDateString();

        // =============================================
        // CARI RIWAYAT UNIT YANG SUDAH MULAI BERLAKU
        // =============================================
        $histories = EmployeeUnitHistory::with('employee')
            ->whereNull('tanggal_selesai')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->get();


        $updated = 0;


        foreach ($histories as $history) {

            $employee = $history->employee;

            if (!$employee) {
                continue;
            }

            // Pegawai nonaktif tidak perlu disinkronkan
            if (!$employee->is_active) {
                continue;
            }

            // Unit sudah benar
            if (
                (int) $employee->unit_id ===
                (int) $history->unit_id
            ) {
                continue;
            }


            DB::transaction(function () use (
                $employee,
                $history,
                &$updated
            ) {

                $updateData = [
                    'unit_id' => $history->unit_id,
                ];

                if ($history->work_schedule_id) {
                    $updateData['work_schedule_id'] =
                        $history->work_schedule_id;
                }

                $employee->update($updateData);

                $updated++;
            });


            $this->line(
                $employee->nama .
                ' → unit ID ' .
                $history->unit_id
            );
        }


        $this->info(
            'Sinkronisasi selesai. ' .
            $updated .
            ' pegawai diperbarui.'
        );

        return self::SUCCESS;
    }
}