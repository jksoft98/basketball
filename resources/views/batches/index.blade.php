@extends('layouts.app')
@section('title', 'Batches')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Batches</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $batches->count() }} total batches</p>
    </div>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('batches.create') }}"
       class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        + New Batch
    </a>
    @endif
</div>

@if($batches->isEmpty())
<div class="bg-white rounded-xl border border-gray-200 flex flex-col items-center py-16 text-gray-400">
    <div class="text-5xl mb-3">📋</div>
    <p class="text-lg font-medium">No batches yet</p>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('batches.create') }}" class="mt-3 text-sm text-orange-500 hover:text-orange-600">Create your first batch →</a>
    @endif
</div>
@else

{{-- Batch cards (ba-ajax style exactly) --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    @foreach($batches as $batch)
    <div class="bg-white rounded-xl border border-gray-200 p-5 hover:border-orange-300 hover:shadow-sm transition-all">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h2 class="font-semibold text-gray-900">{{ $batch->name }}</h2>
                @if($batch->description)<p class="text-xs text-gray-500 mt-0.5">{{ $batch->description }}</p>@endif
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full font-medium flex-shrink-0 ml-2
                @if(!$batch->is_active) bg-gray-100 text-gray-500
                @elseif($batch->skill_level==='beginner') bg-green-100 text-green-700
                @elseif($batch->skill_level==='intermediate') bg-blue-100 text-blue-700
                @else bg-purple-100 text-purple-700 @endif">
                {{ $batch->is_active ? ucfirst($batch->skill_level) : 'Inactive' }}
            </span>
        </div>
        <div class="flex gap-4 py-3 border-y border-gray-100 mb-3">
            <div class="text-center">
                <div class="text-lg font-bold text-gray-900">{{ $batch->students_count }}</div>
                <div class="text-[11px] text-gray-400">Students</div>
            </div>
            <div class="text-center">
                <div class="text-lg font-bold text-gray-900">{{ $batch->sessions()->count() }}</div>
                <div class="text-[11px] text-gray-400">Sessions</div>
            </div>
            <div class="text-center flex-1">
                <div class="text-sm font-medium text-gray-700 truncate">{{ $batch->coach->name ?? '—' }}</div>
                <div class="text-[11px] text-gray-400">Coach</div>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('sessions.create', ['batch_id' => $batch->id]) }}"
               class="flex-1 text-center text-xs font-medium py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors">+ Session</a>
            <a href="{{ route('students.index', ['batch_id' => $batch->id]) }}"
               class="flex-1 text-center text-xs font-medium py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors">Students</a>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('batches.edit', $batch) }}"
               class="px-3 text-xs font-medium py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors">Edit</a>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- DataTable for sortable overview --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100">
        <h2 class="text-sm font-semibold text-gray-700">Batch Overview</h2>
        <p class="text-xs text-gray-400 mt-0.5">Click column headers to sort</p>
    </div>
    <div class="overflow-x-auto">
        <table id="batches-table" class="w-full" style="width:100%">
            <thead>
                <tr>
                    <th>Batch Name</th>
                    <th>Coach</th>
                    <th>Skill Level</th>
                    <th>Students</th>
                    <th>Sessions</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@endif

@push('scripts')
<script>
$(document).ready(function() {
    $('#batches-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  "{{ route('dt.batches') }}",
            type: 'GET',
        },
        columns: [
            { data: 'name',     orderable: true,  searchable: true  },
            { data: 'coach',    orderable: false, searchable: false },
            { data: 'level',    orderable: false, searchable: false },
            { data: 'students', orderable: true,  searchable: false },
            { data: 'sessions', orderable: false, searchable: false },
            { data: 'status',   orderable: true,  searchable: false },
            { data: 'actions',  orderable: false, searchable: false },
        ],
        order: [[3, 'desc']],
        pageLength: 10,
        scrollX: true, // ✅ ADD THIS
        language: {
            search:            '',
            searchPlaceholder: '🔍 Search batches…',
            lengthMenu:        'Show _MENU_',
            info:              '_START_–_END_ of _TOTAL_ batches',
            paginate:          { previous: '‹', next: '›' },
            processing:        'Loading…',
            emptyTable:        'No batches found.',
        },
        dom: '<"dt-bar dt-bar-top"lf>rt<"dt-bar dt-bar-bottom"ip>',
    });
});
</script>
@endpush
@endsection
