@extends('layouts.app')
@section('title', 'Edit Student')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('students.index') }}" class="text-gray-400 hover:text-gray-600">← Back</a>
        <h1 class="text-xl font-bold text-gray-900">Edit Student</h1>
    </div>
    <form action="{{ route('students.update', $student) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf @method('PUT')
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Profile Photo</h2>
            <div class="flex items-start gap-6">
                <div class="w-28 h-28 rounded-xl overflow-hidden flex-shrink-0 border border-gray-200">
                    <img id="preview-img" src="{{  asset('storage/' . $student->photo_path) }}" alt="{{ $student->full_name }}" class="w-full h-full object-cover">
                </div>
                <div class="flex-1">
                    <label for="photo" class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-orange-400 hover:bg-orange-50 transition-colors bg-gray-50">
                        <span class="text-sm font-medium text-gray-600">Replace photo</span>
                        <span class="text-xs text-gray-400 mt-1">Leave empty to keep current</span>
                        <input id="photo" name="photo" type="file" accept="image/jpeg,image/jpg,image/png,image/webp" class="hidden">
                    </label>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Basic Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" value="{{ old('full_name', $student->full_name) }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Batch / Team</label>
                    <select name="batch_id" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" required>
                        @foreach($batches as $batch)
                        <option value="{{ $batch->id }}" {{ old('batch_id',$student->batch_id)==$batch->id ? 'selected' : '' }}>{{ $batch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Age</label>
                    <input type="number" name="age" value="{{ old('age', $student->age) }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" min="5" max="25">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Jersey Number</label>
                    <input type="number" name="jersey_number" value="{{ old('jersey_number', $student->jersey_number) }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" min="1" max="99">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Position</label>
                    <select name="position" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                        <option value="">Not set</option>
                        @foreach(['guard','forward','centre'] as $pos)
                        <option value="{{ $pos }}" {{ old('position',$student->position)===$pos ? 'selected' : '' }}>{{ ucfirst($pos) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Skill Level</label>
                    <select name="skill_level" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" required>
                        @foreach(['beginner','intermediate','advanced'] as $level)
                        <option value="{{ $level }}" {{ old('skill_level',$student->skill_level)===$level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Injury Status</label>
                    <select name="injury_status" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" required>
                        <option value="fit" {{ old('injury_status',$student->injury_status)==='fit' ? 'selected' : '' }}>✅ Fit</option>
                        <option value="injured" {{ old('injury_status',$student->injury_status)==='injured' ? 'selected' : '' }}>🚨 Injured</option>
                        <option value="recovering" {{ old('injury_status',$student->injury_status)==='recovering' ? 'selected' : '' }}>⚠️ Recovering</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Parent Name</label>
                    <input type="text" name="parent_name" value="{{ old('parent_name', $student->parent_name) }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Contact Number</label>
                    <input type="text" name="parent_contact" value="{{ old('parent_contact', $student->parent_contact) }}" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">
                        Joining Date
                    </label>
                    <input type="date" name="joined_at"
                        value="{{ old('joined_at', $student->joined_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                        class="w-full h-9 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white" required>
                    <p class="text-xs text-gray-400 mt-1">
                        Student will not appear in sessions before this date.
                    </p>
                </div>

            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl transition-colors">Save Changes</button>
            </form>
            <form action="{{ route('students.destroy', $student) }}" method="POST" onsubmit="return confirm('Remove {{ $student->full_name }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="px-5 py-3 border border-red-200 text-red-500 rounded-xl hover:bg-red-50">Remove</button>
            </form>
        </div>
    
</div>
@push('scripts')
<script>
document.getElementById('photo').addEventListener('change', function(e) {
    const file = e.target.files[0]; if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => { document.getElementById('preview-img').src = ev.target.result; };
    reader.readAsDataURL(file);
});
</script>
@endpush
@endsection
