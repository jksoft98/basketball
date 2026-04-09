<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Student;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StudentController extends Controller
{
    public function __construct(private ImageService $imageService) {}

    public function index(Request $request)
    {
        $user     = auth()->user();
        $batchIds = $user->isAdmin()
            ? Batch::pluck('id')
            : Batch::where('coach_id', $user->id)->pluck('id');

        $query = Student::whereIn('batch_id', $batchIds)->with('batch')->active();

        if ($request->filled('batch_id'))      $query->where('batch_id', $request->batch_id);
        if ($request->filled('search'))        $query->where('full_name', 'like', '%'.$request->search.'%');
        if ($request->filled('injury_status')) $query->where('injury_status', $request->injury_status);
        if ($request->filled('skill_level'))   $query->where('skill_level', $request->skill_level);

        $students = $query->orderBy('full_name')->paginate(24)->withQueryString();
        $batches  = Batch::whereIn('id', $batchIds)->active()->get();

        // AJAX request — return JSON for live search
        if ($request->ajax() || $request->has('ajax')) {
            return response()->json([
                'students' => $students->map(fn($s) => [
                    'id'            => $s->id,
                    'full_name'     => $s->full_name,
                    'thumb_url'  => $s->photo_thumb_path
                                    ? asset('storage/' . $s->photo_thumb_path)
                                    : asset('images/default-avatar.png'),
                    'batch_name'    => $s->batch->name,
                    'jersey_number' => $s->jersey_number,
                    'skill_level'   => $s->skill_level,
                    'injury_status' => $s->injury_status,
                    'is_at_risk'    => $s->isAtRisk(),
                    'edit_url'      => route('students.edit', $s),
                ]),
                'total'    => $students->total(),
                'has_more' => $students->hasMorePages(),
            ]);
        }

        return view('students.index', compact('students', 'batches'));
    }

    public function create()
    {
        $user    = auth()->user();
        $batches = $user->isAdmin()
            ? Batch::active()->get()
            : Batch::where('coach_id', $user->id)->active()->get();

        return view('students.create', compact('batches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_id'       => 'required|exists:batches,id',
            'full_name'      => 'required|string|max:100',
            'age'            => 'nullable|integer|min:5|max:25',
            'parent_name'    => 'nullable|string|max:100',
            'parent_contact' => 'nullable|string|max:20',
            'jersey_number'  => 'nullable|integer|min:1|max:99',
            'position'       => 'nullable|in:guard,forward,centre',
            'skill_level'    => 'required|in:beginner,intermediate,advanced',
            'injury_status'  => 'required|in:fit,injured,recovering',
            'photo'          => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        [$photoPath, $thumbPath] = $this->imageService->storeStudentPhoto($request->file('photo'));

        $student = Student::create([
            ...$validated,
            'photo_path'       => $photoPath,
            'photo_thumb_path' => $thumbPath,
        ]);

        Cache::forget("batch_students_{$student->batch_id}");

        return redirect()->route('students.index')
            ->with('success', "{$student->full_name} added successfully.");
    }

    public function edit(Student $student)
    {
        $this->authorizeStudent($student);
        $user    = auth()->user();
        $batches = $user->isAdmin()
            ? Batch::active()->get()
            : Batch::where('coach_id', $user->id)->active()->get();

        return view('students.edit', compact('student', 'batches'));
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeStudent($student);

        $validated = $request->validate([
            'batch_id'       => 'required|exists:batches,id',
            'full_name'      => 'required|string|max:100',
            'age'            => 'nullable|integer|min:5|max:25',
            'parent_name'    => 'nullable|string|max:100',
            'parent_contact' => 'nullable|string|max:20',
            'jersey_number'  => 'nullable|integer|min:1|max:99',
            'position'       => 'nullable|in:guard,forward,centre',
            'skill_level'    => 'required|in:beginner,intermediate,advanced',
            'injury_status'  => 'required|in:fit,injured,recovering',
            'photo'          => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        if ($request->hasFile('photo')) {
            $this->imageService->deleteStudentPhoto($student->photo_path, $student->photo_thumb_path);
            [$validated['photo_path'], $validated['photo_thumb_path']] =
                $this->imageService->storeStudentPhoto($request->file('photo'));
        }

        Cache::forget("batch_students_{$student->batch_id}");
        if (isset($validated['batch_id']) && $validated['batch_id'] != $student->batch_id) {
            Cache::forget("batch_students_{$validated['batch_id']}");
        }

        $student->update($validated);

        return redirect()->route('students.index')
            ->with('success', "{$student->full_name} updated.");
    }

    public function destroy(Student $student)
    {
        $this->authorizeStudent($student);
        $this->imageService->deleteStudentPhoto($student->photo_path, $student->photo_thumb_path);
        Cache::forget("batch_students_{$student->batch_id}");
        $student->delete();

        return redirect()->route('students.index')
            ->with('success', 'Student removed.');
    }

    public function toggleStatus(Student $student)
    {
        $this->authorizeStudent($student);
        $student->update(['is_active' => !$student->is_active]);
        Cache::forget("batch_students_{$student->batch_id}");

        return back()->with('success', 'Student status updated.');
    }

    // ── AJAX: inline injury status toggle ─────────────────────────
    public function updateInjuryStatus(Request $request, Student $student)
    {
        $this->authorizeStudent($student);

        $request->validate([
            'injury_status' => 'required|in:fit,injured,recovering',
        ]);

        $student->update(['injury_status' => $request->injury_status]);
        Cache::forget("batch_students_{$student->batch_id}");

        return response()->json([
            'success' => true,
            'status'  => $request->injury_status,
            'label'   => match($request->injury_status) {
                'fit'        => '✅ Fit',
                'injured'    => '🚨 Injured',
                'recovering' => '⚠️ Recovering',
            },
        ]);
    }

    private function authorizeStudent(Student $student): void
    {
        if (auth()->user()->isAdmin()) return;
        $ownBatchIds = Batch::where('coach_id', auth()->id())->pluck('id');
        if (!$ownBatchIds->contains($student->batch_id)) abort(403);
    }
}
