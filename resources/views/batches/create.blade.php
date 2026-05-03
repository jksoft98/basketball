@extends('layouts.app')
@section('title', 'New Batch')
@section('content')
<div class="max-w-xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('batches.index') }}" class="text-gray-400 hover:text-gray-600">← Back</a>
        <h1 class="text-xl font-bold text-gray-900">Create New Batch</h1>
    </div>
    <form action="{{ route('batches.store') }}" method="POST" class="space-y-5">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Batch Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" placeholder="e.g. Under-16 Squad A" required>
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                <input type="text" name="description" value="{{ old('description') }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" placeholder="Optional short description">
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
                            {{ in_array($coach->id, old('coach_ids', [])) ? 'checked' : '' }}
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
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Skill Level <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-3 gap-3 mt-1">
                    @foreach(['beginner'=>'🌱','intermediate'=>'⚡','advanced'=>'🔥'] as $level=>$icon)
                    <label class="cursor-pointer">
                        <input type="radio" name="skill_level" value="{{ $level }}" class="peer hidden" {{ old('skill_level','beginner')===$level ? 'checked' : '' }}>
                        <div class="flex flex-col items-center py-3 border-2 border-gray-200 rounded-xl peer-checked:border-orange-500 peer-checked:bg-orange-50 hover:border-orange-300 transition-all">
                            <span class="text-xl">{{ $icon }}</span>
                            <span class="text-xs font-medium text-gray-700 mt-1">{{ ucfirst($level) }}</span>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl transition-colors">Create Batch</button>
            <a href="{{ route('batches.index') }}" class="px-6 py-3 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 text-center">Cancel</a>
        </div>
    </form>
</div>
@endsection
