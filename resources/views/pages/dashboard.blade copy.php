@extends('layouts.app')

@section('title', 'General Dashboard')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/jqvmap/dist/jqvmap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('library/summernote/dist/summernote-bs4.min.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Dashboard - CASHIER POS</h1>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <a href="{{ route('user.index') }}">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-primary">
                                <i class="far fa-user"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Users</h4>
                                </div>
                                <div class="card-body">
                                    {{ $users }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <a href="{{ route('product.index') }}">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-danger">
                                <i class="fas fa-bread-slice" style="color: #ffffff;"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Product</h4>
                                </div>
                                <div class="card-body">
                                    {{ $products }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <a href="{{ route('category.index') }}">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-success">
                                <i class="far fa-folder-open" style="color: #ffffff;"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Category</h4>
                                </div>
                                <div class="card-body">
                                    {{ $categories }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <a href="{{ route('discount.index') }}">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-warning">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Discounts</h4>
                                </div>
                                <div class="card-body">
                                    {{ $discounts }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <!-- Additional Charges Card -->
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <a href="{{ route('additional_charge.index') }}">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-info">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Additional Charges</h4>
                                </div>
                                <div class="card-body">
                                    {{ $additional_charges }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <a href="{{ route('order.index') }}">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-primary">
                                <i class="far fa-newspaper"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Orders</h4>
                                </div>
                                <div class="card-body">
                                    {{ $ordersLength }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <a href="{{ route('report.index') }}">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-danger">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Report</h4>
                                    <div class="card-body">
                                        3
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <div>
                <div class="col-12">
                    <div class="card card-statistic-1">
                        <div class="card-wrap">
                            <div class="d-flex justify-content-between align-items-center m-4">
                                <h4 style="color: #3949AB; font-weight: 600">Total Sales Today</h4>
                                <h4 style="color: #3949AB; font-weight: bold">
                                    {{ number_format($totalPriceToday, 0, ',', '.') }}
                                </h4>
                            </div>
                            <div class="clearfix mb-3"></div>
                            <table class="table-striped table">
                                <tr>
                                    <th>Transaction Time</th>
                                    <th>Sub Total</th>
                                    <th>Discount</th>
                                    <th>Tax</th>
                                    <th>Service</th>
                                    <th>Total Price</th>
                                    <th>Total Item</th>
                                    <th>Kasir</th>
                                </tr>
                                @foreach ($orders as $order)
                                    <tr>
                                        <td><a
                                                href="{{ route('order.show', $order->id) }}">{{ $order->transaction_time }}</a>
                                        </td>
                                        <td>
                                            {{ number_format($order->sub_total, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            {{ number_format($order->discount_amount, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            {{ number_format($order->tax, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            {{ number_format($order->service_charge, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            {{ number_format($order->total_price, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            {{ $order->total_item }}
                                        </td>
                                        <td>
                                            {{ $order->user->name }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                        <div class="float-right">
                            {{ $orders->withQueryString()->links() }}
                        </div>

                    </div>
                </div>
            </div>
            <div>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Grafik Sales</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('dashboard_grafik.filter') }}" method="GET">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Pilih Bulan</label>
                                                <select name="month" class="form-control">
                                                    @foreach (range(1, 12) as $m)
                                                        <option value="{{ $m }}" {{ $m == request()->query('month', date('m')) ? 'selected' : '' }}>
                                                            {{ date('F', mktime(0, 0, 0, $m, 10)) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('month')
                                                <div class="alert alert-danger">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Pilih Tahun</label>
                                                <select name="year" class="form-control">
                                                    @foreach (range(date('Y'), 2000) as $y)
                                                        <option value="{{ $y }}" {{ $y == request()->query('year', date('Y')) ? 'selected' : '' }}>
                                                            {{ $y }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('year')
                                                <div class="alert alert-danger">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                                                    Filter
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <div class="card mt-4">
                                    <div class="card-body">
                                        <canvas id="grafikSalesChart"></canvas>
                                    </div>
                                </div>


                        </div>
                    </div>
                </div>

            </div>
    </div>


    </section>
    </div>
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="{{ asset('library/simpleweather/jquery.simpleWeather.min.js') }}"></script>
    <script src="{{ asset('library/chart.js/dist/Chart.min.js') }}"></script>
    <script src="{{ asset('library/jqvmap/dist/jquery.vmap.min.js') }}"></script>
    <script src="{{ asset('library/jqvmap/dist/maps/jquery.vmap.world.js') }}"></script>
    <script src="{{ asset('library/summernote/dist/summernote-bs4.min.js') }}"></script>
    <script src="{{ asset('library/chocolat/dist/js/jquery.chocolat.min.js') }}"></script>

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/index-0.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dailyData = @json(array_values($data)); // Data from controller
            const month = @json($month); // Current selected month
            const year = @json($year); // Current selected year

            const days = Array.from({ length: {{ count($data) }} }, (_, i) => i + 1);

            const ctx = document.getElementById('grafikSalesChart').getContext('2d');

            function getMonthName(monthNumber) {
                const monthNames = [
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];
                return monthNames[monthNumber - 1];
            }
            const monthName = getMonthName(month);
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: days,
                    datasets: [{
                        label: 'Total Price',
                        data: dailyData,
                        backgroundColor: 'rgba(57, 73, 171, 0.2)',
                        borderColor: '#3949AB',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                title: function (context) {
                                    const dayIndex = context[0].dataIndex;
                                    const day = days[dayIndex];
                                    return `Date: ${day} ${month} ${year}`;
                                },
                                label: function (context) {
                                    const value = context.raw;
                                    return `Total Price: Rp ${value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")}`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
