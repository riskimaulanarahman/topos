@extends('layouts.app')

@section('title', 'Uang Masuk')

@push('style')
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Uang Masuk</h1>
                <div class="section-header-button">
                    <a href="{{ route('income.create') }}" class="btn btn-primary">Tambah Baru</a>
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item">Uang Masuk</div>
                </div>
            </div>
            <div class="section-body">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('income.index') }}" class="form-inline mb-3">
                            <div class="form-group mr-2">
                                <label class="mr-2">Mulai</label>
                                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                            </div>
                            <div class="form-group mr-2">
                                <label class="mr-2">Sampai</label>
                                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                            </div>
                            <div class="form-group mr-2">
                                <select name="category_id" class="form-control selectric">
                                    <option value="">Semua Kategori</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ request('category_id')==$cat->id?'selected':'' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-primary">Filter</button>
                        </form>
                        @include('layouts.alert')
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Ref</th>
                                        <th>Kategori</th>
                                        <th>Jumlah</th>
                                        <th>Catatan</th>
                                        <th>Lampiran</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($incomes as $income)
                                        <tr>
                                            <td>{{ optional($income->date)->toDateString() }}</td>
                                            <td>{{ $income->reference_no }}</td>
                                            <td>{{ optional($income->category)->name }}</td>
                                            <td>{{ number_format($income->amount,2) }}</td>
                                            <td>{{ $income->notes }}</td>
                                            <td>
                                                @if($income->attachment_path)
                                                    <a href="{{ Storage::url($income->attachment_path) }}" target="_blank">Lihat</a>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                <a href="{{ route('income.edit', $income->id) }}" class="btn btn-sm btn-info">Edit</a>
                                                <form action="{{ route('income.destroy', $income->id) }}" method="POST" class="d-inline js-delete-income">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-danger">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="float-right">
                            {{ $incomes->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.js-delete-income').forEach(function (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          Swal.fire({
            title: 'Hapus data?',
            text: 'Tindakan ini tidak dapat dibatalkan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
          }).then((result) => {
            if (result.isConfirmed) {
              form.submit();
            }
          });
        });
      });
    });
    </script>
@endpush
