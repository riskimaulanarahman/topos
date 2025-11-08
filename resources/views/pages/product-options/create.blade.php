@extends('layouts.app')

@section('title', 'Buat Product Option')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Buat Product Option</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('product-options.index', ['type' => $type]) }}">Product Options</a></div>
                    <div class="breadcrumb-item active">Buat</div>
                </div>
            </div>

            <div class="section-body">
                @include('layouts.alert')

                <div class="mb-4">
                    @php
                        $tabs = [
                            'variant' => 'Variant',
                            'addon' => 'Addon',
                            'preference' => 'Preference',
                        ];
                    @endphp
                    <ul class="nav nav-pills">
                        @foreach($tabs as $tabType => $label)
                            <li class="nav-item">
                                <a class="nav-link {{ $type === $tabType ? 'active' : '' }}" href="{{ route('product-options.create', ['type' => $tabType]) }}">
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <form action="{{ route('product-options.store') }}" method="POST">
                    @csrf
                    @include('pages.product-options.partials.form', ['products' => $products])
                </form>
            </div>
        </section>
    </div>
@endsection
