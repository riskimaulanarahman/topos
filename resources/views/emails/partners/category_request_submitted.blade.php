@component('mail::message')
# Permintaan Akses Kategori

Halo {{ $changeRequest->outlet->owners()->pluck('name')->join(', ') ?? 'Owner' }},

{{ $changeRequest->requester->name }} mengajukan perubahan akses kategori untuk {{ $changeRequest->target->user->name }} pada outlet **{{ $changeRequest->outlet->name }}**.

**Tambah:** {{ collect($changeRequest->payload['add'] ?? [])->count() }} kategori<br>
**Hapus:** {{ collect($changeRequest->payload['remove'] ?? [])->count() }} kategori

@component('mail::button', ['url' => route('outlets.category-requests.index', $changeRequest->outlet_id)])
Tinjau Permintaan
@endcomponent

Terima kasih,
{{ config('app.name') }}
@endcomponent
