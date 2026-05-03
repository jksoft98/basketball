@extends('layouts.app')
@section('title', 'Edit Batch')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('batches.index') }}" class="text-gray-400 hover:text-gray-600 transition-colors">← Back</a>
        <h1 class="text-xl font-bold text-gray-900">Edit Batch</h1>
    </div>

    <form action="{{ route('batches.update', $batch) }}" method="POST" class="space-y-5">
        @csrf @method('PUT')

        {{-- Basic info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700">Basic Information</h2>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Batch Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $batch->name) }}" required
                       class="w-full h-9 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                <input type="text" name="description" value="{{ old('description', $batch->description) }}"
                       class="w-full h-9 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
            </div>

            <div>

                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                    Assign Coaches <span class="text-red-500">*</span>
                    <span class="text-gray-400 font-normal">(select one or more)</span>
                </label>
                <div class="space-y-2 border border-gray-200 rounded-lg p-3 max-h-48 overflow-y-auto">
                    @foreach($coaches as $coach)
                    <label class="flex items-center gap-2.5 cursor-pointer hover:bg-gray-50 px-2 py-1.5 rounded-lg">
                        <input type="checkbox" name="coach_ids[]" value="{{ $coach->id }}"
                            {{ in_array($coach->id, old('coach_ids', $batch->coaches->pluck('id')->toArray())) ? 'checked' : '' }}
                            class="w-4 h-4 accent-orange-500 rounded">
                        <span class="text-sm text-gray-700">{{ $coach->name }}</span>
                    </label>
                    @endforeach
                </div>
                @error('coach_ids')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror

            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Skill Level <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-3 gap-3">
                    @foreach(['beginner' => ['🌱','Beginner'], 'intermediate' => ['⚡','Intermediate'], 'advanced' => ['🔥','Advanced']] as $level => [$icon, $label])
                    <label class="cursor-pointer">
                        <input type="radio" name="skill_level" value="{{ $level }}" class="peer hidden"
                               {{ old('skill_level', $batch->skill_level) === $level ? 'checked' : '' }}>
                        <div class="flex flex-col items-center py-3 border-2 border-gray-200 rounded-xl transition-all
                                    peer-checked:border-orange-500 peer-checked:bg-orange-50 hover:border-orange-300">
                            <span class="text-xl">{{ $icon }}</span>
                            <span class="text-xs font-medium text-gray-700 mt-1">{{ $label }}</span>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between pt-1">
                <div>
                    <p class="text-sm font-medium text-gray-700">Active Batch</p>
                    <p class="text-xs text-gray-400 mt-0.5">Inactive batches are hidden from session creation</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                           {{ old('is_active', $batch->is_active) ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-orange-500
                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                after:bg-white after:rounded-full after:h-5 after:w-5
                                after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
            </div>
        </div>

        {{-- ── Weekly Schedule ──────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <h2 class="text-sm font-semibold text-gray-700">Weekly Schedule</h2>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Sessions are auto-generated from this schedule.
                        Each row = one recurring slot per week.
                    </p>
                </div>
            </div>

            @if($schedules->isNotEmpty())
            <div class="mt-3 mb-2 px-2 hidden sm:grid grid-cols-[1fr_110px_120px_32px] gap-2 text-[11px] font-medium text-gray-400 uppercase tracking-wide">
                <span>Day</span><span>Time</span><span>Type</span><span></span>
            </div>
            @endif

            <div id="schedule-rows" class="space-y-2 mt-3">
                @forelse($schedules as $i => $schedule)
                <div class="schedule-row grid grid-cols-[1fr_110px_120px_32px] gap-2 items-center">
                    <select name="schedules[{{ $i }}][day_of_week]"
                            class="h-9 px-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
                        @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d => $dayName)
                        <option value="{{ $d }}" {{ $schedule->day_of_week === $d ? 'selected' : '' }}>{{ $dayName }}</option>
                        @endforeach
                    </select>

                    <input type="time" name="schedules[{{ $i }}][session_time]"
                           value="{{ \Carbon\Carbon::createFromFormat('H:i:s', $schedule->session_time)->format('H:i') }}"
                           class="h-9 px-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">

                    <select name="schedules[{{ $i }}][session_type]"
                            class="h-9 px-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
                        @foreach(['training' => '🏀 Training', 'match' => '🏆 Match', 'fitness' => '💪 Fitness', 'trial' => '🔍 Trial'] as $type => $typeLabel)
                        <option value="{{ $type }}" {{ $schedule->session_type === $type ? 'selected' : '' }}>{{ $typeLabel }}</option>
                        @endforeach
                    </select>

                    <button type="button" onclick="removeScheduleRow(this)"
                            class="w-8 h-8 flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors text-lg leading-none">
                        ×
                    </button>
                </div>
                @empty
                <p id="no-schedule-msg" class="text-xs text-gray-400 py-2">
                    No recurring schedule yet. Add a slot below.
                </p>
                @endforelse
            </div>

            <button type="button" onclick="addScheduleRow()"
                    class="mt-3 flex items-center gap-1.5 text-sm text-orange-500 hover:text-orange-700 font-medium transition-colors">
                <span class="text-base leading-none">+</span> Add day
            </button>

            @error('schedules')
            <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
            @enderror
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <button type="submit"
                    class="flex-1 h-9 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition-colors">
                Save Changes
            </button>
            @if(!$batch->students()->exists())
            <form action="{{ route('batches.destroy', $batch) }}" method="POST"
                  onsubmit="return confirm('Delete this batch?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="h-9 px-4 border border-red-200 text-red-500 rounded-lg hover:bg-red-50 transition-colors text-sm font-medium">
                    Delete
                </button>
            </form>
            @endif
        </div>
    </form>
</div>

@push('scripts')
<script>
let rowIndex = {{ $schedules->count() }};

function addScheduleRow() {
    document.getElementById('no-schedule-msg')?.remove();

    const container = document.getElementById('schedule-rows');
    const row       = document.createElement('div');
    row.className   = 'schedule-row grid grid-cols-[1fr_110px_120px_32px] gap-2 items-center';

    row.innerHTML = `
        <select name="schedules[${rowIndex}][day_of_week]"
                class="h-9 px-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
            <option value="1">Monday</option>
            <option value="2">Tuesday</option>
            <option value="3">Wednesday</option>
            <option value="4">Thursday</option>
            <option value="5">Friday</option>
            <option value="6">Saturday</option>
            <option value="0">Sunday</option>
        </select>

        <input type="time" name="schedules[${rowIndex}][session_time]"
               value="16:00"
               class="h-9 px-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">

        <select name="schedules[${rowIndex}][session_type]"
                class="h-9 px-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
            <option value="training">🏀 Training</option>
            <option value="match">🏆 Match</option>
            <option value="fitness">💪 Fitness</option>
            <option value="trial">🔍 Trial</option>
        </select>

        <button type="button" onclick="removeScheduleRow(this)"
                class="w-8 h-8 flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors text-lg leading-none">
            ×
        </button>`;

    container.appendChild(row);
    rowIndex++;

    // Focus the time input of the new row
    row.querySelector('input[type="time"]').focus();
}

function removeScheduleRow(btn) {
    const row       = btn.closest('.schedule-row');
    const container = document.getElementById('schedule-rows');
    row.remove();

    // Show placeholder if no rows left
    if (!container.querySelector('.schedule-row')) {
        const msg = document.createElement('p');
        msg.id        = 'no-schedule-msg';
        msg.className = 'text-xs text-gray-400 py-2';
        msg.textContent = 'No recurring schedule yet. Add a slot below.';
        container.appendChild(msg);
    }
}
</script>
@endpush
@endsection
