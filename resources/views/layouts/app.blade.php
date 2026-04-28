<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f97316">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Academy">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icon-192.png">
    <title>@yield('title', 'Dashboard') — Basketball Academy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- DataTables --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/jquery.dataTables.min.css">

    <style>
        /* ── DataTables reset to match app style ────────────────── */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            height: 36px; padding: 0 10px;
            border: 1px solid #e5e7eb; border-radius: 8px;
            font-size: 13px; background: #fff; color: #374151; outline: none;
        }
        .dataTables_wrapper .dataTables_filter input:focus,
        .dataTables_wrapper .dataTables_length select:focus { border-color: #f97316; }
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label { font-size: 12px; color: #6b7280; display:flex; align-items:center; gap:6px; }
        .dataTables_wrapper .dataTables_info         { font-size: 12px; color: #9ca3af; }
        .dataTables_wrapper .dataTables_paginate      { display:flex; gap:2px; align-items:center; }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 4px 10px; font-size: 12px; border-radius: 6px; cursor: pointer;
            border: 1px solid #e5e7eb !important; color: #6b7280 !important; background: #fff !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #f97316 !important; color: #fff !important; border-color: #f97316 !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
            background: #fff7ed !important; color: #f97316 !important; border-color: #fed7aa !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            color: #d1d5db !important; cursor: default !important; border-color: #f3f4f6 !important;
        }
        /* Table cells */
        table.dataTable { border-collapse: collapse !important; }
        table.dataTable thead th {
            font-size: 11px; font-weight: 500; color: #6b7280; text-align: left;
            padding: 10px 16px; background: #f9fafb;
            border-bottom: 1px solid #f3f4f6 !important; white-space: nowrap;
        }
        table.dataTable thead th.sorting:after,
        table.dataTable thead th.sorting_asc:after,
        table.dataTable thead th.sorting_desc:after { color: #d1d5db; margin-left: 4px; }
        table.dataTable tbody td {
            padding: 11px 16px; font-size: 13px;
            border-bottom: 1px solid #f9fafb !important; vertical-align: middle;
        }
        table.dataTable tbody tr:hover td { background: #fffbf5 !important; }
        table.dataTable tbody tr:last-child td { border-bottom: none !important; }
        table.dataTable.no-footer { border-bottom: none; }
        /* dt-bar: top (search/length) and bottom (info/pagination) */
        .dt-bar { display:flex; align-items:center; justify-content:space-between;
                  padding: 12px 16px; flex-wrap:wrap; gap:8px; }
        .dt-bar-top  { border-bottom: 1px solid #f3f4f6; }
        .dt-bar-bottom { border-top: 1px solid #f3f4f6; }
        /* Loading overlay */
        .dataTables_processing {
            position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
            font-size:12px; color:#9ca3af; background:#ffffffcc;
            padding:8px 16px; border-radius:8px; border:1px solid #f3f4f6;
        }
    </style>
    @stack('head')
</head>
<body class="bg-gray-50 min-h-screen font-sans">

{{-- Global Preloader --}}
<div id="preloader" style="display:none">
    <div class="pre-backdrop"></div>
    <div class="pre-box">
        <div class="pre-court">
            <div class="pre-arc"></div>
            <div class="pre-line"></div>
        </div>
        <div class="pre-ball-wrap">
            <div class="pre-ball">
                <div class="pre-seam pre-seam-1"></div>
                <div class="pre-seam pre-seam-2"></div>
            </div>
            <div class="pre-shadow"></div>
        </div>
        <p class="pre-label">Loading…</p>
        <div class="pre-dots">
            <span></span><span></span><span></span>
        </div>
    </div>
</div>

<style>
#preloader{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .25s ease}
#preloader.pre-show{opacity:1;pointer-events:all}
.pre-backdrop{position:absolute;inset:0;background:rgba(17,24,39,.55);backdrop-filter:blur(3px)}
.pre-box{position:relative;z-index:1;background:#fff;border-radius:20px;padding:36px 44px 28px;display:flex;flex-direction:column;align-items:center;gap:0;box-shadow:0 24px 64px rgba(0,0,0,.18);min-width:180px}

/* mini basketball court decoration */
.pre-court{width:80px;height:36px;border:2px solid #f3f4f6;border-radius:6px;position:relative;overflow:hidden;margin-bottom:18px;background:#fafafa}
.pre-arc{position:absolute;bottom:-16px;left:50%;transform:translateX(-50%);width:44px;height:32px;border:2px solid #f3f4f6;border-radius:50% 50% 0 0;background:transparent}
.pre-line{position:absolute;top:0;bottom:0;left:50%;width:2px;background:#f3f4f6;transform:translateX(-50%)}

/* ball + bounce */
.pre-ball-wrap{display:flex;flex-direction:column;align-items:center;gap:0;margin-bottom:16px}
.pre-ball{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#fb923c 0%,#f97316 45%,#ea580c 100%);position:relative;animation:ballBounce .65s cubic-bezier(.33,0,.66,1) infinite alternate}
.pre-seam{position:absolute;border:1.5px solid rgba(0,0,0,.18);border-radius:50%;top:50%;left:50%;transform:translate(-50%,-50%)}
.pre-seam-1{width:100%;height:55%;border-left-color:transparent;border-right-color:transparent}
.pre-seam-2{width:55%;height:100%;border-top-color:transparent;border-bottom-color:transparent}
.pre-shadow{width:36px;height:8px;background:rgba(0,0,0,.12);border-radius:50%;animation:shadowPulse .65s cubic-bezier(.33,0,.66,1) infinite alternate;margin-top:2px}

@keyframes ballBounce{
    0%{transform:translateY(0) scaleX(1) scaleY(1)}
    85%{transform:translateY(-28px) scaleX(1) scaleY(1)}
    100%{transform:translateY(-32px) scaleX(1.04) scaleY(.97)}
}
@keyframes shadowPulse{
    0%{transform:scaleX(1);opacity:.55}
    100%{transform:scaleX(.55);opacity:.2}
}

.pre-label{font-size:13px;font-weight:500;color:#6b7280;margin:0 0 10px;letter-spacing:.03em}

/* animated dots */
.pre-dots{display:flex;gap:5px}
.pre-dots span{width:6px;height:6px;border-radius:50%;background:#fdba74;animation:dot .9s ease-in-out infinite}
.pre-dots span:nth-child(2){animation-delay:.15s;background:#f97316}
.pre-dots span:nth-child(3){animation-delay:.3s;background:#ea580c}
@keyframes dot{0%,80%,100%{transform:scale(.6);opacity:.4}40%{transform:scale(1);opacity:1}}
</style>

{{-- Navigation (ba-ajax style exactly) --}}
<nav class="bg-gray-900 text-white sticky top-0 z-50 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-bold text-lg">
                🏀 <span class="hidden sm:inline">Basketball Academy</span>
            </a>
            <div class="hidden md:flex items-center gap-1">
                @php
                    $navLinks = [
                        ['route'=>'dashboard',      'label'=>'Dashboard'],
                        ['route'=>'batches.index',  'label'=>'Batches'],
                        ['route'=>'students.index', 'label'=>'Students'],
                        ['route'=>'sessions.index', 'label'=>'Sessions'],
                    ];
                    if(auth()->user()->isAdmin()) $navLinks[] = ['route'=>'reports.index','label'=>'Reports'];
                @endphp
                @foreach($navLinks as $link)
                <a href="{{ route($link['route']) }}"
                   class="px-3 py-2 rounded text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white transition-colors
                          {{ request()->routeIs(explode('.',$link['route'])[0].'*') ? 'bg-orange-500 text-white' : '' }}">
                    {{ $link['label'] }}
                </a>
                @endforeach
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden sm:block text-sm text-gray-300">
                    {{ auth()->user()->name }}
                    <span class="ml-1 text-xs px-2 py-0.5 rounded-full {{ auth()->user()->isAdmin() ? 'bg-orange-500' : 'bg-gray-600' }}">
                        {{ ucfirst(auth()->user()->role) }}
                    </span>
                </span>
                {{-- Logout hidden on mobile, shown on sm+ --}}
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                    @csrf
                    <button type="submit" class="text-sm text-gray-400 hover:text-white border border-gray-600 hover:border-gray-400 px-3 py-1 rounded transition-colors">
                        Logout
                    </button>
                </form>
                <button id="mobile-menu-btn" class="md:hidden p-2 rounded text-gray-400 hover:text-white">☰</button>
            </div>
        </div>
    </div>
    <div id="mobile-menu" class="hidden md:hidden bg-gray-800 border-t border-gray-700">
        <div class="px-4 py-2 space-y-1">
            @foreach($navLinks as $link)
            <a href="{{ route($link['route']) }}" class="block px-3 py-2 rounded text-base text-gray-300 hover:bg-gray-700 hover:text-white">{{ $link['label'] }}</a>
            @endforeach
        </div>
        {{-- User info + logout at the bottom of the mobile menu --}}
        <div class="px-4 py-3 border-t border-gray-700 flex items-center justify-between">
            <span class="text-sm text-gray-300">
                {{ auth()->user()->name }}
                <span class="ml-1 text-xs px-2 py-0.5 rounded-full {{ auth()->user()->isAdmin() ? 'bg-orange-500' : 'bg-gray-600' }}">
                    {{ ucfirst(auth()->user()->role) }}
                </span>
            </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-400 hover:text-white border border-gray-600 hover:border-gray-400 px-3 py-1.5 rounded transition-colors">
                    Logout
                </button>
            </form>
        </div>
    </div>
</nav>

{{-- Flash messages (ba-ajax style) --}}
@if(session('success'))
<div id="flash-success" class="fixed top-20 right-4 z-50 bg-green-500 text-white px-5 py-3 rounded-lg shadow-lg flex items-center gap-2 text-sm font-medium max-w-sm">
    ✓ {{ session('success') }}
    <button onclick="this.parentElement.remove()" class="ml-2 opacity-70 hover:opacity-100">✕</button>
</div>
@endif
@if(session('error'))
<div id="flash-error" class="fixed top-20 right-4 z-50 bg-red-500 text-white px-5 py-3 rounded-lg shadow-lg flex items-center gap-2 text-sm font-medium max-w-sm">
    ✕ {{ session('error') }}
    <button onclick="this.parentElement.remove()" class="ml-2 opacity-70 hover:opacity-100">✕</button>
</div>
@endif
@if(session('warning'))
<div id="flash-warning" class="fixed top-20 right-4 z-50 bg-yellow-500 text-white px-5 py-3 rounded-lg shadow-lg flex items-center gap-2 text-sm font-medium max-w-sm">
    ⚠ {{ session('warning') }}
    <button onclick="this.parentElement.remove()" class="ml-2 opacity-70 hover:opacity-100">✕</button>
</div>
@endif

{{-- Validation errors --}}
@if($errors->any())
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <p class="text-sm font-medium text-red-700 mb-2">Please fix the following errors:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $e)
            <li class="text-sm text-red-600">{{ $e }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    @yield('content')
</main>

{{-- jQuery + DataTables --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>

<script>


    const _pre = document.getElementById('preloader');
    let _preCount = 0; // reference counter (multiple concurrent ajax calls)

    function showPreloader() {
        _preCount++;
        _pre.style.display = 'flex';
        requestAnimationFrame(() => _pre.classList.add('pre-show'));
    }
    function hidePreloader(force) {
        if (!force) _preCount = Math.max(0, _preCount - 1);
        if (_preCount > 0 && !force) return;
        _preCount = 0;
        _pre.classList.remove('pre-show');
        setTimeout(() => { if (!_pre.classList.contains('pre-show')) _pre.style.display = 'none'; }, 280);
    }

    // Hide once DOM is ready
    document.addEventListener('DOMContentLoaded', () => hidePreloader(true));
    window.addEventListener('load', () => hidePreloader(true));

    // Page navigations (link clicks)
    document.addEventListener('click', e => {
        const a = e.target.closest('a[href]');
        if (!a) return;
        const h = a.href;
        if (!h || h === '#' || h.startsWith('javascript') || h.startsWith('mailto') || a.target === '_blank' || a.hasAttribute('download')) return;
        showPreloader();
    });

    // Form submissions
    document.addEventListener('submit', () => showPreloader());

    // Back/forward bfcache
    window.addEventListener('pageshow', e => { if (e.persisted) hidePreloader(true); });

    // ── jQuery AJAX (DataTables etc.) ──────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        if (window.jQuery) {
            $(document)
                .ajaxStart(() => showPreloader())
                .ajaxStop(() => hidePreloader());
        }
    });

    // ── Native fetch() patch ───────────────────────────────
    (function () {
        const _fetch = window.fetch;
        window.silentFetch = _fetch;          // ← add this line
        window.fetch = function (...args) {
            showPreloader();
            return _fetch.apply(this, args).finally(() => hidePreloader());
        };
    })();

    // ── XMLHttpRequest patch (non-jQuery libs) ─────────────
    (function () {
        const _open = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function (...args) {
            this.addEventListener('loadstart', () => showPreloader());
            this.addEventListener('loadend', () => hidePreloader());
            return _open.apply(this, args);
        };
    })();

    // Safety fallback — kill after 10s
    window.addEventListener('load', () => setTimeout(() => hidePreloader(true), 10000));



    // Mobile menu
    document.getElementById('mobile-menu-btn')?.addEventListener('click', () => {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });
    // Auto-dismiss flash after 5s
    setTimeout(() => {
        ['flash-success','flash-error','flash-warning'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        });
    }, 5000);
    // PWA install
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', e => {
        e.preventDefault(); deferredPrompt = e;
        const b = document.createElement('div');
        b.id = 'pwa-banner';
        b.innerHTML = `<div style="position:fixed;bottom:80px;left:16px;right:16px;z-index:9999;background:#1f2937;color:#fff;border-radius:12px;padding:12px 16px;display:flex;align-items:center;gap:12px;box-shadow:0 4px 24px rgba(0,0,0,0.3)">
            <span style="font-size:24px">🏀</span>
            <div style="flex:1"><div style="font-size:13px;font-weight:600">Install Basketball Academy</div><div style="font-size:11px;color:#9ca3af">Add to home screen</div></div>
            <button onclick="installPWA()" style="background:#f97316;color:#fff;border:none;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer">Install</button>
            <button onclick="document.getElementById('pwa-banner').remove()" style="background:transparent;color:#9ca3af;border:none;font-size:20px;cursor:pointer">×</button>
        </div>`;
        document.body.appendChild(b);
    });
    async function installPWA() {
        if (!deferredPrompt) return;
        deferredPrompt.prompt(); await deferredPrompt.userChoice;
        deferredPrompt = null; document.getElementById('pwa-banner')?.remove();
    }
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(()=>{}));
    }
    // Global CSRF for fetch
    window.CSRF = document.querySelector('meta[name="csrf-token"]').content;
</script>
@stack('scripts')
</body>
</html>
