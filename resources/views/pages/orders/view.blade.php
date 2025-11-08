@extends('layouts.app')

@section('title', 'Order Detail')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
<div class="main-content">
    <section class="section">
    <div class="section-header">
        <h1>Invoice</h1>
        <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
        <div class="breadcrumb-item">Invoice</div>
        </div>
    </div>

    <div class="section-body">
        <div class="invoice">
        <div class="invoice-print">
            <div class="row">
            <div class="col-lg-12">
                <div class="invoice-title">
                <h2>Invoice </h2>
                <div class="invoice-number">{{ $order->id }}</div>
                </div>
                <hr>
                {{-- <div class="row"> --}}
                {{-- <div class="col-md-6">
                    <address>
                    <strong>Billed To:</strong><br>
                        CASHIER POS<br>
                        Jln. Semarang Timur raya no. 18<br>
                        GayamSari<br>
                        Jawa Tengah, Indonesia
                    </address>
                </div>
                <div class="col-md-6 text-md-right">
                    <address>
                    <strong>Shipped To:</strong><br>
                    Ahmad<br>
                    Jalan Jenderal Sudirman<br>
                    Cimanggu<br>
                    Jawa Tengah, Indonesia

                    </address>
                </div> --}}
                {{-- </div> --}}
                <div class="row">
                <div class="col-md-4">
                    <address>
                    <strong>Payment Method:</strong><br>
                    {{ $order->payment_method }}<br>
                    </address>
                </div>
                <div class="col-md-4 text-md-center">
                    <address>
                    <strong>Cashier:</strong><br>
                    {{ $order->user->name }}<br>
                    </address>
                </div>
                <div class="col-md-4 text-md-right">
                    <address>
                    <strong>Order Date:</strong><br>
                    {{ $order->transaction_time }}<br><br>
                    </address>
                </div>
                </div>
            </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="section-title">Order Summary</div>
                    <p class="section-lead">Semua data pesanan tidak bisa di hapus!</p>
                    <div class="table-responsive">
                    <table class="table table-striped table-hover table-md">
                        <tr>
                        {{-- <th data-width="40">#</th> --}}
                        <th>Product Name</th>
                        <th class="text-center">Price</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-right">Totals</th>
                        </tr>
                        @foreach ($orderItems as $item)
                        <tr>
                            {{-- <td>{{ $i=1; $i++ }}</td> --}}
                            <td>{{ $item->product->name }}</td>
                            </td>
                            <td class="text-center">
                                {{ number_format($item->product->price, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                {{ $item->quantity }}
                            </td>
                            <td class="text-right">
                                {{ number_format($item->total_price, 0, ',', '.') }}

                            </td>
                        </tr>
                    @endforeach
                    </table>


                    <div class="invoice-detail-item d-flex justify-content-between">
                        <div class="invoice-detail-name">Subtotal</div>
                        <div class="invoice-detail-value">{{ number_format($order->sub_total, 0, ',', '.') }}</div>
                    </div>
                    <div class="invoice-detail-item d-flex justify-content-between">
                        <div class="invoice-detail-name">Discount</div>
                        <div class="invoice-detail-value" style="color: #32de84;">-{{ number_format($order->discount_amount, 0, ',', '.') }}</div>
                    </div>
                    <div class="invoice-detail-item d-flex justify-content-between">
                        <div class="invoice-detail-name">Tax</div>
                        <div class="invoice-detail-value" style="color: #fd5c63;">+{{ number_format($order->tax, 0, ',', '.') }}</div>
                    </div>
                    <div class="invoice-detail-item d-flex justify-content-between">
                        <div class="invoice-detail-name">Service</div>
                        <div class="invoice-detail-value" style="color: #fd5c63;">+{{ number_format($order->service_charge, 0, ',', '.') }}</div>
                    </div>
                    <div class="divider"></div>

                    <div class="invoice-detail-item d-flex justify-content-between">
                        <div class="invoice-detail-name">Total</div>
                        <div class="invoice-detail-value" style="color: #6777ef;">{{ number_format($order->total_price, 0, ',', '.') }}</div>
                    </div>
                </div>

                {{-- <div class="row mt-4">
                    <div class="col-lg-8">
                        <div class="section-title">Payment Method</div>
                        <p class="section-lead">The payment method that we provide is to make it easier for you to pay invoices.</p>
                        <div class="images">
                        <img src="{{ asset('/img/qris_logo.png') }}" width="150" alt="qris">
                        </div>
                    </div>
                    <div class="col-lg-4 text-right">
                        <div class="invoice-detail-item">
                        <div class="invoice-detail-name">Total Item</div>
                        <div class="invoice-detail-value">{{ $order->total_item }}</div>
                        </div>
                        <hr class="mt-2 mb-2">
                        <div class="invoice-detail-item">
                        <div class="invoice-detail-name">Total</div>
                        <div class="invoice-detail-value invoice-detail-value-lg">{{ number_format($item->total_price, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div> --}}
            </div>
            </div>
        </div>
        <hr>
        <div class="text-md-right">
            <div class="float-lg-left mb-lg-0 mb-3">
            <button class="btn btn-primary btn-icon icon-left"><i class="fas fa-credit-card"></i> Process Payment</button>
            <button class="btn btn-danger btn-icon icon-left"><i class="fas fa-times"></i> Cancel</button>
            </div>
            <button class="btn btn-warning btn-icon icon-left"><i class="fas fa-print"></i> Print</button>
        </div>
        </div>
    </div>
    </section>
</div>
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/features-posts.js') }}"></script>
@endpush
