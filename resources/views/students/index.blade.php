@extends('layouts.app')
@section('title', 'Students')
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Students</h1>
        <p class="text-sm text-gray-500 mt-1"><span id="student-count">{{ $students->total() }}</span> active students</p>
    </div>
    <a href="{{ route('students.create') }}" class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">+ Add Student</a>
</div>

{{-- Filters (AJAX-powered) --}}
<div class="flex flex-wrap gap-3 mb-5">
    <div class="relative flex-1 min-w-[200px]">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
        <input type="text" id="search-input" value="{{ request('search') }}" placeholder="Search by name…"
               class="w-full pl-9 pr-4 h-9 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
    </div>
    <select id="batch-filter" class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
        <option value="">All Batches</option>
        @foreach($batches as $batch)<option value="{{ $batch->id }}" {{ request('batch_id')==$batch->id?'selected':'' }}>{{ $batch->name }}</option>@endforeach
    </select>
    <select id="level-filter" class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
        <option value="">All Levels</option>
        <option value="beginner" {{ request('skill_level')==='beginner'?'selected':'' }}>Beginner</option>
        <option value="intermediate" {{ request('skill_level')==='intermediate'?'selected':'' }}>Intermediate</option>
        <option value="advanced" {{ request('skill_level')==='advanced'?'selected':'' }}>Advanced</option>
    </select>
    <select id="injury-filter" class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
        <option value="">All Status</option>
        <option value="fit" {{ request('injury_status')==='fit'?'selected':'' }}>Fit</option>
        <option value="injured" {{ request('injury_status')==='injured'?'selected':'' }}>Injured</option>
        <option value="recovering" {{ request('injury_status')==='recovering'?'selected':'' }}>Recovering</option>
    </select>
    <div id="search-spinner" class="hidden flex items-center"><div class="w-4 h-4 border-2 border-orange-400 border-t-transparent rounded-full animate-spin"></div></div>
</div>

<div id="student-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-6">
    @forelse($students as $student)
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:border-orange-400 hover:shadow-sm transition-all group">
        <div class="relative aspect-square bg-gray-100">
            <img src="{{asset('storage/' . $student->photo_thumb_path) }}" alt="{{ $student->full_name }}" loading="lazy" class="w-full h-full object-cover">
            @if($student->isAtRisk())<div class="absolute top-1.5 left-1.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white" title="At risk"></div>@endif

            {{-- Injury badge — clickable for inline toggle --}}
            @if($student->injury_status !== 'fit')
            <button class="injury-toggle absolute top-1.5 right-1.5 text-[9px] font-medium px-1.5 py-0.5 rounded-full {{ $student->injury_status==='injured' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}"
                    data-student-id="{{ $student->id }}"
                    data-status="{{ $student->injury_status }}"
                    title="Click to update injury status">
                {{ $student->injury_status==='injured' ? 'Injured' : 'Recovering' }}
            </button>
            @else
            <button class="injury-toggle absolute top-1.5 right-1.5 text-[9px] font-medium px-1.5 py-0.5 rounded-full bg-green-100 text-green-700 opacity-0 group-hover:opacity-100 transition-opacity"
                    data-student-id="{{ $student->id }}"
                    data-status="fit"
                    title="Click to update injury status">
                Fit
            </button>
            @endif

            @if($student->jersey_number)<span class="absolute bottom-1.5 left-1.5 text-[9px] font-bold bg-black/55 text-white px-1.5 py-0.5 rounded">#{{ $student->jersey_number }}</span>@endif
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100">
                <a href="{{ route('students.edit', $student) }}" class="text-[11px] bg-white text-gray-800 px-2 py-1 rounded font-medium hover:bg-orange-500 hover:text-white transition-colors">Edit</a>
                <a href="{{ route('reports.student', $student) }}" class="text-[11px] bg-white text-gray-800 px-2 py-1 rounded font-medium hover:bg-orange-500 hover:text-white transition-colors">History</a>
            </div>
        </div>
        <div class="p-2.5">
            <div class="text-[11px] font-semibold text-gray-900 truncate">{{ $student->full_name }}</div>
            <div class="text-[10px] text-gray-400 mt-0.5 truncate">{{ $student->batch->name }}</div>
            <span class="inline-block mt-1.5 text-[10px] px-1.5 py-0.5 rounded-full font-medium @if($student->skill_level==='beginner') bg-green-100 text-green-700 @elseif($student->skill_level==='intermediate') bg-blue-100 text-blue-700 @else bg-purple-100 text-purple-700 @endif">{{ ucfirst($student->skill_level) }}</span>
        </div>
    </div>
    @empty
    <div id="no-results" class="col-span-6 text-center py-16 text-gray-400">
        <div class="text-5xl mb-3">🏀</div>
        <p class="text-lg font-medium">No students found</p>
        <p class="text-sm mt-1">Try adjusting your filters.</p>
    </div>
    @endforelse
</div>

<div id="pagination-wrap">{{ $students->withQueryString()->links() }}</div>

@push('scripts')
<script>
const CSRF          = document.querySelector('meta[name="csrf-token"]').content;
const STUDENTS_URL  = "{{ route('students.index') }}";
const INJURY_URL    = id => `/students/${id}/injury-status`;

const searchInput  = document.getElementById('search-input');
const batchFilter  = document.getElementById('batch-filter');
const levelFilter  = document.getElementById('level-filter');
const injuryFilter = document.getElementById('injury-filter');
const spinner      = document.getElementById('search-spinner');
let   searchTimer;

// ── Live AJAX search ──────────────────────────────────────────────
function fetchStudents() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        spinner.classList.remove('hidden');
        const params = new URLSearchParams({
            search:        searchInput.value,
            batch_id:      batchFilter.value,
            skill_level:   levelFilter.value,
            injury_status: injuryFilter.value,
            ajax:          1,
        });
        try {
            const res  = await fetch(`${STUDENTS_URL}?${params}`,{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}});
            const data = await res.json();
            document.getElementById('student-count').textContent = data.total;
            renderStudents(data.students);
            document.getElementById('pagination-wrap').innerHTML = '';
        } catch(e) {}
        spinner.classList.add('hidden');
    }, 350);
}

function renderStudents(students) {
    const grid = document.getElementById('student-grid');
    grid.innerHTML = '';
    if (!students.length) {
        grid.innerHTML = `<div class="col-span-6 text-center py-16 text-gray-400"><div class="text-5xl mb-3">🏀</div><p class="text-lg font-medium">No students found</p></div>`;
        return;
    }
    const injuryLabels  = {fit:'Fit',injured:'Injured',recovering:'Recovering'};
    const injuryClasses = {
        fit:        'bg-green-100 text-green-700 opacity-0 group-hover:opacity-100',
        injured:    'bg-red-100 text-red-700',
        recovering: 'bg-yellow-100 text-yellow-700',
    };
    const levelClasses = {
        beginner:    'bg-green-100 text-green-700',
        intermediate:'bg-blue-100 text-blue-700',
        advanced:    'bg-purple-100 text-purple-700',
    };
    students.forEach(s => {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-xl border border-gray-200 overflow-hidden hover:border-orange-400 hover:shadow-sm transition-all group';
        card.innerHTML = `
            <div class="relative aspect-square bg-gray-100">
                <img src="${s.thumb_url}" alt="${s.full_name}" loading="lazy" class="w-full h-full object-cover">
                ${s.is_at_risk ? '<div class="absolute top-1.5 left-1.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white" title="At risk"></div>' : ''}
                <button class="injury-toggle absolute top-1.5 right-1.5 text-[9px] font-medium px-1.5 py-0.5 rounded-full transition-opacity ${injuryClasses[s.injury_status]}"
                        data-student-id="${s.id}" data-status="${s.injury_status}" title="Click to update injury status">
                    ${injuryLabels[s.injury_status]}
                </button>
                ${s.jersey_number ? `<span class="absolute bottom-1.5 left-1.5 text-[9px] font-bold bg-black/55 text-white px-1.5 py-0.5 rounded">#${s.jersey_number}</span>` : ''}
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100">
                    <a href="${s.edit_url}" class="text-[11px] bg-white text-gray-800 px-2 py-1 rounded font-medium hover:bg-orange-500 hover:text-white transition-colors">Edit</a>
                    <a href="${s.history_url}" class="text-[11px] bg-white text-gray-800 px-2 py-1 rounded font-medium hover:bg-orange-500 hover:text-white transition-colors">History</a>
                </div>
            </div>
            <div class="p-2.5">
                <div class="text-[11px] font-semibold text-gray-900 truncate">${s.full_name}</div>
                <div class="text-[10px] text-gray-400 mt-0.5 truncate">${s.batch_name}</div>
                <span class="inline-block mt-1.5 text-[10px] px-1.5 py-0.5 rounded-full font-medium ${levelClasses[s.skill_level]}">${s.skill_level.charAt(0).toUpperCase()+s.skill_level.slice(1)}</span>
            </div>`;
        grid.appendChild(card);
    });
    initInjuryToggles();
}

// ── Inline injury toggle ──────────────────────────────────────────
function initInjuryToggles() {
    document.querySelectorAll('.injury-toggle').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const id     = btn.dataset.studentId;
            const cycle  = {fit:'injured',injured:'recovering',recovering:'fit'};
            const next   = cycle[btn.dataset.status];
            const labels = {fit:'Fit',injured:'Injured',recovering:'Recovering'};
            const cls    = {fit:'bg-green-100 text-green-700 opacity-0 group-hover:opacity-100',injured:'bg-red-100 text-red-700',recovering:'bg-yellow-100 text-yellow-700'};

            // Optimistic update
            btn.textContent  = labels[next];
            btn.dataset.status = next;
            btn.className = `injury-toggle absolute top-1.5 right-1.5 text-[9px] font-medium px-1.5 py-0.5 rounded-full transition-opacity ${cls[next]}`;

            try {
                await fetch(INJURY_URL(id), {
                    method:'PATCH',
                    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
                    body:JSON.stringify({injury_status:next}),
                });
            } catch(e) {
                // Revert on failure
                const prev = cycle[next] === btn.dataset.status ? Object.keys(cycle).find(k=>cycle[k]===next) : btn.dataset.status;
                btn.textContent = labels[prev]; btn.dataset.status = prev;
            }
        });
    });
}

searchInput.addEventListener('input', fetchStudents);
batchFilter.addEventListener('change', fetchStudents);
levelFilter.addEventListener('change', fetchStudents);
injuryFilter.addEventListener('change', fetchStudents);

// Init toggles on page load
initInjuryToggles();
</script>
@endpush
@endsection
