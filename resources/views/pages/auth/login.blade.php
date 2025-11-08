@extends('layouts.auth')

@section('title', 'TOGA POS')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/bootstrap-social/bootstrap-social.css') }}">
    <style>
        :root {
            --gold: #FFD700;
            --text: #111827;
            --muted: #6B7280;
            --border: #E5E7EB;
            --bg: #FFFFFF;
            --shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        .toga-title { color: var(--text); font-weight: 800; margin-bottom: 4px; }
        .toga-subtitle { color: var(--muted); margin-bottom: 24px; }
        .toga-login {
            background: var(--bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
        }
        .toga-left { padding: 40px 32px; }
        .toga-right { background: linear-gradient(180deg, #FFFBE6 0%, #FFFFFF 60%); display:flex; align-items:center; justify-content:center; padding: 24px; }
        .toga-right svg { width: 100%; max-width: 460px; height: auto; }

        .form-group { margin-bottom: 16px; }
        .input-with-icon label { font-weight: 600; color: var(--text); margin-bottom: 8px; display:block; }
        .input-row { display:flex; gap: 12px; align-items:center; }
        .icon-pill { width: 48px; height: 48px; border: 1px solid var(--border); border-radius: 10px; display:flex; align-items:center; justify-content:center; color:#6B7280; background:#fff; }
        .input-with-icon input.form-control {
            height: 48px;
            padding-left: 14px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text);
            box-shadow: none;
            flex: 1;
        }
        .input-with-icon input.form-control::placeholder { color: #9CA3AF; opacity: 1; }
        .input-with-icon input.form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.15);
        }
        .actions { display:flex; gap: 12px; margin-top: 8px; }
        .btn-gold { background: var(--gold); color: #FFFFFF; font-weight: 700; border: none; padding: 12px 16px; border-radius: 10px; width: 100%; }
        .btn-outline-gray { background: transparent; color: #374151; border: 1px solid var(--border); font-weight: 600; padding: 12px 16px; border-radius: 10px; width: 100%; }
        .btn-gold:hover { filter: brightness(0.98); }
        .btn-outline-gray:hover { border-color: #CFD4DC; }
        .extra-links { margin-top: 12px; }
        .forgot-link { color: var(--muted); font-size: 14px; text-decoration: none; }
        .forgot-link:hover { color: #374151; text-decoration: underline; }
        .remember { display:flex; align-items:center; gap:8px; color:#4B5563; user-select:none; }
        .toggle-password { height:48px; padding:0 12px; border:1px solid var(--border); border-radius:10px; background:#fff; color:#6B7280; }
        .toggle-password:hover { background:#F9FAFB; }

        /* Center the content vertically and reduce spacing under the logo on this page */
        .section { min-height: 100vh; display: flex; align-items: center; padding: 16px 0; }
        .section .container.mt-5 { margin-top: 0 !important; }
        .login-brand { display: none !important; }
        .toga-logo { display:flex; justify-content:center; margin-bottom: 12px; }
        .toga-logo img { width: 180px; height: auto; }

        @media (max-width: 991.98px) {
            .toga-login { grid-template-columns: 1fr; }
            .toga-right { display: none; }
        }
    </style>
@endpush

@section('main')
    <div class="toga-login">
        <div class="toga-left">
            <h3 class="toga-title">Sign in to TOGA POS</h3>
<p class="toga-subtitle">Welcome back. Please enter your details.</p>

            <form method="POST" action="{{ route('login') }}" class="js-disable-on-submit" novalidate>
                @csrf

                <div class="toga-logo">
                    <img src="{{ asset('img/toga-gold-ts.png') }}" alt="TOGA POS logo" />
                </div>

                <div class="form-group input-with-icon">
                    <label for="email">Username or Email</label>
                    <div class="input-row">
                        <span class="icon-pill"><i class="fa-solid fa-user"></i></span>
                        <input id="email" type="email"
                               class="form-control @error('email') is-invalid @enderror"
                               name="email" value="{{ old('email') }}" autocomplete="username" placeholder="you@company.com" tabindex="1" autofocus>
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group input-with-icon">
                    <label for="password">Password</label>
                    <div class="input-row">
                        <span class="icon-pill"><i class="fa-solid fa-lock"></i></span>
                        <input id="password" type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               name="password" autocomplete="current-password" placeholder="••••••••" tabindex="2" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
                        <button type="button" class="toggle-password" data-target="#password" aria-label="Show password" aria-pressed="false">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group remember-row">
                    <label class="remember"><input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}> Remember me</label>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-gold" tabindex="3">Login</button>
                </div>

                <div class="extra-links">
                    <a href="{{ route('password.request') }}" class="forgot-link">Forgot Password?</a>
                </div>
            </form>
        </div>
        <div class="toga-right" aria-hidden="true">
                <img width="300" height="300" src="{{ asset('img/pos icon ts.png') }}" alt="TOGA POS" />
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Toggle password visibility
        document.addEventListener('click', function(e){
            const btn = e.target.closest('.toggle-password');
            if(!btn) return;
            const targetSel = btn.getAttribute('data-target');
            const input = document.querySelector(targetSel);
            if(!input) return;
            const isShown = input.type === 'text';
            input.type = isShown ? 'password' : 'text';
            btn.setAttribute('aria-pressed', String(!isShown));
            const icon = btn.querySelector('i');
            if(icon){ icon.className = isShown ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'; }
        });

        // Disable submit with spinner
        document.addEventListener('submit', function(e){
            const form = e.target.closest('.js-disable-on-submit');
            if(!form) return;
            const btn = form.querySelector('button[type="submit"]');
            if(btn){
                btn.disabled = true;
                const original = btn.innerHTML;
                btn.setAttribute('data-original', original);
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="margin-right:8px"></span> Processing...';
            }
        }, true);
    </script>
@endpush
