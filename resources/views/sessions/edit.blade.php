@extends('layouts.app')
@section('title', 'Edit Session')
@section('content')
<div class="max-w-xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('sessions.index') }}" class="text-gray-400 hover:text-gray-600">← Back</a>
        <h1 class="text-xl font-bold text-gray-900">Edit Session</h1>
    </div>
    <form action="{{ route('sessions.update', $session) }}" method="POST" class="space-y-5">
        @csrf @method('PUT')
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Batch / Team</label>
                <select name="batch_id" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" required>
                    @foreach($batches as $batch)
                    <option value="{{ $batch->id }}" {{ old('batch_id',$session->batch_id)==$batch->id ? 'selected' : '' }}>{{ $batch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Date</label>
                    <input type="date" name="session_date" value="{{ old('session_date', $session->session_date->format('Y-m-d')) }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Time</label>
                    <input type="time" name="session_time" value="{{ old('session_time', $session->session_time) }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Session Type</label>
                <div class="grid grid-cols-4 gap-2">
                    @foreach(['training'=>'🏀','match'=>'🏆','fitness'=>'💪','trial'=>'🔍'] as $type=>$icon)
                    <label class="cursor-pointer">
                        <input type="radio" name="session_type" value="{{ $type }}" class="peer hidden" {{ old('session_type',$session->session_type)===$type ? 'checked' : '' }}>
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
                <textarea name="notes" rows="2" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 resize-none">{{ old('notes', $session->notes) }}</textarea>
            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl transition-colors">Save Changes</button>
            <a href="{{ route('attendance.index', $session) }}" class="px-5 py-3 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 text-center text-sm">View Attendance</a>
        </div>
    </form>
</div>
@endsection
