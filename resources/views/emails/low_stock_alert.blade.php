@component('mail::message')
@include('emails.partials.brand-header', [
    'title' => 'Peringatan Stok Minimum',
    'subtitle' => 'Segera lakukan pengisian ulang untuk item berikut',
])

<div style="background: #FEF9C3; border: 1px solid #FACC15; border-radius: 14px; padding: 18px; margin-bottom: 20px; color: #92400E; font-size: 14px;">
    <strong>Perhatian!</strong> Stok beberapa bahan baku/produk telah berada di bawah batas minimum. Cek daftar berikut dan jadwalkan pengadaan sebelum kehabisan.
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; font-size: 14px; color: #1F2937;">
    <thead>
        <tr style="background: #F3F4F6;">
            <th align="left" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">SKU</th>
            <th align="left" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">Nama</th>
            <th align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">Stok Saat Ini</th>
            <th align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">Batas Minimum</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $it)
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #E5E7EB;">{{ $it['sku'] }}</td>
                <td style="padding: 10px; border-bottom: 1px solid #E5E7EB;">{{ $it['name'] }}</td>
                <td align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB; color: #DC2626; font-weight: 600;">{{ number_format($it['stock'], 4) }}</td>
                <td align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">{{ number_format($it['min'], 4) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<p style="margin: 20px 0 0; font-size: 14px; color: #4B5563;">Gunakan menu Inventory di TOGA POS untuk memperbarui stok setelah pengisian ulang.</p>

@component('mail::subcopy')
Email ini dikirim otomatis oleh {{ $appName }}.
@endcomponent
@endcomponent
