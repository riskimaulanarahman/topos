@extends('layouts.app')

@section('title', 'Edit Product Option')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Edit Product Option</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('product-options.index', ['type' => $type]) }}">Product Options</a></div>
                    <div class="breadcrumb-item active">Edit</div>
                </div>
            </div>

            <div class="section-body">
                @include('layouts.alert')

                <form action="{{ route('product-options.update', $optionGroup) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('pages.product-options.partials.form', ['optionGroup' => $optionGroup, 'products' => $products])
                </form>
            </div>
        </section>
    </div>
@endsection
