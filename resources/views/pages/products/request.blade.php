@extends('layouts.app')

@section('title', 'Ajukan Produk Baru')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Ajukan Produk Baru</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('product.index') }}">Produk</a></div>
                    <div class="breadcrumb-item active">Ajukan</div>
                </div>
            </div>

            <div class="section-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    Anda tidak memiliki izin untuk membuat produk secara langsung. Lengkapi formulir berikut, dan owner outlet akan menerima email permintaan Anda.
                </div>

                @if(isset($activeOutlet))
                    <div class="alert alert-light">
                        <strong>Outlet aktif:</strong> {{ $activeOutlet->name }}
                    </div>
                @endif

                <form action="{{ route('product.request') }}" method="POST">
                    @csrf
                    <div class="card">
                        <div class="card-body">
                            <div class="form-group">
                                <label>Nama Produk</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if(($categories ?? collect())->isNotEmpty())
                                <div class="form-group">
                                    <label>Kategori</label>
                                    <select name="category_id" class="form-control @error('category_id') is-invalid @enderror">
                                        <option value="">Pilih kategori</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ (string)old('category_id') === (string)$category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    Owner belum membagikan akses kategori kepada Anda. Sertakan detail kategori pada catatan di bawah ini.
                                </div>
                            @endif

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Harga Jual (opsional)</label>
                                    <input type="number" step="0.01" min="0" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}">
                                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Catatan untuk Owner (opsional)</label>
                                <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" placeholder="Contoh: alasan penambahan produk, rincian bahan, dsb.">{{ old('notes') }}</textarea>
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
