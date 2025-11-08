@extends('layouts.error')

@section('title', '404 Not Found')

@section('main')
<div class="row">
  <div class="col-12 col-md-10 offset-md-1 col-lg-8 offset-lg-2">
    <div class="card card-primary">
      <div class="card-body text-center py-5">
        <div class="display-1 font-weight-bold mb-3">404</div>
        <h4 class="mb-3">Halaman Tidak Ditemukan</h4>
        <p class="text-muted mb-4">URL yang Anda minta tidak tersedia atau telah dipindahkan.</p>
        <div>
          <a href="{{ auth()->check() ? route('home') : (url()->previous() ?? url('/')) }}" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left mr-1"></i>Kembali</a>
          <a href="{{ route('home') }}" class="btn btn-primary"><i class="fas fa-home mr-1"></i>Dashboard</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

