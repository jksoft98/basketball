<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Student;
use App\Models\TrainingSession;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $batchIds = $this->getBatchIds($user);

        $batches   = Batch::whereIn('id', $batchIds)->active()->withCount('students')->get();
        $batchIds  = $batches->pluck('id');

        $todaySessions = TrainingSession::whereIn('batch_id', $batchIds)
            ->today()->with(['batch', 'attendances'])->get();

        $totalStudents  = Student::whereIn('batch_id', $batchIds)->active()->count();
        $totalSessions  = TrainingSession::whereIn('batch_id', $batchIds)->count();

        $recentSessions = TrainingSession::whereIn('batch_id', $batchIds)
            ->recent()->with('batch')->limit(5)->get();

        $atRiskStudents = Student::whereIn('batch_id', $batchIds)
            ->active()->with('attendances')->get()
            ->filter(fn($s) => $s->isAtRisk())->take(5);

        return view('dashboard', compact(
            'batches', 'todaySessions', 'totalStudents',
            'totalSessions', 'recentSessions', 'atRiskStudents',
        ));
    }

    // ── AJAX: live refresh today's sessions ───────────────────────
    public function todaySessionsApi()
    {
        $user     = auth()->user();
        $batchIds = $this->getBatchIds($user);

        $sessions = TrainingSession::whereIn('batch_id', $batchIds)
            ->today()->with(['batch', 'attendances'])->get()
            ->map(fn($s) => [
                'id'            => $s->id,
                'batch_name'    => $s->batch->name,
                'session_type'  => ucfirst($s->session_type),
                'is_saved'      => $s->isAttendanceSaved(),
                'present_count' => $s->presentCount(),
                'total'         => $s->batch->students()->active()->count(),
                'attendance_url'=> route('attendance.index', $s),
            ]);

        return response()->json(['sessions' => $sessions]);
    }

    // ── AJAX: batch student counts for live badge update ──────────
    public function batchCountsApi()
    {
        $user     = auth()->user();
        $batchIds = $this->getBatchIds($user);

        $batches = Batch::whereIn('id', $batchIds)->active()
            ->withCount(['students' => fn($q) => $q->where('is_active', true)])
            ->get()
            ->map(fn($b) => [
                'id'    => $b->id,
                'name'  => $b->name,
                'count' => $b->students_count,
            ]);

        return response()->json(['batches' => $batches]);
    }

    private function getBatchIds($user)
    {
        return $user->isAdmin()
            ? Batch::pluck('id')
            : Batch::where('coach_id', $user->id)->pluck('id');
    }
}
