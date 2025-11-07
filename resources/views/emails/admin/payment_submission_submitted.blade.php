@php
    $user = $submission->user;
    $account = $submission->paymentAccount;
    $formatCurrency = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengajuan Pembayaran Baru</title>
</head>
<body style="font-family: Arial, sans-serif; color: #2d3748; background-color: #f7fafc; padding: 24px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 640px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden;">
        <tr>
            <td style="background-color: #2d3748; color: #ffffff; padding: 16px 24px;">
                <h2 style="margin: 0; font-size: 20px;">Pengajuan Pembayaran Baru</h2>
            </td>
        </tr>
        <tr>
            <td style="padding: 24px;">
                <p style="margin-top: 0;">Halo Admin,</p>
                <p>
                    Pengguna <strong>{{ $user?->store_name ?? $user?->name ?? 'Tidak diketahui' }}</strong>
                    telah mengirim konfirmasi pembayaran untuk paket <strong>{{ $submission->plan_name ?? '-' }}</strong>.
                </p>

                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 24px 0;">
                    <tr>
                        <td style="padding: 8px; border: 1px solid #e2e8f0; font-weight: bold;">Tanggal Pengajuan</td>
                        <td style="padding: 8px; border: 1px solid #e2e8f0;">
                            {{ optional($submission->created_at)->timezone(config('app.timezone'))->format('d M Y H:i') ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #e2e8f0; font-weight: bold;">Nama Pengirim</td>
                        <td style="padding: 8px; border: 1px solid #e2e8f0;">{{ $submission->payer_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #e2e8f0; font-weight: bold;">Nominal Transfer</td>
                        <td style="padding: 8px; border: 1px solid #e2e8f0;">{{ $formatCurrency($submission->paid_amount) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #e2e8f0; font-weight: bold;">Kode Unik</td>
                        <td style="padding: 8px; border: 1px solid #e2e8f0;">{{ $submission->unique_code ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #e2e8f0; font-weight: bold;">Rekening Tujuan</td>
                        <td style="padding: 8px; border: 1px solid #e2e8f0;">
                            {{ $account?->bank_name ?? data_get($submission->destination_snapshot, 'bank_name') ?? 'Transfer' }}
                            - {{ $account?->account_number ?? data_get($submission->destination_snapshot, 'account_number') ?? '-' }}<br>
                            <span style="color: #718096;">a.n {{ $account?->account_holder ?? data_get($submission->destination_snapshot, 'account_holder') ?? '-' }}</span>
                        </td>
                    </tr>
                </table>

                <p>
                    Silakan tinjau detail lengkap dan lakukan verifikasi melalui tautan berikut:
                </p>
                <p>
                    <a href="{{ $detailsUrl }}" style="display: inline-block; padding: 12px 20px; background-color: #2d3748; color: #ffffff; text-decoration: none; border-radius: 4px;">
                        Buka Detail Pembayaran
                    </a>
                </p>

                <p style="margin-bottom: 0;">Terima kasih.</p>
            </td>
        </tr>
        <tr>
            <td style="background-color: #f1f5f9; padding: 16px 24px; color: #94a3b8; font-size: 12px;">
                Email ini dikirim otomatis oleh sistem TOGA POS.
            </td>
        </tr>
    </table>
</body>
</html>
