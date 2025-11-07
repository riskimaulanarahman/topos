@extends('layouts.app')

@section('title', 'Uang Masuk')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Uang Keluar</h1>
                <div class="section-header-button">
                    <a href="{{ route('income.create') }}" class="btn btn-primary">Tambah Baru</a>
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item">Uang Keluar</div>
                </div>
            </div>
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="float-left">
                                    <h4>Data Uang Keluar</h4>
                                </div>
                                <div class="float-right mt-2">
                                    <form method="GET" action="{{ route('income.index') }}">
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Cari Type Pembayaran" name="type">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="clearfix mb-3"></div>
                                <div class="table-responsive">
                                    <table class="table-striped table">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Description</th>
                                            <th>Qty</th>
                                            <th>Harga Per Unit</th>
                                            <th>Total</th>
                                            <th>Tipe Pembayaran</th>
                                            <th>Aksi</th>
                                        </tr>
                                        @foreach ($incomes as $income)
                                            <tr>
                                                <td>{{ $income->date }}</td>
                                                <td>{{ $income->desc }}</td>
                                                <td>{{ $income->qty }}</td>
                                                <td>{{ number_format($income->price_per_unit) }}</td>
                                                <td>{{ number_format($income->total) }}</td>
                                                <td>{{ ucfirst($income->payment_type) }}</td>
                                                <td>
                                                    <div class="d-flex">
                                                        <a href="{{ route('income.edit', $income->id) }}" class="btn btn-sm btn-info btn-icon">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                        <form action="{{ route('income.destroy', $income->id) }}" method="POST" class="ml-2">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-sm btn-danger btn-icon confirm-delete">
                                                                <i class="fas fa-times"></i> Hapus
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                                <div class="float-right">
                                    {{ $incomes->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>
@endpush
