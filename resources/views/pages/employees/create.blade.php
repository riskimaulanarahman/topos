@extends('layouts.app')

@section('title', 'Tambah Karyawan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header"><h1>Tambah Karyawan</h1></div>
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-6">
          <div class="card">
            <form action="{{ route('employees.store') }}" method="POST">@csrf
              <div class="card-body">
                <div class="form-group"><label>Nama</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="form-group"><label>HP</label><input type="text" name="phone" class="form-control"></div>
                <div class="form-group"><label>PIN</label><input type="password" name="pin" class="form-control" required></div>
                <div class="form-group"><label>Role</label>
                  <select name="role" class="form-control">
                    <option value="owner">owner</option>
                    <option value="manager">manager</option>
                    <option value="staff">staff</option>
                  </select>
                </div>
                <div class="form-group"><div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
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

