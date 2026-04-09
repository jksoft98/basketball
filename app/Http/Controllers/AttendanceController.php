<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Student;
use App\Models\TrainingSession;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    // ── Page load ─────────────────────────────────────────────────
    public function index(TrainingSession $session)
    {
        $this->authorizeSession($session);
        $session->load(['batch', 'batch.coach']);

        // Count existing attendance for summary bar initial state
        $existing      = Attendance::where('session_id', $session->id)->get();
        $presentCount  = $existing->where('status', 'present')->count();
        $lateCount     = $existing->where('status', 'late')->count();
        $absentCount   = $existing->where('status', 'absent')->count();
        $totalStudents = Student::where('batch_id', $session->batch_id)->active()->count();
        $isSaved       = $session->isAttendanceSaved();

        return view('attendance.index', compact(
            'session', 'totalStudents',
            'presentCount', 'lateCount', 'absentCount', 'isSaved',
        ));
    }

    // ── AJAX: load all students + existing attendance ─────────────
    public function students(TrainingSession $session)
    {
        $this->authorizeSession($session);

        // Cache the student list (photo URLs, names, etc.) — invalidated on student change
        $students = Cache::remember(
            "batch_students_{$session->batch_id}",
            now()->addMinutes(10),
            fn() => Student::where('batch_id', $session->batch_id)
                ->active()
                ->orderBy('full_name')
                ->get()
                ->map(fn($s) => [
                    'id'        => $s->id,
                    'name'      => $s->full_name,
                    'jersey'    => $s->jersey_number,
                    'position'  => $s->position,
                    'injury'    => $s->injury_status,
                    'thumb_url'  => $s->photo_thumb_path
                                    ? asset('storage/' . $s->photo_thumb_path)
                                    : asset('images/default-avatar.png'),
                    'is_at_risk'=> $s->isAtRisk(),
                ])
        );

        // Existing attendance for THIS session — never cached (always fresh)
        $existing = Attendance::where('session_id', $session->id)
            ->pluck('status', 'student_id');

        return response()->json([
            'students'   => $students,
            'attendance' => $existing,
            'total'      => $students->count(),
        ]);
    }

    // ── AJAX: save single student tap (auto-save on every tap) ─────
    public function saveSingle(Request $request, TrainingSession $session)
    {
        $this->authorizeSession($session);

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'status'     => 'required|in:present,absent,late',
            'note'       => 'nullable|string|max:200',
        ]);

        $record = Attendance::updateOrCreate(
            ['session_id' => $session->id, 'student_id' => $request->student_id],
            ['status' => $request->status, 'note' => $request->note, 'marked_at' => now()]
        );

        // Return updated summary counts
        return response()->json([
            'success' => true,
            'summary' => $this->getSummary($session),
        ]);
    }

    // ── AJAX: bulk save all (used by Save button) ──────────────────
    public function store(Request $request, TrainingSession $session)
    {
        $this->authorizeSession($session);

        $request->validate([
            'attendance'          => 'required|array',
            'attendance.*.id'     => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,absent,late',
            'attendance.*.note'   => 'nullable|string|max:200',
        ]);

        DB::transaction(function () use ($request, $session) {
            foreach ($request->attendance as $record) {
                Attendance::updateOrCreate(
                    ['session_id' => $session->id, 'student_id' => $record['id']],
                    [
                        'status'    => $record['status'],
                        'note'      => $record['note'] ?? null,
                        'marked_at' => now(),
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Attendance saved successfully.',
            'summary' => $this->getSummary($session),
        ]);
    }

    // ── AJAX: mark all present or absent ──────────────────────────
    public function markAll(Request $request, TrainingSession $session)
    {
        $this->authorizeSession($session);
        $request->validate(['status' => 'required|in:present,absent']);

        $studentIds = Student::where('batch_id', $session->batch_id)
            ->active()->pluck('id');

        DB::transaction(function () use ($studentIds, $session, $request) {
            foreach ($studentIds as $studentId) {
                Attendance::updateOrCreate(
                    ['session_id' => $session->id, 'student_id' => $studentId],
                    ['status' => $request->status, 'marked_at' => now()]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => "All students marked as {$request->status}.",
            'summary' => $this->getSummary($session),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────
    private function getSummary(TrainingSession $session): array
    {
        $records = Attendance::where('session_id', $session->id)->get();
        $total   = Student::where('batch_id', $session->batch_id)->active()->count();
        $present = $records->where('status', 'present')->count();
        $late    = $records->where('status', 'late')->count();
        $absent  = $records->where('status', 'absent')->count();

        return [
            'present' => $present,
            'late'    => $late,
            'absent'  => $absent,
            'total'   => $total,
            'pct'     => $total > 0 ? round((($present + $late) / $total) * 100) : 0,
        ];
    }

    private function authorizeSession(TrainingSession $session): void
    {
        if (auth()->user()->isAdmin()) return;
        $ownBatchIds = Batch::where('coach_id', auth()->id())->pluck('id');
        if (!$ownBatchIds->contains($session->batch_id)) abort(403);
    }
}
