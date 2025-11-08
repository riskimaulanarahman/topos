@component('mail::message')
# Pembaruan Permintaan Kategori

Halo {{ $changeRequest->target->user->name }},

Permintaan akses kategori Anda pada outlet **{{ $changeRequest->outlet->name }}** telah {{ $changeRequest->status === 'approved' ? 'disetujui' : 'ditolak' }}.

@if ($changeRequest->status === 'approved')
- Kategori ditambahkan: {{ collect($changeRequest->payload['add'] ?? [])->count() }}
- Kategori dihapus: {{ collect($changeRequest->payload['remove'] ?? [])->count() }}
@else
@if ($changeRequest->review_notes)
> {{ $changeRequest->review_notes }}
@endif
@endif

Silakan masuk ke aplikasi untuk melihat detail akses terbaru Anda.

Terima kasih,
{{ config('app.name') }}
@endcomponent
