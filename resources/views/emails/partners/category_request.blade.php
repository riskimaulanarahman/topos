@component('mail::message')
# Permintaan Penambahan Kategori

Halo Owner,

Mitra **{{ $partner->name }}** mengajukan penambahan kategori pada outlet **{{ $outlet->name ?? '-' }}**.

@component('mail::panel')
- **Mitra:** {{ $partner->name }} ({{ $partner->email }})
- **Nama Kategori:** {{ $payload['name'] ?? '-' }}
@if(!empty($payload['notes']))
- **Catatan Mitra:** {{ $payload['notes'] }}
@endif
@endcomponent

Silakan tinjau permintaan ini di dashboard dan buat kategori bila disetujui.

Terima kasih.

{{ config('app.name') }}
@endcomponent
