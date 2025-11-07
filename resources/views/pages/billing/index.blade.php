@extends('layouts.app')

@section('title', 'Kelola Langganan')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1 class="h4 mb-0">Kelola Langganan</h1>
            </div>
            <div class="section-body">
                @if (session('subscription_expired'))
                    <div class="alert alert-danger">
                        Masa langganan Anda sudah berakhir. Silakan lakukan pembayaran untuk melanjutkan.
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header">
                                <h4 class="mb-0">Status Langganan</h4>
                            </div>
                            <div class="card-body">
                                @php
                                    $status = $user->subscription_status?->value ?? 'none';
                                    $statusLabel = match ($status) {
                                        'trialing' => 'Trial',
                                        'active' => 'Aktif',
                                        'expired' => 'Kedaluwarsa',
                                        'cancelled' => 'Dibatalkan',
                                        default => 'Tidak diketahui',
                                    };
                                    $statusBadge = match ($status) {
                                        'trialing' => 'badge-warning',
                                        'active' => 'badge-success',
                                        'expired' => 'badge-danger',
                                        'cancelled' => 'badge-secondary',
                                        default => 'badge-light',
                                    };
                                @endphp
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <div class="text-muted small">Status saat ini</div>
                                        <span class="badge {{ $statusBadge }} px-3 py-2 text-uppercase">{{ $statusLabel }}</span>
                                    </div>
                                </div>
                                <dl class="row mb-0">
                                    <dt class="col-sm-6">Mulai Trial</dt>
                                    <dd class="col-sm-6 text-sm-right">
                                        {{ $user->trial_started_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '-' }}
                                    </dd>
                                    <dt class="col-sm-6">Berlaku Hingga</dt>
                                    <dd class="col-sm-6 text-sm-right">
                                        {{ $user->subscription_expires_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '-' }}
                                    </dd>
                                    <dt class="col-sm-6">Sisa Hari</dt>
                                    <dd class="col-sm-6 text-sm-right">
                                        {{ $daysRemaining !== null ? number_format($daysRemaining, 0, ',', '.') : '-' }}
                                    </dd>
                                    <dt class="col-sm-6">Sisa Waktu</dt>
                                    <dd class="col-sm-6 text-sm-right">
                                        {{ $timeRemaining ?? ($user->subscription_expires_at ? 'Sudah kedaluwarsa' : '-') }}
                                    </dd>
                                </dl>
                                <hr>
                                <div class="alert alert-info mb-0">
                                    <p class="mb-1">
                                        Lakukan transfer sesuai paket dan lengkapi form konfirmasi di samping agar tim admin dapat memverifikasi dan memperpanjang langganan secara otomatis.
                                    </p>
                                    <p class="mb-0 small bg-success p-2">
                                        Jika membutuhkan bantuan, hubungi support melalui email
                                        <a href="mailto:togoldarea@gmail.com"><strong>togoldarea@gmail.com</strong></a> atau WhatsApp resmi dengan mencantumkan nama toko.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header">
                                <h4 class="mb-0">Informasi Pembayaran</h4>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <h5 class="font-weight-semibold">Cara Melakukan Pembayaran</h5>
                                    <ol class="pl-3 mb-0">
                                        <li class="mb-2">Pilih paket yang diinginkan lalu tekan tombol <strong>Pilih Paket Ini</strong> untuk membuka form.</li>
                                        <li class="mb-2">Isi detail transfer, tambah kode unik 3 digit, dan unggah bukti pembayaran di dalam modal.</li>
                                        <li class="mb-2">Setelah terkirim, admin akan memverifikasi dan memperpanjang langganan otomatis.</li>
                                    </ol>
                                </div>

                                <div class="alert alert-secondary">
                                    <div class="font-weight-semibold mb-1">Petunjuk Penting</div>
                                    <ul class="pl-3 mb-0">
                                        <li class="mb-2">Transfer sesuai harga paket yang dipilih <strong>ditambah kode unik</strong> agar verifikasi cepat.</li>
                                        <li class="mb-2">Unggah bukti pembayaran resmi (JPG, PNG, atau PDF) maksimal {{ number_format((int) (config('subscriptions.proof_upload.max_size_kb') ?? 4096) / 1024, 0) }} MB.</li>
                                        <li class="mb-0">Form hanya memproses pembayaran transfer ke rekening tujuan di bawah.</li>
                                    </ul>
                                </div>

                                <div class="mt-4">
                                    <h5 class="font-weight-semibold">Nomor Rekening Tujuan</h5>
                                    @forelse ($paymentAccounts as $account)
                                        <div class="border rounded p-3 mb-3">
                                            <div class="font-weight-semibold">
                                                {{ $account->bank_name ?? 'Transfer' }} - {{ $account->account_number }}
                                            </div>
                                            <div class="small text-muted">
                                                a.n {{ $account->account_holder ?? '-' }}
                                            </div>
                                            @if ($account->channel)
                                                <div class="small text-muted">Metode: {{ $account->channel }}</div>
                                            @endif
                                            @if ($account->instructions)
                                                <div class="small mt-2">{{ $account->instructions }}</div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="alert alert-warning mb-0">
                                            Belum ada rekening tujuan yang tersedia. Silakan hubungi admin.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($recentSubmissions->isNotEmpty())
                <div class="section-body">
                    <h2 class="h5 text-primary mb-3">Riwayat Konfirmasi Pembayaran</h2>
                    <div class="card shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead>
                                    <tr>
                                        <th>Tanggal Pengajuan</th>
                                        <th>Paket</th>
                                        <th>Nominal Transfer</th>
                                        <th>Kode Unik</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($recentSubmissions as $submission)
                                        @php
                                            $statusBadge = match ($submission->status) {
                                                \App\Models\PaymentSubmission::STATUS_APPROVED => 'badge-success',
                                                \App\Models\PaymentSubmission::STATUS_REJECTED => 'badge-danger',
                                                default => 'badge-warning',
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                {{ optional($submission->created_at)->timezone(config('app.timezone'))->format('d M Y H:i') }}
                                                <div class="text-muted small">
                                                    Transfer: {{ optional($submission->transferred_at)->timezone(config('app.timezone'))->format('d M Y H:i') ?? '-' }}
                                                </div>
                                            </td>
                                            <td>{{ $submission->plan_name }}</td>
                                            <td>Rp {{ number_format($submission->paid_amount, 0, ',', '.') }}</td>
                                            <td>{{ $submission->unique_code }}</td>
                                            <td>
                                                <span class="badge {{ $statusBadge }}">{{ ucfirst($submission->status) }}</span>
                                                @if ($submission->status === \App\Models\PaymentSubmission::STATUS_REJECTED && $submission->rejection_reason)
                                                    <div class="text-muted small">{{ $submission->rejection_reason }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @php
                $hasPaymentAccounts = $paymentAccounts->count() > 0;
                $plansByCode = collect($plans)->keyBy('code');
            @endphp
            <div class="section-body">
                <h2 class="h5 text-primary mb-4">Paket Harga</h2>
                <div class="row">
                    @foreach ($plans as $plan)
                        <div class="col-md-4 mb-4">
                            <div class="pricing-card border rounded shadow-sm h-100 position-relative">
                                @if (($plan['promo_active'] ?? false) === true)
                                    <div class="badge badge-success position-absolute" style="top: -12px; right: 16px;">Promo</div>
                                @endif
                                <div class="pricing-card__header bg-light px-4 py-3">
                                    <h3 class="h6 mb-1 text-uppercase text-muted">{{ $plan['name'] }}</h3>
                                    <div class="h3 mb-0 text-primary">Rp {{ number_format($plan['price'], 0, ',', '.') }}</div>
                                    @if (($plan['promo_active'] ?? false) === true && isset($plan['normal_price']))
                                        <small class="text-muted d-block">
                                            Harga normal Rp {{ number_format($plan['normal_price'], 0, ',', '.') }}
                                        </small>
                                    @endif
                                </div>
                                <div class="pricing-card__body px-4 py-4">
                                    <p class="mb-4">
                                        {{ $plan['description'] ?? 'Semua paket memberikan akses penuh ke seluruh fitur TOGA POS.' }}
                                    </p>
                                    <small class="mb-4 text-muted d-block">
                                        ** User akan mendapatkan pengembangan fitur dan pembaruan sistem secara berkala selama masa langganan aktif.
                                    </small>
                                    @if ($hasPaymentAccounts)
                                        @php
                                            $sampleTotal = (int) $plan['price'] + (int) $suggestedUniqueCode;
                                        @endphp
                                        <button type="button"
                                                class="btn btn-outline-primary btn-block js-open-payment-modal"
                                                data-plan-code="{{ $plan['code'] }}"
                                                data-plan-name="{{ $plan['name'] }}"
                                                data-plan-price="{{ (int) $plan['price'] }}">
                                            Pilih Paket Ini
                                        </button>
                                        <small class="text-muted d-block text-center mt-2">
                                            Contoh transfer: Rp {{ number_format($sampleTotal, 0, ',', '.') }} (kode unik {{ $suggestedUniqueCode }})
                                        </small>
                                    @else
                                        <button type="button" class="btn btn-outline-primary btn-block" disabled>
                                            Rekening belum tersedia
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="section-body">
                <div class="alert alert-warning mb-0">
                    <strong>Deklarasi:</strong> Harga paket dapat berubah sewaktu-waktu tanpa pemberitahuan sebelumnya.
                    Selalu periksa informasi terbaru sebelum melakukan pembayaran.
                </div>
            </div>

            @if ($hasPaymentAccounts)
                <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="paymentModalLabel">Konfirmasi Pembayaran</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="d-flex justify-content-between align-items-start flex-wrap">
                                    <div class="mb-3">
                                        <div class="small text-muted">Paket dipilih</div>
                                        <div class="h5 mb-0" data-role="plan-name">-</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="small text-muted">Harga Paket</div>
                                        <div class="h5 mb-0" data-role="base-amount">Rp 0</div>
                                    </div>
                                </div>
                                <div class="alert alert-info">
                                    Total transfer: <strong data-role="total-amount">Rp 0</strong> (sudah termasuk kode unik 3 digit).
                                </div>

                                <form action="{{ route('billing.payments.store') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="plan_code" id="modal_plan_code" value="{{ old('plan_code') }}">

                                    @error('plan_code')
                                        <div class="alert alert-danger">{{ $message }}</div>
                                    @enderror

                                    <div class="form-group">
                                        <label for="modal_payment_account_id">Rekening Tujuan <span class="text-danger">*</span></label>
                                        <select name="payment_account_id" id="modal_payment_account_id" class="form-control @error('payment_account_id') is-invalid @enderror" required>
                                            <option value="">-- Pilih rekening --</option>
                                            @foreach ($paymentAccounts as $account)
                                                <option value="{{ $account->id }}" {{ (string) old('payment_account_id') === (string) $account->id ? 'selected' : '' }}>
                                                    {{ $account->bank_name ?? 'Transfer' }} - {{ $account->account_number }} (a.n {{ $account->account_holder ?? '-' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('payment_account_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="modal_unique_code">Kode Unik Transfer <span class="text-danger">*</span></label>
                                            <input type="text"
                                                   name="unique_code"
                                                   id="modal_unique_code"
                                                   class="form-control @error('unique_code') is-invalid @enderror"
                                                   value="{{ old('unique_code', str_pad($suggestedUniqueCode, 3, '0', STR_PAD_LEFT)) }}"
                                                   required
                                                   maxlength="3"
                                                   pattern="\d{3}"
                                                   data-default="{{ $suggestedUniqueCode }}">
                                            <small class="form-text text-muted">
                                                Tambahkan kode ini ke harga paket saat transfer untuk memudahkan verifikasi.
                                            </small>
                                            @error('unique_code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="modal_payer_name">Nama Pengirim <span class="text-danger">*</span></label>
                                            <input type="text" name="payer_name" id="modal_payer_name" class="form-control @error('payer_name') is-invalid @enderror" value="{{ old('payer_name', $user->name) }}" required>
                                            @error('payer_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="modal_transferred_at">Tanggal &amp; Jam Transfer <span class="text-danger">*</span></label>
                                            <input type="datetime-local" name="transferred_at" id="modal_transferred_at" class="form-control @error('transferred_at') is-invalid @enderror" value="{{ old('transferred_at') }}" required>
                                            @error('transferred_at')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="modal_payment_channel">Metode/Kanal Pembayaran</label>
                                            <input type="text" name="payment_channel" id="modal_payment_channel" class="form-control @error('payment_channel') is-invalid @enderror" value="{{ old('payment_channel') }}" placeholder="Contoh: BCA Mobile, Mandiri ATM">
                                            @error('payment_channel')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="modal_customer_note">Catatan Tambahan</label>
                                        <textarea name="customer_note" id="modal_customer_note" rows="3" class="form-control @error('customer_note') is-invalid @enderror" placeholder="Opsional: cantumkan informasi tambahan">{{ old('customer_note') }}</textarea>
                                        @error('customer_note')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="modal_proof">Unggah Bukti Pembayaran <span class="text-danger">*</span></label>
                                        <input type="file" name="proof" id="modal_proof" class="form-control-file @error('proof') is-invalid @enderror" required>
                                        @error('proof')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="modal-footer px-0">
                                        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Kirim Bukti Pembayaran</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection

@if ($hasPaymentAccounts)
    @push('scripts')
        <script>
            (function ($) {
                const plans = @json($plansByCode);
                const modal = $('#paymentModal');
                const planNameEl = modal.find('[data-role="plan-name"]');
                const baseAmountEl = modal.find('[data-role="base-amount"]');
                const totalAmountEl = modal.find('[data-role="total-amount"]');
                const planCodeInput = modal.find('#modal_plan_code');
                const uniqueInput = modal.find('#modal_unique_code');
                const uniqueDefaultValue = parseInt(uniqueInput.data('default'), 10) || 0;
                const floatingAlert = $('.subscription-alert-floating');

                function formatCurrency(value) {
                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                }

                function updateSummary() {
                    const planPrice = parseInt(modal.data('current-plan-price'), 10) || 0;
                    const uniqueValue = parseInt(uniqueInput.val(), 10);
                    const uniqueCode = Number.isNaN(uniqueValue) ? 0 : uniqueValue;
                    baseAmountEl.text(formatCurrency(planPrice));
                    totalAmountEl.text(formatCurrency(planPrice + uniqueCode));
                }

                function setUniqueCode(value) {
                    const formatted = String(value).padStart(3, '0');
                    uniqueInput.val(formatted);
                }

                function openModal(planCode, options) {
                    const plan = plans[planCode];
                    if (!plan) {
                        return;
                    }

                    const settings = options || {};
                    const planPrice = parseInt(plan.price, 10) || 0;

                    planCodeInput.val(planCode);
                    planNameEl.text(plan.name);
                    modal.data('current-plan-price', planPrice);

                    if (Object.prototype.hasOwnProperty.call(settings, 'uniqueValue')) {
                        setUniqueCode(settings.uniqueValue);
                    }

                    updateSummary();
                    modal.modal('show');
                }

                $('.js-open-payment-modal').on('click', function () {
                    const planCode = $(this).data('plan-code');
                    openModal(planCode, { uniqueValue: uniqueDefaultValue });
                });

                uniqueInput.on('input', function () {
                    updateSummary();
                });

                modal.on('show.bs.modal', function () {
                    if (floatingAlert.length) {
                        floatingAlert.addClass('d-none');
                    }
                });

                modal.on('hidden.bs.modal', function () {
                    if (floatingAlert.length) {
                        floatingAlert.removeClass('d-none');
                    }
                });

                const oldPlanCode = @json(old('plan_code'));
                if (oldPlanCode && plans[oldPlanCode]) {
                    const oldUnique = parseInt(@json(old('unique_code', $suggestedUniqueCode)), 10);
                    const uniqueValue = Number.isNaN(oldUnique) ? uniqueDefaultValue : oldUnique;
                    openModal(oldPlanCode, { uniqueValue });
                }
            })(jQuery);
        </script>
    @endpush
@endif
