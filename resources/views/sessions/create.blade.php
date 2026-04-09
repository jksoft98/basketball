@extends('layouts.app')
@section('title', 'New Session')
@section('content')
<div class="max-w-xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('sessions.index') }}" class="text-gray-400 hover:text-gray-600">← Back</a>
        <h1 class="text-xl font-bold text-gray-900">Create Session</h1>
    </div>
    <form action="{{ route('sessions.store') }}" method="POST" class="space-y-5">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Batch / Team <span class="text-red-500">*</span></label>
                <select name="batch_id" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" required>
                    <option value="">Select a batch</option>
                    @foreach($batches as $batch)
                    <option value="{{ $batch->id }}" {{ (old('batch_id',$selectedBatchId)==$batch->id) ? 'selected' : '' }}>
                        {{ $batch->name }} ({{ $batch->students()->active()->count() }} students)
                    </option>
                    @endforeach
                </select>
                @error('batch_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="session_date" value="{{ old('session_date', now()->format('Y-m-d')) }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Time</label>
                    <input type="time" name="session_time" value="{{ old('session_time') }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Session Type <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-4 gap-2 mt-1">
                    @foreach(['training'=>'🏀','match'=>'🏆','fitness'=>'💪','trial'=>'🔍'] as $type=>$icon)
                    <label class="cursor-pointer">
                        <input type="radio" name="session_type" value="{{ $type }}" class="peer hidden" {{ old('session_type','training')===$type ? 'checked' : '' }}>
                        <div class="flex flex-col items-center py-3 border-2 border-gray-200 rounded-xl peer-checked:border-orange-500 peer-checked:bg-orange-50 hover:border-orange-300 transition-all">
                            <span class="text-xl">{{ $icon }}</span>
                            <span class="text-xs font-medium text-gray-700 mt-1">{{ ucfirst($type) }}</span>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Notes</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 resize-none" placeholder="Optional notes...">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl transition-colors">Create & Take Attendance →</button>
            <a href="{{ route('sessions.index') }}" class="px-6 py-3 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 text-center">Cancel</a>
        </div>
    </form>
</div>
@endsection
