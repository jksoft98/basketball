@extends('layouts.app')
@section('title', 'Sessions')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Sessions</h1>
        <p class="text-sm text-gray-500 mt-1">All training sessions across your batches</p>
    </div>
    <a href="{{ route('sessions.create') }}"
       class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        + New Session
    </a>
</div>

{{-- Filter bar (ba-ajax style) --}}
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
    <input type="date" id="filter-date-from" placeholder="From date"
           class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
    <input type="date" id="filter-date-to" placeholder="To date"
           class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-400">
    <button onclick="applyFilters()" class="h-9 px-4 text-sm bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition-colors">Filter</button>
    <button onclick="clearFilters()" class="h-9 px-3 text-sm text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Clear</button>
</div>

{{-- DataTable wrapper --}}
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

@push('scripts')
<script>
let dtSessions;

$(document).ready(function() {
    dtSessions = $('#sessions-table').DataTable({
        processing:  true,
        serverSide:  true,
        ajax: {
            url:  "{{ route('dt.sessions') }}",
            type: 'GET',
            data: function(d) {
                d.batch_id    = $('#filter-batch').val();
                d.session_type= $('#filter-type').val();
                d.status      = $('#filter-status').val();
                d.date_from   = $('#filter-date-from').val();
                d.date_to     = $('#filter-date-to').val();
                return d;
            },
        },
        columns: [
            { data: 'date',       orderable: true,  searchable: false },
            { data: 'batch',      orderable: false, searchable: true  },
            { data: 'type',       orderable: false, searchable: false },
            { data: 'attendance', orderable: false, searchable: false },
            { data: 'notes',      orderable: false, searchable: false },
            { data: 'status',     orderable: false, searchable: false },
            { data: 'actions',    orderable: false, searchable: false },
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
            emptyTable:        'No sessions yet — create your first one.',
        },
        dom: '<"dt-bar dt-bar-top"lf>rt<"dt-bar dt-bar-bottom"ip>',
        drawCallback: function() {
            initNoteEditing();
        },
    });
});

function applyFilters() { dtSessions.ajax.reload(); }

function clearFilters() {
    $('#filter-batch, #filter-type, #filter-status').val('');
    $('#filter-date-from, #filter-date-to').val('');
    dtSessions.search('').ajax.reload();
}

// Inline note editing
function initNoteEditing() {
    $(document).off('dblclick', '.session-note').on('dblclick', '.session-note', function() {
        const sessionId = $(this).data('session-id');
        const original  = $(this).text().trim() === 'Add note…' ? '' : $(this).text().trim();
        const $note     = $(this);
        const $input    = $('<input type="text" class="w-full min-w-[140px] text-xs border border-orange-400 rounded px-2 py-1 bg-white focus:outline-none" maxlength="500">').val(original);

        $note.replaceWith($input);
        $input.focus();

        const saveNote = function() {
            const val = $input.val().trim();
            $.ajax({
                url:    '/sessions/' + sessionId + '/notes',
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                data:   JSON.stringify({ notes: val }),
            });
            const $span = $('<span class="session-note text-xs text-gray-400 italic cursor-pointer hover:text-gray-600">').attr('data-session-id', sessionId).text(val || 'Add note…');
            $input.replaceWith($span);
        };

        $input.on('blur', saveNote);
        $input.on('keydown', function(e) {
            if (e.key === 'Enter') $input.blur();
            if (e.key === 'Escape') { $input.val(original); $input.blur(); }
        });
    });
}

// AJAX delete
function dtDeleteSession(id, btn) {
    if (!confirm('Delete this session? All attendance records will also be removed.')) return;
    const $row = $(btn).closest('tr');
    $row.css('opacity', '0.4');
    $.ajax({
        url:     '/sessions/' + id,
        method:  'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        success: function() { dtSessions.ajax.reload(null, false); },
        error:   function() { $row.css('opacity','1'); alert('Delete failed. Please try again.'); },
    });
}
</script>
@endpush
@endsection
