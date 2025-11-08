@extends('layouts.app')

@section('title', 'Edit Karyawan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header"><h1>Edit Karyawan</h1></div>
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-6">
          <div class="card">
            <form action="{{ route('employees.update',$employee) }}" method="POST">@csrf @method('PUT')
              <div class="card-body">
                <div class="form-group"><label>Nama</label><input type="text" name="name" class="form-control" value="{{ $employee->name }}" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="{{ $employee->email }}" required></div>
                <div class="form-group"><label>HP</label><input type="text" name="phone" class="form-control" value="{{ $employee->phone }}"></div>
                <div class="form-group"><label>PIN (isi jika ingin ubah)</label><input type="password" name="pin" class="form-control"></div>
                <div class="form-group"><label>Role</label>
                  <select name="role" class="form-control">
                    @foreach(['owner','manager','staff'] as $r)
                      <option value="{{ $r }}" {{ $employee->role==$r?'selected':'' }}>{{ $r }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="form-group"><div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ $employee->is_active?'checked':'' }}>
                  <label class="custom-control-label" for="is_active">Aktif</label>
                </div></div>
              </div>
              <div class="card-footer text-right"><button class="btn btn-primary">Simpan</button></div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

