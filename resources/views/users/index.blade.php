@extends('layouts.app')
@section('title', 'User Management')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $totalAdmins }} admin{{ $totalAdmins !== 1 ? 's' : '' }} &middot;
            {{ $totalCoaches }} coach{{ $totalCoaches !== 1 ? 'es' : '' }}
        </p>
    </div>
    <a href="{{ route('users.create') }}"
       class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        + New User
    </a>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="text-2xl mb-1">👑</div>
        <div class="text-2xl font-bold text-gray-900">{{ $totalAdmins }}</div>
        <div class="text-xs text-gray-500 mt-1">Admins</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="text-2xl mb-1">🏀</div>
        <div class="text-2xl font-bold text-gray-900">{{ $totalCoaches }}</div>
        <div class="text-xs text-gray-500 mt-1">Coaches</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="text-2xl mb-1">👥</div>
        <div class="text-2xl font-bold text-gray-900">{{ $totalAdmins + $totalCoaches }}</div>
        <div class="text-xs text-gray-500 mt-1">Total Users</div>
    </div>
</div>

{{-- Users table --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table id="users-table" class="w-full" style="width:100%">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Batches</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr class="{{ $user->id === auth()->id() ? 'bg-orange-50/40' : '' }}">
                    <td>
                        <div class="flex items-center gap-3">
                            {{-- Avatar initial --}}
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0
                                {{ $user->isAdmin() ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 text-sm">
                                    {{ $user->name }}
                                    @if($user->id === auth()->id())
                                    <span class="ml-1 text-[10px] px-1.5 py-0.5 bg-orange-100 text-orange-600 rounded-full font-medium">You</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="text-gray-600 text-sm">{{ $user->email }}</td>
                    <td>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full
                            {{ $user->isAdmin() ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td>
                        @if($user->isCoach())
                        <span class="font-semibold text-gray-900">{{ $user->batches_count }}</span>
                        <span class="text-xs text-gray-400">batch{{ $user->batches_count !== 1 ? 'es' : '' }}</span>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="text-gray-500 text-sm">
                        {{ $user->created_at->format('d M Y') }}
                    </td>
                    <td>
                        <div class="flex items-center gap-2 whitespace-nowrap">
                            <a href="{{ route('users.edit', $user) }}"
                               class="inline-flex items-center text-xs font-semibold text-orange-600 bg-orange-50 hover:bg-orange-100 px-2.5 py-1.5 rounded-lg transition-colors">
                                Edit
                            </a>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('users.destroy', $user) }}" method="POST"
                                  onsubmit="return confirm('Delete {{ addslashes($user->name) }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center text-xs text-red-600 bg-red-50 hover:bg-red-100 px-2.5 py-1.5 rounded-lg transition-colors">
                                    Delete
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#users-table').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[2, 'asc'], [0, 'asc']],
        columnDefs: [
            { orderable: false, searchable: false, targets: 5, className: 'never' },
            { responsivePriority: 1, targets: 0 },
            { responsivePriority: 2, targets: 5 },
            { responsivePriority: 3, targets: 2 },
            { responsivePriority: 4, targets: 1 },
            { responsivePriority: 5, targets: 3 },
            { responsivePriority: 6, targets: 4 },
        ],
        language: {
            search:            '',
            searchPlaceholder: '🔍 Search users…',
            lengthMenu:        'Show _MENU_',
            info:              '_START_–_END_ of _TOTAL_ users',
            paginate:          { previous: '‹', next: '›' },
        },
        dom: '<"dt-bar dt-bar-top"lf>rt<"dt-bar dt-bar-bottom"ip>',
    });
});
</script>
@endpush
@endsection
