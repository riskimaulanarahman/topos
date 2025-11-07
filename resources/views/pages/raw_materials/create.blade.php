@extends('layouts.app')

@section('title', 'Tambah Bahan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Tambah Bahan</h1>
    </div>
    <div class="section-body">
      @include('components.help_panel', [
        'id' => 'help-raw-create',
        'title' => 'Panduan singkat • Bahan Pokok',
        'items' => [
          'Isi SKU (opsional), Nama, dan Satuan. Jika dikosongkan, SKU akan dibuat otomatis.',
          'Stok awal dimulai dari 0 dan akan bertambah dari transaksi Uang Keluar atau penyesuaian stok.',
          'Gunakan menu <em>Adjust Stok</em> untuk stok opname atau koreksi fisik.',
        ],
      ])
      <div class="row">
        <div class="col-12 col-md-6">
          <div class="card">
            <form action="{{ route('raw-materials.store') }}" method="POST">
              @csrf
              <div class="card-body">
                <div class="form-group">
                  <label>SKU <span class="text-muted">(opsional)</span></label>
                  <input type="text" name="sku" class="form-control" value="{{ old('sku') }}" placeholder="Kosongkan untuk auto-generate">
                </div>
                <div class="form-group">
                  <label>Nama</label>
                  <input type="text" name="name" class="form-control" list="expense-name-options" value="{{ old('name') }}" required>
                </div>
                @php
                    $selectedCategories = collect(old('category_ids', []))
                        ->map(fn ($value) => (int) $value)
                        ->filter()
                        ->values();
                @endphp
                <div class="form-group">
                  <label>Kategori <span class="text-danger">*</span></label>
                  @if($categories->isEmpty())
                    <div class="alert alert-warning mb-2">
                      Tidak ada kategori yang dapat dipilih. Hubungi owner untuk meminta akses kategori terlebih dahulu.
                    </div>
                  @else
                    <select name="category_ids[]" class="form-control" multiple required size="{{ min(8, max(4, $categories->count())) }}">
                      @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ $selectedCategories->contains($category->id) ? 'selected' : '' }}>
                          {{ $category->name }}
                        </option>
                      @endforeach
                    </select>
                    <small class="form-text text-muted">Pilih minimal satu kategori agar bahan baku tampil di menu mitra terkait.</small>
                  @endif
                </div>
                <div class="form-group">
                  <label>Satuan Terkecil</label>
                  <select name="unit" class="form-control" required {{ $units->isEmpty() ? 'disabled' : '' }}>
                    <option value="">- Pilih satuan -</option>
                    @foreach($units as $unit)
                      <option value="{{ $unit->code }}" {{ old('unit', $loop->first ? $unit->code : null) === $unit->code ? 'selected' : '' }}>
                        {{ $unit->name }} ({{ $unit->code }})
                      </option>
                    @endforeach
                  </select>
                  @if($units->isEmpty())
                    <small class="form-text text-danger">Belum ada data satuan. Silakan tambahkan satuan terlebih dahulu.</small>
                  @endif
                </div>
                @php
                    $oldMinStock = old('min_stock');
                    $minStockValue = ($oldMinStock === null || $oldMinStock === '') ? '' : number_format((float) $oldMinStock, 1, '.', '');
                @endphp
                <div class="form-group">
                  <label>Harga</label>
                  <div class="form-control-plaintext">0,0 (otomatis)</div>
                  <small class="form-text text-muted">Harga mengikuti transaksi Uang Keluar terkait pembelian bahan.</small>
                </div>
                                <div class="form-group mb-0">
                                    <label>Stok</label>
                                    <div class="form-control-plaintext">0 (otomatis)</div>
                                    <small class="form-text text-muted">Stok akan bertambah ketika pengeluaran bahan dicatat atau melalui menu Adjust Stok.</small>
                                </div>
                <div class="form-group">
                  <label>Stok Minimum</label>
                  <input type="number" step="0.1" name="min_stock" class="form-control" value="{{ $minStockValue }}">
                  <small class="form-text text-muted">Isi nilai ini untuk menyalakan notifikasi ketika stok turun di bawah batas.</small>
                </div>
              </div>
              <div class="card-footer text-right">
                <button class="btn btn-primary" {{ ($units->isEmpty() || $categories->isEmpty()) ? 'disabled' : '' }}>Simpan</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <datalist id="expense-name-options">
      @foreach($nameOptions ?? [] as $name)
        <option value="{{ $name }}"></option>
      @endforeach
    </datalist>
  </section>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // No additional behaviour needed on create form.
  });
</script>
@endpush
