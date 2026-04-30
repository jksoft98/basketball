@extends('layouts.app')
@section('title', 'Sessions')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Sessions</h1>
        <p class="text-sm text-gray-500 mt-1">All training sessions across your batches</p>
    </div>
    <div class="flex items-center gap-2">
        {{-- Auto-generate button --}}
        <button onclick="openGenerateModal()"
                class="border border-orange-400 text-orange-600 hover:bg-orange-50 text-sm font-medium px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5">
            ⚡ Auto-generate
        </button>
        <a href="{{ route('sessions.create') }}"
           class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            + New Session
        </a>
    </div>
</div>

{{-- Filter bar --}}
<div class="flex flex-wrap gap-3 mb-5">
    <select id="filter-batch" class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
        <option value="">All Batches</option>
        @foreach($batches as $b)
        <option value="{{ $b->name }}">{{ $b->name }}</option>
        @endforeach
    </select>
    <select id="filter-type" class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
        <option value="">All Types</option>
        <option value="training">Training</option>
        <option value="match">Match</option>
        <option value="fitness">Fitness</option>
        <option value="trial">Trial</option>
    </select>
    <select id="filter-status" class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
        <option value="">All Status</option>
        <option value="saved">Saved</option>
        <option value="pending">Pending</option>
    </select>
    <input type="date" id="filter-date-from"
           class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
    <input type="date" id="filter-date-to"
           class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
    <button onclick="applyFilters()" class="h-9 px-4 text-sm bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition-colors">Filter</button>
    <button onclick="clearFilters()" class="h-9 px-3 text-sm text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Clear</button>
</div>

{{-- DataTable --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table id="sessions-table" class="w-full" style="width:100%">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Batch</th>
                    <th>Type</th>
                    <th>Attendance</th>
                    <th>Notes</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- ── Auto-generate Modal ──────────────────────────────────────── --}}
<div id="generate-modal"
     class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl border border-gray-200 w-full max-w-sm shadow-xl">

        {{-- Modal header --}}
        <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-100">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Auto-generate Sessions</h2>
                <p class="text-xs text-gray-400 mt-0.5">
                    Creates sessions from each batch's weekly schedule
                </p>
            </div>
            <button onclick="closeGenerateModal()"
                    class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
        </div>

        {{-- Modal body --}}
        <div class="px-6 py-5 space-y-4">

            {{-- Quick presets --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Quick select</label>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="setPreset('this-week')"
                            class="preset-btn h-8 text-xs border border-gray-200 rounded-lg hover:border-orange-400 hover:text-orange-600 transition-colors">
                        This week
                    </button>
                    <button type="button" onclick="setPreset('next-week')"
                            class="preset-btn h-8 text-xs border border-gray-200 rounded-lg hover:border-orange-400 hover:text-orange-600 transition-colors">
                        Next week
                    </button>
                    <button type="button" onclick="setPreset('this-month')"
                            class="preset-btn h-8 text-xs border border-gray-200 rounded-lg hover:border-orange-400 hover:text-orange-600 transition-colors">
                        This month
                    </button>
                </div>
            </div>

            {{-- Date range --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">From</label>
                    <input type="date" id="gen-from"
                           class="w-full h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">To</label>
                    <input type="date" id="gen-to"
                           class="w-full h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
                </div>
            </div>

            {{-- Batch filter --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Batch (optional)</label>
                <select id="gen-batch"
                        class="w-full h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
                    <option value="">All batches with schedules</option>
                    @foreach($batches as $b)
                    <option value="{{ $b->id }}">
                        {{ $b->name }}
                        @if($b->activeSchedules->isNotEmpty())
                            ({{ $b->schedule_summary }})
                        @else
                            — no schedule
                        @endif
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Result message --}}
            <div id="gen-result" class="hidden text-sm px-3 py-2.5 rounded-lg"></div>

            {{-- Per-batch detail --}}
            <div id="gen-details" class="hidden space-y-1.5"></div>
        </div>

        {{-- Modal footer --}}
        <div class="flex gap-3 px-6 pb-5">
            <button onclick="generateSessions()" id="gen-btn"
                    class="flex-1 h-9 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                Generate
            </button>
            <button onclick="closeGenerateModal()"
                    class="h-9 px-4 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let dtSessions;

$(document).ready(function() {
    dtSessions = $('#sessions-table').DataTable({
        processing:  true,
        serverSide:  true,
        responsive:  true,
        ajax: {
            url:  "{{ route('dt.sessions') }}",
            type: 'GET',
            data: function(d) {
                d.batch_id     = $('#filter-batch').val();
                d.session_type = $('#filter-type').val();
                d.status       = $('#filter-status').val();
                d.date_from    = $('#filter-date-from').val();
                d.date_to      = $('#filter-date-to').val();
                return d;
            },
        },
        columns: [
            { data: 'date',       orderable: true,  searchable: false, responsivePriority: 1 },
            { data: 'batch',      orderable: false, searchable: true,  responsivePriority: 2 },
            { data: 'type',       orderable: false, searchable: false, responsivePriority: 4 },
            { data: 'attendance', orderable: false, searchable: false, responsivePriority: 3 },
            { data: 'notes',      orderable: false, searchable: false, responsivePriority: 6 },
            { data: 'status',     orderable: false, searchable: false, responsivePriority: 5 },
            { data: 'actions',    orderable: false, searchable: false, responsivePriority: 1, className: 'never' },
        ],
        order: [[0, 'desc']],
        pageLength: 15,
        lengthMenu: [10, 15, 25, 50],
        scrollX: true, // ✅ ADD THIS
        language: {
            search:            '',
            searchPlaceholder: '🔍 Search sessions…',
            lengthMenu:        'Show _MENU_',
            info:              'Showing _START_–_END_ of _TOTAL_ sessions',
            infoEmpty:         'No sessions found',
            paginate:          { previous: '‹', next: '›' },
            processing:        'Loading…',
            emptyTable:        'No sessions yet.',
        },
        dom: '<"dt-bar dt-bar-top"lf>rt<"dt-bar dt-bar-bottom"ip>',
        drawCallback: function() { initNoteEditing(); },
    });
});

function applyFilters() { dtSessions.ajax.reload(); }
function clearFilters() {
    $('#filter-batch, #filter-type, #filter-status').val('');
    $('#filter-date-from, #filter-date-to').val('');
    dtSessions.search('').ajax.reload();
}

// ── Inline note editing ──────────────────────────────────────────
function initNoteEditing() {
    $(document).off('dblclick', '.session-note').on('dblclick', '.session-note', function() {
        const sessionId = $(this).data('session-id');
        const original  = $(this).text().trim() === 'Add note…' ? '' : $(this).text().trim();
        const $input    = $('<input type="text" class="w-full min-w-[140px] text-xs border border-orange-400 rounded px-2 py-1 bg-white focus:outline-none" maxlength="500">').val(original);
        $(this).replaceWith($input);
        $input.focus();
        const save = () => {
            const val = $input.val().trim();
            $.ajax({
                url: '/sessions/' + sessionId + '/notes', method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                data: JSON.stringify({ notes: val }),
            });
            $input.replaceWith($('<span class="session-note text-xs text-gray-400 italic cursor-pointer hover:text-gray-600">').attr('data-session-id', sessionId).text(val || 'Add note…'));
        };
        $input.on('blur', save);
        $input.on('keydown', e => { if (e.key === 'Enter') $input.blur(); if (e.key === 'Escape') { $input.val(original); $input.blur(); } });
    });
}

// ── AJAX delete ──────────────────────────────────────────────────
function dtDeleteSession(id, btn) {
    if (!confirm('Delete this session? All attendance records will also be removed.')) return;
    $(btn).closest('tr').css('opacity', '0.4');
    $.ajax({
        url: '/sessions/' + id, method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        success: () => dtSessions.ajax.reload(null, false),
        error:   () => { $(btn).closest('tr').css('opacity', '1'); alert('Delete failed.'); },
    });
}

// ── Generate modal ───────────────────────────────────────────────
function openGenerateModal() {
    setPreset('this-week'); // default to this week
    document.getElementById('gen-result').classList.add('hidden');
    document.getElementById('gen-details').classList.add('hidden');
    document.getElementById('generate-modal').classList.remove('hidden');
}

function closeGenerateModal() {
    document.getElementById('generate-modal').classList.add('hidden');
}

// Close on backdrop click
document.getElementById('generate-modal').addEventListener('click', function(e) {
    if (e.target === this) closeGenerateModal();
});

// Date presets
function setPreset(preset) {
    const today  = new Date();
    const monday = d => { const day = d.getDay(); const diff = d.getDate() - day + (day === 0 ? -6 : 1); return new Date(d.setDate(diff)); };
    const fmt    = d => d.toISOString().split('T')[0];

    let from, to;

    if (preset === 'this-week') {
        from = monday(new Date(today));
        to   = new Date(from); to.setDate(to.getDate() + 6);
    } else if (preset === 'next-week') {
        from = monday(new Date(today)); from.setDate(from.getDate() + 7);
        to   = new Date(from); to.setDate(to.getDate() + 6);
    } else if (preset === 'this-month') {
        from = new Date(today.getFullYear(), today.getMonth(), 1);
        to   = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    }

    document.getElementById('gen-from').value = fmt(from);
    document.getElementById('gen-to').value   = fmt(to);

    // Highlight active preset button
    document.querySelectorAll('.preset-btn').forEach(b => {
        b.classList.remove('border-orange-400', 'text-orange-600', 'bg-orange-50');
        b.classList.add('border-gray-200');
    });
    event.currentTarget?.classList.add('border-orange-400', 'text-orange-600', 'bg-orange-50');
    event.currentTarget?.classList.remove('border-gray-200');
}

// Generate sessions via AJAX
async function generateSessions() {
    const btn    = document.getElementById('gen-btn');
    const result = document.getElementById('gen-result');
    const details = document.getElementById('gen-details');
    const from   = document.getElementById('gen-from').value;
    const to     = document.getElementById('gen-to').value;
    const batch  = document.getElementById('gen-batch').value;

    if (!from || !to) {
        result.textContent = 'Please select a date range.';
        result.className   = 'text-sm px-3 py-2.5 rounded-lg bg-yellow-50 text-yellow-700';
        result.classList.remove('hidden');
        return;
    }

    btn.textContent = 'Generating…';
    btn.disabled    = true;
    result.classList.add('hidden');
    details.classList.add('hidden');

    try {
        const res  = await fetch("{{ route('sessions.generate') }}", {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body:    JSON.stringify({ from, to, batch_id: batch || null }),
        });

        const data = await res.json();

        if (!res.ok) {
            result.textContent = data.message || 'Validation error.';
            result.className   = 'text-sm px-3 py-2.5 rounded-lg bg-red-50 text-red-600';
        } else {
            result.textContent = data.message;
            result.className   = data.created > 0
                ? 'text-sm px-3 py-2.5 rounded-lg bg-green-50 text-green-700 font-medium'
                : 'text-sm px-3 py-2.5 rounded-lg bg-gray-50 text-gray-600';

            // Show per-batch breakdown
            if (data.details && data.details.length > 0) {
                details.innerHTML = data.details.map(d => `
                    <div class="flex items-center justify-between text-xs py-1 border-b border-gray-50 last:border-0">
                        <span class="text-gray-600">${d.batch}</span>
                        ${d.status === 'ok'
                            ? `<span class="${d.created > 0 ? 'text-green-600 font-semibold' : 'text-gray-400'}">${d.created > 0 ? '+' + d.created + ' created' : 'Already exists'}</span>`
                            : `<span class="text-yellow-600">${d.reason}</span>`
                        }
                    </div>`).join('');
                details.classList.remove('hidden');
            }

            // Refresh the DataTable if sessions were created
            if (data.created > 0) {
                dtSessions.ajax.reload(null, false);
            }
        }

        result.classList.remove('hidden');

    } catch(e) {
        result.textContent = 'Request failed. Please try again.';
        result.className   = 'text-sm px-3 py-2.5 rounded-lg bg-red-50 text-red-600';
        result.classList.remove('hidden');
    }

    btn.textContent = 'Generate';
    btn.disabled    = false;
}
</script>
@endpush
@endsection
