@component('mail::message')
@include('emails.partials.brand-header', [
    'title' => 'Verifikasi Email Anda',
    'subtitle' => 'Aktifkan akun dan mulai gunakan TOGA POS',
])

<p style="font-size: 14px; color: #374151; margin-bottom: 16px;">
    Halo <strong>{{ $userName }}</strong>,<br>
    Terima kasih sudah mendaftar di {{ $appName }}. Silakan konfirmasi email untuk mengaktifkan akun Anda.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 14px; padding: 18px; margin-bottom: 20px;">
    <tr>
        <td style="font-size: 14px; color: #4B5563;">
            <strong style="color: #111827;">Mengapa perlu verifikasi?</strong>
            <ul style="margin: 12px 0 0 18px; padding: 0;">
                <li style="margin-bottom: 6px;">Mengamankan akses ke data kasir dan inventori.</li>
                <li style="margin-bottom: 6px;">Mengaktifkan fitur seperti laporan dan notifikasi stok.</li>
                <li style="margin-bottom: 0;">Memastikan kami dapat menghubungi Anda jika terjadi perubahan penting.</li>
            </ul>
        </td>
    </tr>
</table>

@component('mail::button', ['url' => $verificationUrl, 'color' => 'primary'])
Aktifkan Akun
@endcomponent

<p style="font-size: 14px; color: #6B7280; margin-top: 16px;">
    Tautan verifikasi ini berlaku selama <strong>60 menit</strong>. Jika Anda tidak merasa melakukan pendaftaran, abaikan email ini.
</p>

<p style="font-size: 13px; color: #9CA3AF; margin-top: 16px;">
    Jika tombol di atas tidak berfungsi, salin dan tempel tautan berikut ke browser Anda:<br>
    <span style="word-break: break-word; color: #1D4ED8;">{{ $verificationUrl }}</span>
</p>

@component('mail::subcopy')
Email ini dikirim otomatis oleh {{ $appName }}.
@endcomponent
@endcomponent
