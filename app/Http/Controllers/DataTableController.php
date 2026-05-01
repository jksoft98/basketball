<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Student;
use App\Models\TrainingSession;
use App\Models\Attendance;
use Illuminate\Http\Request;

/**
 * Handles all server-side DataTable AJAX requests.
 * Every table in the app calls one of these endpoints.
 */
class DataTableController extends Controller
{
    // ── Sessions table ─────────────────────────────────────────────
    public function sessions(Request $request)
    {
        $user     = auth()->user();
        $batchIds = $user->isAdmin()
            ? Batch::pluck('id')
            : Batch::where('coach_id', $user->id)->pluck('id');

        $query = TrainingSession::whereIn('batch_id', $batchIds)
            ->with(['batch', 'batch.coach', 'attendances']);

        // Global search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('batch', fn($b) => $b->where('name', 'like', "%{$search}%"))
                  ->orWhere('session_type', 'like', "%{$search}%")
                  ->orWhere('session_date',  'like', "%{$search}%");
            });
        }

        // External column filters passed as custom params
        if ($batchId = $request->input('batch_id')) {
            $query->where('batch_id', $batchId);
        }
        if ($type = $request->input('session_type')) {
            $query->where('session_type', $type);
        }
        if ($status = $request->input('status')) {
            $sessionIds = TrainingSession::whereIn('batch_id', $batchIds)
                ->get()
                ->filter(fn($s) => $status === 'saved' ? $s->isAttendanceSaved() : !$s->isAttendanceSaved())
                ->pluck('id');
            $query->whereIn('id', $sessionIds);
        }
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('session_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('session_date', '<=', $dateTo);
        }

        // Total before ordering
        $totalFiltered = $query->count();
        $total         = TrainingSession::whereIn('batch_id', $batchIds)->count();

        // Ordering
        $orderCol  = $request->input('order.0.column', 0);
        $orderDir  = $request->input('order.0.dir', 'desc');
        $columns   = ['session_date', 'batch_id', 'session_type', null, null, null];
        if (isset($columns[$orderCol])) {
            $query->orderBy($columns[$orderCol], $orderDir);
        } else {
            $query->orderByDesc('session_date');
        }

        // Pagination
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 15);
        $sessions = $query->skip($start)->take($length)->get();

        $typeColors = [
            'training' => 'bg-blue-100 text-blue-700',
            'match'    => 'bg-yellow-100 text-yellow-700',
            'fitness'  => 'bg-green-100 text-green-700',
            'trial'    => 'bg-purple-100 text-purple-700',
        ];

        $data = $sessions->map(function ($session) use ($typeColors) {
            $saved   = $session->isAttendanceSaved();
            // Only count students who were enrolled on the session date
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
            $present = $session->presentCount();
            $pct     = $total > 0 ? round(($present / $total) * 100) : 0;
            $isToday = $session->session_date->isToday();
            $color   = $typeColors[$session->session_type] ?? 'bg-gray-100 text-gray-600';
            $rowClass = $isToday ? 'bg-orange-50' : '';

            // Date cell
            $dateHtml = '<div class="font-medium text-gray-900">'.
                ($isToday ? '<span class="text-orange-500">Today</span>' : $session->session_date->format('d M Y')).
                '</div>'.
                ($session->session_time
                    ? '<div class="text-xs text-gray-400">'.
                        \Carbon\Carbon::parse($session->session_time)->format('g:i A').
                      '</div>'
                    : '');

            // Batch cell
            $batchHtml = '<div class="font-medium text-gray-900">'.$session->batch->name.'</div>'.
                         '<div class="text-xs text-gray-400">'.$session->batch->coach->name.'</div>';

            // Type badge
            $typeHtml = '<span class="text-xs font-medium px-2.5 py-1 rounded-full '.$color.'">'.
                        ucfirst($session->session_type).'</span>';

            // Attendance cell
            if ($saved) {
                $barColor = $pct >= 80 ? 'bg-green-400' : ($pct >= 60 ? 'bg-yellow-400' : 'bg-red-400');
                $txtColor = $pct >= 80 ? 'text-green-600' : ($pct >= 60 ? 'text-yellow-600' : 'text-red-600');
                $attHtml  = '<div class="flex items-center gap-2">'.
                    '<span class="font-medium text-gray-900">'.$present.'/'.$total.'</span>'.
                    '<div class="w-12 h-1.5 bg-gray-100 rounded-full">'.
                        '<div class="h-1.5 rounded-full '.$barColor.'" style="width:'.$pct.'%"></div>'.
                    '</div>'.
                    '<span class="text-xs '.$txtColor.'">'.$pct.'%</span>'.
                    '</div>';
            } else {
                $attHtml = '<span class="text-xs text-gray-400">Not taken</span>';
            }

            // Status badge
            $statusHtml = $saved
                ? '<span class="text-xs font-medium px-2.5 py-1 rounded-full bg-green-100 text-green-700">Saved</span>'
                : '<span class="text-xs font-medium px-2.5 py-1 rounded-full bg-orange-100 text-orange-700">Pending</span>';

            // Notes (inline editable)
            $notesHtml = '<span class="session-note text-xs text-gray-400 italic cursor-pointer hover:text-gray-600" '.
                'data-session-id="'.$session->id.'" title="Double-click to edit">'.
                (e($session->notes) ?: 'Add note…').'</span>';

            // Actions
            $actionsHtml = '<div class="flex items-center gap-3 whitespace-nowrap">'.
                '<a href="'.route('attendance.index', $session).'" class="text-xs font-medium text-orange-500 hover:text-orange-700">'.
                ($saved ? 'View' : 'Take').' →</a>'.
                '<a href="'.route('sessions.edit', $session).'" class="text-xs text-gray-400 hover:text-gray-600">Edit</a>'.
                '<button onclick="dtDeleteSession('.$session->id.', this)" class="text-xs text-red-400 hover:text-red-600">Delete</button>'.
                '</div>';

            return [
                'DT_RowId'    => 'session-row-'.$session->id,
                'DT_RowClass' => $rowClass,
                'date'        => $dateHtml,
                'batch'       => $batchHtml,
                'type'        => $typeHtml,
                'attendance'  => $attHtml,
                'notes'       => $notesHtml,
                'status'      => $statusHtml,
                'actions'     => $actionsHtml,
                'sort_date'   => $session->session_date->timestamp,
                'sort_status' => $saved ? 1 : 0,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $totalFiltered,
            'data'            => $data,
        ]);
    }

    // ── Batches table ──────────────────────────────────────────────
    public function batches(Request $request)
    {
        $user = auth()->user();
        $query = $user->isAdmin()
            ? Batch::with('coach')->withCount('students')
            : Batch::where('coach_id', $user->id)->with('coach')->withCount('students');

        if ($search = $request->input('search.value')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $total         = $query->count();
        $totalFiltered = $total;

        $orderDir = $request->input('order.0.dir', 'asc');
        $orderCol = (int) $request->input('order.0.column', 0);
        $cols     = ['name', null, 'skill_level', 'students_count', null, 'is_active'];
        if (isset($cols[$orderCol]) && $cols[$orderCol]) {
            $query->orderBy($cols[$orderCol], $orderDir);
        } else {
            $query->orderBy('name');
        }

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $batches = $query->skip($start)->take($length)->get();

        $data = $batches->map(function ($batch) {
            $sessionCount = $batch->sessions()->count();
            $levelColor   = match($batch->skill_level) {
                'beginner'     => 'bg-green-100 text-green-700',
                'intermediate' => 'bg-blue-100 text-blue-700',
                default        => 'bg-purple-100 text-purple-700',
            };

            $nameHtml  = '<div class="font-medium text-gray-900">'.e($batch->name).'</div>'.
                ($batch->description ? '<div class="text-xs text-gray-400">'.e($batch->description).'</div>' : '');
            $levelHtml = '<span class="text-xs font-medium px-2.5 py-1 rounded-full '.$levelColor.'">'.ucfirst($batch->skill_level).'</span>';
            $statusHtml = $batch->is_active
                ? '<span class="text-xs font-medium px-2.5 py-1 rounded-full bg-green-100 text-green-700">Active</span>'
                : '<span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-500">Inactive</span>';

            $actionsHtml = '<div class="flex items-center gap-2 whitespace-nowrap">'.
                '<a href="'.route('sessions.create', ['batch_id' => $batch->id]).'" class="text-xs font-medium text-orange-500 hover:text-orange-700">+ Session</a>'.
                '<a href="'.route('students.index', ['batch_id' => $batch->id]).'" class="text-xs text-gray-400 hover:text-gray-600">Students</a>'.
                (auth()->user()->isAdmin()
                    ? '<a href="'.route('batches.edit', $batch).'" class="text-xs text-gray-400 hover:text-gray-600">Edit</a>'
                    : '').
                '</div>';

            return [
                'name'     => $nameHtml,
                'coach'    => e($batch->coach->name ?? '—'),
                'level'    => $levelHtml,
                'students' => '<span class="font-semibold text-gray-900">'.$batch->students_count.'</span>',
                'sessions' => '<span class="font-semibold text-gray-900">'.$sessionCount.'</span>',
                'status'   => $statusHtml,
                'actions'  => $actionsHtml,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $totalFiltered,
            'data'            => $data,
        ]);
    }

    // ── Report: session breakdown table ───────────────────────────
    public function reportSessions(Request $request)
    {
        $user     = auth()->user();
        $batchIds = $user->isAdmin()
            ? Batch::pluck('id')
            : Batch::where('coach_id', $user->id)->pluck('id');

        $month = $request->input('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $query = TrainingSession::whereIn('batch_id', $batchIds)
            ->whereYear('session_date', $year)
            ->whereMonth('session_date', $mon)
            ->with(['batch', 'attendances']);

        if ($batchId = $request->input('batch_id')) {
            $query->where('batch_id', $batchId);
        }

        if ($search = $request->input('search.value')) {
            $query->whereHas('batch', fn($b) => $b->where('name', 'like', "%{$search}%"));
        }

        $total         = $query->count();
        $totalFiltered = $total;
        $orderDir      = $request->input('order.0.dir', 'desc');
        $query->orderBy('session_date', $orderDir);

        $start    = (int) $request->input('start', 0);
        $length   = (int) $request->input('length', 15);
        $sessions = $query->skip($start)->take($length)->get();

        $typeColors = [
            'training' => 'bg-blue-100 text-blue-700',
            'match'    => 'bg-yellow-100 text-yellow-700',
            'fitness'  => 'bg-green-100 text-green-700',
            'trial'    => 'bg-purple-100 text-purple-700',
        ];

        $data = $sessions->map(function ($session) use ($typeColors) {
            $total   = $session->batch->students()->active()->count();
            $present = $session->presentCount();
            $pct     = $total > 0 ? round(($present / $total) * 100) : 0;
            $color   = $typeColors[$session->session_type] ?? 'bg-gray-100 text-gray-600';
            $barColor = $pct >= 80 ? 'bg-green-400' : ($pct >= 60 ? 'bg-yellow-400' : 'bg-red-400');
            $txtColor = $pct >= 80 ? 'text-green-600' : ($pct >= 60 ? 'text-yellow-600' : 'text-red-600');

            $attHtml = '<div class="flex items-center gap-2">'.
                '<span class="font-medium text-gray-900">'.$present.'/'.$total.'</span>'.
                '<div class="w-12 h-1.5 bg-gray-100 rounded-full">'.
                    '<div class="h-1.5 rounded-full '.$barColor.'" style="width:'.$pct.'%"></div>'.
                '</div>'.
                '<span class="text-xs font-semibold '.$txtColor.'">'.$pct.'%</span>'.
                '</div>';

            return [
                'date'    => '<div class="font-medium text-gray-900">'.$session->session_date->format('d M Y').'</div>',
                'batch'   => '<span class="font-medium text-gray-900">'.e($session->batch->name).'</span>',
                'type'    => '<span class="text-xs font-medium px-2.5 py-1 rounded-full '.$color.'">'.ucfirst($session->session_type).'</span>',
                'attendance' => $attHtml,
                'action'  => '<a href="'.route('reports.session', $session).'" class="text-xs font-medium text-orange-500 hover:text-orange-700">View →</a>',
                'sort_pct' => $pct,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $totalFiltered,
            'data'            => $data,
        ]);
    }

    // ── Report: attendance for one session ─────────────────────────
    public function sessionAttendance(Request $request, TrainingSession $session)
    {
        $query = Student::withTrashed()
        ->where('batch_id', $session->batch_id)
        ->where(function ($q) use ($session) {
            $q->whereNull('joined_at')
            ->orWhere('joined_at', '<=', $session->session_date);
        })
        ->where(function ($q) use ($session) {
            $q->whereNull('deleted_at')
            ->orWhere('deleted_at', '>', $session->session_date);
        });

        if ($search = $request->input('search.value')) {
            $query->where('full_name', 'like', "%{$search}%");
        }

        $total         = $query->count();
        $totalFiltered = $total;
        $orderDir      = $request->input('order.0.dir', 'asc');
        $query->orderBy('full_name', $orderDir);

        $start    = (int) $request->input('start', 0);
        $length   = (int) $request->input('length', 25);
        $students = $query->skip($start)->take($length)->get();

        $attendanceMap = Attendance::where('session_id', $session->id)
            ->pluck('status', 'student_id');
        $noteMap = Attendance::where('session_id', $session->id)
            ->pluck('note', 'student_id');

        $data = $students->map(function ($student) use ($attendanceMap, $noteMap, $session) {
            $status = $attendanceMap[$student->id] ?? 'absent';
            $note   = $noteMap[$student->id] ?? '';
            $pct    = $student->attendancePercentage();

            $statusColor = match($status) {
                'present' => 'bg-green-100 text-green-700',
                'late'    => 'bg-yellow-100 text-yellow-700',
                default   => 'bg-red-100 text-red-600',
            };
            $barColor = $pct >= 80 ? 'bg-green-400' : ($pct >= 60 ? 'bg-yellow-400' : 'bg-red-400');
            $txtColor = $pct >= 80 ? 'text-green-600' : ($pct >= 60 ? 'text-yellow-600' : 'text-red-600');

            $photoHtml = '<div class="flex items-center gap-3">'.
                '<div class="relative flex-shrink-0">'.
                    '<img src="'.e(asset('storage/' . $student->photo_thumb_path)).'" alt="'.e($student->full_name).'" '.
                        'class="w-9 h-9 object-cover bg-gray-100" '.
                        'loading="lazy">'.
                    ($student->isAtRisk()
                        ? '<span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>'
                        : '').
                '</div>'.
                '<div>'.
                    '<p class="font-medium text-gray-900 text-sm">'.e($student->full_name).'</p>'.
                    ($student->jersey_number ? '<p class="text-xs text-gray-400">#'.$student->jersey_number.'</p>' : '').
                '</div>'.
                '</div>';

            $pctHtml = '<div class="flex items-center gap-2">'.
                '<div class="w-14 h-1.5 bg-gray-100 rounded-full">'.
                    '<div class="h-1.5 rounded-full '.$barColor.'" style="width:'.$pct.'%"></div>'.
                '</div>'.
                '<span class="text-xs font-semibold '.$txtColor.'">'.$pct.'%</span>'.
                '</div>';

            return [
                'photo'     => $photoHtml,
                'jersey'    => e($student->jersey_number ? '#'.$student->jersey_number : '—'),
                'position'  => e($student->position ? ucfirst($student->position) : '—'),
                'status'    => '<span class="text-xs font-medium px-2.5 py-1 rounded-full '.$statusColor.'">'.ucfirst($status).'</span>',
                'overall'   => $pctHtml,
                'note'      => '<span class="text-xs text-gray-400 italic">'.e($note ?: '—').'</span>',
                'sort_name' => $student->full_name,
                'sort_status' => $status,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $totalFiltered,
            'data'            => $data,
        ]);
    }

    // ── Report: student session history ────────────────────────────
    public function studentHistory(Request $request, Student $student)
    {
        $month = $request->input('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $query = TrainingSession::where('batch_id', $student->batch_id)
        ->whereYear('session_date', $year)
        ->whereMonth('session_date', $mon)
        ->when($student->joined_at, fn($q) =>
            $q->whereDate('session_date', '>=', $student->joined_at)
        );

        if ($search = $request->input('search.value')) {
            $query->where('session_type', 'like', "%{$search}%");
        }

        $total    = $query->count();
        $orderDir = $request->input('order.0.dir', 'desc');
        $query->orderBy('session_date', $orderDir);

        $start    = (int) $request->input('start', 0);
        $length   = (int) $request->input('length', 25);
        $sessions = $query->skip($start)->take($length)->get();

        $attendanceMap = Attendance::where('student_id', $student->id)
            ->whereIn('session_id', $sessions->pluck('id'))
            ->get()->keyBy('session_id');

        $typeColors = [
            'training' => 'bg-blue-100 text-blue-700',
            'match'    => 'bg-yellow-100 text-yellow-700',
            'fitness'  => 'bg-green-100 text-green-700',
            'trial'    => 'bg-purple-100 text-purple-700',
        ];

        $data = $sessions->map(function ($session) use ($attendanceMap, $typeColors) {
            $record = $attendanceMap[$session->id] ?? null;
            $status = $record?->status ?? 'absent';
            $color  = $typeColors[$session->session_type] ?? 'bg-gray-100 text-gray-600';
            $statusColor = match($status) {
                'present' => 'bg-green-100 text-green-700',
                'late'    => 'bg-yellow-100 text-yellow-700',
                default   => 'bg-red-100 text-red-600',
            };

            return [
                'date'   => '<div class="font-medium text-gray-900">'.$session->session_date->format('d M Y').'</div>'.
                            '<div class="text-xs text-gray-400">'.$session->session_date->format('l').'</div>',
                'type'   => '<span class="text-xs font-medium px-2.5 py-1 rounded-full '.$color.'">'.ucfirst($session->session_type).'</span>',
                'time'   => $session->session_time
                    ? \Carbon\Carbon::parse($session->session_time)->format('g:i A')
                    : '—',
                'status' => '<span class="text-xs font-medium px-2.5 py-1 rounded-full '.$statusColor.'">'.ucfirst($status).'</span>',
                'note'   => '<span class="text-xs text-gray-400 italic">'.e($record?->note ?? '—').'</span>',
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }
}
