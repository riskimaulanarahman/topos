@component('mail::message')
# Undangan Mitra Outlet

Halo {{ $invitation->user->name }},

Anda diundang untuk bergabung sebagai **{{ strtoupper($invitation->role) }}** pada outlet **{{ $outlet->name }}**.

@component('mail::button', ['url' => route('partner-invitations.show', $invitation->invitation_token)])
Terima Undangan
@endcomponent

Jika tombol tidak berfungsi, salin dan buka tautan berikut di browser Anda:
{{ route('partner-invitations.show', $invitation->invitation_token) }}

Terima kasih,
{{ config('app.name') }}
@endcomponent
