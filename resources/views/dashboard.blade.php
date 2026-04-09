@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-sm text-gray-500 mt-1">{{ now()->format('l, d M Y') }} · Welcome back, {{ auth()->user()->name }}</p>
    </div>
    <a href="{{ route('sessions.create') }}" class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">+ New Session</a>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-4"><div class="text-2xl mb-1">👥</div><div class="text-xs text-gray-500 mb-1">Total students</div><div class="text-2xl font-bold text-gray-900">{{ $totalStudents }}</div><div class="text-xs text-gray-400 mt-1">{{ $batches->count() }} batches</div></div>
    <div class="bg-white rounded-xl border border-gray-200 p-4"><div class="text-2xl mb-1">📋</div><div class="text-xs text-gray-500 mb-1">Total sessions</div><div class="text-2xl font-bold text-gray-900">{{ $totalSessions }}</div><div class="text-xs text-gray-400 mt-1">All time</div></div>
    <div class="bg-white rounded-xl border border-gray-200 p-4"><div class="text-2xl mb-1">📅</div><div class="text-xs text-gray-500 mb-1">Today's sessions</div><div class="text-2xl font-bold text-gray-900" id="today-count">{{ $todaySessions->count() }}</div><div class="text-xs text-gray-400 mt-1">Scheduled today</div></div>
    <div class="bg-white rounded-xl border-l-4 border-red-400 border-y border-r border-gray-200 p-4"><div class="text-2xl mb-1">⚠️</div><div class="text-xs text-gray-500 mb-1">At-risk students</div><div class="text-2xl font-bold text-red-600">{{ $atRiskStudents->count() }}</div><div class="text-xs text-gray-400 mt-1">Below 75%</div></div>
</div>

@if($todaySessions->isNotEmpty())
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-semibold text-gray-700">Today's sessions <span class="ml-2 text-xs font-normal text-gray-400">{{ now()->format('D d M') }}</span></h2>
        <span class="text-xs text-gray-400" id="refresh-label">Auto-refreshes every minute</span>
    </div>
    <div id="today-sessions-list" class="divide-y divide-gray-100">
        @foreach($todaySessions as $session)
        <div class="flex items-center justify-between py-3" data-session="{{ $session->id }}">
            <div>
                <div class="font-medium text-gray-900 text-sm">{{ $session->batch->name }}</div>
                <div class="text-xs text-gray-500 mt-0.5">{{ ucfirst($session->session_type) }} · {{ $session->session_time ? \Carbon\Carbon::parse($session->session_time)->format('g:i A') : 'No time set' }} · {{ $session->batch->students()->active()->count() }} students</div>
            </div>
            <div class="flex items-center gap-3">
                @if($session->isAttendanceSaved())
                <span class="status-badge text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full font-medium">Saved · {{ $session->presentCount() }} present</span>
                <a href="{{ route('attendance.index', $session) }}" class="action-btn text-xs px-3 py-1.5 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors">Edit</a>
                @else
                <span class="status-badge text-xs px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full font-medium">Not started</span>
                <a href="{{ route('attendance.index', $session) }}" class="action-btn text-xs px-3 py-1.5 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors font-medium">Take attendance</a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">At-risk students</h2>
        @forelse($atRiskStudents as $student)
        <div class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-0">
            <img src="{{asset('storage/' . $student->photo_thumb_path) }}" alt="{{ $student->full_name }}" class="w-9 h-9 rounded-full object-cover bg-gray-100">
            <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-gray-900 truncate">{{ $student->full_name }}</div>
                <div class="text-xs text-gray-400">{{ $student->batch->name }}@if($student->jersey_number) · #{{ $student->jersey_number }}@endif</div>
            </div>
            <div class="text-right">
                <div class="text-sm font-semibold text-red-600">{{ $student->attendancePercentage() }}%</div>
                <div class="w-16 h-1 bg-gray-100 rounded-full mt-1"><div class="h-1 bg-red-400 rounded-full" style="width:{{ $student->attendancePercentage() }}%"></div></div>
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-400 text-center py-4">All students are on track! 🎉</p>
        @endforelse
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-700">My batches</h2>
            @if(auth()->user()->isAdmin())<a href="{{ route('batches.create') }}" class="text-xs text-orange-500 hover:text-orange-600">+ New</a>@endif
        </div>
        <div class="grid grid-cols-2 gap-2">
            @foreach($batches as $batch)
            <a href="{{ route('sessions.create', ['batch_id' => $batch->id]) }}" class="block p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-orange-300 hover:bg-orange-50 transition-colors">
                <div class="text-sm font-medium text-gray-900 truncate">{{ $batch->name }}</div>
                <div class="text-xs text-gray-500 mt-0.5" data-batch-count="{{ $batch->id }}">{{ $batch->students_count }} students</div>
                <span class="inline-block mt-2 text-xs px-2 py-0.5 rounded-full @if($batch->skill_level==='beginner') bg-green-100 text-green-700 @elseif($batch->skill_level==='intermediate') bg-blue-100 text-blue-700 @else bg-purple-100 text-purple-700 @endif">{{ ucfirst($batch->skill_level) }}</span>
            </a>
            @endforeach
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-5">
    <h2 class="text-sm font-semibold text-gray-700 mb-3">Recent sessions</h2>
    <div class="divide-y divide-gray-100">
        @forelse($recentSessions as $session)
        <div class="flex items-center justify-between py-2.5">
            <div>
                <span class="text-sm font-medium text-gray-900">{{ $session->batch->name }}</span>
                <span class="ml-2 text-xs text-gray-400">{{ $session->session_date->format('d M Y') }} · {{ ucfirst($session->session_type) }}</span>
            </div>
            <a href="{{ route('attendance.index', $session) }}" class="text-xs text-gray-400 hover:text-gray-700">View →</a>
        </div>
        @empty
        <p class="text-sm text-gray-400 py-2">No sessions yet.</p>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// Live refresh today's sessions every 60 seconds
async function refreshTodaySessions() {
    try {
        const res  = await fetch("{{ route('dashboard.today-sessions') }}", {headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}});
        const data = await res.json();
        data.sessions.forEach(session => {
            const row  = document.querySelector(`[data-session="${session.id}"]`);
            if (!row) return;
            const badge = row.querySelector('.status-badge');
            const btn   = row.querySelector('.action-btn');
            if (session.is_saved) {
                badge.className = 'status-badge text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full font-medium';
                badge.textContent = `Saved · ${session.present_count} present`;
                btn.className   = 'action-btn text-xs px-3 py-1.5 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors';
                btn.textContent = 'Edit';
                btn.href        = session.attendance_url;
            } else {
                badge.textContent = 'Not started';
                btn.textContent   = 'Take attendance';
            }
        });
    } catch(e) {}
}

// Refresh batch student counts
async function refreshBatchCounts() {
    try {
        const res  = await fetch("{{ route('dashboard.batch-counts') }}", {headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}});
        const data = await res.json();
        data.batches.forEach(b => {
            const el = document.querySelector(`[data-batch-count="${b.id}"]`);
            if (el) el.textContent = `${b.count} students`;
        });
    } catch(e) {}
}

setInterval(refreshTodaySessions, 60000);
setInterval(refreshBatchCounts, 120000);
</script>
@endpush
@endsection
