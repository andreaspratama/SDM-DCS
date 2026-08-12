<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Unit;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    public function index()
    {
        $data = Employee::latest()->get();
        return view('pages.employee.index', compact('data'));
    }

    public function formUpload()
    {
        return view('pages.employee.form');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        $file = fopen($request->file('file'), 'r');

        $first = true;

        while (($row = fgetcsv($file)) !== false) {

            // skip header
            if ($first) {
                $first = false;
                continue;
            }

            if (!isset($row[0])) continue;

            // handle separator ; atau ,
            $data = str_contains($row[0], ';')
                ? explode(';', $row[0])
                : $row;

            if (count($data) < 2) continue;

            // 🔥 bersihin data
            $uid       = trim($data[0] ?? '');
            $nama      = trim($data[1] ?? '');
            $unitName  = trim($data[2] ?? '');

            if (!$uid || !$nama || !$unitName) {
                continue;
            }

            // 🔥 cari unit
            $unit = Unit::where('nama', $unitName)->first();

            if (!$unit) {
                // optional: log biar tau error
                \Log::warning("Unit tidak ditemukan: " . $unitName);
                continue;
            }

            // 🔥 FIX UTAMA: pakai uid + unit_id
            Employee::updateOrCreate(
                [
                    'uid' => $uid,
                    'unit_id' => $unit->id
                ],
                [
                    'nama' => $nama
                ]
            );
        }

        fclose($file);

        return redirect()->route('employee.index')
            ->with('success', 'Employee berhasil diupload tanpa overwrite!');
    }
}
