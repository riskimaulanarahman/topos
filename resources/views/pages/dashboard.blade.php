@extends('layouts.app')

@section('title', 'General Dashboard')

@push('style')
    <!-- CSS Libraries (tetap, tanpa CSS custom tambahan) -->
    <link rel="stylesheet" href="{{ asset('library/jqvmap/dist/jqvmap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('library/summernote/dist/summernote-bs4.min.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1 class="h4 h3-md mb-0">Dashboard - TOGA POS ({{ Auth::user()->store_name }})</h1>
            </div>

            <div class="section-body">
                @php
                    $dashboardRole = $currentOutletRole ?? null;
                    $dashboardIsPartner = $dashboardRole?->role === 'partner';
                    $dashboardAssignedCategories = isset($assignedCategoryNames) ? collect($assignedCategoryNames) : collect();
                    $dashboardPermissions = $outletPermissions ?? [
                        'can_manage_stock' => true,
                        'can_manage_expense' => true,
                        'can_manage_sales' => true,
                    ];
                @endphp

                @if ($dashboardIsPartner)
                    <div class="alert alert-info shadow-sm">
                        <h4 class="mb-1">Mode Mitra Outlet</h4>
                        <p class="mb-1">
                            Anda sedang mengakses outlet <strong>{{ $activeOutlet->name ?? '-' }}</strong> sebagai mitra.
                        </p>
                        @if ($dashboardAssignedCategories->isNotEmpty() && $dashboardAssignedCategories->first() !== 'Semua Kategori')
                            <p class="mb-0 text-muted small bg-light p-2">
                                Kategori yang dapat diakses:
                                <span class="text-dark">{{ $dashboardAssignedCategories->join(', ') }}</span>
                            </p>
                        @elseif ($dashboardAssignedCategories->first() === 'Semua Kategori')
                            <p class="mb-0 text-muted small">Anda memiliki akses ke seluruh kategori produk.</p>
                        @else
                            <p class="mb-0 text-warning">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                Belum ada kategori yang dibagikan kepada Anda. Silakan ajukan permintaan akses pada owner outlet.
                            </p>
                        @endif
                    </div>

                    <div class="row mt-4">
                        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                            <div class="card card-statistic-1 shadow-sm h-100">
                                <div class="card-header"><h4>Omzet Bulan Ini</h4></div>
                                <div class="card-body h4 mb-0 text-primary">Rp {{ number_format($partnerSummary['monthly_omzet'] ?? $partnerMonthlyOmzet ?? 0, 0, ',', '.') }}</div>
                                <div class="card-footer text-muted small">Periode {{ sprintf('%02d', $month ?? now()->format('m')) }}/{{ $year ?? now()->format('Y') }}</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                            <div class="card card-statistic-1 shadow-sm h-100">
                                <div class="card-header"><h4>Order Selesai</h4></div>
                                <div class="card-body h4 mb-0 text-success">{{ number_format($partnerSummary['monthly_orders'] ?? $monthlyCompletedOrders ?? 0) }}</div>
                                <div class="card-footer text-muted small">Selama bulan berjalan</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                            <div class="card card-statistic-1 shadow-sm h-100">
                                <div class="card-header"><h4>Rata-rata Order</h4></div>
                                <div class="card-body h4 mb-0 text-warning">Rp {{ number_format($partnerSummary['monthly_aov'] ?? $monthlyAov ?? 0, 0, ',', '.') }}</div>
                                <div class="card-footer text-muted small">Nilai transaksi rata-rata</div>
                            </div>
                        </div>
                    </div>

                    @if (!empty($partnerChart['daily'] ?? null))
                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                <h4 class="mb-0">Tren Omzet Bulan Ini</h4>
                            </div>
                            <div class="card-body">
                                <canvas id="partnerDailyRevenueChart" height="160"></canvas>
                            </div>
                        </div>
                    @endif

                    @if (!empty($partnerChart['categoryTotals']['data'] ?? null))
                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                <h4 class="mb-0">Omzet per Kategori (Bulan Ini)</h4>
                            </div>
                            <div class="card-body">
                                <canvas id="partnerCategoryBreakdownChart" height="160"></canvas>
                            </div>
                        </div>
                    @endif

                    <div class="alert alert-light border mt-3">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle text-primary mt-1 mr-2"></i>
                            <div class="small text-muted">
                                Data penjualan dan laporan yang Anda lihat sudah otomatis difilter berdasarkan kategori yang diizinkan.
                                Jika membutuhkan akses tambahan, gunakan tombol <strong>Permintaan Akses Kategori</strong> pada halaman Mitra.
                            </div>
                        </div>
                    </div>
                @else
                {{-- ===== RINGKASAN PENJUALAN ===== --}}
                <div class="mb-4">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3">
                        <h4 class="text-primary mb-2 mb-sm-0">Ringkasan Penjualan Bulan Ini</h4>
                        <span class="text-muted small">Periode: {{ sprintf('%02d', $month ?? now()->format('m')) }}/{{ $year ?? now()->format('Y') }}</span>
                    </div>
                    <div class="row">
                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
                            <div class="card shadow-sm h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="mr-3 d-flex align-items-center justify-content-center bg-success text-white rounded px-3 py-2">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Pendapatan Selesai</div>
                                        <div class="h4 mb-0">{{ number_format($monthlyCompletedRevenue ?? 0) }}</div>
                                        <div class="text-muted small">Total selama bulan berjalan</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
                            <div class="card shadow-sm h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="mr-3 d-flex align-items-center justify-content-center bg-primary text-white rounded px-3 py-2">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Transaksi Selesai</div>
                                        <div class="h4 mb-0">{{ number_format($monthlyCompletedOrders ?? 0) }}</div>
                                        <div class="text-muted small">Jumlah order bulan ini</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
                            <div class="card shadow-sm h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="mr-3 d-flex align-items-center justify-content-center bg-info text-white rounded px-3 py-2">
                                        <i class="fas fa-divide"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Rata-Rata Order</div>
                                        <div class="h4 mb-0">{{ number_format($monthlyAov ?? 0) }}</div>
                                        <div class="text-muted small">Nilai per transaksi</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
                            <div class="card shadow-sm h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="mr-3 d-flex align-items-center justify-content-center bg-warning text-white rounded px-3 py-2">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Metode Pembayaran</div>
                                        <div class="h4 mb-0">{{ number_format($monthlyPaymentMethods ?? 0) }}</div>
                                        <div class="text-muted small">Unik selama bulan ini</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== INSIGHT PENJUALAN ===== --}}
                <div class="row mb-4">
                    <div class="col-xl-8 mb-4 mb-xl-0">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0 text-primary">Grafik Penjualan (Bulan Ini)</h4>
                                </div>
                                <canvas id="grafikSalesChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h4 class="mb-1 text-primary">Penjualan Hari Ini</h4>
                                        <p class="text-muted small mb-0">
                                            @if(($sessionRange['hasSession'] ?? false) && !empty($sessionRange['sessionId']))
                                                Sesi kasir #{{ $sessionRange['sessionId'] }} ({{ ucfirst($sessionRange['status'] ?? '-') }})
                                            @else
                                                Periode hari ini
                                            @endif
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-muted small">Total</div>
                                        <div class="h4 mb-0">{{ number_format($totalPriceToday, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <p class="text-muted small">
                                    <span class="js-transaction-time-display" data-time="{{ $sessionRange['start_iso'] ?? null }}">{{ $sessionRange['start'] ?? '-' }}</span>
                                    -
                                    <span class="js-transaction-time-display" data-time="{{ $sessionRange['end_iso'] ?? null }}">{{ $sessionRange['end'] ?? '-' }}</span>
                                </p>
                                <h6 class="text-muted text-uppercase small mb-2">Metode Pembayaran</h6>
                                @if(isset($paymentBreakdownToday) && $paymentBreakdownToday->count())
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Metode</th>
                                                    <th class="text-right">Pendapatan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($paymentBreakdownToday as $pb)
                                                    <tr>
                                                        <td>{{ $pb->payment_method ?? 'Tidak diketahui' }}</td>
                                                        <td class="text-right">{{ number_format($pb->total_revenue, 0, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-muted small">Belum ada transaksi pada rentang ini.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== OPERASIONAL HARIAN ===== --}}
                <div class="row mb-4">
                    <div class="col-lg-7 mb-4 mb-lg-0">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                                    <div>
                                        <h4 class="mb-1 text-primary">Pesanan Terkini</h4>
                                        <p class="text-muted small mb-0">Daftar transaksi pada rentang kasir aktif.</p>
                                    </div>
                                    <div class="text-md-right mt-3 mt-md-0">
                                        <div class="text-muted small">Penjualan Hari Ini</div>
                                        <div class="h5 mb-0">{{ number_format($totalPriceToday, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-striped mb-3">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Waktu</th>
                                                <th>Total</th>
                                                <th>Item</th>
                                                <th>Metode</th>
                                                <th>Status</th>
                                                <th>Kasir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($orders as $order)
                                                <tr>
                                                    <td>
                                                        <a href="#" class="js-order-details"
                                                           data-url="{{ route('order.details_json', $order->id) }}"
                                                           data-transaction-time="{{ $order->transaction_time_iso }}">
                                                            <span class="js-transaction-time-display"
                                                                  data-time="{{ $order->transaction_time_iso ?: $order->transaction_time_display }}">
                                                                {{ $order->transaction_time_display ?? '-' }}
                                                            </span>
                                                        </a>
                                                    </td>
                                                    <td>{{ number_format($order->total_price, 0, ',', '.') }}</td>
                                                    <td>{{ $order->total_item }}</td>
                                                    <td>{{ $order->payment_method ?? '-' }}</td>
                                                    <td>{{ ucfirst($order->status ?? '-') }}</td>
                                                    <td>{{ $order->user->name }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-end">
                                    {{ $orders->withQueryString()->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                                    <div>
                                        <h4 class="mb-1 text-primary">Monitoring Stok Bahan Baku</h4>
                                        <p class="text-muted small mb-0">
                                            Menampilkan hingga 10 bahan dengan stok terendah. {{ $rawMaterialStockSummary['low'] ?? 0 }} dari {{ $rawMaterialStockSummary['total'] ?? 0 }} bahan berada di bawah stok minimum.
                                        </p>
                                    </div>
                                    <a href="{{ route('raw-materials.index') }}" class="btn btn-sm btn-outline-primary mt-2 mt-md-0">Kelola Bahan</a>
                                </div>
                                @if(isset($rawMaterialStocks) && $rawMaterialStocks->count())
                                    <div class="table-responsive">
                                        <table class="table table-striped mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Bahan</th>
                                                    <th>Stok</th>
                                                    <th>Minimum</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($rawMaterialStocks as $material)
                                                    @php
                                                        $minStock = $material->min_stock;
                                                        $hasThreshold = $minStock !== null && (float) $minStock > 0;
                                                        $isLowStock = $hasThreshold && (float) $material->stock_qty <= (float) $minStock;
                                                    @endphp
                                                    <tr class="{{ $isLowStock ? 'table-danger' : '' }}">
                                                        <td>{{ $material->name }}</td>
                                                        <td>{{ number_format($material->stock_qty, 1, ',', '.') }} {{ $material->unit }}</td>
                                                        <td>
                                                            @if($hasThreshold)
                                                                {{ number_format($material->min_stock, 1, ',', '.') }} {{ $material->unit }}
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($isLowStock)
                                                                <span class="badge badge-danger">Perlu Restok</span>
                                                            @elseif($hasThreshold)
                                                                <span class="badge badge-success">Aman</span>
                                                            @else
                                                                <span class="badge badge-secondary">Belum Diatur</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @if(($rawMaterialStockSummary['total'] ?? 0) > $rawMaterialStocks->count())
                                        <p class="text-muted small mt-2 mb-0">Data lengkap tersedia di menu Bahan Baku.</p>
                                    @endif
                                @else
                                    <div class="text-muted">Belum ada data bahan baku.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== PRODUK & SESI KASIR ===== --}}
                <div class="row">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0 text-primary">Produk Terjual Hari Ini</h4>
                                </div>
                                <p class="text-muted small">
                                    Rentang:
                                    <span class="js-transaction-time-display" data-time="{{ $sessionRange['start_iso'] ?? null }}">{{ $sessionRange['start'] ?? '-' }}</span>
                                    -
                                    <span class="js-transaction-time-display" data-time="{{ $sessionRange['end_iso'] ?? null }}">{{ $sessionRange['end'] ?? '-' }}</span>
                                </p>
                                @if(isset($productSalesToday) && $productSalesToday->count())
                                    <div class="table-responsive">
                                        <table class="table table-striped mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Produk</th>
                                                    <th>Jumlah</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($productSalesToday as $ps)
                                                    <tr>
                                                        <td>{{ $ps->product_name }}</td>
                                                        <td>{{ $ps->total_quantity }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end mt-3">
                                        {{ $productSalesToday->withQueryString()->links() }}
                                    </div>
                                @else
                                    <div class="text-muted">Belum ada produk terjual hari ini.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0 text-primary">Ringkasan Sesi Kasir</h4>
                                </div>
                                @if(isset($cashierSessionSummaries) && $cashierSessionSummaries->count())
                                    <div class="table-responsive">
                                        <table class="table table-striped mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Session</th>
                                                    <th>Dibuka</th>
                                                    <th>Ditutup</th>
                                                    <th>Status</th>
                                                    <th>Net Sales</th>
                                                    <th>Transaksi</th>
                                                    <th>Selisih Kas</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($cashierSessionSummaries as $session)
                                                    <tr>
                                                        <td>
                                                            #{{ $session['id'] }}<br>
                                                            <small class="text-muted">{{ $session['opened_by'] ?? '—' }}
                                                                @if(!empty($session['closed_by']))
                                                                    → {{ $session['closed_by'] }}
                                                                @endif
                                                            </small>
                                                        </td>
                                                        <td>
                                                            @if(!empty($session['opened_at_display']))
                                                                <span class="js-transaction-time-display"
                                                                      data-time="{{ $session['opened_at_iso'] }}">
                                                                    {{ $session['opened_at_display'] }}
                                                                </span>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(!empty($session['closed_at_display']))
                                                                <span class="js-transaction-time-display"
                                                                      data-time="{{ $session['closed_at_iso'] }}">
                                                                    {{ $session['closed_at_display'] }}
                                                                </span>
                                                            @else
                                                                <span class="badge badge-warning">Masih berjalan</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ ucfirst($session['status'] ?? '-') }}</td>
                                                        @php
                                                            $productSales = $session['product_sales'] ?? ['quantity' => 0, 'net' => 0];
                                                            $addonSales = $session['addon_sales'] ?? ['quantity' => 0, 'net' => 0];
                                                        @endphp
                                                        <td>
                                                            {{ number_format($session['totals']['net_sales'] ?? 0, 0, ',', '.') }}
                                                            <div class="text-muted small mt-1">
                                                                Produk: Rp{{ number_format((int) ($productSales['net'] ?? 0), 0, ',', '.') }}
                                                                ({{ $productSales['quantity'] ?? 0 }} qty)
                                                                <br>
                                                                Add-on: Rp{{ number_format((int) ($addonSales['net'] ?? 0), 0, ',', '.') }}
                                                                ({{ $addonSales['quantity'] ?? 0 }} qty)
                                                            </div>
                                                        </td>
                                                        <td>
                                                            {{ $session['transactions']['completed'] ?? 0 }} selesai
                                                            @if(($session['transactions']['refunded'] ?? 0) > 0)
                                                                <br><small class="text-muted">{{ $session['transactions']['refunded'] }} refund</small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            {{ number_format($session['cash_balance']['difference'] ?? 0, 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-muted">Belum ada sesi kasir yang terekam.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            </div>

        </section>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0">Order Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="orderDetailsContent">
                        <div class="mb-2"><strong>Transaction #:</strong> <span id="odTrx"></span></div>
                        <div class="mb-2 d-flex flex-wrap">
                            <div class="mr-4"><strong>Time:</strong> <span id="odTime"></span></div>
                            <div class="mr-4"><strong>Payment:</strong> <span id="odPayment"></span></div>
                            <div class="mr-4"><strong>Status:</strong> <span id="odStatus"></span></div>
                            <div class="mr-4"><strong>Cashier:</strong> <span id="odCashier"></span></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Price</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="odItems"></tbody>
                            </table>
                        </div>
                                <div class="d-flex justify-content-end">
                                    <div class="w-100 w-md-50">
                                        <hr class="my-2"/>
                                        <div class="d-flex justify-content-between font-weight-bold">
                                            <span>Total</span><span id="odTotal"></span>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted small">
                                            <span>Add-on</span><span id="odAddonSummary">Tidak ada add-on</span>
                                        </div>
                                    </div>
                                </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- JS Libraries -->
    <script src="{{ asset('library/simpleweather/jquery.simpleWeather.min.js') }}"></script>
    <script src="{{ asset('library/chart.js/dist/Chart.min.js') }}"></script>
    <script src="{{ asset('library/jqvmap/dist/jquery.vmap.min.js') }}"></script>
    <script src="{{ asset('library/jqvmap/dist/maps/jquery.vmap.world.js') }}"></script>
    <script src="{{ asset('library/summernote/dist/summernote-bs4.min.js') }}"></script>
    <script src="{{ asset('library/chocolat/dist/js/jquery.chocolat.min.js') }}"></script>

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/index-0.js') }}"></script>
    <script>
        window.partnerChartData = @json($partnerChart ?? null);
        const userLocale = navigator.language || navigator.userLanguage || 'en';
        if (typeof moment === 'function' && typeof moment.locale === 'function') {
            moment.locale(userLocale);
        }

        function formatIDR(n){ if(n==null) return '-'; return (n).toLocaleString('id-ID'); }
        function escapeHtml(str){
            if (str === null || str === undefined) return '';
            return String(str).replace(/[&<>"']/g, function (s) {
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[s]);
            });
        }
        function formatDateTime(value){
            if(!value) return '-';
            if(typeof moment !== 'function') return value;
            let parsed = moment.parseZone(value);
            if(!parsed.isValid()){ parsed = moment(value); }
            if(!parsed.isValid()) return value;
            return parsed.local().format('YYYY-MM-DD HH:mm:ss');
        }
        function renderOrderModal(data){
            document.getElementById('odTrx').textContent = data.transaction_number || data.id;
            const trxTime = data.transaction_time_iso || data.transaction_time || '';
            document.getElementById('odTime').textContent = formatDateTime(trxTime);
            document.getElementById('odPayment').textContent = data.payment_method || '-';
            document.getElementById('odStatus').textContent = (data.status||'-');
            document.getElementById('odCashier').textContent = data.cashier || '-';
            document.getElementById('odTotal').textContent = formatIDR(data.total_price||0);
            const addonSummaryEl = document.getElementById('odAddonSummary');
            const tbody = document.getElementById('odItems');
            tbody.innerHTML='';
            (data.items||[]).forEach(it=>{
                const tr=document.createElement('tr');
                const price = formatIDR(it.price??0);
                const total = formatIDR(it.total_price??0);
                tr.innerHTML = `<td>${escapeHtml(it.product_name||'-')}</td>
                                <td class="text-center">${price}</td>
                                <td class="text-center">${it.quantity||0}</td>
                                <td class="text-right">${total}</td>`;
                tbody.appendChild(tr);
                if (Array.isArray(it.addons) && it.addons.length) {
                    const addonLines = it.addons.map(addon => {
                        const name = escapeHtml(addon.name || '-');
                        const group = addon.group ? ` <span class="text-muted">(${escapeHtml(addon.group)})</span>` : '';
                        const qty = addon.quantity || 0;
                        const unit = formatIDR(addon.unit_price ?? 0);
                        const addTotal = formatIDR(addon.total_price ?? 0);
                        return `<li class="d-flex justify-content-between">
                                    <span>${name}${group}</span>
                                    <span>${qty}x · Rp ${unit} <span class="ml-2 font-weight-semibold">Rp ${addTotal}</span></span>
                                </li>`;
                    }).join('');
                    const addonRow = document.createElement('tr');
                    addonRow.classList.add('bg-light');
                    addonRow.innerHTML = `<td colspan="4">
                        <div class="small text-muted font-weight-semibold mb-1"><i class="fas fa-plus-circle mr-1"></i>Add-on</div>
                        <ul class="mb-0 pl-3 small">${addonLines}</ul>
                    </td>`;
                    tbody.appendChild(addonRow);
                }
            });
            if (addonSummaryEl) {
                const summary = data.add_on_summary || {};
                const qty = summary.quantity || 0;
                const total = summary.total_price || 0;
                addonSummaryEl.textContent = qty > 0
                    ? `Rp ${formatIDR(total)} • ${qty} qty`
                    : 'Tidak ada add-on';
            }
            $('#orderDetailsModal').modal('show');
        }
        document.querySelectorAll('.js-transaction-time-display').forEach(el=>{
            const raw = el.getAttribute('data-time') || el.textContent;
            el.textContent = formatDateTime(raw);
        });

        async function loadSalesSeries(params){
            const qs = new URLSearchParams(params).toString();
            const res = await fetch(`{{ route('dashboard.sales_series') }}?${qs}`, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
            if(!res.ok) throw new Error('Gagal memuat data grafik');
            return res.json();
        }

        function toDatasets(datasets, stacked){
            const palette = [
                'rgba(57,73,171,0.5)', 'rgba(255,99,132,0.5)', 'rgba(75,192,192,0.5)',
                'rgba(255,159,64,0.5)', 'rgba(153,102,255,0.5)'
            ];
            return datasets.map((ds,i)=>({
                label: ds.label,
                data: ds.data,
                backgroundColor: palette[i % palette.length],
                borderColor: palette[i % palette.length].replace('0.5','1'),
                borderWidth: 1,
                stack: stacked ? 'revenue' : undefined,
            }));
        }

        // async function renderSalesChart(params){
        //     const series = await loadSalesSeries(params);
        //     const stacked = !!params.segment_by;
        //     const ctx = document.getElementById('grafikSalesChart').getContext('2d');
        //     if(window.salesChart) window.salesChart.destroy();
        //     window.salesChart = new Chart(ctx, {
        //         type: 'bar',
        //         data: { labels: series.labels, datasets: toDatasets(series.datasets, stacked) },
        //         options: {
        //             responsive: true,
        //             maintainAspectRatio: true,
        //             scales: { xAxes: [{ stacked }], yAxes: [{ stacked, ticks: { beginAtZero: true } }] },
        //             tooltips: { callbacks: { label: (item)=>`Rp ${Number(item.yLabel||0).toLocaleString('id-ID')}` } }
        //         }
        //     });
        // }
        // async function renderSalesChart(params){
        //     const series = await loadSalesSeries(params);
        //     const stacked = !!params.segment_by;
        //     const ctx = document.getElementById('grafikSalesChart').getContext('2d');
        //     if(window.salesChart) window.salesChart.destroy();
        //     window.salesChart = new Chart(ctx, {
        //         type: 'bar',
        //         data: { labels: series.labels, datasets: toDatasets(series.datasets, stacked) },
        //         options: {
        //             responsive: true,
        //             maintainAspectRatio: true,
        //             scales: { 
        //                 xAxes: [{ stacked }], 
        //                 yAxes: [{ stacked, ticks: { beginAtZero: true } }] 
        //             },
        //             tooltips: {
        //                 mode: 'index',       // << tampilkan semua dataset di index yg sama
        //                 intersect: false,    // << tidak harus tepat di titik bar
        //                 callbacks: { 
        //                     label: (item)=>`Rp ${Number(item.yLabel||0).toLocaleString('id-ID')}` 
        //                 }
        //             }
        //         }
        //     });
        // }
        // async function renderSalesChart(params){
        //     const series = await loadSalesSeries(params);
        //     const stacked = !!params.segment_by;
        //     const ctx = document.getElementById('grafikSalesChart').getContext('2d');
        //     if(window.salesChart) window.salesChart.destroy();

        //     window.salesChart = new Chart(ctx, {
        //         type: 'bar',
        //         data: { 
        //             labels: series.labels, 
        //             datasets: toDatasets(series.datasets, stacked) 
        //         },
        //         options: {
        //             responsive: true,
        //             maintainAspectRatio: true,
        //             interaction: {
        //                 mode: 'index',      // tampilkan semua dataset di index yang sama
        //                 intersect: false
        //             },
        //             plugins: {
        //                 tooltip: {
        //                     callbacks: {
        //                         // Teks di tooltip
        //                         label: function(ctx){
        //                             // label dataset = Payment Method
        //                             let method = ctx.dataset.label || 'Metode';
        //                             let value = ctx.parsed.y || 0;
        //                             return `${method}: Rp ${Number(value).toLocaleString('id-ID')}`;
        //                         },
        //                         // Judul tooltip = label sumbu X (misal tanggal/hari)
        //                         title: function(ctx){
        //                             return ctx[0].label;
        //                         }
        //                     }
        //                 }
        //             },
        //             scales: { 
        //                 xAxes: [{ stacked }], 
        //                 yAxes: [{ stacked, ticks: { beginAtZero: true } }] 
        //             },
        //         }
        //     });
        // }

        async function renderSalesChart(params){
            const series = await loadSalesSeries(params);
            const stacked = !!params.segment_by;
            const canvasCtx = document.getElementById('grafikSalesChart').getContext('2d');

            if (window.salesChart) window.salesChart.destroy();

            window.salesChart = new Chart(canvasCtx, {
                type: 'bar',
                data: {
                    labels: series.labels,
                    datasets: toDatasets(series.datasets, stacked)
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        xAxes: [{ stacked: stacked }],
                        yAxes: [{ stacked: stacked, ticks: { beginAtZero: true } }]
                    },
                    tooltips: {
                        mode: 'index',     // tampilkan semua dataset pada index yang sama
                        intersect: false,  // tidak harus tepat di batangnya
                        callbacks: {
                            // Judul tooltip (opsional): label sumbu X, mis. tanggal/hari
                            title: function(tooltipItems, data){
                                return tooltipItems.length ? tooltipItems[0].label : '';
                            },
                            // Baris per dataset
                            label: function(tooltipItem, data){
                                const ds = data.datasets[tooltipItem.datasetIndex] || {};
                                const method = ds.label || 'Metode';
                                const val = Number(tooltipItem.yLabel || 0);
                                return `${method} : Rp ${val.toLocaleString('id-ID')}`;
                            },
                            // Footer (opsional): total semua payment method pada index tsb
                            footer: function(tooltipItems, data){
                                const idx = tooltipItems.length ? tooltipItems[0].index : -1;
                                if (idx < 0) return '';
                                const sum = data.datasets.reduce((acc, ds) => acc + (Number(ds.data[idx]) || 0), 0);
                                return `Total: Rp ${sum.toLocaleString('id-ID')}`;
                            }
                        }
                    }
                }
            });
        }


        document.addEventListener('DOMContentLoaded', async function () {
            // Hook order detail links
            document.querySelectorAll('.js-order-details').forEach(a=>{
                a.addEventListener('click', function(e){ e.preventDefault(); const url=this.getAttribute('data-url'); if(!url) return;
                    fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }}).then(r=>r.json()).then(renderOrderModal).catch(()=>alert('Gagal mengambil detail order'));
                });
            });

            // Render daily revenue (current month), grouped by payment method, completed only
            const now = new Date();
            const params = {
                period: 'harian',
                year: now.getFullYear(),
                month: now.getMonth() + 1,
                segment_by: 'payment_method',
                status: 'completed'
            };
            try { await renderSalesChart(params); } catch(e) { console.error(e); }

            if (window.partnerChartData && window.partnerChartData.daily) {
                const ctxPartner = document.getElementById('partnerDailyRevenueChart');
                if (ctxPartner && window.Chart) {
                    const data = window.partnerChartData.daily;
                    new Chart(ctxPartner.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Omzet (Rp)',
                                backgroundColor: 'rgba(103,119,239,0.12)',
                                borderColor: '#6777ef',
                                pointBackgroundColor: '#6777ef',
                                pointRadius: 3,
                                fill: true,
                                lineTension: 0.25,
                                data: data.values.map(v => Number(v) || 0),
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true,
                                        callback: function(value){ return 'Rp ' + Number(value).toLocaleString('id-ID'); }
                                    }
                                }]
                            },
                            tooltips: {
                                callbacks: {
                                    label: function(tooltipItem){
                                        const val = Number(tooltipItem.yLabel || 0);
                                        return tooltipItem.xLabel + ': Rp ' + val.toLocaleString('id-ID');
                                    }
                                }
                            }
                        }
                    });
                }
            }

            if (window.partnerChartData && window.partnerChartData.categoryTotals && window.partnerChartData.categoryTotals.data.length) {
                const ctxCategory = document.getElementById('partnerCategoryBreakdownChart');
                if (ctxCategory && window.Chart) {
                    const data = window.partnerChartData.categoryTotals;
                    new Chart(ctxCategory.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: data.data.map(item => item.label),
                            datasets: [{
                                label: 'Omzet (Rp)',
                                backgroundColor: '#47c363',
                                borderColor: '#47c363',
                                data: data.data.map(item => Number(item.value) || 0),
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true,
                                        callback: function(value){ return 'Rp ' + Number(value).toLocaleString('id-ID'); }
                                    }
                                }]
                            },
                            tooltips: {
                                callbacks: {
                                    label: function(tooltipItem){
                                        const val = Number(tooltipItem.yLabel || 0);
                                        return tooltipItem.xLabel + ': Rp ' + val.toLocaleString('id-ID');
                                    }
                                }
                            }
                        }
                    });
                }
            }
        });
    </script>
@endpush
