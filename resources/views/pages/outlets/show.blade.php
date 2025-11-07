@extends('layouts.app')

@section('title', $outlet->name)

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
            <h1>{{ $outlet->name }}</h1>
            <div class="section-header-button d-flex align-items-center">
                @can('update', $outlet)
                    <a href="{{ route('outlets.edit', $outlet) }}" class="btn btn-outline-primary mr-2">
                        <i class="fas fa-edit mr-2"></i>Edit Outlet
                    </a>
                @endcan
                <a href="{{ route('outlets.partners.index', $outlet) }}" class="btn btn-primary">
                    <i class="fas fa-users mr-2"></i>Kelola Mitra
                </a>
            </div>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('outlets.index') }}">Outlet</a></div>
                <div class="breadcrumb-item active">Detail</div>
            </div>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Informasi Outlet</h4>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Nama</dt>
                                <dd class="col-sm-8">{{ $outlet->name }}</dd>

                                <dt class="col-sm-4">Kode</dt>
                                <dd class="col-sm-8">{{ $outlet->code ?? '—' }}</dd>

                                <dt class="col-sm-4">Alamat</dt>
                                <dd class="col-sm-8">{{ $outlet->address ?? '—' }}</dd>

                                <dt class="col-sm-4">Catatan</dt>
                                <dd class="col-sm-8">{{ $outlet->notes ?? '—' }}</dd>

                                <dt class="col-sm-4">Dibuat</dt>
                                <dd class="col-sm-8">{{ $outlet->created_at?->format('d M Y H:i') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Ringkasan Mitra</h4>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">Status Keanggotaan:</p>
                            <ul class="list-unstyled mb-0">
                                <li><span class="badge badge-success mr-2">Aktif</span> {{ $members->where('status', 'active')->count() }} anggota</li>
                                <li><span class="badge badge-warning mr-2">Pending</span> {{ $members->where('status', 'pending')->count() }} undangan</li>
                                <li><span class="badge badge-danger mr-2">Dicabut</span> {{ $members->where('status', 'revoked')->count() }} riwayat</li>
                            </ul>
                            <hr>
                            <p class="mb-2">Owner:</p>
                            <ul class="list-unstyled mb-0">
                                @foreach ($members->where('role', 'owner') as $owner)
                                    <li>{{ $owner->user->name }} <small class="text-muted">({{ $owner->user->email }})</small></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $currentMember = $members->firstWhere('user_id', auth()->id());
            @endphp

            @if ($currentMember)
                <div class="card">
                    <div class="card-header">
                        <h4>PIN Outlet Saya</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            PIN digunakan saat mengakses outlet ini melalui aplikasi mobile. Kosongkan PIN untuk menonaktifkan permintaan PIN.
                        </p>
                        <form method="POST" action="{{ route('outlets.pin.update', $outlet) }}" class="row">
                            @csrf
                            @method('PUT')
                            <div class="form-group col-md-4">
                                <label for="pin">PIN Baru</label>
                                <input type="password" name="pin" id="pin" class="form-control @error('pin') is-invalid @enderror" inputmode="numeric" autocomplete="new-pin">
                                @error('pin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="pin_confirmation">Konfirmasi PIN</label>
                                <input type="password" name="pin_confirmation" id="pin_confirmation" class="form-control" inputmode="numeric" autocomplete="new-pin">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Status</label>
                                <div class="d-flex flex-column">
                                    <span class="badge badge-{{ $currentMember->hasPin() ? 'success' : 'secondary' }} mb-2">
                                        {{ $currentMember->hasPin() ? 'PIN Aktif' : 'Belum Diset' }}
                                    </span>
                                    @if ($currentMember->pin_last_set_at)
                                        <small class="text-muted">Terakhir diatur: {{ $currentMember->pin_last_set_at->format('d M Y H:i') }}</small>
                                    @endif
                                    @if ($currentMember->pin_last_verified_at)
                                        <small class="text-muted">Terakhir diverifikasi: {{ $currentMember->pin_last_verified_at->format('d M Y H:i') }}</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12 text-right">
                                <button type="submit" class="btn btn-primary">Simpan PIN</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h4>Mitra Outlet</h4>
                    <div class="card-header-action">
                        <a href="{{ route('outlets.partners.index', $outlet) }}" class="btn btn-outline-primary btn-sm">
                            Lihat Semua Mitra
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Peran</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($members->take(5) as $member)
                                <tr>
                                    <td>{{ $member->user->name }}</td>
                                    <td>{{ $member->user->email }}</td>
                                    <td>{{ ucfirst($member->role) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $member->status === 'active' ? 'success' : ($member->status === 'pending' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($member->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada mitra untuk outlet ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </section>
    </div>
@endsection
