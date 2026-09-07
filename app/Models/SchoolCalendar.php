<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SchoolCalendar extends Model
{
    protected $fillable = [
        'tanggal_mulai',
        'tanggal_selesai',
        'nama',
        'jenis',
        'is_hari_kerja',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_hari_kerja' => 'boolean',
    ];

    public static function isHariKerja($tanggal): bool
    {
        $tanggal = Carbon::parse($tanggal)->startOfDay();

        $calendar = self::whereDate('tanggal_mulai', '<=', $tanggal)
            ->whereDate('tanggal_selesai', '>=', $tanggal)
            ->orderByDesc('id')
            ->first();

        if ($calendar) {
            return $calendar->is_hari_kerja;
        }

        return $tanggal->isWeekday();
    }
}
