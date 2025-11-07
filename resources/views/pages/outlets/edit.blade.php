@extends('layouts.app')

@section('title', 'Edit Outlet')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Edit Outlet</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item"><a href="{{ route('outlets.index') }}">Outlet</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('outlets.show', $outlet) }}">{{ $outlet->name }}</a></div>
                    <div class="breadcrumb-item active">Edit</div>
                </div>
            </div>

            <div class="section-body">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('outlets.update', $outlet) }}">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label for="name">Nama Outlet</label>
                                <input
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    id="name"
                                    name="name"
                                    value="{{ old('name', $outlet->name) }}"
                                    required
                                >
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="code">Kode Outlet (opsional)</label>
                                <input
                                    type="text"
                                    class="form-control @error('code') is-invalid @enderror"
                                    id="code"
                                    name="code"
                                    value="{{ old('code', $outlet->code) }}"
                                    placeholder="Misal: OUT-01"
                                >
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="address">Alamat</label>
                                <textarea
                                    class="form-control @error('address') is-invalid @enderror"
                                    id="address"
                                    name="address"
                                    rows="3"
                                >{{ old('address', $outlet->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="notes">Catatan</label>
                                <textarea
                                    class="form-control @error('notes') is-invalid @enderror"
                                    id="notes"
                                    name="notes"
                                    rows="3"
                                >{{ old('notes', $outlet->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group d-flex justify-content-between">
                                <a href="{{ route('outlets.show', $outlet) }}" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

