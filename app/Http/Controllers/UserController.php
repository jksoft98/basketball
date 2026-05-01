<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // ── User list (admin only) ─────────────────────────────────────
    public function index()
    {
        $this->requireAdmin();

        $users = User::withCount('batches')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $totalAdmins = $users->where('role', 'admin')->count();
        $totalCoaches = $users->where('role', 'coach')->count();

        return view('users.index', compact('users', 'totalAdmins', 'totalCoaches'));
    }

    // ── Create user (admin only) ───────────────────────────────────
    public function create()
    {
        $this->requireAdmin();
        return view('users.create');
    }

    public function store(Request $request)
    {
        $this->requireAdmin();

        $validated = $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|unique:users,email',
            'role'                  => 'required|in:admin,coach',
            'password'              => ['required', Password::min(8)->letters()->numbers()],
            'password_confirmation' => 'required|same:password',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'role'     => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('users.index')
            ->with('success', "{$user->name} has been created successfully.");
    }

    // ── Edit user (admin only) ─────────────────────────────────────
    public function edit(User $user)
    {
        $this->requireAdmin();
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->requireAdmin();

        $validated = $request->validate([
            'name'  => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'  => 'required|in:admin,coach',
        ]);

        // Optional password change
        if ($request->filled('password')) {
            $request->validate([
                'password'              => ['required', Password::min(8)->letters()->numbers()],
                'password_confirmation' => 'required|same:password',
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        // Prevent demoting the only admin
        if ($user->role === 'admin' && $validated['role'] === 'coach') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'Cannot demote the only admin. Create another admin first.');
            }
        }

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', "{$user->name} has been updated.");
    }

    // ── Delete user (admin only) ───────────────────────────────────
    public function destroy(User $user)
    {
        $this->requireAdmin();

        // Cannot delete yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Cannot delete the only admin
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Cannot delete the only admin account.');
        }

        // Cannot delete coach who has batches
        if ($user->batches()->exists()) {
            return back()->with('error',
                "Cannot delete {$user->name} — they have batches assigned. Reassign the batches first.");
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "{$name} has been deleted.");
    }

    // ── Profile (any authenticated user) ──────────────────────────
    public function profile()
    {
        $user = auth()->user();

        // Stats for coaches
        $stats = [];
        if ($user->isCoach()) {
            $stats['batches']   = $user->batches()->count();
            $stats['students']  = \App\Models\Student::whereIn(
                'batch_id', $user->batches()->pluck('id')
            )->active()->count();
            $stats['sessions']  = \App\Models\TrainingSession::whereIn(
                'batch_id', $user->batches()->pluck('id')
            )->count();
        }

        return view('users.profile', compact('user', 'stats'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'  => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
        ]);

        // Password change (optional)
        if ($request->filled('current_password')) {
            $request->validate([
                'current_password'      => 'required',
                'password'              => ['required', Password::min(8)->letters()->numbers()],
                'password_confirmation' => 'required|same:password',
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    // ── Helper ─────────────────────────────────────────────────────
    private function requireAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Only admins can manage users.');
        }
    }
}
