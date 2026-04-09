@extends('layouts.app')
@section('title', $student->full_name . ' — Report')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('reports.index') }}" class="text-gray-400 hover:text-gray-600 transition-colors">← Reports</a>
    <h1 class="text-xl font-bold text-gray-900">{{ $student->full_name }}</h1>
    @if($student->isAtRisk())
    <span class="text-xs font-medium px-2 py-0.5 bg-red-100 text-red-600 rounded-full">⚠ At Risk</span>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Left: profile --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="relative">
                <img src="{{  asset('storage/' . $student->photo_path) }}" alt="{{ $student->full_name }}"
                     class="w-full aspect-square object-cover bg-gray-100">
                @if($student->jersey_number)
                <span class="absolute top-2.5 left-2.5 text-sm font-bold bg-black/60 text-white px-2.5 py-1 rounded-lg">
                    #{{ $student->jersey_number }}
                </span>
                @endif
                <span class="absolute top-2.5 right-2.5 text-xs font-medium px-2 py-0.5 rounded-full
                    @if($student->injury_status==='fit') bg-green-100 text-green-700
                    @elseif($student->injury_status==='injured') bg-red-100 text-red-700
                    @else bg-yellow-100 text-yellow-700 @endif">
                    {{ ucfirst($student->injury_status) }}
                </span>
            </div>
            <div class="p-4">
                <p class="font-bold text-gray-900 text-base">{{ $student->full_name }}</p>
                <p class="text-xs text-gray-500 mt-1">
                    {{ $student->batch->name }}
                    @if($student->position) &middot; {{ ucfirst($student->position) }} @endif
                    @if($student->age) &middot; Age {{ $student->age }} @endif
                </p>
                <div class="flex gap-4 mt-3 pt-3 border-t border-gray-100">
                    <div class="text-center">
                        <div class="text-xl font-bold text-orange-500">{{ $allTime['pct'] }}%</div>
                        <div class="text-[10px] text-gray-400">Overall</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-bold text-green-600">{{ $allTime['present'] }}</div>
                        <div class="text-[10px] text-gray-400">Present</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-bold text-yellow-500">{{ $allTime['late'] }}</div>
                        <div class="text-[10px] text-gray-400">Late</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-bold text-red-500">{{ $allTime['absent'] }}</div>
                        <div class="text-[10px] text-gray-400">Absent</div>
                    </div>
                    @if($streak > 0)
                    <div class="text-center">
                        <div class="text-xl font-bold text-blue-500">{{ $streak }}</div>
                        <div class="text-[10px] text-gray-400">Streak 🔥</div>
                    </div>
                    @endif
                </div>
                <div class="mt-3">
                    @php $pct = $allTime['pct']; @endphp
                    <div class="w-full h-2 bg-gray-100 rounded-full">
                        <div class="h-2 rounded-full {{ $pct>=80?'bg-green-400':($pct>=60?'bg-yellow-400':'bg-red-400') }}"
                             style="width:{{ $pct }}%"></div>
                    </div>
                    @if($student->isAtRisk())
                    <p class="text-xs text-red-500 mt-1.5 font-medium">⚠ Below 75% — consider contacting parent</p>
                    @endif
                </div>
            </div>
        </div>

        @if($student->parent_name || $student->parent_contact)
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Parent / Guardian</h3>
            @if($student->parent_name)<p class="text-sm text-gray-600 mb-1">👤 {{ $student->parent_name }}</p>@endif
            @if($student->parent_contact)<p class="text-sm text-gray-600"><a href="tel:{{ $student->parent_contact }}" class="text-orange-500 hover:text-orange-700">📞 {{ $student->parent_contact }}</a></p>@endif
        </div>
        @endif
    </div>

    {{-- Right: session history DataTable AJAX --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-gray-700">Session History</h3>
                <div class="flex gap-2">
                    <input type="month" id="month-picker" value="{{ $month }}"
                           class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
                    <button onclick="changeMonth()" class="h-9 px-4 text-sm bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition-colors">View</button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table id="student-history-table" class="w-full" style="width:100%">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let dtHistory;

$(document).ready(function() {
    dtHistory = $('#student-history-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  buildHistoryUrl(),
            type: 'GET',
        },
        columns: [
            { data: 'date',   orderable: true  },
            { data: 'type',   orderable: false },
            { data: 'time',   orderable: false },
            { data: 'status', orderable: false },
            { data: 'note',   orderable: false },
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        scrollX: true, // ✅ ADD THIS
        language: {
            search:            '',
            searchPlaceholder: '🔍 Search…',
            lengthMenu:        'Show _MENU_',
            info:              '_TOTAL_ sessions',
            paginate:          { previous: '‹', next: '›' },
            processing:        'Loading…',
            emptyTable:        'No sessions this month.',
        },
        dom: '<"dt-bar dt-bar-top"lf>rt<"dt-bar dt-bar-bottom"ip>',
    });
});

function buildHistoryUrl() {
    const month = document.getElementById('month-picker').value;
    return `{{ route('dt.student.history', $student) }}?month=${month}`;
}

function changeMonth() {
    dtHistory.ajax.url(buildHistoryUrl()).load();
}
</script>
@endpush
@endsection
