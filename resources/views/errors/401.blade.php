@extends('layouts.error')

@section('title', '401 Unauthorized')

@section('main')
<div class="row">
  <div class="col-12 col-md-10 offset-md-1 col-lg-8 offset-lg-2">
    <div class="card card-primary">
      <div class="card-body text-center py-5">
        <div class="display-1 font-weight-bold mb-3">401</div>
        <h4 class="mb-3">Tidak Diizinkan</h4>
        <p class="text-muted mb-4">Anda belum login atau sesi Anda telah berakhir. Silakan login untuk melanjutkan.</p>
        <div>
          <a href="{{ auth()->check() ? route('home') : (url()->previous() ?? url('/')) }}" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left mr-1"></i>Kembali</a>
          <a href="{{ url('/') }}" class="btn btn-primary"><i class="fas fa-sign-in-alt mr-1"></i>Masuk</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

