<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\UnitFormTokenController;
use App\Http\Controllers\PublicFormIzinController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SchoolCalendarController;
use App\Http\Controllers\AttendancePermissionApprovalController;
use App\Http\Controllers\EmployeeOrganizationController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WorkCalendarController;
use App\Http\Controllers\WorkScheduleController;
use App\Http\Controllers\WorkScheduleAssignmentController;
use App\Http\Controllers\EmployeeWorkScheduleController;

// Route::get('/', function () {
//     return view('welcome');
// });

// LOGIN
Route::get('/', function () {
    return view('pages.auth.login');
})->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->name('login.process');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// TOKEN FORM
Route::get('/form-izin/{token}', [PublicFormIzinController::class, 'create'])->name('formIzin');
Route::post('/formIzinStore/{token}', [AbsensiController::class, 'formIzinStore'])->name('formIzinStore');

Route::prefix('admin')
    ->middleware('auth')
    ->group(function () {
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
        Route::get('/employee/datatable', [EmployeeController::class, 'datatable'])->name('employee.datatable');
        Route::get('/employee', [EmployeeController::class, 'index'])->name('employee.index');
        Route::get('/employee/upload', [EmployeeController::class, 'formUpload'])->name('employee.upload.form');
        Route::post('/employee/upload', [EmployeeController::class, 'upload'])->name('employee.upload');

        // TOKEN
        Route::get('/unit-form-token', [UnitFormTokenController::class, 'index'])->name('unitFormToken.index');
        Route::post('/unit-form-token/{unit}/generate', [UnitFormTokenController::class, 'generate'])->name('unitFormToken.generate');

        // KALDIK
        Route::resource('school-calendar', SchoolCalendarController::class)->except(['show']);

        // ATTENDANCE PERMISSIONS
        Route::get('/attendance-permissions', [AttendancePermissionApprovalController::class, 'index'])->name('attendancePermission.index');
        Route::post('/attendance-permissions/{permission}/approve', [AttendancePermissionApprovalController::class, 'approve'])->name('attendancePermission.approve');
        Route::post('/attendance-permissions/{permission}/reject', [AttendancePermissionApprovalController::class, 'reject'])->name('attendancePermission.reject');

        // ORGANISASI KEPEGAWAIAN
        Route::get('/employee-organization', [EmployeeOrganizationController::class, 'index'])->name('employeeOrganization.index');
        Route::post('/employee-organization/bulk-update', [EmployeeOrganizationController::class, 'bulkUpdate'])->name('employeeOrganization.bulkUpdate');
        Route::get('/employee-organization/{employee}/edit', [EmployeeOrganizationController::class, 'edit'])->name('employeeOrganization.edit');
        Route::put('/employee-organization/{employee}', [EmployeeOrganizationController::class, 'update'])->name('employeeOrganization.update');

        // MASTER BIDANG / DIVISI
        Route::get('/divisions', [DivisionController::class, 'index'])->name('division.index');
        Route::post('/divisions', [DivisionController::class, 'store'])->name('division.store');
        Route::put('/divisions/{division}', [DivisionController::class, 'update'])->name('division.update');
        Route::delete('/divisions/{division}', [DivisionController::class, 'destroy'])->name('division.destroy');

        // USER MANAGEMENT
        Route::get('/users', [UserManagementController::class, 'index'])->name('userManagement.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('userManagement.store');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('userManagement.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('userManagement.destroy');

        // WORK CALENDAR / KALDIK AKTIF
        Route::get('/work-calendar', [WorkCalendarController::class, 'index'])->name('workCalendar.index');
        Route::post('/work-calendar', [WorkCalendarController::class, 'store'])->name('workCalendar.store');
        Route::put('/work-calendar/{workCalendar}', [WorkCalendarController::class, 'update'])->name('workCalendar.update');
        Route::delete('/work-calendar/{workCalendar}', [WorkCalendarController::class, 'destroy'])->name('workCalendar.destroy');

        // TEMPLATE JADWAL KERJA
        Route::get('/work-schedules/create', [WorkScheduleController::class, 'create'])->name('workSchedule.create');
        Route::post('/work-schedules', [WorkScheduleController::class, 'store'])->name('workSchedule.store');
        Route::get('/work-schedules', [WorkScheduleController::class, 'index'])->name('workSchedule.index');
        Route::get('/work-schedules/{workSchedule}/edit', [WorkScheduleController::class, 'edit'])->name('workSchedule.edit');
        Route::put('/work-schedules/{workSchedule}', [WorkScheduleController::class, 'update'])->name('workSchedule.update');

        // PLOTTING JADWAL PEGAWAI
        Route::get('/work-schedule-assignment', [WorkScheduleAssignmentController::class, 'index'])->name('workScheduleAssignment.index');
        Route::post('/work-schedule-assignment/bulk-update', [WorkScheduleAssignmentController::class, 'bulkUpdate'])->name('workScheduleAssignment.bulkUpdate');
        Route::post('/work-schedule-assignment/clear', [WorkScheduleAssignmentController::class, 'clear'])->name('workScheduleAssignment.clear');

        // JADWAL KHUSUS PEGAWAI
        Route::get('/employee-work-schedules', [EmployeeWorkScheduleController::class, 'index'])->name('employeeWorkSchedule.index');
        Route::get('/employee-work-schedules/create', [EmployeeWorkScheduleController::class, 'create'])->name('employeeWorkSchedule.create');
        Route::post('/employee-work-schedules', [EmployeeWorkScheduleController::class, 'store'])->name('employeeWorkSchedule.store');
        Route::get('/employee-work-schedules/{employeeWorkSchedule}/edit', [EmployeeWorkScheduleController::class, 'edit'])->name('employeeWorkSchedule.edit');
        Route::put('/employee-work-schedules/{employeeWorkSchedule}', [EmployeeWorkScheduleController::class, 'update'])->name('employeeWorkSchedule.update');
        Route::delete('/employee-work-schedules/{employeeWorkSchedule}', [EmployeeWorkScheduleController::class, 'destroy'])->name('employeeWorkSchedule.destroy');

    });
