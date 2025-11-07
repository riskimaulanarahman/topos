@extends('layouts.app')

@section('title', 'Tambah Produk')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Tambah Produk</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('product.index') }}">Produk</a></div>
                    <div class="breadcrumb-item active">Tambah</div>
                </div>
            </div>

            <div class="section-body">
                <form action="{{ route('product.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @include('pages.products.partials.wizard')
                </form>
            </div>
        </section>
    </div>
@endsection
