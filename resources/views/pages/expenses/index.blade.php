@extends('layouts.app')

@section('title', 'Uang Keluar')

@push('style')
<link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
@php
    $expenseRole = $currentOutletRole ?? null;
    $expenseIsOwner = $expenseRole?->role === 'owner';
    $expenseCanManage = ($outletPermissions['can_manage_expense'] ?? false) || $expenseIsOwner || (Auth::user()?->roles === 'admin');
@endphp
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Uang Keluar</h1>
            @if ($expenseCanManage)
                <div class="section-header-button">
                    <a href="{{ route('expenses.create') }}" class="btn btn-primary">Tambah Baru</a>
                </div>
            @endif
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                <div class="breadcrumb-item">Uang Keluar</div>
            </div>
        </div>
        <div class="section-body">
            @unless($expenseCanManage)
                <div class="alert alert-info shadow-sm">
                    <i class="fas fa-info-circle mr-1"></i>
                    Anda memiliki akses baca untuk pengeluaran. Hubungi owner outlet jika perlu izin untuk menambahkan atau mengubah data.
                </div>
            @endunless
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('expenses.index') }}" class="form-inline mb-3">
                        <div class="form-group mr-2">
                            <label class="mr-2">Mulai</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                        </div>
                        <div class="form-group mr-2">
                            <label class="mr-2">Sampai</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                        </div>
                        <div class="form-group mr-2">
                            <input type="text" name="vendor" value="{{ request('vendor') }}" class="form-control" placeholder="Cari vendor" list="vendor-suggestions">
                            @if(isset($vendorSuggestions) && count($vendorSuggestions))
                                <datalist id="vendor-suggestions">
                                    @foreach($vendorSuggestions as $v)
                                        <option value="{{ $v }}">
                                    @endforeach
                                </datalist>
                            @endif
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
                                    <th>Vendor</th>
                                    <th>Jumlah</th>
                                    <th>Catatan</th>
                                    <th>Lampiran</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expenses as $e)
                                <tr>
                                    <td>{{ optional($e->date)->toDateString() }}</td>
                                    <td>{{ $e->reference_no }}</td>
                                    <td>{{ optional($e->category)->name }}</td>
                                    <td>{{ $e->vendor }}</td>
                                    <td>{{ number_format($e->amount,2) }}</td>
                                    <td>{{ $e->notes }}</td>
                                    <td>
                                        @if($e->attachment_path)
                                            <a href="{{ Storage::url($e->attachment_path) }}" target="_blank">Lihat</a>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if ($expenseCanManage)
                                            <form action="{{ route('expenses.duplicate', $e) }}" method="POST" class="d-inline js-duplicate-expense">
                                                @csrf
                                                <button class="btn btn-sm btn-secondary" title="Duplikat transaksi">Duplikat</button>
                                            </form>
                                            <a href="{{ route('expenses.edit',$e) }}" class="btn btn-sm btn-info">Edit</a>
                                            <form action="{{ route('expenses.destroy',$e) }}" method="POST" class="d-inline js-delete-expense">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger">Hapus</button>
                                            </form>
                                        @else
                                            <span class="text-muted small">Hanya dapat melihat</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="float-right">{{ $expenses->withQueryString()->links() }}</div>
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
  // Delete confirmation
  document.querySelectorAll('.js-delete-expense').forEach(function (form) {
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

  // Duplicate confirmation
  document.querySelectorAll('.js-duplicate-expense').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      Swal.fire({
        title: 'Duplikat transaksi?',
        text: 'Transaksi baru akan dibuat dengan tanggal hari ini.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, duplikat',
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
