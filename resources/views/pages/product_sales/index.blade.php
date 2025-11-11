@extends('layouts.app')

@section('title', 'Product Sales Report')

@push('style')
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
    <link rel="stylesheet" href="{{ asset('library/datatables/media/css/jquery.dataTables.css') }}">
    <link rel="stylesheet" href="{{ asset('library/select2/dist/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('library/bootstrap-daterangepicker/daterangepicker.css') }}">
    
    <style>
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.8rem;
            }
            .card-body {
                padding: 0.5rem;
            }
            .form-group {
                margin-bottom: 0.5rem;
            }
            .btn {
                font-size: 0.875rem;
                padding: 0.5rem 1rem;
            }
            .col-md-3 {
                margin-bottom: 1rem;
            }
            #productSalesChart {
                height: 200px !important;
            }
        }
        
        @media (max-width: 576px) {
            .section-header h1 {
                font-size: 1.5rem;
            }
            .card-header h4 {
                font-size: 1rem;
            }
            .table-responsive {
                font-size: 0.7rem;
            }
            .btn-lg {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }
        }
        
        .table th {
            white-space: nowrap;
        }
        
        .form-row {
            margin-bottom: 1rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 200px;
            }
        }
    </style>
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Product Sales</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('report.product-sales') }}">Product Sales</a></div>
                    <div class="breadcrumb-item">Report</div>
                </div>
            </div>
            <div class="section-body">
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Product Sales Report</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('report.product-sales.filter') }}" method="GET">
                                    <input type="hidden" name="filtered" value="1">
                                    @if(isset($userId))
                                        <input type="hidden" name="user_id" value="{{ $userId }}">
                                    @endif
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Periode <span class="text-muted" title="Pilih periode terlebih dahulu, lalu filter lainnya akan muncul">?</span></label>
                                                <select name="period" class="form-control" id="periodSelect">
                                                    <option value="harian" {{ request('period')=='harian' ? 'selected' : '' }}>Harian</option>
                                                    <option value="mingguan" {{ request('period')=='mingguan' ? 'selected' : '' }}>Mingguan</option>
                                                    <option value="bulanan" {{ request('period')=='bulanan' ? 'selected' : '' }}>Bulanan</option>
                                                    <option value="tahunan" {{ request('period')=='tahunan' ? 'selected' : '' }}>Tahunan</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div id="dateRangeContainer" class="col-md-5">
                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Dari Tanggal</label>
                                                        <input type="text"
                                                               name="date_from"
                                                               id="dateFromInput"
                                                               value="{{ old('date_from') ?? ($date_from ?? request()->query('date_from')) }}"
                                                               class="form-control"
                                                               placeholder="YYYY-MM-DD"
                                                               autocomplete="off"
                                                               readonly>
                                                    </div>
                                                    @error('date_from')
                                                        <div class="alert alert-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Ke Tanggal</label>
                                                        <input type="text"
                                                               name="date_to"
                                                               id="dateToInput"
                                                               value="{{ old('date_to') ?? ($date_to ?? request()->query('date_to')) }}"
                                                               class="form-control"
                                                               placeholder="YYYY-MM-DD"
                                                               autocomplete="off"
                                                               readonly>
                                                    </div>
                                                    @error('date_to')
                                                        <div class="alert alert-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="yearCol" style="display:none;">
                                            <div class="form-group">
                                                <label>Tahun</label>
                                                @php($currentYear = (int) (old('year') ?? ($year ?? request('year') ?? now()->year)))
                                                <select name="year" id="yearSelect" class="form-control">
                                                    @for($y = $currentYear + 1; $y >= $currentYear - 5; $y--)
                                                        <option value="{{ $y }}" {{ $currentYear==$y ? 'selected' : '' }}>{{ $y }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="monthCol" style="display:none;">
                                            <div class="form-group">
                                                <label>Bulan</label>
                                                @php($currentMonth = (int) (old('month') ?? ($month ?? request('month') ?? now()->month)))
                                                @php($monthNames = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'])
                                                <select name="month" id="monthSelect" class="form-control">
                                                    @for($m=1;$m<=12;$m++)
                                                        <option value="{{ $m }}" {{ $currentMonth==$m ? 'selected' : '' }}>{{ $monthNames[$m] }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="weekCol" style="display:none;">
                                            <div class="form-group">
                                                <label>Opsi Mingguan</label>
                                                <select id="weekOptionSelect" class="form-control">
                                                    <option value="">Pilih...</option>
                                                    <optgroup label="Minggu di Bulan">
                                                        <option value="w1" {{ (request('week_in_month')=='w1')?'selected':'' }}>Minggu ke-1</option>
                                                        <option value="w2" {{ (request('week_in_month')=='w2')?'selected':'' }}>Minggu ke-2</option>
                                                        <option value="w3" {{ (request('week_in_month')=='w3')?'selected':'' }}>Minggu ke-3</option>
                                                        <option value="w4" {{ (request('week_in_month')=='w4')?'selected':'' }}>Minggu ke-4</option>
                                                        <option value="w5" {{ (request('week_in_month')=='w5')?'selected':'' }}>Minggu ke-5</option>
                                                    </optgroup>
                                                    <optgroup label="Hari Terakhir">
                                                        <option value="last_7" {{ (request('last_days')=='7')?'selected':'' }}>7 hari terakhir</option>
                                                        <option value="last_14" {{ (request('last_days')=='14')?'selected':'' }}>14 hari terakhir</option>
                                                        <option value="last_21" {{ (request('last_days')=='21')?'selected':'' }}>21 hari terakhir</option>
                                                        <option value="last_28" {{ (request('last_days')=='28')?'selected':'' }}>28 hari terakhir</option>
                                                    </optgroup>
                                                </select>
                                                <input type="hidden" name="week_in_month" id="weekInMonthInput" value="{{ request('week_in_month') }}">
                                                <input type="hidden" name="last_days" id="lastDaysInput" value="{{ request('last_days') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Kategori</label>
                                                @php($selCats = collect(($categoryId ?? (array) request('category_id', [])))->map(fn($v)=> (string)$v)->all())
                                                <select name="category_id[]" class="form-control select2" multiple data-placeholder="Pilih kategori">
                                                    @foreach(($categories ?? []) as $cat)
                                                        <option value="{{ $cat->id }}" {{ in_array((string)$cat->id, $selCats, true) ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Outlet</label>
                                                <select name="outlet_id" class="form-control">
                                                    <option value="">Semua</option>
                                                    @foreach(($outlets ?? []) as $outlet)
                                                        <option value="{{ $outlet->id }}" {{ ($outletId ?? request('outlet_id')) == $outlet->id ? 'selected' : '' }}>{{ $outlet->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Produk</label>
                                                <select name="product_id" class="form-control">
                                                    <option value="">Semua</option>
                                                    @foreach(($products ?? []) as $prod)
                                                        <option value="{{ $prod->id }}" {{ ($productId ?? request('product_id')) == $prod->id ? 'selected' : '' }}>{{ $prod->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group d-flex">
                                                <button type="submit" class="btn btn-primary btn-lg mr-2">Filter</button>
                                                <button type="button" id="btnResetPS" class="btn btn-light btn-lg">Reset</button>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($totalProductSold ?? '')
                                        <!-- Filter Chips -->
                                        <div class="mb-3">
                                            @php($chips = [])
                                            @if(request('period')) @php($chips[] = 'Periode: '.ucfirst(request('period'))) @endif
                                            @if(request('date_from')) @php($chips[] = 'Dari: '.request('date_from')) @endif
                                            @if(request('date_to')) @php($chips[] = 'Ke: '.request('date_to')) @endif
                                            @php($selCats = (array) request('category_id', ($categoryId ?? [])))
                                            @if(!empty($selCats))
                                                @php($names = ($categories ?? collect())->whereIn('id', array_map('intval', $selCats))->pluck('name')->all())
                                                @foreach($names as $nm)
                                                    @php($chips[] = 'Kategori: '.$nm)
                                                @endforeach
                                            @endif
                                            @if($outletId) @php($chips[] = 'Outlet: '.($outlets->find($outletId)?->name ?? $outletId)) @endif
                                            @if($productId) @php($chips[] = 'Produk: '.($products->find($productId)?->name ?? $productId)) @endif
                                            @if(count($chips))
                                                <div>
                                                    @foreach($chips as $chip)
                                                        <span class="badge badge-primary mr-2">{{ $chip }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Summary Cards -->
                                        <div class="row mb-4">
                                            <div class="col-md-3 col-sm-6 mb-3">
                                                <div class="card bg-primary text-white">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Total Revenue</h5>
                                                        <p class="card-text">Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-3">
                                                <div class="card bg-success text-white">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Total Profit</h5>
                                                        <p class="card-text">Rp {{ number_format($totalProfit ?? 0, 0, ',', '.') }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-3">
                                                <div class="card bg-info text-white">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Avg Profit Margin</h5>
                                                        <p class="card-text">{{ number_format($avgProfitMargin ?? 0, 2, ',', '.') }}%</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-3">
                                                <div class="card bg-warning text-white">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Total Products</h5>
                                                        <p class="card-text">{{ $totalProductSold->count() }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4 chart-container">
                                            <canvas id="productSalesChart"></canvas>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <button type="button" id="btnExportPS" class="btn btn-outline-primary mr-2">Export CSV</button>
                                            <button type="button" id="btnExportPDF" class="btn btn-primary">Export PDF</button>
                                        </div>

                                        <!-- Desktop Table View -->
                                        <div class="d-none d-md-block table-responsive table-responsive-sm">
                                            <table id="productSalesTable" class="table table-striped table-bordered text-center table-sm">
                                                <thead class="thead-dark">
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Product</th>
                                                        <th>Total Quantity</th>
                                                        <th>Total Price</th>
                                                        <th>Total Profit</th>
                                                        <th>Profit Margin</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($totalProductSold as $productSold)
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td>{{ $productSold->product_name }}</td>
                                                            <td>{{ $productSold->total_quantity }}</td>
                                                            <td>{{ number_format($productSold->total_price, 0, ',', '.') }}</td>
                                                            <td>{{ number_format($productSold->total_profit ?? 0, 0, ',', '.') }}</td>
                                                            <td>{{ number_format($productSold->profit_margin ?? 0, 2, ',', '.') }}%</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="2" class="text-right">Total</th>
                                                        <th id="ftQtyPS"></th>
                                                        <th id="ftRevPS"></th>
                                                        <th id="ftProfitPS"></th>
                                                        <th id="ftMarginPS"></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        
                                        <!-- Mobile Card View -->
                                        <div class="d-md-none">
                                            @foreach ($totalProductSold as $productSold)
                                                <div class="card mb-3 shadow-sm">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h6 class="card-title mb-0">{{ $productSold->product_name }}</h6>
                                                            <span class="badge badge-primary">{{ $loop->iteration }}</span>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <small class="text-muted">Quantity</small>
                                                                <div class="h5 mb-0">{{ $productSold->total_quantity }}</div>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted">Revenue</small>
                                                                <div class="h5 mb-0 text-success">Rp {{ number_format($productSold->total_price, 0, ',', '.') }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="row mt-2">
                                                            <div class="col-6">
                                                                <small class="text-muted">Profit</small>
                                                                <div class="h5 mb-0 text-info">Rp {{ number_format($productSold->total_profit ?? 0, 0, ',', '.') }}</div>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted">Margin</small>
                                                                <div class="h5 mb-0 text-warning">{{ number_format($productSold->profit_margin ?? 0, 2, ',', '.') }}%</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            
                                            <!-- Mobile Total Card -->
                                            <div class="card bg-dark text-white">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <small>Total Quantity</small>
                                                            <div class="h4 mb-0" id="ftQtyPSMobile">0</div>
                                                        </div>
                                                        <div class="col-6">
                                                            <small>Total Revenue</small>
                                                            <div class="h4 mb-0" id="ftRevPSMobile">Rp 0</div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <small>Total Profit</small>
                                                            <div class="h4 mb-0" id="ftProfitPSMobile">Rp 0</div>
                                                        </div>
                                                        <div class="col-6">
                                                            <small>Avg Margin</small>
                                                            <div class="h4 mb-0" id="ftMarginPSMobile">0%</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>
    <script src="{{ asset('library/datatables/media/js/jquery.dataTables.js') }}"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css" />
    <script src="{{ asset('library/select2/dist/js/select2.min.js') }}"></script>
    <script src="{{ asset('library/bootstrap-daterangepicker/moment.min.js') }}"></script>
    <script src="{{ asset('library/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
    <script>
        const userLocale = navigator.language || navigator.userLanguage || 'en';
        if (typeof moment === 'function' && typeof moment.locale === 'function') {
            moment.locale(userLocale);
        }

        function parseCurrency(str){ 
            if(!str) return 0; 
            return parseInt(String(str).replace(/[^0-9\-]/g,'')) || 0; 
        }
        
        function formatDateTime(value){
            if(!value) return '-';
            if(typeof moment !== 'function') return value;
            let parsed = moment.parseZone(value);
            if(!parsed.isValid()){ parsed = moment(value); }
            if(!parsed.isValid()) return value;
            return parsed.local().format('YYYY-MM-DD HH:mm:ss');
        }
        
        function formatPaymentMethod(value){
            if(!value) return '-';
            return String(value)
                .replace(/_/g,' ')
                .trim()
                .split(/\s+/)
                .map(part => part ? part.charAt(0).toUpperCase() + part.slice(1).toLowerCase() : '')
                .join(' ');
        }
        
        function syncDateRangePicker(){
            if(typeof $ === 'undefined' || typeof moment === 'undefined') return;
            const $start = $('#dateFromInput');
            const $end = $('#dateToInput');
            if(!$start.length || !$end.length) return;
            const drp = $start.data('daterangepicker');
            if(!drp) return;
            const format = drp.locale?.format || 'YYYY-MM-DD';
            const startVal = $start.val();
            const endVal = $end.val();
            if(startVal){
                const mStart = moment(startVal, format, true).isValid() ? moment(startVal, format) : moment(startVal);
                drp.setStartDate(mStart);
                $start.val(mStart.format(format));
            }
            if(endVal){
                const mEnd = moment(endVal, format, true).isValid() ? moment(endVal, format) : moment(endVal);
                drp.setEndDate(mEnd);
                $end.val(mEnd.format(format));
            }
        }
        
        function initDateRangePicker(){
            if(typeof $ === 'undefined' || typeof moment === 'undefined' || !$.fn.daterangepicker) return;
            const $start = $('#dateFromInput');
            const $end = $('#dateToInput');
            if(!$start.length || !$end.length) return;
            const format = 'YYYY-MM-DD';
            const startVal = $start.val() || moment().startOf('month').format(format);
            const endVal = $end.val() || moment().endOf('month').format(format);
            $start.daterangepicker({
                autoUpdateInput: false,
                showDropdowns: true,
                alwaysShowCalendars: true,
                startDate: moment(startVal),
                endDate: moment(endVal),
                locale: {
                    format,
                    applyLabel: 'Pilih',
                    cancelLabel: 'Reset'
                }
            }, function(start, end){
                $start.val(start.format(format));
                $end.val(end.format(format));
            });
            $start.on('apply.daterangepicker', function(ev, picker){
                $start.val(picker.startDate.format(format));
                $end.val(picker.endDate.format(format));
            });
            $start.on('cancel.daterangepicker', function(){
                $start.val('');
                $end.val('');
            });
            $end.on('click', function(){ $start.trigger('click'); });
            syncDateRangePicker();
        }
        
        function computeRangeAdvanced(period, year, month, weekOpt){
            const pad=n=>String(n).padStart(2,'0');
            const toStr=d=>`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            const clampMonth=(y,m)=>{ const s=new Date(y,m-1,1); const e=new Date(y,m,0); return {s,e}; };
            if(!period) return null;
            if(period==='harian'){ const {s,e}=clampMonth(year,month); return {from:toStr(s), to:toStr(e)}; }
            if(period==='mingguan'){
                if(weekOpt && weekOpt.startsWith('last_')){
                    const days=parseInt(weekOpt.split('_')[1]); const today=new Date(); const to=new Date(today.getFullYear(),today.getMonth(),today.getDate()); const from=new Date(to); from.setDate(to.getDate()-(days-1)); return {from:toStr(from), to:toStr(to)};
                }
                const idx=weekOpt && weekOpt.startsWith('w') ? parseInt(weekOpt.slice(1)) : 1;
                const firstDay=new Date(year,month-1,1); const firstMonday=new Date(firstDay); const day=firstMonday.getDay(); const diff=(day===0?1:(day===1?0:(8-day))); firstMonday.setDate(1+diff); const start=new Date(firstMonday); start.setDate(firstMonday.getDate()+7*(idx-1)); const end=new Date(start); end.setDate(start.getDate()+6); const {s:ms,e:me}=clampMonth(year,month); const s=start<ms?ms:start; const e=end>me?me:end; return {from:toStr(s), to:toStr(e)};
            }
            if(period==='bulanan'){ const {s,e}=clampMonth(year,month); return {from:toStr(s), to:toStr(e)}; }
            if(period==='tahunan'){ const s=new Date(year,0,1); const e=new Date(year,11,31); return {from:toStr(s), to:toStr(e)}; }
            return null;
        }
        
        function updateVisibility(){
            const period=document.getElementById('periodSelect')?.value||'';
            const yc=document.getElementById('yearCol'); const mc=document.getElementById('monthCol'); const wc=document.getElementById('weekCol'); const dr=document.getElementById('dateRangeContainer');
            const toggleOthers=(show)=>{
                ['category_id[]','outlet_id','product_id','user_id'].forEach(n=>{ const el=document.querySelector(`[name="${n}"]`); if(!el) return; const col=el.closest('.col-md-1, .col-md-2, .col-md-3, .col-md-6, .col-md-12'); if(col) col.style.display = show ? '' : 'none'; });
            };
            if(!period){ if(yc) yc.style.display='none'; if(mc) mc.style.display='none'; if(wc) wc.style.display='none'; if(dr) dr.style.display='none'; toggleOthers(false); return; }
            toggleOthers(true);
            if(yc) yc.style.display='block'; if(mc) mc.style.display = (period==='tahunan') ? 'none' : 'block'; if(wc) wc.style.display = (period==='mingguan') ? 'block' : 'none'; if(dr) dr.style.display = (period==='harian') ? 'block' : 'none';
        }
        
        function recomputeRange(){
            const period=document.getElementById('periodSelect')?.value||'';
            const year=parseInt(document.getElementById('yearSelect')?.value||'{{ now()->year }}');
            const month=parseInt(document.getElementById('monthSelect')?.value||'{{ now()->month }}');
            const weekOpt=document.getElementById('weekOptionSelect')?.value||'';
            const r=computeRangeAdvanced(period,year,month,weekOpt); if(!r) return;
            const df=document.querySelector('input[name="date_from"]'); const dt=document.querySelector('input[name="date_to"]'); if(df && r.from) df.value=r.from; if(dt && r.to) dt.value=r.to;
            const wim=document.getElementById('weekInMonthInput'); const ld=document.getElementById('lastDaysInput'); if(wim) wim.value = (weekOpt.startsWith('w')?weekOpt:''); if(ld) ld.value=(weekOpt.startsWith('last_')?weekOpt.split('_')[1]:'');
            syncDateRangePicker();
        }
        
        function savePrefs(prefix){
            const f=document.querySelector('form');
            const data={};
            const singleNames=['date_from','date_to','period'];
            singleNames.forEach(n=>{ const el=f.querySelector(`[name="${n}"]`); if(el) data[n]=el.value||''; });
            const catSel=f.querySelector('[name="category_id[]"]');
            if(catSel){ data['category_id'] = Array.from(catSel.selectedOptions).map(o=>o.value); }
            localStorage.setItem(prefix, JSON.stringify(data));
        }
        
        function loadPrefs(prefix){
            const q=new URLSearchParams(location.search); if([...q.keys()].length) return; const raw=localStorage.getItem(prefix); if(!raw) return; const data=JSON.parse(raw);
            Object.entries(data).forEach(([k,v])=>{
                if(k==='category_id' && Array.isArray(v)){
                    const el=document.querySelector('[name="category_id[]"]');
                    if(el){ Array.from(el.options).forEach(o=>{ o.selected = v.includes(o.value); }); }
                } else {
                    const el=document.querySelector(`[name="${k}"]`); if(el && !el.value) el.value=v;
                }
            });
        }
        
        function exportDataTableCSV(table, filename){
            const rows=[]; 
            const headers=[]; 
            $(table.table().header()).find('th').each(function(){ 
                headers.push($(this).text().trim()); 
            }); 
            rows.push(headers.join(',')); 
            table.rows({search:'applied'}).every(function(){ 
                const cols=[]; 
                $(this.node()).find('td').each(function(){ 
                    cols.push('"'+$(this).text().trim().replace(/"/g,'""')+'"'); 
                }); 
                rows.push(cols.join(',')); 
            }); 
            const blob=new Blob([rows.join('\n')],{type:'text/csv;charset=utf-8;'}); 
            const a=document.createElement('a'); 
            a.href=URL.createObjectURL(blob); 
            a.download=filename; 
            a.click(); 
        }
        
        let psChart;
        const psData = @json($chart ?? null);
        if (psData) {
            const pctx = document.getElementById('productSalesChart').getContext('2d');
            psChart = new Chart(pctx, {
                type: 'bar',
                data: {
                    labels: psData.labels,
                    datasets: [
                        {
                            label: 'Quantity',
                            data: psData.quantity,
                            backgroundColor: 'rgba(54,162,235,0.6)',
                            borderColor: 'rgba(54,162,235,1)',
                            borderWidth: 1,
                            yAxisID: 'y1',
                            hoverBackgroundColor: 'rgba(54,162,235,0.8)'
                        },
                        {
                            label: 'Revenue',
                            data: psData.revenue,
                            backgroundColor: 'rgba(255,159,64,0.6)',
                            borderColor: 'rgba(255,159,64,1)',
                            borderWidth: 1,
                            yAxisID: 'y2',
                            hoverBackgroundColor: 'rgba(255,159,64,0.8)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        if (context.dataset.label === 'Revenue') {
                                            label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                        } else {
                                            label += context.parsed.y.toLocaleString('id-ID');
                                        }
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y1:{
                            type:'linear',
                            position:'left',
                            title:{ display:true, text:'Quantity' },
                            grid: {
                                drawOnChartArea: false
                            }
                        },
                        y2:{
                            type:'linear',
                            position:'right',
                            grid:{ drawOnChartArea:false},
                            title:{ display:true, text:'Revenue (Rp)' }
                        }
                    }
                }
            });
        }

        $(function(){
            initDateRangePicker();
            
            // Initialize Select2 for category multi-select
            if ($.fn.select2) {
                $('[name="category_id[]"]').select2({
                    width: '100%',
                    placeholder: $("[name='category_id[]']").data('placeholder') || 'Pilih kategori',
                    allowClear: true
                });
            }
            
            loadPrefs('product_sales_filters');
            syncDateRangePicker();
            
            const dt = $('#productSalesTable').DataTable({
                paging:true,
                info:true,
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ entri",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                }
            });
            
            function updateAll(){ 
                if(!psChart) return; 
                const qtyBy={}, revBy={};
                dt.rows({ search:'applied' }).every(function(){ 
                    const $r=$(this.node()); 
                    const tds=$r.find('td'); 
                    const label=$(tds.get(1)).text().trim(); 
                    const qty=parseInt($(tds.get(2)).text())||0; 
                    const rev=parseCurrency($(tds.get(3)).text()); 
                    qtyBy[label]=(qtyBy[label]||0)+qty; 
                    revBy[label]=(revBy[label]||0)+rev; 
                });
                const labels = Object.keys(qtyBy);
                psChart.data.labels = labels;
                psChart.data.datasets[0].data = labels.map(l=>qtyBy[l]);
                psChart.data.datasets[1].data = labels.map(l=>revBy[l]);
                psChart.update('none');
                
                let tq=0,tr=0,tp=0; 
                dt.rows({search:'applied'}).every(function(){ 
                    const tds=$(this.node()).find('td'); 
                    tq+=parseInt($(tds.get(2)).text())||0; 
                    tr+=parseCurrency($(tds.get(3)).text()); 
                    tp+=parseCurrency($(tds.get(4)).text());
                });
                $('#ftQtyPS').text(tq.toLocaleString('id-ID'));
                $('#ftRevPS').text(tr.toLocaleString('id-ID'));
                $('#ftProfitPS').text(tp.toLocaleString('id-ID'));
                const avgMargin = tr > 0 ? ((tp / tr) * 100) : 0;
                $('#ftMarginPS').text(avgMargin.toFixed(2) + '%');
                
                // Update mobile totals
                $('#ftQtyPSMobile').text(tq.toLocaleString('id-ID'));
                $('#ftRevPSMobile').text('Rp ' + tr.toLocaleString('id-ID'));
                $('#ftProfitPSMobile').text('Rp ' + tp.toLocaleString('id-ID'));
                $('#ftMarginPSMobile').text(avgMargin.toFixed(2) + '%');
            }
            
            dt.on('draw', updateAll); 
            updateAll();
            
            // Event listeners
            document.getElementById('periodSelect')?.addEventListener('change', ()=>{ updateVisibility(); recomputeRange(); });
            document.getElementById('yearSelect')?.addEventListener('change', recomputeRange);
            document.getElementById('monthSelect')?.addEventListener('change', recomputeRange);
            document.getElementById('weekOptionSelect')?.addEventListener('change', recomputeRange);
            
            // On first load, don't override server-provided dates
            updateVisibility();
            (function(){
                const dfEl = document.querySelector('input[name="date_from"]');
                const dtEl = document.querySelector('input[name="date_to"]');
                const hasServerDates = !!((dfEl && dfEl.value) || (dtEl && dtEl.value));
                if(document.getElementById('periodSelect')?.value && !hasServerDates){
                    recomputeRange();
                }
            })();
            
            $('#btnExportPS').on('click', ()=>{
                exportDataTableCSV(dt,'product_sales_view.csv');
            });
            
            $('#btnResetPS').on('click', function(){ 
                try { localStorage.removeItem('product_sales_filters'); } catch(_){ }
                window.location.href = "{{ route('report.product-sales') }}";
            });
            
            document.querySelector('form')?.addEventListener('submit', ()=>savePrefs('product_sales_filters'));
            
            function exportProductSalesPDF(){
                const jsPdfNS = window.jspdf;
                if(!jsPdfNS || typeof jsPdfNS.jsPDF !== 'function'){
                    alert('Library PDF belum dimuat.');
                    return;
                }
                
                const btn = document.getElementById('btnExportPDF');
                const originalLabel = btn?.textContent;
                if(btn){
                    btn.disabled = true;
                    btn.textContent = 'Membuat PDF...';
                }
                
                try{
                    const doc = new jsPdfNS.jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
                    const formatNumber = (n) => (Number(n) || 0).toLocaleString('id-ID');
                    const dateFrom = document.querySelector('input[name="date_from"]')?.value || '-';
                    const dateTo = document.querySelector('input[name="date_to"]')?.value || '-';
                    
                    // Title
                    doc.setFontSize(16);
                    doc.text('Laporan Penjualan Produk', 40, 40);
                    
                    // Period info
                    doc.setFontSize(11);
                    doc.text(`Periode: ${dateFrom} s/d ${dateTo}`, 40, 60);
                    
                    // Summary cards data
                    const totalRevenue = document.getElementById('ftRevPS')?.textContent || 'Rp 0';
                    const totalProfit = document.getElementById('ftProfitPS')?.textContent || 'Rp 0';
                    const avgMargin = document.getElementById('ftMarginPS')?.textContent || '0%';
                    
                    doc.text(`Total Revenue: ${totalRevenue}`, 40, 80);
                    doc.text(`Total Profit: ${totalProfit}`, 40, 100);
                    doc.text(`Avg Profit Margin: ${avgMargin}`, 40, 120);
                    
                    // Table data
                    const tableData = [];
                    document.querySelectorAll('#productSalesTable tbody tr').forEach((tr, idx) => {
                        const tds = tr.querySelectorAll('td');
                        if(tds.length >= 5){
                            tableData.push([
                                (idx + 1).toString(),
                                tds[1].textContent.trim(),
                                tds[2].textContent.trim(),
                                tds[3].textContent.trim(),
                                tds[4].textContent.trim(),
                                tds[5].textContent.trim()
                            ]);
                        }
                    });
                    
                    if(tableData.length > 0 && typeof doc.autoTable === 'function'){
                        doc.autoTable({
                            head: [['No', 'Produk', 'Quantity', 'Revenue', 'Profit', 'Margin']],
                            body: tableData,
                            startY: 140,
                            styles: { fontSize: 9 },
                            headStyles: { fillColor: [52, 152, 219] },
                            columnStyles: {
                                0: { halign: 'center' },
                                1: { halign: 'left' },
                                2: { halign: 'center' },
                                3: { halign: 'right' },
                                4: { halign: 'right' },
                                5: { halign: 'right' }
                            }
                        });
                    }
                    
                    // Chart
                    const canvas = document.getElementById('productSalesChart');
                    if(canvas && psChart){
                        const chartImage = canvas.toDataURL('image/png', 1.0);
                        const imgWidth = doc.internal.pageSize.getWidth() - 80;
                        const imgHeight = (imgWidth * canvas.height) / canvas.width;
                        
                        doc.addPage();
                        doc.setFontSize(14);
                        doc.text('Grafik Performa Produk', 40, 40);
                        doc.addImage(chartImage, 'PNG', 40, 60, imgWidth, imgHeight);
                    }
                    
                    const fileName = `laporan_penjualan_produk_${dateFrom}_${dateTo}.pdf`;
                    doc.save(fileName);
                } catch (err) {
                    console.error(err);
                    alert('Gagal membuat PDF.');
                } finally {
                    if(btn){
                        btn.disabled = false;
                        btn.textContent = originalLabel || 'Export PDF';
                    }
                }
            }
            
            $('#btnExportPDF').on('click', exportProductSalesPDF);
        });
    </script>
@endpush
