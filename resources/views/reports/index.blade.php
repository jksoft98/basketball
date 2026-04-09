@extends('layouts.app')
@section('title', 'Reports')

@section('content')

<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Reports & Analytics</h1>
        <p class="text-sm text-gray-500 mt-1" id="report-period">{{ \Carbon\Carbon::parse($month)->format('F Y') }}</p>
    </div>
    <div class="flex gap-2 flex-wrap">
        <input type="month" id="month-filter" value="{{ $month }}"
               class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
        <select id="batch-filter" class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
            <option value="">All Batches</option>
            @foreach($batches as $b)
            <option value="{{ $b->id }}" {{ $selectedBatchId==$b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        <div id="report-spinner" class="hidden items-center px-1">
            <div class="w-4 h-4 border-2 border-orange-400 border-t-transparent rounded-full animate-spin"></div>
        </div>
    </div>
</div>

<div id="report-body" class="transition-opacity duration-150">

{{-- Stat cards (ba-ajax style) --}}
@php $totalStudentsAll = $batchStats->sum('students_count'); @endphp
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
        <div id="stat-avg" class="text-3xl font-bold text-orange-500">{{ round($batchStats->avg('attendance_rate')) }}%</div>
        <div class="text-xs text-gray-500 mt-1">Average attendance</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
        <div id="stat-sessions" class="text-3xl font-bold text-gray-900">{{ $monthlySessions->count() }}</div>
        <div class="text-xs text-gray-500 mt-1">Sessions this month</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
        <div class="text-3xl font-bold text-gray-900">{{ $totalStudentsAll }}</div>
        <div class="text-xs text-gray-500 mt-1">Active students</div>
    </div>
    <div class="bg-white rounded-xl border {{ $atRiskStudents->count()>0 ? 'border-l-4 border-red-400' : 'border-gray-200' }} p-4 text-center">
        <div id="stat-risk" class="text-3xl font-bold {{ $atRiskStudents->count()>0 ? 'text-red-600' : 'text-gray-900' }}">{{ $atRiskStudents->count() }}</div>
        <div class="text-xs text-gray-500 mt-1">At-risk students</div>
    </div>
</div>

{{-- Trend --}}
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-4">Attendance trend — last 6 months</h2>
    <div class="flex items-end gap-3 h-28" id="trend-chart">
        @foreach($trend as $i => $point)
        @php $isLast = $i === $trend->count()-1; @endphp
        <div class="flex-1 flex flex-col items-center gap-1" id="trend-bar-wrap-{{ $i }}">
            <span id="trend-bar-val-{{ $i }}" class="text-xs font-medium {{ $isLast ? 'text-orange-500' : 'text-gray-500' }}">{{ $point['rate'] }}%</span>
            <div class="w-full rounded-t-md" id="trend-bar-{{ $i }}"
                 style="height:{{ max(4,$point['rate']) }}px;background:{{ $isLast ? '#f97316' : '#e5e7eb' }}"></div>
            <span class="text-[10px] {{ $isLast ? 'font-semibold text-gray-800' : 'text-gray-400' }}">{{ $point['label'] }}</span>
        </div>
        @endforeach
    </div>
</div>

{{-- At-risk + Top attendees --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">At-risk students <span class="text-xs font-normal text-red-500 ml-1">below 75%</span></h2>
        <div id="at-risk-list">
            @forelse($atRiskStudents as $student)
            @php $pct = $student->attendancePercentage(); @endphp
            <div class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-0">
                <img src="{{asset('storage/' . $student->photo_thumb_path) }}" alt="{{ $student->full_name }}" class="w-9 h-9 rounded-full object-cover bg-gray-100 flex-shrink-0">
                <div class="flex-1 min-w-0">
                    <a href="{{ route('reports.student', $student) }}" class="text-sm font-medium text-gray-900 hover:text-orange-500 truncate block">{{ $student->full_name }}</a>
                    <div class="text-xs text-gray-400">{{ $student->batch->name }}@if($student->jersey_number) &middot; #{{ $student->jersey_number }}@endif</div>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="text-sm font-bold {{ $pct<60 ? 'text-red-600' : 'text-yellow-600' }}">{{ $pct }}%</div>
                    <div class="w-16 h-1.5 bg-gray-100 rounded-full mt-1">
                        <div class="h-1.5 rounded-full {{ $pct<60 ? 'bg-red-400' : 'bg-yellow-400' }}" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-6 text-gray-400"><div class="text-3xl mb-2">🎉</div><p class="text-sm">All students above 75%</p></div>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Top attendees</h2>
        @forelse($topStudents as $i => $student)
        <div class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-0">
            <div class="w-6 text-center text-xs font-bold {{ $i===0?'text-yellow-500':($i===1?'text-gray-400':'text-orange-400') }}">#{{ $i+1 }}</div>
            <img src="{{asset('storage/' . $student->photo_thumb_path) }}" alt="{{ $student->full_name }}" class="w-9 h-9 rounded-full object-cover bg-gray-100 flex-shrink-0">
            <div class="flex-1 min-w-0">
                <a href="{{ route('reports.student', $student) }}" class="text-sm font-medium text-gray-900 hover:text-orange-500 truncate block">{{ $student->full_name }}</a>
                <div class="text-xs text-gray-400">{{ $student->batch->name }}</div>
            </div>
            <div class="text-right flex-shrink-0">
                <div class="text-sm font-bold text-green-600">{{ $student->attendancePercentage() }}%</div>
                <div class="w-16 h-1.5 bg-gray-100 rounded-full mt-1">
                    <div class="h-1.5 bg-green-400 rounded-full" style="width:{{ $student->attendancePercentage() }}%"></div>
                </div>
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-400 py-4 text-center">No data yet.</p>
        @endforelse
    </div>
</div>

{{-- Batch performance table --}}
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-4">Batch performance</h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100">
                <th class="text-left text-xs text-gray-500 font-medium pb-2">Batch</th>
                <th class="text-left text-xs text-gray-500 font-medium pb-2">Coach</th>
                <th class="text-center text-xs text-gray-500 font-medium pb-2">Students</th>
                <th class="text-center text-xs text-gray-500 font-medium pb-2">Sessions</th>
                <th class="text-left text-xs text-gray-500 font-medium pb-2">Attendance rate</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($batchStats as $batch)
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="py-3 font-medium text-gray-900">{{ $batch->name }}</td>
                <td class="py-3 text-gray-500">{{ $batch->coach->name ?? '—' }}</td>
                <td class="py-3 text-center text-gray-700">{{ $batch->students_count }}</td>
                <td class="py-3 text-center text-gray-700">{{ $batch->session_count }}</td>
                <td class="py-3">
                    <div class="flex items-center gap-3">
                        <div class="flex-1 h-2 bg-gray-100 rounded-full max-w-[120px]">
                            <div class="h-2 rounded-full {{ $batch->attendance_rate>=80?'bg-green-400':($batch->attendance_rate>=60?'bg-yellow-400':'bg-red-400') }}"
                                 style="width:{{ $batch->attendance_rate }}%"></div>
                        </div>
                        <span class="text-sm font-semibold {{ $batch->attendance_rate>=80?'text-green-600':($batch->attendance_rate>=60?'text-yellow-600':'text-red-600') }}">{{ $batch->attendance_rate }}%</span>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Session breakdown — DataTables AJAX --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
        <h2 class="text-sm font-semibold text-gray-700">
            Session breakdown — <span id="breakdown-period">{{ \Carbon\Carbon::parse($month)->format('F Y') }}</span>
        </h2>
        <form method="GET" action="{{ route('reports.export') }}" class="flex gap-2">
            <input type="hidden" id="export-month" name="month" value="{{ $month }}">
            <select name="batch_id" required class="h-8 px-2 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none">
                <option value="">Select batch</option>
                @foreach($batches as $b)
                <option value="{{ $b->id }}" {{ $selectedBatchId==$b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="h-8 px-3 text-xs font-medium bg-orange-500 text-white rounded-lg hover:bg-orange-600">⬇ CSV</button>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table id="report-sessions-table" class="w-full" style="width:100%">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Batch</th>
                    <th>Type</th>
                    <th>Attendance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

</div>{{-- #report-body --}}

@push('scripts')
<script>
let dtReportSessions;

function buildDtAjaxUrl() {
    const month   = document.getElementById('month-filter').value;
    const batchId = document.getElementById('batch-filter').value;
    return `{{ route('dt.report.sessions') }}?month=${month}&batch_id=${batchId}`;
}

$(document).ready(function() {
    dtReportSessions = $('#report-sessions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:    buildDtAjaxUrl(),
            type: 'GET',
        },
        columns: [
            { data: 'date',       orderable: true,  searchable: false },
            { data: 'batch',      orderable: false, searchable: true  },
            { data: 'type',       orderable: false, searchable: false },
            { data: 'attendance', orderable: false, searchable: false },
            { data: 'action',     orderable: false, searchable: false },
        ],
        order: [[0, 'desc']],
        pageLength: 15,
        scrollX: true, // ✅ ADD THIS
        language: {
            search:            '',
            searchPlaceholder: '🔍 Search sessions…',
            lengthMenu:        'Show _MENU_',
            info:              '_START_–_END_ of _TOTAL_ sessions',
            paginate:          { previous: '‹', next: '›' },
            processing:        'Loading…',
            emptyTable:        'No sessions for this period.',
        },
        dom: '<"dt-bar dt-bar-top"lf>rt<"dt-bar dt-bar-bottom"ip>',
    });
});

// AJAX filter
const REPORTS_URL = "{{ route('reports.index') }}";

['month-filter','batch-filter'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', fetchReport);
});

async function fetchReport() {
    const month   = document.getElementById('month-filter').value;
    const batchId = document.getElementById('batch-filter').value;

    document.getElementById('report-body').style.opacity = '0.45';
    document.getElementById('report-spinner').classList.remove('hidden');
    document.getElementById('report-spinner').classList.add('flex');
    document.getElementById('export-month').value = month;
    document.getElementById('report-period').textContent    = new Date(month+'-01').toLocaleDateString('en-GB',{month:'long',year:'numeric'});
    document.getElementById('breakdown-period').textContent = new Date(month+'-01').toLocaleDateString('en-GB',{month:'long',year:'numeric'});

    try {
        const res  = await fetch(`${REPORTS_URL}?month=${month}&batch_id=${batchId}&ajax=1`, {
            headers: { 'Accept':'application/json','X-CSRF-TOKEN':CSRF }
        });
        const data = await res.json();

        document.getElementById('stat-avg').textContent      = data.avg_rate + '%';
        document.getElementById('stat-sessions').textContent = data.total_sessions;
        document.getElementById('stat-risk').textContent     = data.at_risk_count;
        document.getElementById('stat-risk').className = `text-3xl font-bold ${data.at_risk_count > 0 ? 'text-red-600' : 'text-gray-900'}`;

        data.trend.forEach((pt, i) => {
            const bar = document.getElementById(`trend-bar-${i}`);
            const val = document.getElementById(`trend-bar-val-${i}`);
            if (bar) bar.style.height = Math.max(4, pt.rate) + 'px';
            if (val) val.textContent = pt.rate + '%';
        });

        const el = document.getElementById('at-risk-list');
        if (!data.at_risk_students.length) {
            el.innerHTML = '<div class="text-center py-6 text-gray-400"><div class="text-3xl mb-2">🎉</div><p class="text-sm">All students above 75%</p></div>';
        } else {
            el.innerHTML = data.at_risk_students.map(s => `
                <div class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-0">
                    <img src="${s.thumb_url}" alt="${s.name}" class="w-9 h-9 rounded-full object-cover bg-gray-100 flex-shrink-0">
                    <div class="flex-1 min-w-0">
                        <a href="${s.url}" class="text-sm font-medium text-gray-900 hover:text-orange-500 truncate block">${s.name}</a>
                        <div class="text-xs text-gray-400">${s.batch}</div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-sm font-bold ${s.pct<60?'text-red-600':'text-yellow-600'}">${s.pct}%</div>
                        <div class="w-16 h-1.5 bg-gray-100 rounded-full mt-1">
                            <div class="h-1.5 rounded-full ${s.pct<60?'bg-red-400':'bg-yellow-400'}" style="width:${s.pct}%"></div>
                        </div>
                    </div>
                </div>`).join('');
        }

        // Reload the DataTable with new month/batch
        dtReportSessions.ajax.url(buildDtAjaxUrl()).load();

    } catch(e) {}

    document.getElementById('report-body').style.opacity = '1';
    document.getElementById('report-spinner').classList.add('hidden');
    document.getElementById('report-spinner').classList.remove('flex');
}

function buildDtAjaxUrl() {
    const month   = document.getElementById('month-filter').value;
    const batchId = document.getElementById('batch-filter').value;
    return `{{ route('dt.report.sessions') }}?month=${month}&batch_id=${batchId}`;
}
</script>
@endpush
@endsection
