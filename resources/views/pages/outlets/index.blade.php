@extends('layouts.app')

@section('title', 'Outlet & Mitra')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
            <h1>Outlet Saya</h1>
            <div class="section-header-button">
                <a href="{{ route('outlets.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-2"></i>Tambah Outlet</a>
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

            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Kode</th>
                                <th>Peran Saya</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($outlets as $outlet)
                                <tr>
                                    <td>{{ $outlet->name }}</td>
                                    <td>{{ $outlet->code ?? '—' }}</td>
                                    <td>{{ ucfirst($outlet->pivot->role ?? '-') }}</td>
                                    <td>
                                        <span class="badge badge-{{ ($outlet->pivot->status ?? 'pending') === 'active' ? 'success' : 'warning' }}">
                                            {{ ucfirst($outlet->pivot->status ?? 'pending') }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('outlets.show', $outlet) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada outlet. Tambahkan outlet pertama Anda.</td>
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
