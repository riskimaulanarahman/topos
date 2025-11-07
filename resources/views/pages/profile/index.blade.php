@extends('layouts.app')

@section('title', 'Profil')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Profil Toko</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item active">Profil</div>
                </div>
            </div>

            <div class="section-body">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h4>Informasi Profil</h4>
                            </div>
                            <div class="card-body">
                                @if (session('status'))
                                    <div class="alert alert-success">
                                        {{ session('status') }}
                                    </div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-group">
                                        <label for="store_name">Nama Toko</label>
                                        <input type="text" id="store_name" name="store_name"
                                               class="form-control @error('store_name') is-invalid @enderror"
                                               value="{{ old('store_name', $user->store_name) }}" required>
                                        @error('store_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="name">Nama Pemilik</label>
                                        <input type="text" id="name" name="name"
                                               class="form-control @error('name') is-invalid @enderror"
                                               value="{{ old('name', $user->name) }}">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" id="email" class="form-control" value="{{ $user->email }}" disabled>
                                        <small class="form-text text-muted">Email tidak dapat diubah dari halaman ini.</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="phone">Nomor Telepon</label>
                                        <input type="text" id="phone" name="phone"
                                               class="form-control @error('phone') is-invalid @enderror"
                                               value="{{ old('phone', $user->phone) }}">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="store_description">Deskripsi Toko</label>
                                        <textarea id="store_description" name="store_description" rows="4"
                                                  class="form-control @error('store_description') is-invalid @enderror"
                                                  placeholder="Ceritakan tentang toko Anda...">{{ old('store_description', $user->store_description) }}</textarea>
                                        @error('store_description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="operating_hours">Jam Operasional</label>
                                        <textarea id="operating_hours" name="operating_hours" rows="3"
                                                  class="form-control @error('operating_hours.*') is-invalid @enderror"
                                                  placeholder="Contoh: Senin - Jumat, 08:00 - 22:00&#10;Sabtu, 09:00 - 21:00">{{ old('operating_hours', implode("\n", $user->operating_hours ?? [])) }}</textarea>
                                        <small class="form-text text-muted">Pisahkan setiap jadwal pada baris baru.</small>
                                        @error('operating_hours.*')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="store_addresses">Alamat / Lokasi</label>
                                        <textarea id="store_addresses" name="store_addresses" rows="3"
                                                  class="form-control @error('store_addresses.*') is-invalid @enderror"
                                                  placeholder="Contoh: Jl. Mawar No.1, Jakarta&#10;Jl. Melati No.2, Bandung">{{ old('store_addresses', implode("\n", $user->store_addresses ?? [])) }}</textarea>
                                        <small class="form-text text-muted">Pisahkan setiap alamat pada baris baru.</small>
                                        @error('store_addresses.*')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="map_links">Link Google Maps</label>
                                        <textarea id="map_links" name="map_links" rows="3"
                                                  class="form-control @error('map_links.*') is-invalid @enderror"
                                                  placeholder="https://maps.app.goo.gl/...">{{ old('map_links', implode("\n", $user->map_links ?? [])) }}</textarea>
                                        <small class="form-text text-muted">Pisahkan setiap link pada baris baru.</small>
                                        @error('map_links.*')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="store_logo">Logo Toko</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input @error('store_logo') is-invalid @enderror" id="store_logo" name="store_logo" accept="image/*">
                                            <label class="custom-file-label" for="store_logo">Pilih file</label>
                                            @error('store_logo')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        @if ($user->store_logo_url)
                                            <div class="mt-3">
                                                <p class="text-muted mb-2">Logo saat ini:</p>
                                                <img src="{{ $user->store_logo_url }}" alt="Store Logo" class="img-thumbnail" style="max-height: 150px;">
                                            </div>
                                        @endif
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>
    <script src="{{ asset('js/page/forms-advanced-forms.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('store_logo');
            if (!input) return;

            input.addEventListener('change', function () {
                const label = this.nextElementSibling;
                if (!label) return;

                if (this.files && this.files.length > 0) {
                    label.textContent = this.files[0].name;
                } else {
                    label.textContent = 'Pilih file';
                }
            });
        });
    </script>
@endpush
