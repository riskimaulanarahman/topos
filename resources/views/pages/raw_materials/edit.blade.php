@extends('layouts.app')

@section('title', 'Edit Bahan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Edit Bahan</h1>
    </div>
    <div class="section-body">
      @include('components.help_panel', [
        'id' => 'help-raw-edit',
        'title' => 'Panduan singkat • Bahan Pokok',
        'items' => [
          'Perbarui informasi dasar bahan. Stok hanya berubah lewat transaksi Uang Keluar atau menu <em>Adjust Stok</em>.',
          'Saat menambah stok melalui Adjust, isi Unit Cost agar harga rata-rata berjalan akurat.',
          'Tampilan angka dibulatkan 2 desimal. Perhitungan internal tetap presisi.',
        ],
      ])
      <div class="row">
        <div class="col-12 col-md-6">
          <div class="card">
            <form action="{{ route('raw-materials.update',$material) }}" method="POST">
              @csrf
              @method('PUT')
              <div class="card-body">
                <div class="form-group">
                  <label>SKU <span class="text-muted">(kosongkan untuk auto-generate)</span></label>
                  <input type="text" name="sku" class="form-control" value="{{ old('sku', $material->sku) }}" placeholder="Biarkan kosong untuk mempertahankan/generate">
                  <small class="form-text text-muted">Jika dikosongkan sistem akan mempertahankan SKU lama atau membuat yang baru bila belum ada.</small>
                </div>
                <div class="form-group">
                  <label>Nama</label>
                  <input type="text" name="name" class="form-control" list="expense-name-options" value="{{ old('name', $material->name) }}" required>
                </div>
                @php
                  $categorySelection = collect(old('category_ids', $selectedCategories ?? $material->categories->pluck('id')->values()->all()))
                    ->map(fn ($value) => (int) $value)
                    ->filter()
                    ->values();
                @endphp
                <div class="form-group">
                  <label>Kategori <span class="text-danger">*</span></label>
                  @if($categories->isEmpty())
                    <div class="alert alert-warning mb-2">
                      Tidak ada kategori yang dapat dipilih untuk bahan ini.
                    </div>
                  @else
                    <select name="category_ids[]" class="form-control" multiple required size="{{ min(8, max(4, $categories->count())) }}">
                      @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ $categorySelection->contains($category->id) ? 'selected' : '' }}>
                          {{ $category->name }}
                        </option>
                      @endforeach
                    </select>
                    <small class="form-text text-muted">Pastikan bahan terhubung minimal ke satu kategori.</small>
                  @endif
                </div>
                <div class="form-group">
                  <label>Satuan Terkecil</label>
                  <select name="unit" class="form-control" required {{ $units->isEmpty() ? 'disabled' : '' }}>
                    @foreach($units as $unit)
                      <option value="{{ $unit->code }}" {{ old('unit', $material->unit) === $unit->code ? 'selected' : '' }}>
                        {{ $unit->name }} ({{ $unit->code }})
                      </option>
                    @endforeach
                  </select>
                  @if($units->isEmpty())
                    <small class="form-text text-danger">Belum ada data satuan. Silakan tambahkan satuan terlebih dahulu.</small>
                  @endif
                </div>
                @php
                  $minStockOld = old('min_stock', $material->min_stock);
                  $minStockDisplay = ($minStockOld === null || $minStockOld === '') ? '' : number_format((float) $minStockOld, 1, '.', '');
                @endphp
                <div class="form-group">
                  <label>Harga</label>
                  <div class="form-control-plaintext">{{ number_format((float) $material->unit_cost, 1, ',', '.') }} (otomatis)</div>
                  <small class="form-text text-muted">Harga mengikuti transaksi Uang Keluar terkait pembelian bahan.</small>
                </div>
                <div class="form-group">
                  <label>Stok Saat Ini</label>
                  <input type="number" step="0.1" class="form-control" value="{{ number_format((float) $material->stock_qty, 1, '.', '') }}" disabled>
                  <small class="form-text text-muted">Gunakan menu Adjust Stok untuk mengubah nilai ini.</small>
                </div>
                <div class="form-group">
                  <label>Stok Minimum</label>
                  <input type="number" step="0.1" name="min_stock" class="form-control" value="{{ $minStockDisplay }}">
                  <small class="form-text text-muted">Digunakan sebagai batas notifikasi stok rendah.</small>
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
    // No additional behaviour needed on edit form.
  });
</script>
@endpush
