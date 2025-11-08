@extends('layouts.app')

@section('title', 'Detail Pembayaran')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1 class="h4 mb-0">Detail Konfirmasi Pembayaran</h1>
                <div class="section-header-breadcrumb">
                    <a href="{{ route('admin.billing.payments.index') }}" class="btn btn-sm btn-light">Kembali</a>
                </div>
            </div>

            <div class="section-body">
                @php
                    $formatDateTime = static function ($value) {
                        if ($value instanceof \Illuminate\Support\Carbon) {
                            return $value->copy()->timezone(config('app.timezone'))->format('d M Y H:i');
                        }

                        if (empty($value)) {
                            return null;
                        }

                        try {
                            return \Illuminate\Support\Carbon::parse($value)->timezone(config('app.timezone'))->format('d M Y H:i');
                        } catch (\Throwable $e) {
                            return null;
                        }
                    };
                @endphp

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="row">
                    <div class="col-lg-7 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h4 class="mb-0">Informasi Pengajuan</h4>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Status</dt>
                                    <dd class="col-sm-8">
                                        @php
                                            $statusBadge = match ($submission->status) {
                                                \App\Models\PaymentSubmission::STATUS_APPROVED => 'badge-success',
                                                \App\Models\PaymentSubmission::STATUS_REJECTED => 'badge-danger',
                                                default => 'badge-warning',
                                            };
                                        @endphp
                                        <span class="badge {{ $statusBadge }}">{{ ucfirst($submission->status) }}</span>
                                    </dd>

                                    <dt class="col-sm-4">Nama Toko</dt>
                                    <dd class="col-sm-8">{{ $submission->user->store_name ?? '-' }}</dd>

                                    <dt class="col-sm-4">Pemilik Akun</dt>
                                    <dd class="col-sm-8">
                                        {{ $submission->user->name }}<br>
                                        <span class="text-muted">{{ $submission->user->email }}</span>
                                    </dd>

                                    <dt class="col-sm-4">Paket</dt>
                                    <dd class="col-sm-8">
                                        {{ $submission->plan_name }}<br>
                                        <span class="text-muted">Kode: {{ $submission->plan_code ?? '-' }}</span>
                                    </dd>

                                    <dt class="col-sm-4">Nominal Transfer</dt>
                                    <dd class="col-sm-8">
                                        Rp {{ number_format($submission->paid_amount, 0, ',', '.') }}<br>
                                        <span class="text-muted">Dasar Rp {{ number_format($submission->base_amount, 0, ',', '.') }} + kode unik {{ $submission->unique_code }}</span>
                                    </dd>

                                    <dt class="col-sm-4">Kode Unik</dt>
                                    <dd class="col-sm-8">{{ $submission->unique_code }}</dd>

                                    <dt class="col-sm-4">Nama Pengirim</dt>
                                    <dd class="col-sm-8">{{ $submission->payer_name }}</dd>

                                    <dt class="col-sm-4">Tanggal Transfer</dt>
                                    <dd class="col-sm-8">
                                        {{ $formatDateTime($submission->transferred_at) ?? '-' }}
                                    </dd>

                                    <dt class="col-sm-4">Metode</dt>
                                    <dd class="col-sm-8">{{ $submission->payment_channel ?? '-' }}</dd>

                                    <dt class="col-sm-4">Catatan Pengguna</dt>
                                    <dd class="col-sm-8">{{ $submission->customer_note ?? '-' }}</dd>

                                    <dt class="col-sm-4">Rekening Tujuan</dt>
                                    <dd class="col-sm-8">
                                        {{ $submission->paymentAccount?->bank_name ?? data_get($submission->destination_snapshot, 'bank_name') }}
                                        - {{ $submission->paymentAccount?->account_number ?? data_get($submission->destination_snapshot, 'account_number') }}<br>
                                        <span class="text-muted">a.n {{ $submission->paymentAccount?->account_holder ?? data_get($submission->destination_snapshot, 'account_holder') }}</span>
                                    </dd>

                                    <dt class="col-sm-4">Diajukan</dt>
                                    <dd class="col-sm-8">
                                        {{ $formatDateTime($submission->created_at) ?? '-' }}
                                    </dd>

                                    <dt class="col-sm-4">Ditinjau</dt>
                                    <dd class="col-sm-8">
                                        @php $reviewedAt = $formatDateTime($submission->reviewed_at); @endphp
                                        @if ($reviewedAt)
                                            {{ $reviewedAt }} oleh {{ $submission->reviewer?->name ?? '-' }}
                                        @else
                                            <span class="text-muted">Belum</span>
                                        @endif
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 mb-4">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                <h4 class="mb-0">Bukti Pembayaran</h4>
                            </div>
                            <div class="card-body">
                                @if ($proofUrl)
                                    <a href="{{ $proofUrl }}" target="_blank" class="btn btn-outline-primary btn-block mb-3">
                                        Lihat Bukti Pembayaran
                                    </a>
                                    <div class="embed-responsive embed-responsive-4by3 border">
                                        <iframe src="{{ $proofUrl }}" class="embed-responsive-item" title="Bukti Pembayaran"></iframe>
                                    </div>
                                @else
                                    <div class="text-muted">Bukti pembayaran belum tersedia.</div>
                                @endif
                            </div>
                        </div>

                        @if ($submission->status === \App\Models\PaymentSubmission::STATUS_PENDING)
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h4 class="mb-0">Tindakan</h4>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('admin.billing.payments.approve', $submission) }}" method="POST" class="mb-4">
                                        @csrf
                                        <div class="form-group">
                                            <label for="approval_note">Catatan (opsional)</label>
                                            <textarea name="approval_note" id="approval_note" rows="3" class="form-control" placeholder="Catatan internal untuk persetujuan"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-block" onclick="return confirm('Setujui pembayaran ini dan perpanjang langganan?')">
                                            Approve &amp; Perpanjang
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.billing.payments.reject', $submission) }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label for="rejection_reason">Alasan Penolakan <span class="text-danger">*</span></label>
                                            <textarea name="rejection_reason" id="rejection_reason" rows="3" class="form-control" required placeholder="Cantumkan alasan penolakan"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Tolak pengajuan pembayaran ini?')">
                                            Tolak Pengajuan
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h4 class="mb-0">Catatan Admin</h4>
                                </div>
                                <div class="card-body">
                                    @if ($submission->status === \App\Models\PaymentSubmission::STATUS_REJECTED)
                                        <div class="alert alert-danger">
                                            <strong>Ditolak:</strong> {{ $submission->rejection_reason }}
                                        </div>
                                    @endif
                                    @php
                                        $meta = $submission->metadata ?? [];
                                    @endphp
                                    @if (!empty($meta['approval_note']))
                                        <div class="alert alert-info">
                                            <strong>Catatan Persetujuan:</strong>
                                            {{ $meta['approval_note'] }}
                                        </div>
                                    @elseif (!empty($meta['last_rejection_note']))
                                        <div class="alert alert-info">
                                            <strong>Catatan Terakhir:</strong>
                                            {{ $meta['last_rejection_note'] }}
                                        </div>
                                    @else
                                        <div class="text-muted">Tidak ada catatan admin.</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
