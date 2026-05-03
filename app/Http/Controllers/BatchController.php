<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchSchedule;
use App\Models\User;
use App\Services\SessionGeneratorService;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $batches = $user->isAdmin()                                          // ← Change 1
            ? Batch::with(['coaches', 'activeSchedules'])->withCount('students')->latest()->get()
            : $user->batches()->with(['coaches', 'activeSchedules'])->withCount('students')->latest()->get();

        return view('batches.index', compact('batches'));
    }

    public function create()
    {
        $this->requireAdmin();
        $coaches = User::where('role', 'coach')->orderBy('name')->get();
        return view('batches.create', compact('coaches'));
    }

    public function store(Request $request)
    {
        $this->requireAdmin();

        $validated = $request->validate([
            'name'                     => 'required|string|max:100',
            'description'              => 'nullable|string|max:255',
            'coach_ids'                => 'required|array|min:1',
            'coach_ids.*'              => 'exists:users,id',
            'skill_level'              => 'required|in:beginner,intermediate,advanced',
            'schedules'                => 'nullable|array',
            'schedules.*.day_of_week'  => 'required|integer|between:0,6',
            'schedules.*.session_time' => 'required|date_format:H:i',
            'schedules.*.session_type' => 'required|in:training,match,fitness,trial',
        ]);

        $batch = Batch::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'skill_level' => $validated['skill_level'],
        ]);

        // Attach selected coaches
        if (!empty($validated['coach_ids'])) {
            $batch->coaches()->sync($validated['coach_ids']);
        }

        // Save schedules
        if (!empty($validated['schedules'])) {
            $this->saveSchedules($batch, $validated['schedules']);
        }

        return redirect()->route('batches.index')
            ->with('success', "Batch \"{$batch->name}\" created successfully.");
    }

    public function edit(Batch $batch)
    {
        $this->requireAdmin();
        $coaches          = User::where('role', 'coach')->orderBy('name')->get();
        $schedules        = $batch->schedules()->orderBy('day_of_week')->get();
        $assignedCoachIds = $batch->coaches()->pluck('users.id')->toArray(); // ← Change 3
        return view('batches.edit', compact('batch', 'coaches', 'schedules', 'assignedCoachIds'));
    }

    public function update(Request $request, Batch $batch)
    {
        $this->requireAdmin();

        $validated = $request->validate([
            'name'                     => 'required|string|max:100',
            'description'              => 'nullable|string|max:255',
            'coach_ids'                => 'required|array|min:1',
            'coach_ids.*'              => 'exists:users,id',
            'skill_level'              => 'required|in:beginner,intermediate,advanced',
            'is_active'                => 'boolean',
            'schedules'                => 'nullable|array',
            'schedules.*.day_of_week'  => 'required|integer|between:0,6',
            'schedules.*.session_time' => 'required|date_format:H:i',
            'schedules.*.session_type' => 'required|in:training,match,fitness,trial',
        ]);

        $batch->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'skill_level' => $validated['skill_level'],
            'is_active'   => $validated['is_active'] ?? $batch->is_active,
        ]);

        // Sync coaches
        if (!empty($validated['coach_ids'])) {
            $batch->coaches()->sync($validated['coach_ids']);
        }

        // Replace all schedules with new ones
        $batch->schedules()->delete();
        if (!empty($validated['schedules'])) {   // ← Change 2 (bug fix)
            $this->saveSchedules($batch, $validated['schedules']);
        }

        return redirect()->route('batches.index')
            ->with('success', "Batch \"{$batch->name}\" updated.");
    }

    public function destroy(Batch $batch)
    {
        $this->requireAdmin();

        if ($batch->students()->exists()) {
            return back()->with('error',
                'Cannot delete batch with students. Move or remove students first.');
        }

        $batch->schedules()->delete();
        $batch->coaches()->detach(); // detach from pivot before deleting
        $batch->delete();

        return redirect()->route('batches.index')
            ->with('success', 'Batch deleted.');
    }

    // ── AJAX: generate sessions from schedules ─────────────────────
    public function generateSessions(Request $request, SessionGeneratorService $generator)
    {
        $request->validate([
            'from'     => 'required|date',
            'to'       => 'required|date|after_or_equal:from',
            'batch_id' => 'nullable|exists:batches,id',
        ]);

        $from = \Carbon\Carbon::parse($request->from);
        $to   = \Carbon\Carbon::parse($request->to);

        if ($from->diffInDays($to) > 92) {
            return response()->json([
                'success' => false,
                'message' => 'Date range cannot exceed 3 months.',
            ], 422);
        }

        $result = $generator->generateForPeriod(
            $from,
            $to,
            $request->batch_id ?: null
        );

        $message = $result['created'] > 0
            ? "✓ Created {$result['created']} session".($result['created'] > 1 ? 's' : '').
              ($result['skipped'] > 0 ? ", {$result['skipped']} already existed" : '.')
            : ($result['skipped'] > 0
                ? "All sessions already exist for this period."
                : "No schedules found. Set up batch schedules first.");

        return response()->json([
            'success' => true,
            'created' => $result['created'],
            'skipped' => $result['skipped'],
            'message' => $message,
            'details' => $result['details'],
        ]);
    }

    // ── Private helpers ────────────────────────────────────────────

    private function saveSchedules(Batch $batch, array $schedules): void
    {
        foreach ($schedules as $s) {
            BatchSchedule::create([
                'batch_id'     => $batch->id,
                'day_of_week'  => $s['day_of_week'],
                'session_time' => $s['session_time'],
                'session_type' => $s['session_type'],
                'is_active'    => true,
            ]);
        }
    }

    private function requireAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Only admins can manage batches.');
        }
    }
}