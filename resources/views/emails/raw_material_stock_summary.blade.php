@php
    $locale = $summary['locale'] ?? config('app.locale', 'id');
    $locale = strtolower(str_replace('-', '_', $locale));
    if (\Illuminate\Support\Str::startsWith($locale, 'en')) {
        $locale = 'en';
    } else {
        $locale = 'id';
    }
    \Carbon\Carbon::setLocale($locale);

    $categories = $summary['categories'] ?? [];
    $belowMin = $categories['below_min'] ?? [];
    $nearMin = $categories['near_min'] ?? [];
    $safe = $categories['safe'] ?? [];
    $nearPercent = $summary['near_percent'] ?? null;
    $nearPercentFormatted = $summary['near_percent_formatted'] ?? null;
    $timezone = $summary['timezone'] ?? config('app.timezone');
    $generatedAtRaw = $summary['generated_at'] ?? now($timezone);
    if ($generatedAtRaw instanceof \Carbon\CarbonInterface) {
        $generatedAtLocal = $generatedAtRaw->copy()->setTimezone($timezone);
    } else {
        $generatedAtLocal = \Carbon\Carbon::parse($generatedAtRaw)->setTimezone($timezone);
    }

    $formatNumber = function ($value) use ($locale) {
        $decimalSeparator = $locale === 'en' ? '.' : ',';
        $thousandSeparator = $locale === 'en' ? ',' : '.';
        $formatted = number_format((float) $value, 0, $decimalSeparator, $thousandSeparator);
        $pattern = sprintf('/%s?0+$/', preg_quote($decimalSeparator, '/'));
        $formatted = preg_replace($pattern, '', $formatted);
        if (str_ends_with($formatted, $decimalSeparator)) {
            $formatted = substr($formatted, 0, -1);
        }
        return $formatted;
    };
    $timezoneLabel = $generatedAtLocal->format('T');

    $renderTable = function (array $items, string $accent) use ($formatNumber) {
        if (empty($items)) {
            return '<p style="margin: 0; font-size: 14px; color: #6B7280;">Tidak ada data pada kategori ini.</p>';
        }

        $rows = '';
        foreach ($items as $item) {
            $stock = $item['stock'] ?? 0;
            $min = $item['min'] ?? null;
            $difference = is_null($min) ? null : ($stock - $min);
            $stockFormatted = $item['stock_formatted'] ?? $formatNumber($stock);
            $minFormatted = $item['min_formatted'] ?? (!is_null($min) ? $formatNumber($min) : null);
            $differenceFormatted = $item['difference_formatted'] ?? (!is_null($difference) ? $formatNumber($difference) : null);
            $differenceDisplay = is_null($difference)
                ? '—'
                : $differenceFormatted;
            $differenceColor = is_null($difference)
                ? '#374151'
                : ($difference < 0 ? '#DC2626' : ($difference > 0 ? '#047857' : '#92400E'));

            $rows .= '<tr>'
                . '<td style="padding: 10px; border-bottom: 1px solid #E5E7EB;">' . e($item['sku'] ?? '-') . '</td>'
                . '<td style="padding: 10px; border-bottom: 1px solid #E5E7EB;">' . e($item['name'] ?? '-') . '</td>'
                . '<td align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB; font-weight: 600; color: ' . $accent . ';">' . $stockFormatted . '</td>'
                . '<td align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">' . ($minFormatted ?? '—') . '</td>'
                . '<td align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB; color: ' . $differenceColor . ';">' . $differenceDisplay . '</td>'
                . '</tr>';
        }

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; font-size: 14px; color: #1F2937;">'
            . '<thead>'
            . '<tr style="background: #F3F4F6;">'
            . '<th align="left" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">SKU</th>'
            . '<th align="left" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">Nama</th>'
            . '<th align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">Stok</th>'
            . '<th align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">Minimum</th>'
            . '<th align="right" style="padding: 10px; border-bottom: 1px solid #E5E7EB;">Selisih</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>';
    };
@endphp

@component('mail::message')
    @include('emails.partials.brand-header', [
        'title' => 'Ringkasan Stok Bahan Baku',
        'subtitle' => '',
    ])

    <p style="margin: 0 0 16px; font-size: 14px; color: #374151;">
        Berikut ringkasan stok bahan baku yang dikelompokkan menjadi tiga kategori:
    </p>

    <div style="margin: 0 0 20px;">
        <h3 style="margin: 0 0 8px; font-size: 16px; color: #DC2626;">1. Habis / Di Bawah Minimum</h3>
        {!! $renderTable($belowMin, '#DC2626') !!}
    </div>

    <div style="margin: 0 0 20px;">
        <h3 style="margin: 0 0 8px; font-size: 16px; color: #D97706;">2. Mendekati Minimum{{ $nearPercentFormatted ? ' (≤ ' . $nearPercentFormatted . '% di atas minimum)' : '' }}</h3>
        {!! $renderTable($nearMin, '#D97706') !!}
    </div>

    <div style="margin: 0 0 20px;">
        <h3 style="margin: 0 0 8px; font-size: 16px; color: #047857;">3. Masih Aman</h3>
        {!! $renderTable($safe, '#047857') !!}
    </div>

    <p style="margin: 0; font-size: 14px; color: #4B5563;">
        Pastikan untuk melakukan pemesanan ulang terhadap bahan baku yang berada pada kategori merah dan oranye agar operasional tetap lancar.
    </p>

    @component('mail::subcopy')
        Email ini dikirim otomatis oleh {{ $appName }}.
    @endcomponent
@endcomponent
