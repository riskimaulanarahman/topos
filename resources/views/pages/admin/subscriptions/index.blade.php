@extends('layouts.app')

@section('title', 'Kelola Langganan User')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <h1 class="h4 mb-1">Manajemen Langganan Pengguna</h1>
                    <p class="text-muted mb-0">Perbarui status paket dan tanggal kedaluwarsa akun pelanggan.</p>
                </div>
                <form method="GET" class="mt-3 mt-md-0" style="min-width: 280px;">
                    <div class="input-group">
                        <input type="text" name="search" value="{{ $search }}" class="form-control"
                            placeholder="Cari nama toko, pengguna, atau email">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search mr-1"></i>Cari</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="section-body">
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Nama Toko</th>
                                        <th>Pengguna</th>
                                        <th>Status</th>
                                        <th>Mulai Trial</th>
                                        <th>Kedaluwarsa</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($users as $user)
                                        <tr>
                                            <td>
                                                <strong>{{ $user->store_name }}</strong>
                                                <div class="text-muted small">{{ $user->email }}</div>
                                            </td>
                                            <td>{{ $user->name }}</td>
                                            <td>
                                                <span class="badge badge-{{ $user->subscription_status?->value === 'active' ? 'success' : ($user->subscription_status?->value === 'trialing' ? 'warning' : 'secondary') }}">
                                                    {{ $user->subscription_status?->label() ?? 'Belum diatur' }}
                                                </span>
                                            </td>
                                            <td style="min-width: 220px;">
                                                <input type="datetime-local" name="trial_started_at"
                                                    form="subscription-form-{{ $user->id }}"
                                                    class="form-control form-control-sm"
                                                    value="{{ optional($user->trial_started_at)->format('Y-m-d\TH:i') }}">
                                            </td>
                                            <td style="min-width: 220px;">
                                                <input type="datetime-local" name="subscription_expires_at"
                                                    form="subscription-form-{{ $user->id }}"
                                                    class="form-control form-control-sm"
                                                    value="{{ optional($user->subscription_expires_at)->format('Y-m-d\TH:i') }}">
                                            </td>
                                            <td class="text-center">
                                                <form action="{{ route('admin.subscriptions.update', $user) }}" method="POST"
                                                      id="subscription-form-{{ $user->id }}"
                                                      class="d-inline-flex flex-column flex-lg-row align-items-lg-center justify-content-center">
                                                    @csrf
                                                    @method('PUT')
                                                    @if ($search)
                                                        <input type="hidden" name="search" value="{{ $search }}">
                                                    @endif
                                                    @if ($users->currentPage() > 1)
                                                        <input type="hidden" name="page" value="{{ $users->currentPage() }}">
                                                    @endif
                                                    <select name="subscription_status"
                                                            class="form-control form-control-sm d-inline-block w-auto mr-lg-2 mb-2 mb-lg-0">
                                                        @foreach ($statuses as $status)
                                                            <option value="{{ $status->value }}"
                                                                @selected($user->subscription_status?->value === $status->value)>
                                                                {{ $status->label() }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        Simpan
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                Tidak ada pengguna ditemukan.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
