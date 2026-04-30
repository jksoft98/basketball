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

        $batches = $user->isAdmin()
            ? Batch::with(['coach', 'activeSchedules'])->withCount('students')->latest()->get()
            : Batch::where('coach_id', $user->id)
                   ->with(['coach', 'activeSchedules'])->withCount('students')->latest()->get();

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
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'coach_id'    => 'required|exists:users,id',
            'skill_level' => 'required|in:beginner,intermediate,advanced',
            // Schedules
            'schedules'                    => 'nullable|array',
            'schedules.*.day_of_week'      => 'required|integer|between:0,6',
            'schedules.*.session_time'     => 'required|date_format:H:i',
            'schedules.*.session_type'     => 'required|in:training,match,fitness,trial',
        ]);

        $batch = Batch::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'coach_id'    => $validated['coach_id'],
            'skill_level' => $validated['skill_level'],
        ]);

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
        $coaches   = User::where('role', 'coach')->orderBy('name')->get();
        $schedules = $batch->schedules()->orderBy('day_of_week')->get();
        return view('batches.edit', compact('batch', 'coaches', 'schedules'));
    }

    public function update(Request $request, Batch $batch)
    {
        $this->requireAdmin();

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'coach_id'    => 'required|exists:users,id',
            'skill_level' => 'required|in:beginner,intermediate,advanced',
            'is_active'   => 'boolean',
            // Schedules
            'schedules'                    => 'nullable|array',
            'schedules.*.day_of_week'      => 'required|integer|between:0,6',
            'schedules.*.session_time'     => 'required|date_format:H:i',
            'schedules.*.session_type'     => 'required|in:training,match,fitness,trial',
        ]);

        $batch->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'coach_id'    => $validated['coach_id'],
            'skill_level' => $validated['skill_level'],
            'is_active'   => $validated['is_active'] ?? $batch->is_active,
        ]);

        // Replace all schedules with new ones
        $batch->schedules()->delete();
        if (!empty($validated['schedules'])) {
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

        // Prevent generating more than 3 months at once
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
