@extends('layouts.app')

@section('title', 'Edit Uang Masuk')

@section('main')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit Uang Masuk</h1>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="card">
                        <form action="{{ route('income.update', $income->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Tanggal</label>
                                    <input type="date" name="date" value="{{ $income->date }}" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <input type="text" name="desc" value="{{ $income->desc }}" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Qty</label>
                                    <input type="number" name="qty" value="{{ $income->qty }}" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Harga Per Unit</label>
                                    <input type="number" name="price_per_unit" value="{{ $income->price_per_unit }}" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Tipe Pembayaran</label>
                                    <select name="payment_type" class="form-control">
                                        <option value="cash" {{ $income->payment_type == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="transfer" {{ $income->payment_type == 'transfer' ? 'selected' : '' }}>Transfer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
