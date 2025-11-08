@extends('layouts.app')

@section('title', 'Edit Satuan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Edit Satuan</h1>
    </div>
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-6">
          <div class="card">
            <form action="{{ route('units.update', $unit) }}" method="POST">
              @csrf
              @method('PUT')
              <div class="card-body">
                <div class="form-group">
                  <label>Kode</label>
                  <input type="text" name="code" class="form-control" value="{{ old('code', $unit->code) }}" required>
                </div>
                <div class="form-group">
                  <label>Nama</label>
                  <input type="text" name="name" class="form-control" value="{{ old('name', $unit->name) }}" required>
                </div>
                <div class="form-group">
                  <label>Deskripsi</label>
                  <input type="text" name="description" class="form-control" value="{{ old('description', $unit->description) }}">
                </div>
              </div>
              <div class="card-footer text-right">
                <button class="btn btn-primary">Simpan</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
