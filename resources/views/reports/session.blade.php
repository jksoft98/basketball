@extends('layouts.app')
@section('title', 'Session Report')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('reports.index') }}" class="text-gray-400 hover:text-gray-600 transition-colors">← Reports</a>
    <h1 class="text-xl font-bold text-gray-900">Session Report</h1>
</div>

{{-- Session summary (ba-ajax style) --}}
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
    <div class="flex items-start justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-900">{{ $session->batch->name }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                {{ ucfirst($session->session_type) }} &middot;
                {{ $session->session_date->format('D d M Y') }}
                @if($session->session_time) &middot; {{ \Carbon\Carbon::parse($session->session_time)->format('g:i A') }} @endif
                &middot; Coach: {{ $session->batch->coach->name }}
            </p>
            @if($session->notes)<p class="text-xs text-gray-400 mt-1.5 italic">"{{ $session->notes }}"</p>@endif
        </div>
        <a href="{{ route('attendance.index', $session) }}"
           class="text-xs font-medium px-3 py-1.5 border border-orange-300 text-orange-600 rounded-lg hover:bg-orange-50 transition-colors flex-shrink-0">
            Edit Attendance
        </a>
    </div>
    @php $pct = $summary['total']>0 ? round((($summary['present']+$summary['late'])/$summary['total'])*100) : 0; @endphp
    <div class="grid grid-cols-4 gap-3 mt-4 pt-4 border-t border-gray-100">
        <div class="text-center"><div class="text-2xl font-bold text-green-600">{{ $summary['present'] }}</div><div class="text-xs text-gray-400 mt-0.5">Present</div></div>
        <div class="text-center"><div class="text-2xl font-bold text-yellow-500">{{ $summary['late'] }}</div><div class="text-xs text-gray-400 mt-0.5">Late</div></div>
        <div class="text-center"><div class="text-2xl font-bold text-red-500">{{ $summary['absent'] }}</div><div class="text-xs text-gray-400 mt-0.5">Absent</div></div>
        <div class="text-center"><div class="text-2xl font-bold text-orange-500">{{ $pct }}%</div><div class="text-xs text-gray-400 mt-0.5">Attendance</div></div>
    </div>
    <div class="mt-3 h-2 bg-gray-100 rounded-full overflow-hidden">
        <div class="h-2 rounded-full {{ $pct>=80?'bg-green-400':($pct>=60?'bg-yellow-400':'bg-red-400') }}" style="width:{{ $pct }}%"></div>
    </div>
</div>

{{-- Attendance DataTable AJAX --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100">
        <h2 class="text-sm font-semibold text-gray-700">Student Attendance — {{ $students->count() }} students</h2>
    </div>
    <div class="overflow-x-auto">
        <table id="session-attendance-table" class="w-full" style="width:100%">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Jersey</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Overall %</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#session-attendance-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  "{{ route('dt.session.attendance', $session) }}",
            type: 'GET',
        },
        columns: [
            { data: 'photo',    orderable: true,  searchable: true  },
            { data: 'jersey',   orderable: false, searchable: false },
            { data: 'position', orderable: false, searchable: false },
            { data: 'status',   orderable: false, searchable: false },
            { data: 'overall',  orderable: true,  searchable: false },
            { data: 'note',     orderable: false, searchable: false },
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        scrollX: true, // ✅ ADD THIS
        language: {
            search:            '',
            searchPlaceholder: '🔍 Search students…',
            lengthMenu:        'Show _MENU_',
            info:              '_TOTAL_ students',
            paginate:          { previous: '‹', next: '›' },
            processing:        'Loading…',
        },
        dom: '<"dt-bar dt-bar-top"lf>rt<"dt-bar dt-bar-bottom"ip>',
    });
});
</script>
@endpush
@endsection
