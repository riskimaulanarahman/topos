@php
    use App\Enums\SubscriptionStatus;
    use Illuminate\Support\Facades\Auth;

    $user = Auth::user();
@endphp

@if ($user)
    @php
        $expiresAt = $user->subscription_expires_at;
        $daysRemaining = $expiresAt ? now()->diffInDays($expiresAt, false) : null;
        $timeRemaining = $expiresAt && $expiresAt->isFuture()
            ? now()->locale(app()->getLocale())->diffForHumans($expiresAt, [
                'parts' => 2,
                'join' => true,
                'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
            ])
            : null;
        $status = $user->subscription_status;
    @endphp

    @php
        $shouldShowTrialWarning = $status === SubscriptionStatus::TRIALING && isset($daysRemaining) && $daysRemaining >= 0 && $daysRemaining <= 3;
        $shouldShowExpiredWarning = $status === SubscriptionStatus::EXPIRED || $status === SubscriptionStatus::CANCELLED;
    @endphp

    @if ($shouldShowExpiredWarning || $shouldShowTrialWarning)
        <div class="subscription-alert-floating">
            <div
                class="alert {{ $shouldShowExpiredWarning ? 'alert-danger' : 'alert-warning' }} shadow-lg d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-0"
                role="alert"
            >
                <div class="pr-md-3">
                    @if ($shouldShowExpiredWarning)
                        <strong>Langganan tidak aktif.</strong>
                        <span class="d-block d-md-inline mt-2 mt-md-0">
                            Akses fitur dibatasi sampai Anda melakukan perpanjangan.
                        </span>
                    @else
                        <strong>Trial berakhir dalam {{ $timeRemaining ?? 'kurang dari 1 hari' }}.</strong>
                        <span class="d-block d-md-inline mt-2 mt-md-0">
                            Lakukan upgrade sekarang untuk menghindari penghentian layanan.
                        </span>
                    @endif
                </div>
                <a
                    href="{{ route('billing.index') }}"
                    class="btn {{ $shouldShowExpiredWarning ? 'btn-success' : 'btn-outline-dark' }} btn-sm mt-3 mt-md-0 flex-shrink-0"
                >
                    {{ $shouldShowExpiredWarning ? 'Perpanjang Sekarang' : 'Lihat Paket' }}
                </a>
            </div>
        </div>
    @endif
@endif
