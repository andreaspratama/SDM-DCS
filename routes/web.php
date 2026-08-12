<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\UnitFormTokenController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/formIzin', [AbsensiController::class, 'formIzin'])->name('formIzin');
Route::post('/formIzinStore', [AbsensiController::class, 'formIzinStore'])->name('formIzinStore');

Route::prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ABSENSI
    Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.index');
    Route::get('/absensi/upload', [AbsensiController::class, 'formUpload'])->name('absensi.upload.form');
    Route::post('/absensi/upload', [AbsensiController::class, 'uploadLog'])->name('absensi.upload');
    Route::get('/absensi/process', [AbsensiController::class, 'process'])->name('absensi.process');
    Route::get('/absensi/alpha', [AbsensiController::class, 'generateAlpha'])->name('absensi.alpha');
    Route::get('/absensi/detail/{employee}', [AbsensiController::class, 'detailRange'])->name('absensi.detailRange');
    Route::get('/absensi/datatable', [AbsensiController::class, 'datatable'])->name('absensi.datatable');
    Route::get('/absensi/export', [AbsensiController::class, 'export'])->name('absensi.export');

    // EMPLOYEE
    Route::get('/employee', [EmployeeController::class, 'index'])->name('employee.index');
    Route::get('/employee/upload', [EmployeeController::class, 'formUpload'])->name('employee.upload.form');
    Route::post('/employee/upload', [EmployeeController::class, 'upload'])->name('employee.upload');

    // TOKEN UNIT
    Route::get('/unit-form-token/{unit}/generate', [
        UnitFormTokenController::class,
        'generate'
    ]);
});
