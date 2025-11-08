@extends('layouts.app')

@section('title', 'Resep Produk')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Resep: {{ $product->name }}</h1>
    </div>
    <div class="section-body">
      @include('layouts.alert')
      @if($materials->isEmpty())
        <div class="alert alert-warning">
          Belum ada bahan baku yang memiliki pengeluaran tercatat. Silakan input pengeluaran bahan baku terlebih dahulu agar dapat disusun resep produk.
        </div>
      @endif
      @include('components.help_panel', [
        'id' => 'help-recipe-edit',
        'title' => 'Panduan singkat • Resep Produk',
        'items' => [
          'Isi Yield Qty dan Unit.',
          'Tambahkan bahan (Bahan Pokok), isi Qty per Yield dan Waste % (opsional).',
          'Simpan. HPP per unit dihitung otomatis dari rata-rata biaya bahan saat ini.',
        ],
      ])
      <div class="row">
        <div class="col-12 col-md-8">
          <div class="card">
            <form action="{{ route('product-recipes.update',$product) }}" method="POST">
              @csrf
              <div class="card-body">
                <div class="form-group">
                  <label>Yield Qty</label>
                  <input type="number" step="0.0001" name="yield_qty" value="{{ old('yield_qty',$recipe->yield_qty ?? 1) }}" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Unit</label>
                  <input type="text" name="unit" value="{{ old('unit',$recipe->unit ?? 'pcs') }}" class="form-control">
                </div>
                <div class="form-group">
                  <label>Komposisi</label>
                  <div id="items">
                    @php($i=0)
                    @foreach(($recipe->items ?? []) as $it)
                      <div class="form-row mb-2">
                        <div class="col">
                          <select name="items[{{ $i }}][raw_material_id]" class="form-control">
                            @foreach($materials as $m)
                              <option value="{{ $m->id }}" {{ $it->raw_material_id==$m->id?'selected':'' }}>{{ $m->name }} ({{ $m->unit }})</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col">
                          <input type="number" step="0.0001" name="items[{{ $i }}][qty_per_yield]" value="{{ $it->qty_per_yield }}" class="form-control" placeholder="Qty / Yield">
                        </div>
                        <div class="col">
                          <input type="number" step="0.01" name="items[{{ $i }}][waste_pct]" value="{{ $it->waste_pct }}" class="form-control" placeholder="Waste %">
                        </div>
                      </div>
                      @php($i++)
                    @endforeach
                    @for($k=$i;$k<$i+3;$k++)
                      <div class="form-row mb-2">
                        <div class="col">
                          <select name="items[{{ $k }}][raw_material_id]" class="form-control">
                            <option value="">- pilih bahan -</option>
                            @foreach($materials as $m)
                              <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->unit }})</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col">
                          <input type="number" step="0.0001" name="items[{{ $k }}][qty_per_yield]" class="form-control" placeholder="Qty / Yield">
                        </div>
                        <div class="col">
                          <input type="number" step="0.01" name="items[{{ $k }}][waste_pct]" class="form-control" placeholder="Waste %">
                        </div>
                      </div>
                    @endfor
                  </div>
                </div>
              </div>
              <div class="card-footer text-right">
                <button class="btn btn-primary">Simpan Resep</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
