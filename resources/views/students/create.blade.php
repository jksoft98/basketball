@extends('layouts.app')
@section('title', 'Add Student')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('students.index') }}" class="text-gray-400 hover:text-gray-600">← Back</a>
        <h1 class="text-xl font-bold text-gray-900">Add New Student</h1>
    </div>
    <form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Profile Photo <span class="text-red-500">*</span></h2>
            <div class="flex items-start gap-6">
                <div id="photo-preview" class="w-28 h-28 rounded-xl bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden flex-shrink-0">
                    <span id="preview-placeholder" class="text-3xl">📷</span>
                    <img id="preview-img" src="" alt="" class="hidden w-full h-full object-cover">
                </div>
                <div class="flex-1">
                    <label for="photo" class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-orange-400 hover:bg-orange-50 transition-colors bg-gray-50">
                        <span class="text-2xl mb-1">📤</span>
                        <span class="text-sm font-medium text-gray-600">Click to upload photo</span>
                        <span class="text-xs text-gray-400 mt-1">JPG, PNG, WebP · Max 5MB</span>
                        <input id="photo" name="photo" type="file" accept="image/jpeg,image/jpg,image/png,image/webp" class="hidden" required>
                    </label>
                    <input id="camera-input" type="file" accept="image/*" capture="user" class="hidden">
                    <button type="button" id="camera-btn" class="mt-2 w-full text-xs text-center text-gray-500 border border-gray-200 rounded-lg py-2 hover:bg-gray-50 md:hidden">📸 Take Photo with Camera</button>
                </div>
            </div>
            @error('photo')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Basic Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" required>
                    @error('full_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Batch / Team <span class="text-red-500">*</span></label>
                    <select name="batch_id" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" required>
                        <option value="">Select a batch</option>
                        @foreach($batches as $batch)
                        <option value="{{ $batch->id }}" {{ old('batch_id')==$batch->id ? 'selected' : '' }}>{{ $batch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Age</label>
                    <input type="number" name="age" value="{{ old('age') }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" min="5" max="25">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Jersey Number</label>
                    <input type="number" name="jersey_number" value="{{ old('jersey_number') }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" min="1" max="99">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Position</label>
                    <select name="position" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                        <option value="">Not set</option>
                        <option value="guard" {{ old('position')==='guard' ? 'selected' : '' }}>Guard</option>
                        <option value="forward" {{ old('position')==='forward' ? 'selected' : '' }}>Forward</option>
                        <option value="centre" {{ old('position')==='centre' ? 'selected' : '' }}>Centre</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Skill Level <span class="text-red-500">*</span></label>
                    <select name="skill_level" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" required>
                        <option value="beginner" {{ old('skill_level','beginner')==='beginner' ? 'selected' : '' }}>Beginner</option>
                        <option value="intermediate" {{ old('skill_level')==='intermediate' ? 'selected' : '' }}>Intermediate</option>
                        <option value="advanced" {{ old('skill_level')==='advanced' ? 'selected' : '' }}>Advanced</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Injury Status <span class="text-red-500">*</span></label>
                    <select name="injury_status" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" required>
                        <option value="fit" {{ old('injury_status','fit')==='fit' ? 'selected' : '' }}>✅ Fit</option>
                        <option value="injured" {{ old('injury_status')==='injured' ? 'selected' : '' }}>🚨 Injured</option>
                        <option value="recovering" {{ old('injury_status')==='recovering' ? 'selected' : '' }}>⚠️ Recovering</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Parent / Guardian</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Parent Name</label>
                    <input type="text" name="parent_name" value="{{ old('parent_name') }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Contact Number</label>
                    <input type="text" name="parent_contact" value="{{ old('parent_contact') }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                </div>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                    Joining Date
                </label>
                <input type="date" name="joined_at"
                    value="{{ old('joined_at', now()->format('Y-m-d')) }}"
                    class="w-full h-9 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" required>
                <p class="text-xs text-gray-400 mt-1">
                    Student will not appear in sessions before this date.
                </p>
            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl transition-colors">Add Student</button>
            <a href="{{ route('students.index') }}" class="px-6 py-3 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 text-center">Cancel</a>
        </div>
    </form>
</div>
@push('scripts')
<script>
document.getElementById('photo').addEventListener('change', function(e) {
    const file = e.target.files[0]; if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
        document.getElementById('preview-img').src = ev.target.result;
        document.getElementById('preview-img').classList.remove('hidden');
        document.getElementById('preview-placeholder').classList.add('hidden');
    };
    reader.readAsDataURL(file);
});
document.getElementById('camera-btn')?.addEventListener('click', () => document.getElementById('camera-input').click());
document.getElementById('camera-input').addEventListener('change', function(e) {
    if (!e.target.files[0]) return;
    const dt = new DataTransfer(); dt.items.add(e.target.files[0]);
    document.getElementById('photo').files = dt.files;
    document.getElementById('photo').dispatchEvent(new Event('change'));
});
</script>
@endpush
@endsection
