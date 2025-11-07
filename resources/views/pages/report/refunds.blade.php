@extends('layouts.app')

@section('title', 'Report - Refunds')

@push('style')
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css" />
@endpush

@section('main')
<div class="main-content">
<section class="section">
  <div class="section-header">
    <h1>Refunds</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></div>
      <div class="breadcrumb-item">Reports</div>
      <div class="breadcrumb-item active">Refunds</div>
    </div>
  </div>

  <div class="section-body">
    <div class="card mb-4">
      <div class="card-header">
        <h4>Filter</h4>
      </div>
      <div class="card-body">
        <form class="row g-3" method="GET" action="{{ route('report.refunds') }}">
          <div class="col-md-3">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" class="form-control" name="date_from" value="{{ $date_from }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" class="form-control" name="date_to" value="{{ $date_to }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">User</label>
            <select name="user_id" class="form-control">
              @foreach($users as $u)
                <option value="{{ $u->id }}" {{ (int)$userId === (int)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Page Size</label>
            <select name="page_size" class="form-control">
              @foreach([20,50,100] as $ps)
                <option value="{{ $ps }}" {{ (int)request('page_size', 50) === $ps ? 'selected' : '' }}>{{ $ps }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
          </div>
        </form>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4>Periode: {{ $date_from }} s/d {{ $date_to }}</h4>
          </div>
          <div class="card-body">
            <div class="mb-3">
              @php($chips = [])
              @if(request('date_from')) @php($chips[] = 'Dari: '.request('date_from')) @endif
              @if(request('date_to')) @php($chips[] = 'Ke: '.request('date_to')) @endif
              @if(request('user_id'))
                  @php($u = ($users ?? collect())->firstWhere('id', request('user_id')))
                  @if($u) @php($chips[] = 'User: '.$u->name) @endif
              @endif
              @if(count($chips))
                  <div>
                      @foreach($chips as $chip)
                          <span class="badge badge-primary mr-2">{{ $chip }}</span>
                      @endforeach
                  </div>
              @endif
            </div>
            <div class="row mb-3">
              <div class="col-md-3">
                <div class="small text-muted">Refund Count</div>
                <div class="h5 mb-0">{{ number_format($summary['refund_count']) }}</div>
              </div>
              <div class="col-md-3">
                <div class="small text-muted">Refund Amount</div>
                <div class="h5 mb-0">{{ number_format($summary['refund_amount']) }}</div>
              </div>
              <div class="col-md-3">
                <div class="small text-muted">Refund Rate</div>
                <div class="h5 mb-0">{{ $summary['refund_rate_pct'] }}%</div>
              </div>
              <div class="col-md-3">
                <div class="small text-muted">Total Orders</div>
                <div class="h5 mb-0">{{ number_format($summary['total_orders']) }}</div>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Transaction</th>
                    <th>User</th>
                    <th class="text-right">Refund</th>
                    <th>Method</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($refunds as $o)
                    <tr>
                      <td>{{ $o->id }}</td>
                      <td>{{ $o->transaction_number }}</td>
                      <td>{{ optional($o->user)->name }}</td>
                      <td class="text-right">{{ number_format($o->refund_nominal) }}</td>
                      <td>{{ $o->refund_method }}</td>
                      <td>{{ optional($o->created_at)->toDateTimeString() }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="6" class="text-center text-muted">No data</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <div>
              {{ $refunds->links() }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
</div>
@endsection
