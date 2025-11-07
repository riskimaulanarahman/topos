@extends('layouts.app')

@section('title', 'Edit Rekening Pembayaran')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1 class="h4 mb-0">Edit Rekening Pembayaran</h1>
                <div class="section-header-breadcrumb">
                    <a href="{{ route('admin.billing.accounts.index') }}" class="btn btn-light">Kembali</a>
                </div>
            </div>

            <div class="section-body">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="{{ route('admin.billing.accounts.update', $account) }}" method="POST">
                            @method('PUT')
                            @include('pages.billing.admin.accounts.partials.form', ['account' => $account])

                            <div class="text-right">
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

