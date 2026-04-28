<x-guest-layout>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── Break out of Breeze guest layout constraints ── */
    body > div {
        min-height: 100vh !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
        display: block !important;
        align-items: unset !important;
        justify-content: unset !important;
    }
    body > div > div {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        overflow: visible !important;
    }

    body {
        font-family: 'DM Sans', sans-serif;
        background: #0d0d0d;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    /* ── Page shell ── */
    .shell {
        width: 100vw;
        min-height: 100vh;
        display: flex;
    }

    /* ── Left panel ── */
    .panel-left {
        flex: 1.1;
        position: relative;
        background: #0f0f0f;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 56px 52px;
        overflow: hidden;
    }

    .court-bg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: .13;
    }

    .glow {
        position: absolute;
        width: 480px;
        height: 480px;
        border-radius: 50%;
        background: radial-gradient(circle, #f97316 0%, transparent 70%);
        top: -120px;
        right: -160px;
        opacity: .22;
        animation: glowPulse 6s ease-in-out infinite alternate;
    }
    @keyframes glowPulse {
        from { opacity:.18; transform:scale(1); }
        to   { opacity:.28; transform:scale(1.08); }
    }

    .ball-art {
        position: absolute;
        top: 60px;
        right: -30px;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: linear-gradient(135deg, #fb923c 0%, #f97316 40%, #c2410c 100%);
        opacity: .9;
        box-shadow: inset -28px -28px 60px rgba(0,0,0,.5), inset 14px 14px 40px rgba(255,180,100,.2);
        animation: ballFloat 7s ease-in-out infinite alternate;
    }
    .ball-art::before, .ball-art::after {
        content:''; position:absolute; border-radius:50%;
        border: 2.5px solid rgba(0,0,0,.35);
    }
    .ball-art::before {
        width:100%; height:56%;
        top:50%; left:50%;
        transform:translate(-50%,-50%);
    }
    .ball-art::after {
        width:56%; height:100%;
        top:50%; left:50%;
        transform:translate(-50%,-50%);
    }
    @keyframes ballFloat {
        from { transform: translateY(0) rotate(-8deg); }
        to   { transform: translateY(-22px) rotate(5deg); }
    }

    .left-tag {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .2em;
        color: #f97316;
        text-transform: uppercase;
        margin-bottom: 16px;
        position: relative;
        z-index: 2;
        animation: fadeUp .8s ease both;
    }
    .left-title {
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(52px, 6.5vw, 88px);
        line-height: .92;
        color: #fff;
        position: relative;
        z-index: 2;
        animation: fadeUp .8s .1s ease both;
    }
    .left-title span { color: #f97316; }
    .left-sub {
        margin-top: 20px;
        font-size: 15px;
        font-weight: 300;
        color: #6b7280;
        line-height: 1.7;
        max-width: 340px;
        position: relative;
        z-index: 2;
        animation: fadeUp .8s .2s ease both;
    }
    .left-stats {
        display: flex;
        gap: 32px;
        margin-top: 40px;
        position: relative;
        z-index: 2;
        animation: fadeUp .8s .3s ease both;
    }
    .stat-item { display: flex; flex-direction: column; gap: 3px; }
    .stat-num  { font-family: 'Bebas Neue', sans-serif; font-size: 34px; color: #fff; line-height: 1; }
    .stat-lbl  { font-size: 11px; font-weight: 500; color: #4b5563; text-transform: uppercase; letter-spacing: .1em; }

    /* ── Right panel ── */
    .panel-right {
        width: 460px;
        min-width: 460px;
        background: #fafaf9;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 56px 48px;
        position: relative;
        overflow: hidden;
    }
    .panel-right::before {
        content:'';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, #f97316, #fb923c, #fdba74);
    }
    .panel-right::after {
        content:'';
        position: absolute;
        bottom: -80px; right: -80px;
        width: 240px; height: 240px;
        border-radius: 50%;
        background: #f97316;
        opacity: .04;
    }

    .form-head { margin-bottom: 36px; animation: fadeUp .7s .1s ease both; }
    .form-head p {
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .18em;
        color: #f97316;
        text-transform: uppercase;
        margin-bottom: 8px;
    }
    .form-head h2 {
        font-size: 28px;
        font-weight: 600;
        color: #111;
        line-height: 1.2;
    }
    .form-head h2 span { color: #9ca3af; font-weight: 300; }

    /* ── Field ── */
    .field { margin-bottom: 20px; animation: fadeUp .7s .2s ease both; }
    .field label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        letter-spacing: .06em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }
    .field-wrap {
        position: relative;
        display: block;
    }
    .field-wrap .fi {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        display: flex;
        align-items: center;
        color: #9ca3af;
        transition: color .2s;
        z-index: 1;
    }
    .field-wrap:focus-within .fi { color: #f97316; }
    .field-wrap input {
        display: block;
        width: 100%;
        height: 48px;
        padding: 0 44px 0 42px;
        background: #fff;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        font-family: 'DM Sans', sans-serif;
        font-size: 14px;
        color: #111;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }
    .field-wrap input::placeholder { color: #c4c4c4; }
    .field-wrap input:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 3px rgba(249,115,22,.12);
    }
    .field-wrap .pi-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        color: #9ca3af;
        line-height: 0;
        transition: color .2s;
        z-index: 1;
    }
    .field-wrap .pi-btn:hover { color: #f97316; }
    .field-error { font-size: 12px; color: #ef4444; margin-top: 5px; }

    /* ── Remember row ── */
    .form-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 28px;
        animation: fadeUp .7s .3s ease both;
    }
    .remember {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 13px;
        color: #4b5563;
        user-select: none;
    }
    .remember input[type=checkbox] {
        appearance: none;
        -webkit-appearance: none;
        width: 16px;
        height: 16px;
        border: 1.5px solid #d1d5db;
        border-radius: 4px;
        cursor: pointer;
        position: relative;
        transition: .2s;
        flex-shrink: 0;
    }
    .remember input[type=checkbox]:checked {
        background: #f97316;
        border-color: #f97316;
    }
    .remember input[type=checkbox]:checked::after {
        content: '';
        position: absolute;
        left: 3px; top: 1px;
        width: 8px; height: 5px;
        border-left: 2px solid #fff;
        border-bottom: 2px solid #fff;
        transform: rotate(-45deg);
    }

    /* ── Submit button ── */
    .btn-submit {
        width: 100%;
        height: 52px;
        background: #111;
        color: #fff;
        font-family: 'DM Sans', sans-serif;
        font-size: 15px;
        font-weight: 600;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: transform .15s, box-shadow .15s;
        letter-spacing: .02em;
        animation: fadeUp .7s .4s ease both;
    }
    .btn-submit::before {
        content:'';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, #f97316, #ea580c);
        transform: translateX(-101%);
        transition: transform .35s cubic-bezier(.4,0,.2,1);
    }
    .btn-submit:hover::before { transform: translateX(0); }
    .btn-submit:hover { box-shadow: 0 8px 24px rgba(249,115,22,.35); transform: translateY(-1px); }
    .btn-submit:active { transform: scale(.98); }
    .btn-submit span { position: relative; z-index: 1; }

    /* ── Session / validation errors ── */
    .session-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 13px;
        color: #dc2626;
        margin-bottom: 20px;
        animation: fadeUp .5s ease both;
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Responsive ── */
    @media (max-width: 820px) {
        .shell { flex-direction: column; }
        .panel-left {
            flex: none;
            height: 240px;
            padding: 32px 28px;
            justify-content: flex-end;
        }
        .ball-art { width: 160px; height: 160px; top: 20px; right: -10px; }
        .left-title { font-size: 42px; }
        .left-sub, .left-stats { display: none; }
        .panel-right {
            width: 100%;
            min-width: unset;
            padding: 36px 28px 48px;
        }
    }

    .fill-current.text-gray-500 { display: none !important; }
</style>

<div class="shell">

    {{-- ── Left panel ── --}}
    <div class="panel-left">
        <div class="glow"></div>

        <svg class="court-bg" viewBox="0 0 600 700" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <rect x="40" y="40" width="520" height="620" rx="8" stroke="#f97316" stroke-width="2"/>
            <line x1="40" y1="350" x2="560" y2="350" stroke="#f97316" stroke-width="1.5"/>
            <circle cx="300" cy="350" r="70" stroke="#f97316" stroke-width="1.5"/>
            <circle cx="300" cy="350" r="6" fill="#f97316"/>
            <rect x="160" y="40" width="280" height="170" rx="4" stroke="#f97316" stroke-width="1.5"/>
            <rect x="160" y="490" width="280" height="170" rx="4" stroke="#f97316" stroke-width="1.5"/>
            <path d="M160 210 Q300 310 440 210" stroke="#f97316" stroke-width="1.5" fill="none"/>
            <path d="M160 490 Q300 390 440 490" stroke="#f97316" stroke-width="1.5" fill="none"/>
            <circle cx="300" cy="130" r="50" stroke="#f97316" stroke-width="1.5"/>
            <circle cx="300" cy="570" r="50" stroke="#f97316" stroke-width="1.5"/>
            <line x1="270" y1="40" x2="270" y2="210" stroke="#f97316" stroke-width="1"/>
            <line x1="330" y1="40" x2="330" y2="210" stroke="#f97316" stroke-width="1"/>
            <line x1="270" y1="490" x2="270" y2="660" stroke="#f97316" stroke-width="1"/>
            <line x1="330" y1="490" x2="330" y2="660" stroke="#f97316" stroke-width="1"/>
        </svg>

        <div class="ball-art"></div>

        <div class="left-tag">Be Ballers. 2026 · Sydney, AU</div>
        <div class="left-title">Basketball<br><span>Academy</span></div>
        <p class="left-sub">Train harder. Play smarter. Track every session, every player, every moment that matters.</p>
        <div class="left-stats">
            <div class="stat-item"><span class="stat-num">12+</span><span class="stat-lbl">Batches</span></div>
            <div class="stat-item"><span class="stat-num">200+</span><span class="stat-lbl">Players</span></div>
            <div class="stat-item"><span class="stat-num">98%</span><span class="stat-lbl">Uptime</span></div>
        </div>
    </div>

    {{-- ── Right panel ── --}}
    <div class="panel-right">

        <div class="form-head">
            <p>Coach Portal</p>
            <h2>Welcome back<span>.</span></h2>
        </div>

        <x-auth-session-status :status="session('status')" />

        @if($errors->any())
        <div class="session-error">
            @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            {{-- Email --}}
            <div class="field">
                <label for="email">Email address</label>
                <div class="field-wrap">
                    <span class="fi">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="4" width="20" height="16" rx="2"/>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                    </span>
                    <input type="email" id="email" name="email"
                           value="{{ old('email') }}"
                           placeholder="coach@academy.com"
                           required autocomplete="email">
                </div>
                @error('email') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            {{-- Password --}}
            <div class="field">
                <label for="password">Password</label>
                <div class="field-wrap">
                    <span class="fi">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input type="password" id="password" name="password"
                           placeholder="••••••••"
                           required autocomplete="current-password">
                    <button type="button" class="pi-btn" onclick="togglePw(this)" aria-label="Toggle password">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                @error('password') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            {{-- Remember ── --}}
            <div class="form-footer">
                <label class="remember">
                    <input type="checkbox" name="remember">
                    Keep me signed in
                </label>
            </div>

            <button type="submit" class="btn-submit">
                <span>Sign in to Dashboard →</span>
            </button>

        </form>
    </div>

</div>

<script>
function togglePw(btn) {
    const input = btn.closest('.field-wrap').querySelector('input');
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.innerHTML = isText
        ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>`
        : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-8-10-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 10 8 10 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="2" y1="2" x2="22" y2="22"/></svg>`;
}
</script>

</x-guest-layout>