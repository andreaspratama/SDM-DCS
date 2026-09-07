<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolCalendar;

class SchoolCalendarController extends Controller
{
    public function index()
    {
        $calendars = SchoolCalendar::orderBy('tanggal_mulai')->get();

        return view('pages.kaldik.index', compact('calendars'));
    }

    public function create()
    {
        return view('pages.kaldik.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:efektif,libur_semester,libur_nasional,libur_khusus,kegiatan_sekolah,lainnya'],
            'is_hari_kerja' => ['required', 'boolean'],
            'keterangan' => ['nullable', 'string'],
        ]);

        SchoolCalendar::create($validated);

        return redirect()
            ->route('school-calendar.index')
            ->with('success', 'Data kalender pendidikan berhasil ditambahkan.');
    }

    public function edit(SchoolCalendar $schoolCalendar)
    {
        return view('pages.kaldik.edit', compact('schoolCalendar'));
    }

    public function update(Request $request, SchoolCalendar $schoolCalendar)
    {
        $validated = $request->validate([
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:efektif,libur_semester,libur_nasional,libur_khusus,kegiatan_sekolah,lainnya'],
            'is_hari_kerja' => ['required', 'boolean'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $schoolCalendar->update($validated);

        return redirect()
            ->route('admin.school-calendar.index')
            ->with('success', 'Data kalender pendidikan berhasil diperbarui.');
    }

    public function destroy(SchoolCalendar $schoolCalendar)
    {
        $schoolCalendar->delete();

        return redirect()
            ->route('admin.school-calendar.index')
            ->with('success', 'Data kalender pendidikan berhasil dihapus.');
    }
}
