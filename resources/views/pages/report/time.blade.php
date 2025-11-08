@extends('layouts.app')

@section('title', 'Report - Time Analysis')

@push('style')
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css" />
@endpush

@section('main')
<div class="main-content">
<section class="section">
  <div class="section-header">
    <h1>Time Analysis</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></div>
      <div class="breadcrumb-item">Reports</div>
      <div class="breadcrumb-item active">Time</div>
    </div>
  </div>

  <div class="section-body">
    <div class="card mb-4">
      <div class="card-header">
        <h4>Filter</h4>
      </div>
      <div class="card-body">
        <form action="{{ route('report.time') }}" method="GET">
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Dari Tanggal</label>
                <input type="date" class="form-control" name="date_from" value="{{ $date_from }}">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Sampai Tanggal</label>
                <input type="date" class="form-control" name="date_to" value="{{ $date_to }}">
              </div>
            </div>
            <!-- Status filter removed; enforced to Completed -->
            <div class="col-md-3">
              <div class="form-group">
                <label>Mode</label>
                <select name="mode" class="form-control">
                  <option value="hour" {{ ($mode ?? '') === 'hour' ? 'selected' : '' }}>By Hour</option>
                  <option value="dow" {{ ($mode ?? '') === 'dow' ? 'selected' : '' }}>Day of Week</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>User</label>
                <select name="user_id" class="form-control">
                  @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ (int)$userId === (int)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <button type="submit" class="btn btn-primary btn-lg mr-2"><i class="fas fa-search"></i> Filter</button>
              <a href="{{ route('report.time') }}" class="btn btn-light btn-lg">Reset</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h4>Periode: {{ $date_from }} s/d {{ $date_to }} — Mode: {{ strtoupper($chart['mode']) }}</h4>
      </div>
      <div class="card-body">
        <div class="mb-3">
          @php($chips = [])
          @if(request('date_from')) @php($chips[] = 'Dari: '.request('date_from')) @endif
          @if(request('date_to')) @php($chips[] = 'Ke: '.request('date_to')) @endif
          @php($chips[] = 'Status: Completed')
          @if(request('mode')) @php($chips[] = 'Mode: '.strtoupper(request('mode'))) @endif
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

        <div class="mb-4">
          <canvas id="timeChart" height="80"></canvas>
        </div>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Bucket</th>
                <th class="text-right">Orders</th>
                <th class="text-right">Revenue</th>
              </tr>
            </thead>
            <tbody>
              @foreach(($chart['labels'] ?? []) as $i => $label)
                <tr>
                  <td>{{ $label }}</td>
                  <td class="text-right">{{ number_format(($chart['orders'][$i] ?? 0)) }}</td>
                  <td class="text-right">{{ number_format(($chart['revenue'][$i] ?? 0)) }}</td>
                </tr>
              @endforeach
              @if(empty($chart['labels']) || count($chart['labels']) === 0)
                <tr><td colspan="3" class="text-center text-muted">No data</td></tr>
              @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      const timePayload = @json($chart ?? null);
      if (timePayload) {
        const ctx = document.getElementById('timeChart').getContext('2d');
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: timePayload.labels,
            datasets: [
              { label: 'Revenue', data: timePayload.revenue, borderColor: 'rgba(54,162,235,1)', backgroundColor: 'rgba(54,162,235,0.2)', tension: 0.3 },
              { label: 'Orders', data: timePayload.orders, type: 'bar', yAxisID: 'y2', borderColor: 'rgba(255,99,132,1)', backgroundColor: 'rgba(255,99,132,0.2)' }
            ]
          },
          options: { responsive:true, scales: { y: { beginAtZero: true }, y2: { beginAtZero:true, position:'right', grid:{ drawOnChartArea:false } } } }
        });
      }
    </script>
@endpush
