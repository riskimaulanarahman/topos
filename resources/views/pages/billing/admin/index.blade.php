@extends('layouts.app')

@section('title', 'Verifikasi Pembayaran')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1 class="h4 mb-0">Verifikasi Pembayaran Langganan</h1>
            </div>

            <div class="section-body">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="form-inline mb-3">
                            <div class="form-group mr-2 mb-2">
                                <label for="status" class="mr-2">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">Semua</option>
                                    @foreach ($statusOptions as $value => $label)
                                        <option value="{{ $value }}" {{ $activeStatus === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mr-2 mb-2">
                                <label for="search" class="sr-only">Cari</label>
                                <input type="text" name="search" id="search" placeholder="Cari nama toko, email, paket"
                                       value="{{ $search }}" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary mb-2">Terapkan</button>
                            <a href="{{ route('admin.billing.payments.index') }}" class="btn btn-light ml-2 mb-2">Reset</a>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped table-md">
                                <thead>
                                <tr>
                                    <th>Waktu Kirim</th>
                                    <th>Pengguna</th>
                                    <th>Paket</th>
                                    <th>Nominal Transfer</th>
                                    <th>Status</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($submissions as $submission)
                                    @php
                                        $statusBadge = match ($submission->status) {
                                            \App\Models\PaymentSubmission::STATUS_APPROVED => 'badge-success',
                                            \App\Models\PaymentSubmission::STATUS_REJECTED => 'badge-danger',
                                            default => 'badge-warning',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="font-weight-semibold">
                                                {{ optional($submission->created_at)->timezone(config('app.timezone'))->format('d M Y H:i') }}
                                            </div>
                                            <div class="text-muted small">
                                                Transfer: {{ optional($submission->transferred_at)->timezone(config('app.timezone'))->format('d M Y H:i') ?? '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-weight-semibold">{{ $submission->user->store_name ?? $submission->user->name }}</div>
                                            <div class="text-muted small">{{ $submission->user->email }}</div>
                                        </td>
                                        <td>
                                            <div class="font-weight-semibold">{{ $submission->plan_name }}</div>
                                            <div class="text-muted small">Kode: {{ $submission->plan_code ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="font-weight-semibold">Rp {{ number_format($submission->paid_amount, 0, ',', '.') }}</div>
                                            <div class="text-muted small">Dasar Rp {{ number_format($submission->base_amount, 0, ',', '.') }} + {{ $submission->unique_code }}</div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusBadge }}">{{ ucfirst($submission->status) }}</span>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.billing.payments.show', $submission) }}" class="btn btn-sm btn-outline-primary">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada pengajuan pembayaran.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $submissions->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

