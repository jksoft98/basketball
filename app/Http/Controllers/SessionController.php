<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\TrainingSession;
use App\Services\SessionGeneratorService;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth()->user();
        $batchIds = $user->isAdmin()
            ? Batch::pluck('id')
            : $user->batches()->pluck('batches.id');         // ← Change 1

        $query = TrainingSession::whereIn('batch_id', $batchIds)
            ->with(['batch', 'batch.coaches', 'attendances']) // ← Change 2
            ->recent();

        if ($request->filled('batch_id'))     $query->where('batch_id', $request->batch_id);
        if ($request->filled('session_type')) $query->where('session_type', $request->session_type);
        if ($request->filled('date_from'))    $query->whereDate('session_date', '>=', $request->date_from);
        if ($request->filled('date_to'))      $query->whereDate('session_date', '<=', $request->date_to);

        $sessions = $query->paginate(15)->withQueryString();
        $batches  = Batch::whereIn('id', $batchIds)->active()->get();

        if ($request->ajax() || $request->has('ajax')) {
            return response()->json([
                'sessions' => $sessions->map(fn($s) => [
                    'id'             => $s->id,
                    'date_label'     => $s->session_date->isToday() ? 'Today' : $s->session_date->format('d M Y'),
                    'time_label'     => $s->session_time ? \Carbon\Carbon::parse($s->session_time)->format('g:i A') : '',
                    'batch_name'     => $s->batch->name,
                    'coach_name'     => $s->batch->coaches->pluck('name')->join(', '), // ← Change 3
                    'session_type'   => $s->session_type,
                    'type_label'     => ucfirst($s->session_type),
                    'type_class'     => match($s->session_type) {
                        'training' => 'bg-blue-100 text-blue-700',
                        'match'    => 'bg-yellow-100 text-yellow-700',
                        'fitness'  => 'bg-green-100 text-green-700',
                        'trial'    => 'bg-purple-100 text-purple-700',
                        default    => 'bg-gray-100 text-gray-600',
                    },
                    'is_saved'       => $s->isAttendanceSaved(),
                    'is_today'       => $s->session_date->isToday(),
                    'present_count'  => $s->presentCount(),
                    'total'          => $s->batch->students()->active()  // ← Change 4
                        ->where(function ($q) use ($s) {
                            $q->whereNull('joined_at')
                              ->orWhere('joined_at', '<=', $s->session_date);
                        })->count(),
                    'attendance_url' => route('attendance.index', $s),
                    'edit_url'       => route('sessions.edit', $s),
                    'destroy_url'    => route('sessions.destroy', $s),
                ]),
                'has_more'     => $sessions->hasMorePages(),
                'current_page' => $sessions->currentPage(),
                'total'        => $sessions->total(),
            ]);
        }

        return view('sessions.index', compact('sessions', 'batches'));
    }

    public function create(Request $request)
    {
        $user    = auth()->user();
        $batches = $user->isAdmin()
            ? Batch::active()->with('coaches')->get()         // ← Change 5
            : $user->batches()->active()->with('coaches')->get();

        $selectedBatchId = $request->query('batch_id');
        return view('sessions.create', compact('batches', 'selectedBatchId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_id'     => 'required|exists:batches,id',
            'session_date' => 'required|date',
            'session_time' => 'nullable|date_format:H:i',
            'session_type' => 'required|in:training,match,fitness,trial',
            'notes'        => 'nullable|string|max:500',
        ]);

        $exists = TrainingSession::where('batch_id', $validated['batch_id'])
            ->whereDate('session_date', $validated['session_date'])
            ->where('session_type', $validated['session_type'])
            ->exists();

        if ($exists) {
            return back()->withInput()
                ->with('error', 'A session of this type already exists for this batch on that date.');
        }

        $session = TrainingSession::create([...$validated, 'created_by' => auth()->id()]);

        return redirect()->route('attendance.index', $session)
            ->with('success', 'Session created. Mark attendance below.');
    }

    public function edit(TrainingSession $session)
    {
        $this->authorizeSession($session);
        $user    = auth()->user();
        $batches = $user->isAdmin()
            ? Batch::active()->with('coaches')->get()         // ← Change 6
            : $user->batches()->active()->with('coaches')->get();

        return view('sessions.edit', compact('session', 'batches'));
    }

    public function update(Request $request, TrainingSession $session)
    {
        $this->authorizeSession($session);
        $validated = $request->validate([
            'batch_id'     => 'required|exists:batches,id',
            'session_date' => 'required|date',
            'session_time' => 'nullable|date_format:H:i',
            'session_type' => 'required|in:training,match,fitness,trial',
            'notes'        => 'nullable|string|max:500',
        ]);
        $session->update($validated);

        return redirect()->route('sessions.index')->with('success', 'Session updated.');
    }

    public function updateNotes(Request $request, TrainingSession $session)
    {
        $this->authorizeSession($session);
        $request->validate(['notes' => 'nullable|string|max:500']);
        $session->update(['notes' => $request->notes]);

        return response()->json(['success' => true, 'notes' => $session->notes]);
    }

    public function destroy(TrainingSession $session)
    {
        $this->authorizeSession($session);
        $session->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('sessions.index')->with('success', 'Session deleted.');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'from'     => 'required|date',
            'to'       => 'required|date|after_or_equal:from',
            'batch_id' => 'nullable|exists:batches,id',
        ]);

        $result = app(SessionGeneratorService::class)->generateForPeriod(
            \Carbon\Carbon::parse($request->from),
            \Carbon\Carbon::parse($request->to),
            $request->batch_id ?: null
        );

        return response()->json([
            'success' => true,
            'message' => "Generated {$result['created']} sessions, {$result['skipped']} already existed.",
            'created' => $result['created'],
            'skipped' => $result['skipped'],
        ]);
    }

    private function authorizeSession(TrainingSession $session): void
    {
        if (auth()->user()->isAdmin()) return;
        $ownBatchIds = auth()->user()->batches()->pluck('batches.id'); // ← Change 7
        if (!$ownBatchIds->contains($session->batch_id)) abort(403);
    }
}