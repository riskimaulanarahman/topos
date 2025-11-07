{{-- resources/views/auth/verify-result.blade.php --}}
@php
  // Nilai aman (fallback) bila variabel tidak dikirim dari controller
  $status       = $status       ?? 'info';
  $title        = $title        ?? 'Verifikasi Email';
  $message      = $message      ?? 'Status verifikasi email.';
  $code         = $code         ?? null;
  $prefillEmail = $prefillEmail ?? '';

  // Peta alert Bootstrap & ikon sederhana (emoji) per status
  $alertMap = [
    'verified'         => ['class' => 'alert-success', 'icon' => '✅'],
    'already_verified' => ['class' => 'alert-success', 'icon' => '✅'],
    'invalid'          => ['class' => 'alert-warning', 'icon' => '⚠️'],
    'expired'          => ['class' => 'alert-primary', 'icon' => '⏰'],
    'error'            => ['class' => 'alert-danger',  'icon' => '❌'],
    'info'             => ['class' => 'alert-info',    'icon' => 'ℹ️'],
  ];
  $alert = $alertMap[$status] ?? $alertMap['info'];
@endphp
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title }}</title>
  {{-- Bootstrap 5 (CSS & JS via CDN) --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(135deg, #f8f9fa, #eef1f5); min-height: 100vh; }
    .card-verify { border-radius: 1rem; }
    .emoji-badge { font-size: 1.75rem; line-height: 1; }
  </style>
</head>
<body>
  <main class="container py-5">
    <div class="row justify-content-center">
      <div class="col-12 col-md-10 col-lg-7">
        <div class="card shadow-sm card-verify">
          <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-start gap-3 mb-3">
              <div class="emoji-badge" aria-hidden="true">{{ $alert['icon'] }}</div>
              <div>
                <h1 class="h4 mb-1">{{ $title }}</h1>
              </div>
            </div>

            {{-- Alert kontekstual --}}
            <div class="alert {{ $alert['class'] }} mb-4" role="alert">
              @switch($status)
                @case('verified')
                  Email kamu berhasil diverifikasi. Kamu bisa lanjut login.
                  @break
                @case('already_verified')
                  Email ini sudah terverifikasi sebelumnya. Silakan login.
                  @break
                @case('invalid')
                  Tautan verifikasi tidak valid. Minta tautan baru atau hubungi dukungan.
                  @break
                @case('expired')
                  Tautan verifikasi sudah kadaluarsa. Kirim ulang tautan verifikasi di bawah.
                  @break
                @case('error')
                  Terjadi kesalahan saat memproses verifikasi. Coba beberapa saat lagi.
                  @break
                @default
                  Informasi verifikasi ditampilkan di atas.
              @endswitch
            </div>
          </div>

          <div class="card-footer bg-light text-muted small text-center">
            Jika ini bukan kamu, abaikan halaman ini.<br> &copy; {{ date('Y') }} • TOGA POS
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
