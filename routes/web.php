<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DataTableController;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard/today-sessions', [DashboardController::class, 'todaySessionsApi'])->name('dashboard.today-sessions');
    Route::get('/api/batches/counts',            [DashboardController::class, 'batchCountsApi'])->name('dashboard.batch-counts');

    // DataTable AJAX endpoints
    Route::prefix('api/dt')->name('dt.')->group(function () {
        Route::get('sessions',                      [DataTableController::class, 'sessions'])->name('sessions');
        Route::get('batches',                       [DataTableController::class, 'batches'])->name('batches');
        Route::get('report/sessions',               [DataTableController::class, 'reportSessions'])->name('report.sessions');
        Route::get('sessions/{session}/attendance', [DataTableController::class, 'sessionAttendance'])->name('session.attendance');
        Route::get('students/{student}/history',    [DataTableController::class, 'studentHistory'])->name('student.history');
    });

    // Batches
    Route::resource('batches', BatchController::class);

    // Students
    Route::middleware([\App\Http\Middleware\EnsureFileUploadSafe::class])->group(function () {
        Route::resource('students', StudentController::class);
    });
    Route::patch('students/{student}/toggle-status', [StudentController::class, 'toggleStatus'])->name('students.toggle-status');
    Route::patch('students/{student}/injury-status', [StudentController::class, 'updateInjuryStatus'])->name('students.injury-status');

    // Sessions
    Route::resource('sessions', SessionController::class);
    Route::patch('sessions/{session}/notes', [SessionController::class, 'updateNotes'])->name('sessions.update-notes');

    // Attendance
    Route::get('sessions/{session}/attendance',
        [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('sessions/{session}/attendance/students',
        [AttendanceController::class, 'students'])->name('attendance.students');
    Route::post('sessions/{session}/attendance/single',
        [AttendanceController::class, 'saveSingle'])->name('attendance.save-single')->middleware('throttle:attendance-save');
    Route::post('sessions/{session}/attendance',
        [AttendanceController::class, 'store'])->name('attendance.store')->middleware('throttle:attendance-save');
    Route::patch('sessions/{session}/attendance/mark-all',
        [AttendanceController::class, 'markAll'])->name('attendance.mark-all');

    // Reports (admin only)
    Route::middleware([\App\Http\Middleware\RoleMiddleware::class.':admin'])->group(function () {
        Route::get('reports',                   [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/student/{student}', [ReportController::class, 'student'])->name('reports.student');
        Route::get('reports/session/{session}', [ReportController::class, 'session'])->name('reports.session');
        Route::get('reports/export',            [ReportController::class, 'export'])->name('reports.export');
    });
});

require __DIR__.'/auth.php';
