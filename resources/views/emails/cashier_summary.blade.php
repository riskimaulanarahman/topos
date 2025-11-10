@component('mail::message')
@php($netSales = $totals['net_sales'] ?? 0)
@php($salesTotal = $totals['sales'] ?? 0)
@php($refundTotal = $totals['refunds'] ?? 0)
@php($difference = $cashBalance['difference'] ?? 0)
@php($sessionRemarks = trim((string) ($session['remarks'] ?? '')))

@php($sessionTimezoneOffset = $sessionTimezoneOffset ?? null)
@php($resolveDate = function ($value, $fallback) use ($sessionTimezone, $sessionTimezoneOffset) {
    if ($value) {
        $carbon = \Illuminate\Support\Carbon::parse($value);
        if ($sessionTimezone) {
            return $carbon->setTimezone($sessionTimezone);
        }

        if ($sessionTimezoneOffset !== null) {
            return $carbon->setTimezone('UTC')->addMinutes($sessionTimezoneOffset);
        }

        return $carbon;
    }

    if ($fallback instanceof \Illuminate\Support\Carbon) {
        $copy = $fallback->copy();
        if ($sessionTimezone) {
            return $copy->setTimezone($sessionTimezone);
        }

        if ($sessionTimezoneOffset !== null) {
            return $copy->setTimezone('UTC')->addMinutes($sessionTimezoneOffset);
        }

        return $copy;
    }

    return null;
})
@php($openedAt = $resolveDate($session['opened_at'] ?? null, $report->session?->opened_at))
@php($closedAt = $resolveDate($session['closed_at'] ?? null, $report->session?->closed_at))

@include('emails.partials.brand-header', [
    'title' => 'Ringkasan Tutup Kasir',
    // 'subtitle' => optional($closedAt)->format('d F Y, H:i'),
])

<div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 16px; padding: 24px; margin-bottom: 24px;">
    <p style="margin: 0 0 16px; font-size: 15px; color: #111827;">
        Halo <strong>{{ $report->user->name ?? 'Kasir' }}</strong>,<br>
        berikut ringkasan singkat sesi kasir yang baru saja ditutup.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
        <tr>
            <td style="width: 33.333%; padding: 12px; border-radius: 12px; background: #FFF; border: 1px solid #E5E7EB;">
                <div style="font-size: 12px; text-transform: uppercase; color: #6B7280; letter-spacing: 0.6px;">Penjualan Bersih</div>
                <div style="font-size: 20px; font-weight: 700; color: #047857;">Rp{{ number_format($netSales, 0, ',', '.') }}</div>
            </td>
            <td style="width: 33.333%; padding: 12px; border-radius: 12px; background: #FFF; border: 1px solid #E5E7EB;">
                <div style="font-size: 12px; text-transform: uppercase; color: #6B7280; letter-spacing: 0.6px;">Total Transaksi</div>
                <div style="font-size: 20px; font-weight: 700; color: #1D4ED8;">{{ $transactions['total'] ?? 0 }}</div>
                <div style="font-size: 12px; color: #6B7280;">{{ $transactions['completed'] ?? 0 }} selesai • {{ $transactions['refunded'] ?? 0 }} refund</div>
            </td>
            <td style="width: 33.333%; padding: 12px; border-radius: 12px; background: #FFF; border: 1px solid #E5E7EB;">
                <div style="font-size: 12px; text-transform: uppercase; color: #6B7280; letter-spacing: 0.6px;">Selisih Kas</div>
                <div style="font-size: 20px; font-weight: 700; color: {{ $difference == 0 ? '#111827' : ($difference > 0 ? '#047857' : '#DC2626') }};">Rp{{ number_format($difference, 0, ',', '.') }}</div>
                <div style="font-size: 12px; color: #6B7280;">Kas dihitung: Rp{{ number_format($cashBalance['counted'] ?? 0, 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <div style="margin-bottom: 16px;">
        <div style="font-weight: 600; color: #111827; font-size: 15px; margin-bottom: 8px;">Informasi Sesi</div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #374151;">
            {{-- <tr><td style="padding: 4px 0; width: 45%;">Dibuka</td><td style="padding: 4px 0;">{{ optional($openedAt)->format('d M Y H:i') ?? '-' }}</td></tr>
            <tr><td style="padding: 4px 0;">Ditutup</td><td style="padding: 4px 0;">{{ optional($closedAt)->format('d M Y H:i') ?? '-' }}</td></tr> --}}
            <tr><td style="padding: 4px 0;">Modal awal</td><td style="padding: 4px 0;">Rp{{ number_format($session['opening_balance'] ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0;">Saldo akhir</td><td style="padding: 4px 0;">Rp{{ number_format($session['closing_balance'] ?? 0, 0, ',', '.') }}</td></tr>
            {{-- @if ($sessionRemarks !== '') --}}
                <tr>
                    <td style="padding: 4px 0;">Catatan</td>
                    <td style="padding: 4px 0;">{!! nl2br(e($sessionRemarks)) !!}</td>
                </tr>
            {{-- @endif --}}
        </table>
    </div>

    <div style="margin-bottom: 16px;">
        <div style="font-weight: 600; color: #111827; font-size: 15px; margin-bottom: 8px;">Ringkasan Penjualan</div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #374151;">
            <tr><td style="padding: 4px 0; width: 45%;">Total penjualan</td><td style="padding: 4px 0;">Rp{{ number_format($salesTotal, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0;">Total refund</td><td style="padding: 4px 0;">Rp{{ number_format($refundTotal, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0;">Penjualan bersih</td><td style="padding: 4px 0;">Rp{{ number_format($netSales, 0, ',', '.') }}</td></tr>
        </table>
    </div>

    <div style="margin-bottom: 16px;">
        <div style="font-weight: 600; color: #111827; font-size: 15px; margin-bottom: 8px;">Saldo Kas Tunai</div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #374151;">
            <tr><td style="padding: 4px 0; width: 45%;">Modal awal</td><td style="padding: 4px 0;">Rp{{ number_format($cashBalance['opening'] ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0;">Penjualan cash</td><td style="padding: 4px 0;">Rp{{ number_format($cashBalance['cash_sales'] ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0;">Refund cash</td><td style="padding: 4px 0;">Rp{{ number_format($cashBalance['cash_refunds'] ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0; font-weight: 600;">Pengeluaran kasir</td><td style="padding: 4px 0; font-weight: 600; color: #DC2626;">-Rp{{ number_format($cashBalance['cash_outflows'] ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0;">Estimasi kas</td><td style="padding: 4px 0;">Rp{{ number_format($cashBalance['expected'] ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0;">Kas dihitung</td><td style="padding: 4px 0;">Rp{{ number_format($cashBalance['counted'] ?? 0, 0, ',', '.') }}</td></tr>
        </table>
    </div>

    @if(!empty($outflows['by_category']))
        <div style="margin-bottom: 16px;">
            <div style="font-weight: 600; color: #111827; font-size: 15px; margin-bottom: 8px;">Detail Pengeluaran Kasir</div>
            <p style="margin: 0 0 8px; font-size: 14px; color: #6B7280;">
                Total pengeluaran: {{ $outflows['count'] ?? 0 }} transaksi • Nilai: Rp{{ number_format($outflows['total'] ?? 0, 0, ',', '.') }}
            </p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #374151; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th align="left" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Kategori</th>
                        <th align="right" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Jumlah</th>
                        <th align="right" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($outflows['by_category'] as $outflow)
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">
                                <div style="font-weight: 600;">{{ $outflow['label'] ?? ucfirst($outflow['category']) }}</div>
                            </td>
                            <td align="right" style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">
                                {{ $outflow['count'] ?? 0 }}x
                            </td>
                            <td align="right" style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB; color: #DC2626; font-weight: 600;">
                                -Rp{{ number_format($outflow['total'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div>
        <div style="font-weight: 600; color: #111827; font-size: 15px; margin-bottom: 8px;">Rincian Pembayaran</div>
        @if(empty($payments))
            <p style="margin: 0; font-size: 14px; color: #6B7280;">Tidak ada transaksi pembayaran yang tercatat.</p>
        @else
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #374151; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th align="left" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Metode</th>
                        <th align="right" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Transaksi</th>
                        <th align="right" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">{{ $payment['method'] }}</td>
                            <td align="right" style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">{{ $payment['transactions'] }}</td>
                            <td align="right" style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">Rp{{ number_format($payment['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if(!empty($productSalesItems))
        <div style="margin-top: 16px;">
            <div style="font-weight: 600; color: #111827; font-size: 15px; margin-bottom: 8px;">Produk Terjual</div>
            <p style="margin: 0 0 8px; font-size: 14px; color: #6B7280;">
                Total produk: {{ count($productSalesItems) }} • Total qty: {{ $salesItemsTotalQuantity }} • Nilai bersih: Rp{{ number_format($salesItemsTotalNet, 0, ',', '.') }}
            </p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #374151; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th align="left" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Produk</th>
                        <th align="left" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Varian</th>
                        <th align="right" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Qty</th>
                        <th align="right" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productSalesItems as $item)
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">
                                <div style="font-weight: 600;">{{ $item['name'] ?? '-' }}</div>
                                @if(!empty($item['sku']))
                                    <div style="font-size: 12px; color: #9CA3AF;">SKU: {{ $item['sku'] }}</div>
                                @endif
                            </td>
                            <td style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">
                                {{ $item['variant'] ?? '-' }}
                            </td>
                            <td align="right" style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">
                                {{ $item['quantity'] ?? 0 }}x
                            </td>
                            <td align="right" style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">
                                Rp{{ number_format($item['net_total'] ?? 0, 0, ',', '.') }}
                                @if(($item['discount_total'] ?? 0) > 0)
                                    <div style="font-size: 12px; color: #DC2626;">Diskon: Rp{{ number_format($item['discount_total'] ?? 0, 0, ',', '.') }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(!empty($addonSalesItems))
        <div style="margin-top: 24px;">
            <div style="font-weight: 600; color: #111827; font-size: 15px; margin-bottom: 8px;">Add-on Terjual</div>
            <p style="margin: 0 0 8px; font-size: 14px; color: #6B7280;">
                Total add-on: {{ count($addonSalesItems) }} • Total qty: {{ $addonItemsTotalQuantity }} • Nilai bersih: Rp{{ number_format($addonItemsTotalNet, 0, ',', '.') }}
            </p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #374151; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th align="left" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Add-on</th>
                        <th align="left" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Grup</th>
                        <th align="right" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Qty</th>
                        <th align="right" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($addonSalesItems as $item)
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">
                                <div style="font-weight: 600;">{{ $item['name'] ?? '-' }}</div>
                                @if(!empty($item['sku']))
                                    <div style="font-size: 12px; color: #9CA3AF;">SKU: {{ $item['sku'] }}</div>
                                @endif
                            </td>
                            <td style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">
                                {{ $item['variant'] ?? '-' }}
                            </td>
                            <td align="right" style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">
                                {{ $item['quantity'] ?? 0 }}x
                            </td>
                            <td align="right" style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">
                                Rp{{ number_format($item['net_total'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@component('mail::subcopy')
    <p style="margin: 0; color: #9CA3AF;">Email ini dikirim otomatis oleh {{ $appName }}.</p>
@endcomponent
@endcomponent
