@extends('layouts.app')

@section('title', 'Bahan Pokok')

@section('main')
@php
    $materialRole = $currentOutletRole ?? null;
    $materialIsOwner = $materialRole?->role === 'owner';
    $materialCanTransfer = $materialIsOwner;
    $materialCanManage = ($outletPermissions['can_manage_stock'] ?? false) || $materialIsOwner || (Auth::user()?->roles === 'admin');
@endphp
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Bahan Pokok</h1>
      @if ($materialCanManage)
          <div class="section-header-button">
            <button type="button"
                    class="btn btn-outline-primary mr-2"
                    id="btn-send-stock-alert"
                    data-url="{{ route('raw-materials.send-stock-alert') }}">
              Kirim Alert Stok
            </button>
            <a href="{{ route('raw-materials.create') }}" class="btn btn-primary">Tambah</a>
          </div>
      @endif
    </div>
    <div class="section-body">
      @unless($materialCanManage)
        <div class="alert alert-info shadow-sm">
          <i class="fas fa-info-circle mr-1"></i>
          Anda dapat melihat stok, tetapi tidak memiliki izin untuk mengubah atau menyesuaikan jumlah stok.
        </div>
      @endunless
      <div class="card">
        <div class="card-body">
          <form class="form-inline mb-3" method="GET" action="{{ route('raw-materials.index') }}">
            <input type="text" class="form-control mr-2" name="search" placeholder="Cari nama/SKU" value="{{ request('search') }}">
            <button class="btn btn-primary">Cari</button>
          </form>
          @include('layouts.alert')
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Nama Bahan</th>
                  <th>Kategori</th>
                  <th>Satuan</th>
                  <th>Harga Rata-rata</th>
                  <th>Stok</th>
                  <th>Stok Minimum</th>
                  <th class="text-right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($materials as $m)
                <tr>
                  <td>{{ $m->sku }}</td>
                  <td>{{ $m->name }}</td>
                  <td>
                    @php
                        $categoryNames = $m->categories->pluck('name')->implode(', ');
                    @endphp
                    {{ $categoryNames ?: '—' }}
                  </td>
                  <td>{{ $m->unit }}</td>
                  <td>{{ number_format($m->unit_cost, 1, ',', '.') }}</td>
                  <td>{{ number_format($m->stock_qty, 1, ',', '.') }}</td>
                  <td>{{ number_format($m->min_stock, 1, ',', '.') }}</td>
                  <td class="text-right">
                    @if ($materialCanManage)
                        <a href="{{ route('raw-materials.edit',$m) }}" class="btn btn-sm btn-info">Edit</a>
                        <a href="{{ route('raw-materials.adjust-form',$m) }}" class="btn btn-sm btn-warning">Adjust</a>
                        @if ($materialCanTransfer)
                            <a href="{{ route('raw-materials.transfer-form',$m) }}" class="btn btn-sm btn-secondary">Transfer</a>
                        @endif
                        <form action="{{ route('raw-materials.destroy',$m) }}" method="POST" class="d-inline js-delete-material">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
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
          <div class="float-right">{{ $materials->withQueryString()->links() }}</div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const sendButton = document.getElementById('btn-send-stock-alert');
    if (sendButton) {
      sendButton.addEventListener('click', async function () {
        const { isConfirmed } = await Swal.fire({
          title: 'Kirim ringkasan stok?',
          text: 'Email ringkasan akan dikirim ke admin.',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Kirim',
          cancelButtonText: 'Batal',
        });

        if (!isConfirmed) {
          return;
        }

        try {
          Swal.fire({
            title: 'Mengirim...',
            text: 'Mohon tunggu sebentar.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
              Swal.showLoading();
            },
          });

          const response = await fetch(this.dataset.url, {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
            },
          });

          const data = await response.json().catch(() => ({}));

          if (response.ok) {
            const recipients = Array.isArray(data?.sent_to) ? data.sent_to.length : 0;
            await Swal.fire({
              title: 'Berhasil',
              text: data?.message || `Ringkasan dikirim ke ${recipients} penerima.`,
              icon: 'success',
            });
            return;
          }

          const message = data?.message
            || (data?.errors ? Object.values(data.errors).flat().join(' ') : null)
            || 'Ringkasan stok gagal dikirim.';
          await Swal.fire({
            title: 'Gagal',
            text: message,
            icon: 'error',
          });
        } catch (error) {
          await Swal.fire({
            title: 'Gagal',
            text: 'Terjadi kesalahan. Silakan coba lagi.',
            icon: 'error',
          });
        }
      });
    }

    document.querySelectorAll('.js-delete-material').forEach(function (form) {
      form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const row = this.closest('tr');
        const materialName = row?.querySelector('td:nth-child(2)')?.textContent?.trim() || 'bahan baku ini';

        const result = await Swal.fire({
          title: `Hapus ${materialName}?`,
          text: 'Tindakan ini tidak dapat dibatalkan.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, hapus',
          cancelButtonText: 'Batal',
        });

        if (!result.isConfirmed) {
          return;
        }

        const formData = new FormData(this);
        const action = this.getAttribute('action');
        const methodInput = this.querySelector('input[name="_method"]');
        const method = methodInput ? methodInput.value.toUpperCase() : 'POST';

        try {
          const response = await fetch(action, {
            method: method === 'POST' ? 'POST' : 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': this.querySelector('input[name="_token"]').value,
              'Accept': 'application/json',
            },
            body: formData,
          });

          if (response.ok) {
            await Swal.fire({
              title: 'Berhasil',
              text: 'Bahan dihapus.',
              icon: 'success',
              timer: 1500,
              showConfirmButton: false,
            });
            window.location.reload();
            return;
          }

          const data = await response.json();
          const message = data?.message || 'Bahan tidak dapat dihapus.';
          await Swal.fire({
            title: 'Gagal',
            text: message,
            icon: 'error',
          });
        } catch (error) {
          await Swal.fire({
            title: 'Gagal',
            text: 'Terjadi kesalahan. Silakan coba lagi.',
            icon: 'error',
          });
        }
      });
    });
  });
</script>
@endpush
