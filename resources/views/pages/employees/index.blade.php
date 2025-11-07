@extends('layouts.app')

@section('title', 'Karyawan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Karyawan</h1>
      <div class="section-header-button">
        <a href="{{ route('employees.create') }}" class="btn btn-primary">Tambah</a>
      </div>
    </div>
    <div class="section-body">
      @include('layouts.alert')
      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>Email</th>
                  <th>HP</th>
                  <th>Role</th>
                  <th>Aktif</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @foreach($employees as $e)
                <tr>
                  <td>{{ $e->name }}</td>
                  <td>{{ $e->email }}</td>
                  <td>{{ $e->phone }}</td>
                  <td>{{ $e->role }}</td>
                  <td>{{ $e->is_active ? 'Ya' : 'Tidak' }}</td>
                  <td class="text-right">
                    <a href="{{ route('employees.edit',$e) }}" class="btn btn-sm btn-info">Edit</a>
                    @if(!$e->is_active)
                      <form action="{{ route('employees.activate',$e) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-success">Aktifkan</button></form>
                    @else
                      <form action="{{ route('employees.deactivate',$e) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-warning">Nonaktifkan</button></form>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="float-right">{{ $employees->links() }}</div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

