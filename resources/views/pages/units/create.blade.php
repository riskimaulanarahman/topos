@extends('layouts.app')

@section('title', 'Tambah Satuan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Tambah Satuan</h1>
    </div>
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-6">
          <div class="card">
            <form action="{{ route('units.store') }}" method="POST">
              @csrf
              <div class="card-body">
                <div class="form-group">
                  <label>Kode</label>
                  <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
                  <small class="form-text text-muted">Contoh: g, ml, pcs.</small>
                </div>
                <div class="form-group">
                  <label>Nama</label>
                  <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="form-group">
                  <label>Deskripsi</label>
                  <input type="text" name="description" class="form-control" value="{{ old('description') }}">
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
