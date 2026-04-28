@extends('layouts.app')
@section('title', 'Attendance — ' . $session->batch->name)

@section('content')

<div class="sticky top-16 z-30 bg-gray-900 text-white rounded-xl mb-4 overflow-hidden shadow-lg">
    <div class="flex items-center justify-between px-5 py-3">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('sessions.index') }}" class="text-gray-400 hover:text-white text-sm">←</a>
                <h1 class="font-semibold text-base">{{ $session->batch->name }}</h1>
                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-700 text-gray-300">{{ ucfirst($session->session_type) }}</span>
            </div>
            <p class="text-xs text-gray-400 mt-0.5 ml-5">
                {{ $session->session_date->format('D d M Y') }}
                @if($session->session_time) · {{ \Carbon\Carbon::parse($session->session_time)->format('g:i A') }} @endif
                · <span id="total-count">{{ $totalStudents }}</span> students
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span id="saved-badge" class="{{ $isSaved ? '' : 'hidden' }} text-xs px-3 py-1 bg-green-500/20 text-green-400 rounded-full font-medium border border-green-500/30">✓ Saved</span>
            <span id="saving-badge" class="hidden text-xs px-3 py-1 bg-blue-500/20 text-blue-400 rounded-full border border-blue-500/30">Saving…</span>
        </div>
    </div>
    <div class="flex items-center gap-5 px-5 py-2.5 bg-gray-800/60 border-t border-gray-700/50">
        <div class="text-center"><div id="count-present" class="text-xl font-bold text-green-400 leading-none">{{ $presentCount }}</div><div class="text-[10px] text-gray-500 mt-0.5">Present</div></div>
        <div class="w-px h-7 bg-gray-700"></div>
        <div class="text-center"><div id="count-late" class="text-xl font-bold text-yellow-400 leading-none">{{ $lateCount }}</div><div class="text-[10px] text-gray-500 mt-0.5">Late</div></div>
        <div class="w-px h-7 bg-gray-700"></div>
        <div class="text-center"><div id="count-absent" class="text-xl font-bold text-red-400 leading-none">{{ $absentCount }}</div><div class="text-[10px] text-gray-500 mt-0.5">Absent</div></div>
        <div class="flex-1"></div>
        <div class="text-right">
            <div id="pct-display" class="text-xl font-bold text-green-400 leading-none">{{ $totalStudents > 0 ? round((($presentCount+$lateCount)/$totalStudents)*100) : 0 }}%</div>
            <div class="text-[10px] text-gray-500 mt-0.5">Attendance</div>
        </div>
    </div>
</div>

<div class="flex flex-wrap gap-2 mb-4">
    <div class="relative flex-1 min-w-[180px]">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">🔍</span>
        <input type="text" id="search-input" placeholder="Search by name or jersey…"
               class="w-full pl-9 pr-4 h-10 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
    </div>
    <button onclick="bulkMarkAll('present')" class="h-10 px-4 text-sm font-medium border border-green-300 text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">✓ All Present</button>
    <button onclick="bulkMarkAll('absent')"  class="h-10 px-4 text-sm font-medium border border-red-200 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">✕ All Absent</button>
</div>

<div class="flex items-center gap-4 mb-4 text-xs text-gray-500 flex-wrap">
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-400 inline-block"></span>Present</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-yellow-400 inline-block"></span>Late</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-gray-200 inline-block"></span>Absent</span>
    <span class="text-gray-400 hidden sm:inline">Tap = Present · Tap again = Late · Third tap = Absent · Each tap auto-saves</span>
</div>

<div id="grid-loading" class="text-center py-20 text-gray-400">
    <div class="text-5xl mb-3 animate-bounce">🏀</div>
    <p class="text-sm font-medium">Loading students…</p>
</div>

<div id="grid-error" class="hidden text-center py-20 text-red-400">
    <div class="text-4xl mb-3">⚠️</div>
    <p class="text-sm font-medium">Failed to load students</p>
    <button onclick="loadStudents()" class="mt-3 text-sm text-orange-500 underline">Retry</button>
</div>

<div id="attendance-grid" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3 mb-28 hidden"></div>

<div class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 px-4 py-3 shadow-lg">
    <div class="max-w-7xl mx-auto flex items-center gap-3">
        <div class="flex items-center gap-2 flex-1">
            <span id="unsaved-dot" class="w-2 h-2 rounded-full bg-yellow-400 hidden"></span>
            <span id="save-status-text" class="text-xs text-gray-500">@if($isSaved) Attendance saved @else Tap photos to mark attendance @endif</span>
        </div>
        <button id="save-btn" onclick="saveAllAttendance()"
                class="bg-orange-500 hover:bg-orange-600 active:scale-95 text-white font-semibold px-8 py-3 rounded-xl text-sm transition-all">
            {{ $isSaved ? 'Save All' : 'Save Attendance' }}
        </button>
    </div>
</div>

<style>
.attendance-card { touch-action:manipulation; -webkit-tap-highlight-color:transparent; user-select:none; }
.attendance-card:active .card-wrap { transform:scale(0.93); }
.card-wrap { transition:transform 0.08s ease,border-color 0.12s ease,background-color 0.12s ease; }
.lazy-photo { transition:opacity 0.3s ease; }
.attendance-card.hidden-filter { display:none !important; }
.skeleton { animation:shimmer 1.4s infinite; background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%); background-size:200% 100%; }
@keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
#attendance-grid { padding-bottom:5rem; }
</style>

@push('scripts')
<script>
const SESSION_ID   = {{ $session->id }};
const STUDENTS_URL = "{{ route('attendance.students', $session) }}";
const SAVE_SINGLE  = "{{ route('attendance.save-single', $session) }}";
const SAVE_ALL_URL = "{{ route('attendance.store', $session) }}";
const MARK_ALL_URL = "{{ route('attendance.mark-all', $session) }}";
const CSRF         = document.querySelector('meta[name="csrf-token"]').content;

const CYCLE = {present:'late',late:'absent',absent:'present'};
const CFG   = {
    present:{border:'#22c55e',bg:'#f0fdf4',ind:'#22c55e',icon:'✓',label:'Present',lc:'#15803d'},
    late:   {border:'#f59e0b',bg:'#fffbeb',ind:'#f59e0b',icon:'⏱',label:'Late',   lc:'#b45309'},
    absent: {border:'#e5e7eb',bg:'#f9fafb',ind:'transparent',icon:'',label:'',    lc:'#9ca3af'},
};

let attendanceMap = {};
let saveQueue     = {};
let isDirty       = false;
const DRAFT_KEY   = 'draft_' + SESSION_ID;

async function loadStudents() {
    document.getElementById('grid-loading').classList.remove('hidden');
    document.getElementById('grid-error').classList.add('hidden');
    document.getElementById('attendance-grid').classList.add('hidden');
    try {
        const res  = await silentFetch(STUDENTS_URL,{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}});
        if (!res.ok) throw new Error();
        const data = await res.json();
        attendanceMap = data.attendance || {};
        renderGrid(data.students);
        syncSummaryFromMap(data.students.length);
        document.getElementById('grid-loading').classList.add('hidden');
        document.getElementById('attendance-grid').classList.remove('hidden');
        requestAnimationFrame(initLazyPhotos);
        @if(!$isSaved) restoreDraft(); @endif
    } catch(err) {
        document.getElementById('grid-loading').classList.add('hidden');
        document.getElementById('grid-error').classList.remove('hidden');
    }
}

function renderGrid(students) {
    const grid = document.getElementById('attendance-grid');
    grid.innerHTML = '';
    students.forEach(student => {
        const status = attendanceMap[student.id] ?? 'absent';
        const c = CFG[status];
        const card = document.createElement('div');
        card.className = 'attendance-card';
        card.dataset.studentId = student.id;
        card.dataset.status    = status;
        card.dataset.name      = student.name.toLowerCase();
        card.dataset.jersey    = student.jersey ?? '';
        card.setAttribute('role','button');
        card.setAttribute('tabindex','0');
        card.innerHTML = `
            <div class="card-wrap relative aspect-square rounded-xl overflow-hidden border-[3px] cursor-pointer select-none" style="border-color:${c.border};background-color:${c.bg}">
                <div class="skeleton absolute inset-0"></div>
                <img data-src="${student.thumb_url}" alt="${student.name}" draggable="false"
                     class="lazy-photo absolute inset-0 w-full h-full object-cover pointer-events-none opacity-0">
                ${student.jersey ? `<span class="absolute top-1.5 left-1.5 text-[9px] font-bold bg-black/55 text-white px-1.5 py-0.5 rounded">#${student.jersey}</span>` : ''}
                ${student.is_at_risk ? `<span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 border border-white" title="At-risk"></span>` : ''}
                ${student.injury !== 'fit' ? `<span class="absolute bottom-7 left-1.5 text-[9px] font-medium px-1 py-0.5 rounded ${student.injury==='injured'?'bg-red-500/80 text-white':'bg-yellow-400/80 text-gray-900'}">${student.injury==='injured'?'INJ':'REC'}</span>` : ''}
                <div class="status-indicator absolute bottom-1.5 right-1.5 w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold border-2 border-white" style="background:${c.ind};opacity:${status==='absent'?0:1}">${c.icon}</div>
                <div class="save-dot absolute top-1.5 left-1.5 w-2 h-2 rounded-full bg-blue-400 hidden"></div>
            </div>
            <div class="mt-1.5 px-0.5">
                <div class="text-[11px] font-semibold text-gray-900 truncate leading-tight">${student.name}</div>
                <div class="status-label text-[10px] mt-0.5 font-medium" style="color:${c.lc}">${c.label}</div>
            </div>`;
        card.addEventListener('click', () => cycleStatus(card));
        card.addEventListener('keydown', e => { if(e.key==='Enter'||e.key===' '){e.preventDefault();cycleStatus(card);} });
        grid.appendChild(card);
    });
}

function initLazyPhotos() {
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const img = entry.target;
            img.src = img.dataset.src;
            img.onload = () => { img.classList.remove('opacity-0'); img.classList.add('opacity-100'); img.previousElementSibling?.remove(); img.removeAttribute('data-src'); };
            img.onerror = () => {
                const name = img.getAttribute('alt')||'?';
                const initials = name.split(' ').map(n=>n[0]).join('').toUpperCase().slice(0,2);
                img.style.display='none'; img.previousElementSibling?.remove();
                const fb = document.createElement('div');
                fb.className='absolute inset-0 flex items-center justify-center text-xl font-semibold text-gray-400 bg-gray-100';
                fb.textContent=initials; img.parentElement.insertBefore(fb,img.parentElement.firstChild);
            };
            obs.unobserve(img);
        });
    },{rootMargin:'300px 0px',threshold:0.01});
    document.querySelectorAll('img.lazy-photo[data-src]').forEach(img => obs.observe(img));
}

function cycleStatus(card) {
    const next = CYCLE[card.dataset.status||'absent'];
    applyStatus(card, next);
    attendanceMap[card.dataset.studentId] = next;
    saveQueue[card.dataset.studentId]     = next;
    clearTimeout(window._saveTimer);
    window._saveTimer = setTimeout(flushSaveQueue, 800);
    updateSummary(); markDirty(); saveDraftToStorage();
}

async function flushSaveQueue() {
    const queue = {...saveQueue}; saveQueue = {};
    if (!Object.keys(queue).length) return;

    Object.keys(queue).forEach(id => document.querySelector(`[data-student-id="${id}"]`)?.querySelector('.save-dot')?.classList.remove('hidden'));
    document.getElementById('saving-badge').classList.remove('hidden');

    try {
        const saves = Object.entries(queue).map(([id,status]) =>
            silentFetch(SAVE_SINGLE,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({student_id:parseInt(id),status})}).then(r=>r.json())
        );
        const results = await Promise.all(saves);
        const last = results[results.length-1];
        if (last?.summary) updateSummaryFromServer(last.summary);
        Object.keys(queue).forEach(id => document.querySelector(`[data-student-id="${id}"]`)?.querySelector('.save-dot')?.classList.add('hidden'));
        document.getElementById('saving-badge').classList.add('hidden');
        document.getElementById('saved-badge').classList.remove('hidden');
        document.getElementById('save-status-text').textContent = 'Auto-saved ✓';
        localStorage.removeItem(DRAFT_KEY);
        isDirty = false;
    } catch {
        Object.entries(queue).forEach(([id,s])=>{saveQueue[id]=s;});
        document.getElementById('saving-badge').classList.add('hidden');
        document.getElementById('save-status-text').textContent = '⚠ Auto-save failed — retrying';
        setTimeout(flushSaveQueue,5000);
    }
}

function applyStatus(card,status) {
    const c=CFG[status]; const wrap=card.querySelector('.card-wrap'); const ind=card.querySelector('.status-indicator'); const lbl=card.querySelector('.status-label');
    wrap.style.borderColor=c.border; wrap.style.backgroundColor=c.bg;
    ind.style.background=c.ind; ind.textContent=c.icon; ind.style.opacity=status==='absent'?'0':'1';
    lbl.textContent=c.label; lbl.style.color=c.lc; card.dataset.status=status;
}

async function bulkMarkAll(status) {
    document.querySelectorAll('.attendance-card:not(.hidden-filter)').forEach(card=>{ applyStatus(card,status); attendanceMap[card.dataset.studentId]=status; });
    updateSummary(); markDirty();
    document.getElementById('saving-badge').classList.remove('hidden');
    try {
        const res=await fetch(MARK_ALL_URL,{method:'PATCH',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({status})});
        const data=await res.json();
        if(data.summary) updateSummaryFromServer(data.summary);
        document.getElementById('saving-badge').classList.add('hidden');
        document.getElementById('saved-badge').classList.remove('hidden');
        document.getElementById('save-status-text').textContent=`All marked ${status}`;
        isDirty=false; localStorage.removeItem(DRAFT_KEY);
    } catch { document.getElementById('saving-badge').classList.add('hidden'); document.getElementById('save-status-text').textContent='⚠ Failed — retry'; }
}

async function saveAllAttendance() {
    const btn=document.getElementById('save-btn'); btn.textContent='Saving…'; btn.disabled=true;
    const attendance=[...document.querySelectorAll('.attendance-card')].map(c=>({id:parseInt(c.dataset.studentId),status:c.dataset.status,note:''}));
    try {
        const res=await fetch(SAVE_ALL_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({attendance})});
        const data=await res.json();
        if(data.success) {
            btn.textContent='✓ Saved!'; btn.style.background='#16a34a';
            if(data.summary) updateSummaryFromServer(data.summary);
            document.getElementById('unsaved-dot').classList.add('hidden');
            document.getElementById('save-status-text').textContent='All attendance saved';
            document.getElementById('saved-badge').classList.remove('hidden');
            isDirty=false; localStorage.removeItem(DRAFT_KEY);
            setTimeout(()=>{btn.textContent='Save All';btn.style.background='';btn.disabled=false;},2500);
        } else throw new Error();
    } catch { btn.textContent='Save Failed — Retry'; btn.style.background='#dc2626'; btn.disabled=false; setTimeout(()=>{btn.textContent='Save Attendance';btn.style.background='';},3000); }
}

document.getElementById('search-input').addEventListener('input',function(){
    const q=this.value.toLowerCase().trim();
    document.querySelectorAll('.attendance-card').forEach(card=>{ card.classList.toggle('hidden-filter',!(!q||card.dataset.name.includes(q)||card.dataset.jersey.includes(q))); });
});

function updateSummary() {
    const cards=[...document.querySelectorAll('.attendance-card')];
    const p=cards.filter(c=>c.dataset.status==='present').length; const l=cards.filter(c=>c.dataset.status==='late').length;
    const pct=cards.length>0?Math.round(((p+l)/cards.length)*100):0;
    document.getElementById('count-present').textContent=p; document.getElementById('count-late').textContent=l;
    document.getElementById('count-absent').textContent=cards.length-p-l; document.getElementById('pct-display').textContent=pct+'%';
    document.getElementById('pct-display').style.color=pct>=80?'#4ade80':pct>=60?'#fbbf24':'#f87171';
}

function syncSummaryFromMap(total) {
    const p=Object.values(attendanceMap).filter(s=>s==='present').length; const l=Object.values(attendanceMap).filter(s=>s==='late').length;
    const pct=total>0?Math.round(((p+l)/total)*100):0;
    document.getElementById('count-present').textContent=p; document.getElementById('count-late').textContent=l;
    document.getElementById('count-absent').textContent=total-p-l; document.getElementById('pct-display').textContent=pct+'%';
}

function updateSummaryFromServer(s) {
    document.getElementById('count-present').textContent=s.present; document.getElementById('count-late').textContent=s.late;
    document.getElementById('count-absent').textContent=s.absent; document.getElementById('pct-display').textContent=s.pct+'%';
    document.getElementById('pct-display').style.color=s.pct>=80?'#4ade80':s.pct>=60?'#fbbf24':'#f87171';
}

function markDirty() {
    isDirty=true; document.getElementById('unsaved-dot').classList.remove('hidden'); document.getElementById('saved-badge').classList.add('hidden');
}

function saveDraftToStorage() {
    try { localStorage.setItem(DRAFT_KEY,JSON.stringify({map:{...attendanceMap},savedAt:Date.now()})); } catch(e) {}
}

function restoreDraft() {
    try {
        const raw=localStorage.getItem(DRAFT_KEY); if(!raw) return;
        const {map,savedAt}=JSON.parse(raw); if((Date.now()-savedAt)/60000>120){localStorage.removeItem(DRAFT_KEY);return;}
        let n=0; document.querySelectorAll('.attendance-card').forEach(card=>{const s=map[card.dataset.studentId];if(s&&s!==card.dataset.status){applyStatus(card,s);attendanceMap[card.dataset.studentId]=s;n++;}});
        if(n>0){const mins=Math.round((Date.now()-savedAt)/60000);document.getElementById('save-status-text').textContent=`Draft restored (${mins>0?mins+'m ago':'just now'})`;markDirty();updateSummary();}
    } catch(e) {}
}

setInterval(saveDraftToStorage,30000);
window.addEventListener('beforeunload',e=>{if(isDirty&&Object.keys(saveQueue).length>0){e.preventDefault();e.returnValue='';}});
loadStudents();
</script>
@endpush
@endsection
