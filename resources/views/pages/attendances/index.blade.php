@extends('layouts.app')

@section('title', 'Absensi Karyawan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Absensi Karyawan</h1>
      <div class="section-header-button">
        <form method="GET" action="{{ route('attendances.export') }}">
          <div class="form-row">
            <div class="col"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" placeholder="Dari"></div>
            <div class="col"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" placeholder="Sampai"></div>
            <div class="col">
              <select name="employee_id" class="form-control">
                <option value="">Semua</option>
                @foreach($employees as $emp)
                  <option value="{{ $emp->id }}" {{ request('employee_id')==$emp->id?'selected':'' }}>{{ $emp->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col"><button class="btn btn-success">Export CSV</button></div>
          </div>
        </form>
      </div>
    </div>
    <div class="section-body">
      <div class="card">
        <div class="card-body">
          <form method="GET" action="{{ route('attendances.index') }}" class="form-inline mb-3">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control mr-2">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control mr-2">
            <select name="employee_id" class="form-control mr-2">
              <option value="">Semua</option>
              @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ request('employee_id')==$emp->id?'selected':'' }}>{{ $emp->name }}</option>
              @endforeach
            </select>
            <button class="btn btn-primary">Filter</button>
          </form>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>Clock In</th>
                  <th>Clock Out</th>
                  <th>Menit</th>
                  <th>Catatan</th>
                </tr>
              </thead>
              <tbody>
                @foreach($rows as $r)
                <tr>
                  <td>{{ $r->employee?->name }}</td>
                  <td>{{ optional($r->clock_in_at)->toDateTimeString() }}</td>
                  <td>{{ optional($r->clock_out_at)->toDateTimeString() }}</td>
                  <td>{{ $r->work_minutes }}</td>
                  <td>{{ $r->notes }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="float-right">{{ $rows->withQueryString()->links() }}</div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

