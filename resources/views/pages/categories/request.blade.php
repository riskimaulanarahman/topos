@extends('layouts.app')

@section('title', 'Ajukan Kategori Baru')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Ajukan Kategori</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('category.index') }}">Kategori</a></div>
                    <div class="breadcrumb-item active">Ajukan</div>
                </div>
            </div>

            <div class="section-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    Anda tidak memiliki izin untuk membuat kategori secara langsung. Kirim permintaan berikut agar owner outlet dapat mempertimbangkannya.
                </div>

                @if(isset($activeOutlet))
                    <div class="alert alert-light">
                        <strong>Outlet aktif:</strong> {{ $activeOutlet->name }}
                    </div>
                @endif

                <form action="{{ route('category.request') }}" method="POST">
                    @csrf
                    <div class="card">
                        <div class="card-body">
                            <div class="form-group">
                                <label>Nama Kategori</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group">
                                <label>Catatan untuk Owner (opsional)</label>
                                <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" placeholder="Contoh: alasan membutuhkan kategori ini.">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <a href="{{ route('category.index') }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-primary">Kirim Permintaan</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
