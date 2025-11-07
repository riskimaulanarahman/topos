@component('mail::message')
@include('emails.partials.brand-header', [
    'title' => 'Ringkasan Stok Minimum',
    'subtitle' => 'Ikhtisar harian bahan baku kritis',
])

@if(empty($items))
    <div style="background: #ECFDF5; border: 1px solid #6EE7B7; border-radius: 14px; padding: 18px; color: #047857; font-size: 14px;">
        Semua aman! Tidak ada bahan baku yang berada di bawah batas minimum hari ini.
    </div>
@else
    <p style="margin: 0 0 16px; font-size: 14px; color: #374151;">
        Berikut daftar bahan baku yang perlu mendapat perhatian:
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; font-size: 14px; color: #1F2937;">
        <thead>
            <tr style="background: #F3F4F6;">
                <th align="left" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">SKU</th>
                <th align="left" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">Nama</th>
                <th align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">Stok</th>
                <th align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">Minimum</th>
                <th align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">Selisih</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $it)
                @php($difference = ($it['stock'] ?? 0) - ($it['min'] ?? 0))
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #E5E7EB;">{{ $it['sku'] }}</td>
                    <td style="padding: 10px; border-bottom: 1px solid #E5E7EB;">{{ $it['name'] }}</td>
                    <td align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB; color: #DC2626; font-weight: 600;">{{ number_format($it['stock'], 4) }}</td>
                    <td align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">{{ number_format($it['min'], 4) }}</td>
                    <td align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB; color: {{ $difference >= 0 ? '#047857' : '#DC2626' }};">{{ number_format($difference, 4) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="margin: 16px 0 0; font-size: 14px; color: #4B5563;">Berikan prioritas pada item dengan selisih negatif untuk menjaga operasional berjalan lancar.</p>
@endif

@component('mail::subcopy')
Email ini dikirim otomatis oleh {{ $appName }}.
@endcomponent
@endcomponent
