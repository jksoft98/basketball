<x-guest-layout>
    <style>
        body {
            background: #f3f4f6;
            font-family: Arial, sans-serif;
        }

        .login-container {
            /* min-height: 100vh; */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .login-box h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: bold;
            color: #111827;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-size: 14px;
            color: #374151;
        }

        .form-group input {
            width: 100%;
            padding: 11px;
            margin-top: 5px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            outline: none;
            transition: 0.2s;
        }

        .form-group input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 2px rgba(79,70,229,0.1);
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            margin-top: 10px;
        }

        .form-footer a {
            color: #4f46e5;
            text-decoration: none;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        /* Custom Button */
        .btn-login {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background: linear-gradient(135deg, #f97316, #f97316);
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #f97316, #f97316);
            transform: translateY(-1px);
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .error {
            color: red;
            font-size: 13px;
            margin-top: 5px;
        }
        .fill-current.text-gray-500 {
            display: none !important;
        }
    </style>

    <div class="login-container">
        <div class="login-box">

            <h2>🏀Basketball Academy</h2>

            <!-- Session Status -->
            <x-auth-session-status :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email -->
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                    @error('email')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                    @error('password')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Remember + Forgot -->
                <div class="form-footer">
                    <label>
                        <input type="checkbox" name="remember"> Remember
                    </label>

                    @if (Route::has('password.request'))
                        <!-- <a href="{{ route('password.request') }}">Forgot?</a> -->
                    @endif
                </div>

                <!-- Button -->
                <button type="submit" class="btn-login">
                    Log in
                </button>
            </form>

        </div>
    </div>
</x-guest-layout>