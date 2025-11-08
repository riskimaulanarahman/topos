@extends('layouts.app')

@section('title', 'Ajukan Perubahan Produk')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Ajukan Perubahan Produk</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('product.index') }}">Produk</a></div>
                    <div class="breadcrumb-item active">Permintaan Perubahan</div>
                </div>
            </div>

            <div class="section-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    Anda tidak memiliki izin untuk langsung mengubah produk. Lengkapi formulir berikut untuk mengirim permintaan kepada owner outlet.
                </div>

                <form action="{{ route('product.request.update', $product) }}" method="POST">
                    @csrf
                    <div class="card">
                        <div class="card-body">
                            <div class="form-group">
                                <label>Nama Produk Saat Ini</label>
                                <p class="form-control-plaintext">{{ $product->name }}</p>
                            </div>
                            <div class="form-group">
                                <label>Nama Produk Baru (opsional)</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Kosongkan jika tidak diubah">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Harga Saat Ini</label>
                                    <p class="form-control-plaintext">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Harga Baru (opsional)</label>
                                    <input type="number" step="0.01" min="0" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" placeholder="Kosongkan jika tidak diubah">
                                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            @if($categories->isNotEmpty())
                                <div class="form-group">
                                    <label>Kategori Baru (opsional)</label>
                                    <select name="category_id" class="form-control @error('category_id') is-invalid @enderror">
                                        <option value="">Pertahankan kategori saat ini</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ (string)old('category_id') === (string)$category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endif

                            <div class="form-group">
                                <label>Catatan Untuk Owner (opsional)</label>
                                <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" placeholder="Tambahkan penjelasan perubahan, jika diperlukan.">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <a href="{{ route('product.index') }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-primary">Kirim Permintaan</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
