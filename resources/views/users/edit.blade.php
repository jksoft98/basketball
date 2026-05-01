@extends('layouts.app')
@section('title', 'Edit User — ' . $user->name)

@section('content')
<div class="max-w-lg mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('users.index') }}" class="text-gray-400 hover:text-gray-600 transition-colors">← Back</a>
        <h1 class="text-xl font-bold text-gray-900">Edit User</h1>
        @if($user->id === auth()->id())
        <span class="text-xs px-2 py-0.5 bg-orange-100 text-orange-600 rounded-full font-medium">You</span>
        @endif
    </div>

    {{-- User info card --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold flex-shrink-0
            {{ $user->isAdmin() ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <p class="font-semibold text-gray-900">{{ $user->name }}</p>
            <p class="text-xs text-gray-400 mt-0.5">
                Member since {{ $user->created_at->format('d M Y') }}
                &middot;
                @if($user->isCoach())
                    {{ $user->batches()->count() }} batch{{ $user->batches()->count() !== 1 ? 'es' : '' }}
                @else
                    Admin
                @endif
            </p>
        </div>
    </div>

    <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-5">
        @csrf @method('PUT')

        {{-- Basic info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700">Account Information</h2>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full h-9 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                @error('name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                    Email Address <span class="text-red-500">*</span>
                </label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="w-full h-9 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                @error('email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Role</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="coach" class="peer hidden"
                               {{ old('role', $user->role) === 'coach' ? 'checked' : '' }}>
                        <div class="flex flex-col items-center py-4 border-2 border-gray-200 rounded-xl
                                    peer-checked:border-orange-500 peer-checked:bg-orange-50
                                    hover:border-orange-300 transition-all">
                            <span class="text-2xl mb-1.5">🏀</span>
                            <span class="text-sm font-semibold text-gray-700">Coach</span>
                            <span class="text-xs text-gray-400 mt-1">Manages their own batches</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="admin" class="peer hidden"
                               {{ old('role', $user->role) === 'admin' ? 'checked' : '' }}>
                        <div class="flex flex-col items-center py-4 border-2 border-gray-200 rounded-xl
                                    peer-checked:border-orange-500 peer-checked:bg-orange-50
                                    hover:border-orange-300 transition-all">
                            <span class="text-2xl mb-1.5">👑</span>
                            <span class="text-sm font-semibold text-gray-700">Admin</span>
                            <span class="text-xs text-gray-400 mt-1">Full access to everything</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Password change (optional) --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-700">Change Password</h2>
                <p class="text-xs text-gray-400 mt-0.5">Leave blank to keep the current password</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">New Password</label>
                <div class="relative">
                    <input type="password" name="password" id="new-password"
                           placeholder="Min. 8 characters with letters and numbers"
                           class="w-full h-9 px-3 pr-10 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                    <button type="button" onclick="togglePassword('new-password', 'eye1')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <span id="eye1">👁</span>
                    </button>
                </div>
                @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Confirm New Password</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="new-password-confirm"
                           placeholder="Repeat new password"
                           class="w-full h-9 px-3 pr-10 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                    <button type="button" onclick="togglePassword('new-password-confirm', 'eye2')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <span id="eye2">👁</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="flex-1 h-9 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition-colors">
                Save Changes
            </button>
            <a href="{{ route('users.index') }}"
               class="h-9 px-5 flex items-center border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 text-sm">
                Cancel
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function togglePassword(inputId, eyeId) {
    const input = document.getElementById(inputId);
    const eye   = document.getElementById(eyeId);
    input.type      = input.type === 'password' ? 'text' : 'password';
    eye.textContent = input.type === 'password' ? '👁' : '🙈';
}
</script>
@endpush
@endsection
