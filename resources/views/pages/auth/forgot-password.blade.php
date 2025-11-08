@extends('layouts.auth')

@section('title', 'Forgot Password – TOGA POS')

@push('style')
    <style>
        :root { --gold:#FFD700; --text:#111827; --muted:#6B7280; --border:#E5E7EB; --bg:#FFFFFF; --shadow:0 10px 30px rgba(0,0,0,0.08); }
        .toga-login{background:#fff;border-radius:16px;box-shadow:var(--shadow);display:grid;grid-template-columns:1fr 1fr;overflow:hidden}
        .toga-left{padding:40px 32px}
        .form-group{margin-bottom:16px}
        .toga-title{color:var(--text);font-weight:800;margin:0 0 6px}
        .toga-subtitle{color:var(--muted);margin:0 0 16px}
        .input-row{display:flex;gap:12px;align-items:center}
        .icon-pill{width:48px;height:48px;border:1px solid var(--border);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#6B7280;background:#fff}
        .form-control{height:48px;border:1px solid var(--border);border-radius:10px}
        .form-control:focus{border-color:var(--gold);box-shadow:0 0 0 4px rgba(255,215,0,0.15)}
        .btn-gold{background:var(--gold);color:#fff;border:none;border-radius:10px;padding:12px 16px;width:100%;font-weight:700}
        .extra-links{margin-top:12px}
        .forgot-link{color:var(--muted);text-decoration:none}
        .forgot-link:hover{color:#374151;text-decoration:underline}
        .toga-right{background:linear-gradient(180deg,#FFFBE6 0%,#FFFFFF 60%);display:flex;justify-content:center;align-items:center;padding:24px}
        .toga-right svg{width:100%;max-width:460px;height:auto}
        .toga-logo{display:flex;justify-content:center;margin-bottom:12px}
        .toga-logo img{width:180px;height:auto}
        .alert{border-radius:10px;padding:12px 14px;margin-bottom:12px}
        .alert-success{background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0}
        .section{min-height:100vh;display:flex;align-items:center;padding:16px 0}
        .section .container.mt-5{margin-top:0!important}
        .login-brand{display:none!important}
        @media(max-width:991.98px){.toga-login{grid-template-columns:1fr}.toga-right{display:none}}
    </style>
@endpush

@section('main')
<div class="toga-login">
  <div class="toga-left">
    <form method="POST" action="{{ route('password.email') }}" class="js-disable-on-submit" novalidate>
      @csrf
      <div class="toga-logo"><img src="{{ asset('img/toga-gold-ts.png') }}" alt="TOGA POS logo"></div>
      <h3 class="toga-title">Forgot Password</h3>
      <p class="toga-subtitle">Enter your email and we’ll send you a reset link.</p>

      @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif

      <div class="form-group">
        <label for="email">Email</label>
        <div class="input-row">
          <span class="icon-pill"><i class="fa-solid fa-envelope"></i></span>
          <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="you@company.com" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
        </div>
        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
      </div>

      <div class="actions"><button type="submit" class="btn btn-gold">Send reset link</button></div>

      <div class="extra-links"><a href="{{ route('login') }}" class="forgot-link">Back to login</a></div>
    </form>
  </div>
  <div class="toga-right" aria-hidden="true">
    <svg viewBox="0 0 600 480" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="POS cashier illustration">
      <defs><linearGradient id="goldGradientFP" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#FFE680"/><stop offset="100%" stop-color="#FFD700"/></linearGradient></defs>
      <rect x="40" y="300" width="520" height="120" rx="16" fill="#F3F4F6"/>
      <rect x="150" y="240" width="160" height="60" rx="10" fill="#E5E7EB"/>
      <rect x="170" y="210" width="180" height="40" rx="8" fill="url(#goldGradientFP)"/>
      <rect x="360" y="180" width="120" height="80" rx="10" fill="#111827"/>
      <rect x="370" y="190" width="100" height="60" rx="6" fill="#1F2937"/>
      <rect x="380" y="200" width="60" height="8" rx="4" fill="#10B981"/>
      <rect x="380" y="215" width="80" height="6" rx="3" fill="#374151"/>
    </svg>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Disable submit with spinner
  document.addEventListener('submit', function(e){
    const form = e.target.closest('.js-disable-on-submit');
    if(!form) return;
    const btn = form.querySelector('button[type="submit"]');
    if(btn){
      btn.disabled = true;
      const original = btn.innerHTML;
      btn.setAttribute('data-original', original);
      btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="margin-right:8px"></span> Sending...';
    }
  }, true);
</script>
@endpush

