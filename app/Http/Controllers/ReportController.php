<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Student;
use App\Models\TrainingSession;
use App\Models\Attendance;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth()->user();
        $batchIds = $user->isAdmin()                             // ← Change 1
            ? Batch::pluck('id')
            : $user->batches()->pluck('batches.id');

        $batches  = Batch::whereIn('id', $batchIds)->active()->get();
        $month    = $request->input('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);
        $selectedBatchId = $request->input('batch_id');

        $sessionsQuery = TrainingSession::whereIn('batch_id', $batchIds)
            ->whereYear('session_date', $year)
            ->whereMonth('session_date', $mon)
            ->with(['batch', 'attendances']);
        if ($selectedBatchId) $sessionsQuery->where('batch_id', $selectedBatchId);
        $monthlySessions = $sessionsQuery->latest('session_date')->get();

        $batchStats = Batch::whereIn('id', $batchIds)->active()->withCount('students')->get()
            ->map(function ($batch) use ($year, $mon) {
                $sessionIds = $batch->sessions()
                    ->whereYear('session_date', $year)
                    ->whereMonth('session_date', $mon)
                    ->pluck('id');
                $total   = Attendance::whereIn('session_id', $sessionIds)->count();
                $present = Attendance::whereIn('session_id', $sessionIds)
                    ->whereIn('status', ['present', 'late'])->count();
                $batch->session_count   = $sessionIds->count();
                $batch->attendance_rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
                return $batch;
            });

        $atRiskStudents = Student::whereIn('batch_id', $batchIds)
            ->active()->with(['batch', 'attendances'])->get()
            ->filter(fn($s) => $s->isAtRisk())
            ->sortBy(fn($s) => $s->attendancePercentage())
            ->values();

        $topStudents = Student::whereIn('batch_id', $batchIds)
            ->active()->with('attendances')->get()
            ->filter(fn($s) => $s->attendances->count() >= 3)
            ->sortByDesc(fn($s) => $s->attendancePercentage())
            ->take(5)->values();

        $trend = collect(range(5, 0))->map(function ($monthsAgo) use ($batchIds) {
            $date       = now()->subMonths($monthsAgo);
            $sessionIds = TrainingSession::whereIn('batch_id', $batchIds)
                ->whereYear('session_date', $date->year)
                ->whereMonth('session_date', $date->month)
                ->pluck('id');
            $total   = Attendance::whereIn('session_id', $sessionIds)->count();
            $present = Attendance::whereIn('session_id', $sessionIds)
                ->whereIn('status', ['present', 'late'])->count();
            return [
                'label'    => $date->format('M'),
                'rate'     => $total > 0 ? round(($present / $total) * 100) : 0,
                'sessions' => $sessionIds->count(),
            ];
        });

        if ($request->ajax() || $request->has('ajax')) {
            return response()->json([
                'avg_rate'         => round($batchStats->avg('attendance_rate')),
                'total_sessions'   => $monthlySessions->count(),
                'at_risk_count'    => $atRiskStudents->count(),
                'trend'            => $trend->values(),
                'at_risk_students' => $atRiskStudents->map(fn($s) => [
                    'id'        => $s->id,
                    'name'      => $s->full_name,
                    'thumb_url' => $s->thumb_url,
                    'batch'     => $s->batch->name,
                    'pct'       => $s->attendancePercentage(),
                    'url'       => route('reports.student', $s),
                ]),
            ]);
        }

        return view('reports.index', compact(
            'batches', 'batchStats', 'monthlySessions',
            'atRiskStudents', 'topStudents', 'trend', 'month', 'selectedBatchId'
        ));
    }

    public function student(Student $student, Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $sessions = TrainingSession::where('batch_id', $student->batch_id)
            ->whereYear('session_date', $year)
            ->whereMonth('session_date', $mon)
            ->when($student->joined_at, fn($q) =>
                $q->whereDate('session_date', '>=', $student->joined_at)
            )
            ->latest('session_date')->get();

        $attendanceMap = Attendance::where('student_id', $student->id)
            ->whereIn('session_id', $sessions->pluck('id'))
            ->get()->keyBy('session_id');

        // ← Change 3 — respect joined_at and deleted_at in allTime stats
        $baseQuery = $student->attendances()
            ->when($student->joined_at, fn($q) =>
                $q->whereHas('session', fn($s) =>
                    $s->whereDate('session_date', '>=', $student->joined_at)
                )
            )
            ->when($student->deleted_at, fn($q) =>
                $q->whereHas('session', fn($s) =>
                    $s->whereDate('session_date', '<=', $student->deleted_at)
                )
            );

        $allTime = [
            'total'   => (clone $baseQuery)->count(),
            'present' => (clone $baseQuery)->where('status', 'present')->count(),
            'late'    => (clone $baseQuery)->where('status', 'late')->count(),
            'absent'  => (clone $baseQuery)->where('status', 'absent')->count(),
            'pct'     => $student->attendancePercentage(),
        ];

        $streak = $this->calculateStreak($student);

        return view('reports.student', compact(
            'student', 'sessions', 'attendanceMap', 'allTime', 'streak', 'month'
        ));
    }

    public function session(TrainingSession $session)
    {
        $session->load(['batch', 'batch.coaches', 'attendances.student']); // ← Change 2

        $students = Student::withTrashed()
            ->where('batch_id', $session->batch_id)
            ->where(function ($q) use ($session) {
                $q->whereNull('joined_at')
                  ->orWhere('joined_at', '<=', $session->session_date);
            })
            ->where(function ($q) use ($session) {
                $q->whereNull('deleted_at')
                  ->orWhere('deleted_at', '>', $session->session_date);
            })
            ->get();

        $attendanceMap = $session->attendances->keyBy('student_id');

        $summary = [
            'present' => $session->attendances->where('status', 'present')->count(),
            'late'    => $session->attendances->where('status', 'late')->count(),
            'absent'  => $session->attendances->where('status', 'absent')->count(),
            'total'   => $students->count(),
        ];

        return view('reports.session', compact('session', 'students', 'attendanceMap', 'summary'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'month'    => 'required|date_format:Y-m',
        ]);

        [$year, $mon] = explode('-', $request->month);
        $batch    = Batch::findOrFail($request->batch_id);
        $sessions = TrainingSession::where('batch_id', $batch->id)
            ->whereYear('session_date', $year)
            ->whereMonth('session_date', $mon)
            ->orderBy('session_date')->get();

        $endOfMonth   = \Carbon\Carbon::createFromFormat('Y-m', $request->month)->endOfMonth();
        $startOfMonth = \Carbon\Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();

        $students = Student::withTrashed()
            ->where('batch_id', $batch->id)
            ->where(function ($q) use ($endOfMonth) {
                $q->whereNull('joined_at')
                  ->orWhere('joined_at', '<=', $endOfMonth);
            })
            ->where(function ($q) use ($startOfMonth) {
                $q->whereNull('deleted_at')
                  ->orWhere('deleted_at', '>=', $startOfMonth);
            })
            ->orderBy('full_name')
            ->get();

        $filename = 'attendance_' . str_replace([' ', '/'], '_', $batch->name) . "_{$request->month}.csv";

        return response()->stream(function () use ($sessions, $students) {
            $handle = fopen('php://output', 'w');
            $header = ['Student Name', 'Jersey #', 'Position'];
            foreach ($sessions as $s) {
                $header[] = $s->session_date->format('d M') . ' (' . ucfirst($s->session_type) . ')';
            }
            $header[] = 'Total Present';
            $header[] = 'Total Sessions';
            $header[] = 'Attendance %';
            fputcsv($handle, $header);

            foreach ($students as $student) {
                $row          = [$student->full_name, $student->jersey_number ?? '-', $student->position ?? '-'];
                $presentCount = 0;
                foreach ($sessions as $session) {
                    $record = Attendance::where('session_id', $session->id)
                        ->where('student_id', $student->id)->first();
                    $status = $record?->status ?? 'absent';
                    $row[]  = match($status) { 'present' => 'P', 'late' => 'L', default => 'A' };
                    if (in_array($status, ['present', 'late'])) $presentCount++;
                }
                $total = $sessions->count();
                $row[] = $presentCount;
                $row[] = $total;
                $row[] = ($total > 0 ? round(($presentCount / $total) * 100, 1) : 0) . '%';
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function calculateStreak(Student $student): int
    {
        $streak = 0;
        foreach (Attendance::where('student_id', $student->id)->orderByDesc('created_at')->get() as $r) {
            if (in_array($r->status, ['present', 'late'])) $streak++;
            else break;
        }
        return $streak;
    }
}