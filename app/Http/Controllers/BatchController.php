<?php
namespace App\Http\Controllers;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Http\Request;

class BatchController extends Controller {
    public function index() {
        $user = auth()->user();
        $batches = $user->isAdmin()
            ? Batch::with(['coach'])->withCount('students')->latest()->get()
            : Batch::where('coach_id', $user->id)->with(['coach'])->withCount('students')->latest()->get();
        return view('batches.index', compact('batches'));
    }
    public function create() {
        $this->requireAdmin();
        $coaches = User::where('role','coach')->orderBy('name')->get();
        return view('batches.create', compact('coaches'));
    }
    public function store(Request $request) {
        $this->requireAdmin();
        $validated = $request->validate([
            'name'=>'required|string|max:100','description'=>'nullable|string|max:255',
            'coach_id'=>'required|exists:users,id','skill_level'=>'required|in:beginner,intermediate,advanced',
        ]);
        $batch = Batch::create($validated);
        return redirect()->route('batches.index')->with('success', "Batch \"{$batch->name}\" created.");
    }
    public function edit(Batch $batch) {
        $this->requireAdmin();
        $coaches = User::where('role','coach')->orderBy('name')->get();
        return view('batches.edit', compact('batch','coaches'));
    }
    public function update(Request $request, Batch $batch) {
        $this->requireAdmin();
        $validated = $request->validate([
            'name'=>'required|string|max:100','description'=>'nullable|string|max:255',
            'coach_id'=>'required|exists:users,id','skill_level'=>'required|in:beginner,intermediate,advanced',
            'is_active'=>'boolean',
        ]);
        $batch->update($validated);
        return redirect()->route('batches.index')->with('success', "Batch updated.");
    }
    public function destroy(Batch $batch) {
        $this->requireAdmin();
        if ($batch->students()->exists()) {
            return back()->with('error', 'Cannot delete batch with students.');
        }
        $batch->delete();
        return redirect()->route('batches.index')->with('success', 'Batch deleted.');
    }
    private function requireAdmin(): void {
        if (!auth()->user()->isAdmin()) abort(403, 'Only admins can manage batches.');
    }
}
