@extends('layouts.app')
@section('title', 'New User')

@section('content')
<div class="max-w-lg mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('users.index') }}" class="text-gray-400 hover:text-gray-600 transition-colors">← Back</a>
        <h1 class="text-xl font-bold text-gray-900">Create New User</h1>
    </div>

    <form action="{{ route('users.store') }}" method="POST" class="space-y-5">
        @csrf

        {{-- Basic info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700">Account Information</h2>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       placeholder="e.g. Coach Mike"
                       class="w-full h-9 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                @error('name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                    Email Address <span class="text-red-500">*</span>
                </label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       placeholder="e.g. coach@academy.com"
                       class="w-full h-9 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                @error('email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">
                    Role <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="coach" class="peer hidden"
                               {{ old('role', 'coach') === 'coach' ? 'checked' : '' }}>
                        <div class="flex flex-col items-center py-4 border-2 border-gray-200 rounded-xl
                                    peer-checked:border-orange-500 peer-checked:bg-orange-50
                                    hover:border-orange-300 transition-all">
                            <span class="text-2xl mb-1.5">🏀</span>
                            <span class="text-sm font-semibold text-gray-700">Coach</span>
                            <span class="text-xs text-gray-400 mt-1 text-center">Manages their own batches</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="admin" class="peer hidden"
                               {{ old('role') === 'admin' ? 'checked' : '' }}>
                        <div class="flex flex-col items-center py-4 border-2 border-gray-200 rounded-xl
                                    peer-checked:border-orange-500 peer-checked:bg-orange-50
                                    hover:border-orange-300 transition-all">
                            <span class="text-2xl mb-1.5">👑</span>
                            <span class="text-sm font-semibold text-gray-700">Admin</span>
                            <span class="text-xs text-gray-400 mt-1 text-center">Full access to everything</span>
                        </div>
                    </label>
                </div>
                @error('role')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Password --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700">Password</h2>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                    Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" name="password" id="password" required
                           placeholder="Min. 8 characters with letters and numbers"
                           class="w-full h-9 px-3 pr-10 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                    <button type="button" onclick="togglePassword('password', 'eye1')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <span id="eye1">👁</span>
                    </button>
                </div>
                @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                    Confirm Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                           placeholder="Repeat password"
                           class="w-full h-9 px-3 pr-10 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                    <button type="button" onclick="togglePassword('password_confirmation', 'eye2')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <span id="eye2">👁</span>
                    </button>
                </div>
                @error('password_confirmation')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Strength indicator --}}
            <div id="strength-wrap" class="hidden">
                <div class="flex gap-1 mb-1">
                    <div id="s1" class="h-1 flex-1 rounded-full bg-gray-200"></div>
                    <div id="s2" class="h-1 flex-1 rounded-full bg-gray-200"></div>
                    <div id="s3" class="h-1 flex-1 rounded-full bg-gray-200"></div>
                    <div id="s4" class="h-1 flex-1 rounded-full bg-gray-200"></div>
                </div>
                <p id="strength-label" class="text-xs text-gray-400"></p>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="flex-1 h-9 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition-colors">
                Create User
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
    if (input.type === 'password') {
        input.type    = 'text';
        eye.textContent = '🙈';
    } else {
        input.type    = 'password';
        eye.textContent = '👁';
    }
}

// Password strength indicator
document.getElementById('password').addEventListener('input', function() {
    const val   = this.value;
    const wrap  = document.getElementById('strength-wrap');
    const label = document.getElementById('strength-label');
    const bars  = [document.getElementById('s1'), document.getElementById('s2'),
                   document.getElementById('s3'), document.getElementById('s4')];

    if (!val) { wrap.classList.add('hidden'); return; }
    wrap.classList.remove('hidden');

    let score = 0;
    if (val.length >= 8)              score++;
    if (/[A-Z]/.test(val))            score++;
    if (/[0-9]/.test(val))            score++;
    if (/[^A-Za-z0-9]/.test(val))     score++;

    const configs = [
        { color: 'bg-red-400',    text: 'Weak',      textColor: 'text-red-500'    },
        { color: 'bg-yellow-400', text: 'Fair',      textColor: 'text-yellow-600' },
        { color: 'bg-blue-400',   text: 'Good',      textColor: 'text-blue-600'   },
        { color: 'bg-green-400',  text: 'Strong',    textColor: 'text-green-600'  },
    ];

    const cfg = configs[score - 1] || configs[0];

    bars.forEach((b, i) => {
        b.className = 'h-1 flex-1 rounded-full ' + (i < score ? cfg.color : 'bg-gray-200');
    });

    label.textContent = cfg.text;
    label.className   = 'text-xs ' + cfg.textColor;
});
</script>
@endpush
@endsection
