@extends('layouts.app')

@section('title', 'Adjust Stok Bahan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Adjust Stok: {{ $material->name }}</h1>
    </div>
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-6">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
              <h4 class="mb-0">Penyesuaian Stok</h4>
              <div class="mt-2 mt-md-0">
                <button type="button" class="btn btn-outline-secondary btn-sm mr-2" data-toggle="modal" data-target="#adjustHistoryModal">
                  Riwayat Adjust
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#adjustTransferHistoryModal">
                  Riwayat Transfer
                </button>
              </div>
            </div>
            <form action="{{ route('raw-materials.adjust',$material) }}" method="POST">
              @csrf
              <div class="card-body">
                <div class="alert alert-light border mb-4" role="alert">
                  <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                      <div class="font-weight-bold mb-1">Ringkasan Stok</div>
                      <div>Stok saat ini: <strong>{{ number_format($material->stock_qty, 1, ',', '.') }} {{ $material->unit }}</strong></div>
                      <div>Harga rata-rata: <strong>{{ number_format($material->unit_cost, 1, ',', '.') }}</strong></div>
                    </div>
                    <div class="text-muted small mt-2 mt-md-0">
                      SKU: {{ $material->sku }}
                    </div>
                  </div>
                  <hr>
                  @if(isset($lastMovement) && $lastMovement)
                    <div class="mb-1">Pergerakan terakhir: <strong>{{ ucfirst(str_replace('_',' ', $lastMovement->type)) }}</strong></div>
                    <div class="small text-muted">
                      {{ optional($lastMovement->occurred_at)->format('d/m/Y H:i') ?? '—' }} • Qty {{ number_format($lastMovement->qty_change, 1, ',', '.') }} • Harga {{ number_format($lastMovement->unit_cost, 1, ',', '.') }}
                      @php
                        $reasonLabels = [
                          'stock_opname' => 'Stok Opname',
                          'damage' => 'Barang Rusak',
                          'loss' => 'Barang Hilang',
                          'other' => 'Penyesuaian Lainnya',
                        ];
                      @endphp
                      @if($lastMovement->adjustment_reason)
                        <div class="mt-1">Alasan: {{ $reasonLabels[$lastMovement->adjustment_reason] ?? ucfirst(str_replace('_', ' ', $lastMovement->adjustment_reason)) }}</div>
                      @endif
                      @if($lastMovement->notes)
                        <div class="mt-1">Catatan: {{ $lastMovement->notes }}</div>
                      @endif
                    </div>
                  @else
                    <div class="small text-muted">Belum ada histori pergerakan stok.</div>
                  @endif
                </div>
                <div class="form-group">
                  <label>Jenis Penyesuaian</label>
                  <select name="adjustment_reason" class="form-control" required>
                    <option value="">- Pilih jenis penyesuaian -</option>
                    <option value="stock_opname" {{ old('adjustment_reason') === 'stock_opname' ? 'selected' : '' }}>Stok Opname</option>
                    <option value="damage" {{ old('adjustment_reason') === 'damage' ? 'selected' : '' }}>Barang Rusak</option>
                    <option value="loss" {{ old('adjustment_reason') === 'loss' ? 'selected' : '' }}>Barang Hilang</option>
                    <option value="other" {{ old('adjustment_reason') === 'other' ? 'selected' : '' }}>Penyesuaian Lainnya</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Jumlah Hasil Hitung (opsional)</label>
                  <input type="number" step="0.1" name="counted_qty" class="form-control" value="{{ old('counted_qty') }}" data-current-stock="{{ number_format($material->stock_qty, 1, '.', '') }}">
                  <small class="form-text text-muted">Isi dengan jumlah fisik yang kamu temukan saat stok opname.</small>
                  <div class="small text-muted mt-1" id="counted-diff-indicator" data-unit="{{ $material->unit }}"></div>
                </div>
                <div class="form-group">
                  <label>Qty Change (boleh negatif)</label>
                  <input type="number" step="0.1" name="qty_change" class="form-control" value="{{ old('qty_change') }}">
                  <small class="form-text text-muted">Biarkan kosong jika sudah mengisi jumlah hasil hitung.</small>
                </div>
                <div class="form-group">
                  <label>Catatan</label>
                  <textarea name="notes" class="form-control">{{ old('notes') }}</textarea>
                </div>
              </div>
              <div class="card-footer text-right">
                <button class="btn btn-primary">Simpan</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="modal fade" id="adjustHistoryModal" tabindex="-1" role="dialog" aria-labelledby="adjustHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="adjustHistoryModalLabel">Riwayat Penyesuaian Stok</h5>
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
                      <th>Perubahan</th>
                      <th>Hasil Hitung</th>
                      <th>Alasan</th>
                      <th>Catatan</th>
                    </tr>
                  </thead>
                  <tbody>
                    @php
                      $reasonLabels = [
                        'stock_opname' => 'Stok Opname',
                        'damage' => 'Barang Rusak',
                        'loss' => 'Barang Hilang',
                        'other' => 'Penyesuaian Lainnya',
                        'transfer_out' => 'Transfer Keluar',
                        'transfer_in' => 'Transfer Masuk',
                      ];
                    @endphp
                    @forelse($adjustHistory as $history)
                      <tr>
                        <td>{{ optional($history->occurred_at)->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ number_format((float) $history->qty_change, 1, ',', '.') }} {{ $material->unit }}</td>
                        <td>
                          @if(!is_null($history->counted_qty))
                            {{ number_format((float) $history->counted_qty, 1, ',', '.') }} {{ $material->unit }}
                          @else
                            —
                          @endif
                        </td>
                        <td>{{ $reasonLabels[$history->adjustment_reason] ?? ($history->adjustment_reason ? ucfirst(str_replace('_', ' ', $history->adjustment_reason)) : '—') }}</td>
                        <td>{{ $history->notes ?: '—' }}</td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada riwayat penyesuaian stok.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="adjustTransferHistoryModal" tabindex="-1" role="dialog" aria-labelledby="adjustTransferHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="adjustTransferHistoryModalLabel">Riwayat Transfer Stok</h5>
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
                    @forelse($transferHistory as $transfer)
                      @php
                        $isOutgoing = $transfer->raw_material_from_id === $material->id;
                        $directionLabel = $isOutgoing ? 'Keluar' : 'Masuk';
                        $badgeClass = $isOutgoing ? 'badge-danger' : 'badge-success';
                        $counterOutlet = $isOutgoing ? $transfer->targetOutlet?->name : $transfer->sourceOutlet?->name;
                        $sign = $isOutgoing ? '-' : '+';
                      @endphp
                      <tr>
                        <td>{{ optional($transfer->transferred_at)->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $directionLabel }}</span></td>
                        <td>{{ $counterOutlet ?? '—' }}</td>
                        <td>{{ $sign }}{{ number_format((float) $transfer->qty, 1, ',', '.') }} {{ $material->unit }}</td>
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
    const countedInput = document.querySelector('input[name="counted_qty"]');
    const qtyChangeInput = document.querySelector('input[name="qty_change"]');
    const diffIndicator = document.getElementById('counted-diff-indicator');
    if (countedInput) {
      const currentStock = parseFloat(countedInput.dataset.currentStock || '0');
      const unitLabel = diffIndicator ? (diffIndicator.dataset.unit || '') : '';

      const updateDiff = () => {
        const rawValue = parseFloat(countedInput.value);
        if (Number.isNaN(rawValue)) {
          if (diffIndicator) {
            diffIndicator.textContent = '';
          }
          return;
        }
        const diff = parseFloat((rawValue - currentStock).toFixed(1));
        if (qtyChangeInput) {
          qtyChangeInput.value = diff.toFixed(1);
        }
        if (diffIndicator) {
          const sign = diff > 0 ? '+' : '';
          diffIndicator.textContent = `Selisih terhadap stok sistem: ${sign}${diff.toFixed(1)} ${unitLabel}`;
        }
      };

      countedInput.addEventListener('input', updateDiff);
      if (countedInput.value) {
        updateDiff();
      }
    }
  });
</script>
@endpush
