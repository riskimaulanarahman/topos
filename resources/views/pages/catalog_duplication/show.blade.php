@extends('layouts.app')

@section('title', 'Detail Duplikasi Katalog')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Detail Duplikasi</h1>
      <div class="section-header-button">
        <a href="{{ route('catalog-duplication.index') }}" class="btn btn-light">Kembali</a>
      </div>
    </div>
    <div class="section-body">
      @include('layouts.alert')
      <div class="card">
        <div class="card-header">
          <h4>Ringkasan</h4>
        </div>
        <div class="card-body">
          <dl class="row">
            <dt class="col-sm-3">Outlet Sumber</dt>
            <dd class="col-sm-9">{{ $job->sourceOutlet?->name ?? '—' }}</dd>
            <dt class="col-sm-3">Outlet Tujuan</dt>
            <dd class="col-sm-9">{{ $job->targetOutlet?->name ?? '—' }}</dd>
            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
              <span class="badge badge-{{ $job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : 'warning') }}">
                {{ ucfirst($job->status) }}
              </span>
            </dd>
            <dt class="col-sm-3">Dibuat</dt>
            <dd class="col-sm-9">{{ optional($job->created_at)->format('d/m/Y H:i') ?? '—' }}</dd>
            <dt class="col-sm-3">Selesai</dt>
            <dd class="col-sm-9">{{ optional($job->finished_at)->format('d/m/Y H:i') ?? '—' }}</dd>
          </dl>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h4>Detail Item</h4>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead>
                <tr>
                  <th>Jenis</th>
                  <th>ID Sumber</th>
                  <th>ID Tujuan</th>
                  <th>Status</th>
                  <th>Catatan</th>
                </tr>
              </thead>
              <tbody>
                @forelse($job->items as $item)
                  <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $item->entity_type)) }}</td>
                    <td>{{ $item->source_id }}</td>
                    <td>{{ $item->target_id ?? '—' }}</td>
                    <td>
                      <span class="badge badge-{{ $item->status === 'completed' ? 'success' : ($item->status === 'failed' ? 'danger' : 'warning') }}">
                        {{ ucfirst($item->status) }}
                      </span>
                    </td>
                    <td>{{ $item->notes ?: '—' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">Belum ada item terdaftar.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
