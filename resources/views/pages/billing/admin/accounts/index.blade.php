@extends('layouts.app')

@section('title', 'Rekening Pembayaran')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1 class="h4 mb-0">Daftar Rekening Pembayaran</h1>
                <div class="section-header-breadcrumb">
                    <a href="{{ route('admin.billing.payments.index') }}" class="btn btn-light mr-2">Verifikasi Pembayaran</a>
                    <a href="{{ route('admin.billing.accounts.create') }}" class="btn btn-primary">Tambah Rekening</a>
                </div>
            </div>

            <div class="section-body">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                <tr>
                                    <th>Label</th>
                                    <th>Rekening</th>
                                    <th>Kanal</th>
                                    <th>Instruksi</th>
                                    <th>Status</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($accounts as $account)
                                    <tr>
                                        <td>
                                            <div class="font-weight-semibold">{{ $account->label ?? '-' }}</div>
                                            <div class="text-muted small">Urutan: {{ $account->sort_order }}</div>
                                        </td>
                                        <td>
                                            <div class="font-weight-semibold">
                                                {{ $account->bank_name ?? 'Transfer' }} &mdash; {{ $account->account_number }}
                                            </div>
                                            <div class="text-muted small">a.n {{ $account->account_holder ?? '-' }}</div>
                                        </td>
                                        <td>{{ $account->channel ?? '-' }}</td>
                                        <td>
                                            @if ($account->instructions)
                                                <span class="text-muted small">{{ \Illuminate\Support\Str::limit($account->instructions, 80) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $account->is_active ? 'badge-success' : 'badge-secondary' }}">
                                                {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.billing.accounts.edit', $account) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                            <form action="{{ route('admin.billing.accounts.destroy', $account) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Hapus rekening ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">Belum ada rekening pembayaran.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

