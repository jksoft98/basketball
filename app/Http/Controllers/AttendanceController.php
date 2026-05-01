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
        $totalStudents = Student::withTrashed()
        ->where('batch_id', $session->batch_id)
        ->where(function ($q) use ($session) {
            $q->whereNull('joined_at')
            ->orWhere('joined_at', '<=', $session->session_date);
        })
        ->where(function ($q) use ($session) {
            $q->whereNull('deleted_at')
            ->orWhere('deleted_at', '>', $session->session_date);
        })
        ->count();
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

        $sessionDate = $session->session_date;

        // Cache the student list (photo URLs, names, etc.) — invalidated on student change
        $students = Cache::remember(
           "batch_students_{$session->batch_id}_{$sessionDate->toDateString()}",
            now()->addMinutes(10),
                fn() => Student::withTrashed()->where('batch_id', $session->batch_id)
                ->active()
                ->where(function ($query) use ($sessionDate) {
                    $query->whereNull('joined_at')
                        ->orWhere('joined_at', '<=', $sessionDate);
                })
                ->where(function ($q) use ($sessionDate) {
                    // Was NOT yet deleted on session date
                    $q->whereNull('deleted_at')
                    ->orWhere('deleted_at', '>', $sessionDate);
                })
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

        // Verify student was enrolled on session date
        $student = Student::find($request->student_id);
        if ($student->joined_at && $student->joined_at->gt($session->session_date)) {
            return response()->json([
                'success' => false,
                'message' => 'Student was not enrolled on this session date.',
            ], 422);
        }

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

        // Only allow saving attendance for students enrolled on session date
        $sessionDate        = $session->session_date;
        $eligibleStudentIds = Student::where('batch_id', $session->batch_id)
            ->active()
            ->where(function ($q) use ($sessionDate) {
                $q->whereNull('joined_at')
                ->orWhere('joined_at', '<=', $sessionDate);
            })
            ->pluck('id')
            ->toArray();

        DB::transaction(function () use ($request, $session) {
            foreach ($request->attendance as $record) {
                // Skip students not enrolled on this session date
                if (!in_array($record['id'], $eligibleStudentIds)) continue;
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
        ->active()
        ->where(function ($query) use ($session) {
            $query->whereNull('joined_at')
                ->orWhere('joined_at', '<=', $session->session_date);
        })
        ->pluck('id');

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
        $total = Student::withTrashed()
            ->where('batch_id', $session->batch_id)
            ->where(function ($q) use ($session) {
                $q->whereNull('joined_at')
                ->orWhere('joined_at', '<=', $session->session_date);
            })
            ->where(function ($q) use ($session) {
                $q->whereNull('deleted_at')
                ->orWhere('deleted_at', '>', $session->session_date);
            })
            ->count();
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
