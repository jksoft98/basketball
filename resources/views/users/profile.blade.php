@extends('layouts.app')
@section('title', 'My Profile')

@section('content')
<div class="max-w-2xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">My Profile</h1>
        <p class="text-sm text-gray-500 mt-1">Manage your account information and password</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

        {{-- Left: avatar + stats --}}
        <div class="space-y-4">

            {{-- Avatar card --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 text-center">
                <div class="w-20 h-20 rounded-full flex items-center justify-center text-3xl font-bold mx-auto mb-3
                    {{ auth()->user()->isAdmin() ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600' }}">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <p class="font-bold text-gray-900 text-base">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ auth()->user()->email }}</p>
                <span class="inline-block mt-2 text-xs font-medium px-2.5 py-1 rounded-full
                    {{ auth()->user()->isAdmin() ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}">
                    {{ ucfirst(auth()->user()->role) }}
                </span>
                <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-400">
                    Member since {{ auth()->user()->created_at->format('d M Y') }}
                </div>
            </div>

            {{-- Stats (coaches only) --}}
            @if(auth()->user()->isCoach() && !empty($stats))
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">My Stats</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Batches</span>
                        <span class="text-sm font-bold text-gray-900">{{ $stats['batches'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Students</span>
                        <span class="text-sm font-bold text-gray-900">{{ $stats['students'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Sessions</span>
                        <span class="text-sm font-bold text-gray-900">{{ $stats['sessions'] }}</span>
                    </div>
                </div>
            </div>
            @endif

        </div>

        {{-- Right: edit form --}}
        <div class="md:col-span-2 space-y-5">

            <form action="{{ route('profile.update') }}" method="POST" class="space-y-5">
                @csrf @method('PUT')

                {{-- Basic info --}}
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-gray-700">Basic Information</h2>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name"
                               value="{{ old('name', auth()->user()->name) }}" required
                               class="w-full h-9 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                        @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email"
                               value="{{ old('email', auth()->user()->email) }}" required
                               class="w-full h-9 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                        @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full h-9 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition-colors">
                        Save Changes
                    </button>
                </div>

                {{-- Change password --}}
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-700">Change Password</h2>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Leave all three fields blank to keep your current password
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">
                            Current Password
                        </label>
                        <div class="relative">
                            <input type="password" name="current_password" id="cur-pwd"
                                   placeholder="Enter your current password"
                                   class="w-full h-9 px-3 pr-10 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                            <button type="button" onclick="togglePwd('cur-pwd','eye-cur')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <span id="eye-cur">👁</span>
                            </button>
                        </div>
                        @error('current_password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">
                            New Password
                        </label>
                        <div class="relative">
                            <input type="password" name="password" id="new-pwd"
                                   placeholder="Min. 8 characters with letters and numbers"
                                   class="w-full h-9 px-3 pr-10 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                            <button type="button" onclick="togglePwd('new-pwd','eye-new')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <span id="eye-new">👁</span>
                            </button>
                        </div>
                        @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">
                            Confirm New Password
                        </label>
                        <div class="relative">
                            <input type="password" name="password_confirmation" id="conf-pwd"
                                   placeholder="Repeat new password"
                                   class="w-full h-9 px-3 pr-10 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                            <button type="button" onclick="togglePwd('conf-pwd','eye-conf')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <span id="eye-conf">👁</span>
                            </button>
                        </div>
                    </div>

                    {{-- Strength bar --}}
                    <div id="strength-wrap" class="hidden">
                        <div class="flex gap-1 mb-1">
                            <div id="s1" class="h-1 flex-1 rounded-full bg-gray-200"></div>
                            <div id="s2" class="h-1 flex-1 rounded-full bg-gray-200"></div>
                            <div id="s3" class="h-1 flex-1 rounded-full bg-gray-200"></div>
                            <div id="s4" class="h-1 flex-1 rounded-full bg-gray-200"></div>
                        </div>
                        <p id="strength-label" class="text-xs text-gray-400"></p>
                    </div>

                    <button type="submit"
                            class="w-full h-9 bg-gray-800 hover:bg-gray-700 text-white text-sm font-semibold rounded-lg transition-colors">
                        Update Password
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

@push('scripts')
<script>
function togglePwd(inputId, eyeId) {
    const input = document.getElementById(inputId);
    const eye   = document.getElementById(eyeId);
    input.type      = input.type === 'password' ? 'text' : 'password';
    eye.textContent = input.type === 'password' ? '👁' : '🙈';
}

document.getElementById('new-pwd').addEventListener('input', function() {
    const val   = this.value;
    const wrap  = document.getElementById('strength-wrap');
    const label = document.getElementById('strength-label');
    const bars  = ['s1','s2','s3','s4'].map(id => document.getElementById(id));

    if (!val) { wrap.classList.add('hidden'); return; }
    wrap.classList.remove('hidden');

    let score = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;

    const configs = [
        { color:'bg-red-400',    text:'Weak',   textColor:'text-red-500'    },
        { color:'bg-yellow-400', text:'Fair',   textColor:'text-yellow-600' },
        { color:'bg-blue-400',   text:'Good',   textColor:'text-blue-600'   },
        { color:'bg-green-400',  text:'Strong', textColor:'text-green-600'  },
    ];
    const cfg = configs[Math.max(score - 1, 0)];

    bars.forEach((b, i) => {
        b.className = 'h-1 flex-1 rounded-full ' + (i < score ? cfg.color : 'bg-gray-200');
    });
    label.textContent = cfg.text;
    label.className   = 'text-xs ' + cfg.textColor;
});
</script>
@endpush
@endsection
