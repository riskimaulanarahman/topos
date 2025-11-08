@php($brandLogo = $logoUrl ?? asset('img/toga-gold-ts.png'))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
    <tr>
        <td align="center" style="padding-bottom: 12px;">
            <img src="{{ $brandLogo }}" alt="{{ $appName ?? config('app.name') }}" style="max-width: 160px; width: 160px; height: auto; display: block;" />
        </td>
    </tr>
    @if(!empty($title))
        <tr>
            <td align="center" style="padding-bottom: 4px;">
                <span style="font-size: 20px; font-weight: 700; color: #111827; letter-spacing: 0.2px;">{{ $title }}</span>
            </td>
        </tr>
    @endif
    @if(!empty($subtitle))
        <tr>
            <td align="center">
                <span style="font-size: 14px; color: #6B7280;">{{ $subtitle }}</span>
            </td>
        </tr>
    @endif
</table>
