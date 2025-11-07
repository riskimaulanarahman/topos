@component('mail::message')
# Permintaan Produk dari Mitra

Halo Owner,

Mitra **{{ $partner->name }}** mengajukan permintaan terkait produk pada outlet **{{ $outlet->name ?? '-' }}**.

@php $action = $payload['action'] ?? 'create'; @endphp

@component('mail::panel')
- **Mitra:** {{ $partner->name }} ({{ $partner->email }})
- **Jenis Permintaan:** {{ strtoupper($action) }}

@if($action === 'create')
    - **Nama Produk Baru:** {{ $payload['name'] ?? '-' }}
    @if(!empty($payload['category_name']))
    - **Kategori:** {{ $payload['category_name'] }}
    @endif
    @if(isset($payload['price']))
    - **Harga Jual:** Rp {{ number_format($payload['price'], 0, ',', '.') }}
    @endif
@elseif($action === 'update' && !empty($payload['product']))
    - **Produk Saat Ini:** {{ $payload['product']['name'] ?? '-' }}
    @if(isset($payload['product']['price']))
    - **Harga Saat Ini:** Rp {{ number_format($payload['product']['price'], 0, ',', '.') }}
    @endif
    @if(!empty($payload['product']['category_name']))
    - **Kategori Saat Ini:** {{ $payload['product']['category_name'] }}
    @endif
    - **Perubahan yang Diajukan:**
        @php $proposed = $payload['proposed'] ?? []; @endphp
        @if(array_filter([$proposed['name'] ?? null, $proposed['price'] ?? null, $proposed['category_name'] ?? null]))
        @if(!empty($proposed['name']))
        - Nama Baru: {{ $proposed['name'] }}
        @endif
        @if(!is_null($proposed['price']))
        - Harga Baru: Rp {{ number_format($proposed['price'], 0, ',', '.') }}
        @endif
        @if(!empty($proposed['category_name']))
        - Kategori Baru: {{ $proposed['category_name'] }}
        @endif
        @else
        - Tidak ada perubahan spesifik (lihat catatan).
        @endif
@elseif($action === 'delete' && !empty($payload['product']))
    - **Produk:** {{ $payload['product']['name'] ?? '-' }}
    @if(isset($payload['product']['price']))
    - **Harga Saat Ini:** Rp {{ number_format($payload['product']['price'], 0, ',', '.') }}
    @endif
    @if(!empty($payload['product']['category_name']))
    - **Kategori:** {{ $payload['product']['category_name'] }}
    @endif
@endif

@if(!empty($payload['notes']))
- **Catatan Mitra:** {{ $payload['notes'] }}
@endif
@endcomponent

Silakan tinjau permintaan ini melalui dashboard owner.

Terima kasih.

{{ config('app.name') }}
@endcomponent
