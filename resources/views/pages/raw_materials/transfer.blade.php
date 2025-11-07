@extends('layouts.app')

@section('title', 'Transfer Stok Bahan')

@section('main')
@php
    $availableStock = number_format($material->stock_qty, 1, ',', '.');
@endphp
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Transfer Stok: {{ $material->name }}</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('raw-materials.index') }}">Bahan Pokok</a></div>
        <div class="breadcrumb-item active">Transfer</div>
      </div>
    </div>
    <div class="section-body">
      @include('layouts.alert')

      <div class="row">
        <div class="col-12 col-lg-6">
          <div class="card shadow-sm">
            <div class="card-header">
              <h4>Form Transfer</h4>
            </div>
            <form action="{{ route('raw-materials.transfer', $material) }}" method="POST">
              @csrf
              <div class="card-body">
                <div class="alert alert-light border" role="alert">
                  <div class="font-weight-bold mb-1">Ringkasan Bahan</div>
                  <div>SKU: <strong>{{ $material->sku }}</strong></div>
                  <div>Stok tersedia: <strong>{{ $availableStock }} {{ $material->unit }}</strong></div>
                  <div>Satuan: <strong>{{ $material->unit }}</strong></div>
                </div>

                @if($destinationOutlets->isEmpty())
                  <div class="alert alert-warning">
                    Anda belum memiliki outlet lain sebagai owner. Buat outlet baru atau minta akses owner untuk mentransfer stok.
                  </div>
                @else
                  <div class="form-group">
                    <label for="destination_outlet_id">Outlet Tujuan <span class="text-danger">*</span></label>
                    <select
                      id="destination_outlet_id"
                      name="destination_outlet_id"
                      class="form-control @error('destination_outlet_id') is-invalid @enderror"
                      required
                    >
                      <option value="">- Pilih Outlet -</option>
                      @foreach($destinationOutlets as $outlet)
                        <option value="{{ $outlet->id }}" {{ (int) old('destination_outlet_id') === $outlet->id ? 'selected' : '' }}>
                          {{ $outlet->name }}
                        </option>
                      @endforeach
                    </select>
                    @error('destination_outlet_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="form-group">
                    <label for="destination_raw_material_id">Bahan di Outlet Tujuan <span class="text-danger">*</span></label>
                    <select
                      id="destination_raw_material_id"
                      name="destination_raw_material_id"
                      class="form-control @error('destination_raw_material_id') is-invalid @enderror"
                      required
                      data-old="{{ old('destination_raw_material_id') }}"
                    >
                      <option value="">- Pilih Bahan -</option>
                    </select>
                    @error('destination_raw_material_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="small text-muted mt-2" id="destination-material-info"></div>
                  </div>

                  <div class="form-group">
                    <label for="qty">Jumlah Transfer <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <input
                        type="number"
                        step="0.1"
                        min="0.1"
                        class="form-control @error('qty') is-invalid @enderror"
                        id="qty"
                        name="qty"
                        value="{{ old('qty') }}"
                        placeholder="Masukkan jumlah"
                        required
                      >
                      <div class="input-group-append">
                        <span class="input-group-text">{{ $material->unit }}</span>
                      </div>
                      @error('qty')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                      @enderror
                    </div>
                    <small class="form-text text-muted">Maksimal {{ $availableStock }} {{ $material->unit }}.</small>
                  </div>

                  <div class="form-group">
                    <label for="notes">Catatan</label>
                    <textarea
                      class="form-control @error('notes') is-invalid @enderror"
                      id="notes"
                      name="notes"
                      rows="3"
                    >{{ old('notes') }}</textarea>
                    @error('notes')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                @endif
              </div>
              <div class="card-footer text-right">
                <a href="{{ route('raw-materials.index') }}" class="btn btn-light mr-2">Batal</a>
                <button class="btn btn-primary" {{ $destinationOutlets->isEmpty() ? 'disabled' : '' }}>Transfer</button>
              </div>
            </form>
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
              <h4 class="mb-0">Riwayat Transfer Terkait</h4>
              <button type="button" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0" data-toggle="modal" data-target="#transferHistoryModal">
                Lihat Riwayat
              </button>
            </div>
            <div class="card-body p-0">
              @if($recentTransfers->isEmpty())
                <div class="p-4 text-center text-muted">Belum ada riwayat transfer untuk bahan ini.</div>
              @else
                <div class="table-responsive">
                  <table class="table mb-0">
                    <thead>
                      <tr>
                        <th>Tanggal</th>
                        <th>Outlet</th>
                        <th>Kuantitas</th>
                        <th>Catatan</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($recentTransfers as $transfer)
                        @php
                          $isOutgoing = $transfer->raw_material_from_id === $material->id;
                          $qty = number_format($transfer->qty, 1, ',', '.');
                        @endphp
                        <tr>
                          <td>{{ optional($transfer->transferred_at)->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                          <td>
                            @if($isOutgoing)
                              <span class="badge badge-danger mr-1">Keluar</span>
                              {{ $transfer->targetOutlet?->name ?? '—' }}
                            @else
                              <span class="badge badge-success mr-1">Masuk</span>
                              {{ $transfer->sourceOutlet?->name ?? '—' }}
                            @endif
                          </td>
                          <td>
                            {{ $isOutgoing ? '-' : '+' }}{{ $qty }} {{ $material->unit }}
                          </td>
                          <td>{{ $transfer->notes ?: '—' }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="transferHistoryModal" tabindex="-1" role="dialog" aria-labelledby="transferHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="transferHistoryModalLabel">Riwayat Transfer Stok</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body p-0">
              <div class="table-responsive">
                <table class="table table-striped mb-0">
                  <thead>
                    <tr>
                      <th>Tanggal</th>
                      <th>Arah</th>
                      <th>Outlet</th>
                      <th>Jumlah</th>
                      <th>Catatan</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($recentTransfers as $transfer)
                      @php
                        $isOutgoing = $transfer->raw_material_from_id === $material->id;
                        $directionLabel = $isOutgoing ? 'Keluar' : 'Masuk';
                        $badgeClass = $isOutgoing ? 'badge-danger' : 'badge-success';
                        $counterOutlet = $isOutgoing ? $transfer->targetOutlet?->name : $transfer->sourceOutlet?->name;
                        $sign = $isOutgoing ? '-' : '+';
                        $qty = number_format((float) $transfer->qty, 1, ',', '.');
                      @endphp
                      <tr>
                        <td>{{ optional($transfer->transferred_at)->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $directionLabel }}</span></td>
                        <td>{{ $counterOutlet ?? '—' }}</td>
                        <td>{{ $sign }}{{ $qty }} {{ $material->unit }}</td>
                        <td>{{ $transfer->notes ?: '—' }}</td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada riwayat transfer stok.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
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
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const outletSelect = document.getElementById('destination_outlet_id');
    const materialSelect = document.getElementById('destination_raw_material_id');
    const materialInfo = document.getElementById('destination-material-info');
    const materialsByOutlet = @json($materialsByOutlet);

    const renderMaterialOptions = (outletId) => {
      const options = materialsByOutlet[outletId] || [];
      const oldValue = materialSelect.dataset.old || '';
      materialSelect.innerHTML = '<option value=\"\">- Pilih Bahan -</option>';
      options.forEach((item) => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = `${item.name} (${item.unit})`;
        if (String(oldValue) === String(item.id)) {
          option.selected = true;
        }
        materialSelect.appendChild(option);
      });

      if (!options.length) {
        materialInfo.textContent = 'Tidak ada bahan tersedia di outlet ini. Buat bahan terlebih dahulu.';
      } else {
        const selected = options.find((item) => String(item.id) === String(materialSelect.value));
        updateMaterialInfo(selected);
      }
    };

    const updateMaterialInfo = (data) => {
      if (!data) {
        materialInfo.textContent = '';
        return;
      }
      materialInfo.textContent = `SKU: ${data.sku || '-'} • Stok: ${Number(data.stock_qty).toFixed(1)} ${data.unit}`;
    };

    outletSelect?.addEventListener('change', (event) => {
      materialSelect.dataset.old = '';
      renderMaterialOptions(event.target.value);
    });

    materialSelect?.addEventListener('change', (event) => {
      const outletId = outletSelect?.value;
      const options = materialsByOutlet[outletId] || [];
      const selected = options.find((item) => String(item.id) === String(event.target.value));
      updateMaterialInfo(selected);
    });

    if (outletSelect?.value) {
      renderMaterialOptions(outletSelect.value);
    }
  });
</script>
@endpush
