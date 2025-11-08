@extends('layouts.error')

@section('title', '500 Internal Server Error')

@section('main')
<div class="row">
  <div class="col-12 col-md-10 offset-md-1 col-lg-8 offset-lg-2">
    <div class="card card-primary">
      <div class="card-body text-center py-5">
        <div class="display-1 font-weight-bold mb-3">500</div>
        <h4 class="mb-3">Kesalahan Server</h4>
        <p class="text-muted mb-4">Terjadi kesalahan tak terduga pada server. Silakan coba lagi beberapa saat lagi.</p>
        <div>
          <a href="{{ auth()->check() ? route('home') : (url()->previous() ?? url('/')) }}" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left mr-1"></i>Kembali</a>
          <a href="{{ route('home') }}" class="btn btn-primary"><i class="fas fa-home mr-1"></i>Dashboard</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

