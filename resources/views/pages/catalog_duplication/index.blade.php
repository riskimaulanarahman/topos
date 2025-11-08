@extends('layouts.app')

@section('title', 'Riwayat Duplikasi Katalog')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Duplikasi Katalog</h1>
      <div class="section-header-button">
        <a href="{{ route('catalog-duplication.create') }}" class="btn btn-primary">Mulai Duplikasi</a>
      </div>
    </div>
    <div class="section-body">
      @include('layouts.alert')
      <div class="card">
        <div class="card-header">
          <h4>Riwayat Duplikasi</h4>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Outlet Sumber</th>
                  <th>Outlet Tujuan</th>
                  <th>Status</th>
                  <th>Ringkasan</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse($jobs as $job)
                  <tr>
                    <td>{{ optional($job->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>{{ $job->sourceOutlet?->name ?? '—' }}</td>
                    <td>{{ $job->targetOutlet?->name ?? '—' }}</td>
                    <td>
                      <span class="badge badge-{{ $job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : 'warning') }}">
                        {{ ucfirst($job->status) }}
                      </span>
                    </td>
                    <td>
                      @php
                        $counts = $job->counts ?? [];
                      @endphp
                      <span class="small text-muted">
                        Kategori: {{ $counts['categories'] ?? 0 }},
                        Bahan: {{ $counts['raw_materials'] ?? 0 }},
                        Produk: {{ $counts['products'] ?? 0 }}
                      </span>
                    </td>
                    <td class="text-right">
                      <a href="{{ route('catalog-duplication.jobs.show', $job) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada pekerjaan duplikasi.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer text-right">
          {{ $jobs->links() }}
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
