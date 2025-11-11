@extends('layouts.app')

@section('title', 'Report Order by Category')

@php
    $paymentMethodOptions = collect($paymentMethods ?? [])
        ->map(function ($item) {
            if (is_array($item)) {
                if (array_key_exists('payment_method', $item)) {
                    return $item['payment_method'];
                }
                if (array_key_exists('value', $item)) {
                    return $item['value'];
                }
                return count($item) ? reset($item) : null;
            }
            if (is_object($item) && isset($item->payment_method)) {
                return $item->payment_method;
            }
            return $item;
        })
        ->map(fn ($value) => is_string($value) ? trim($value) : $value)
        ->filter(fn ($value) => filled($value))
        ->unique(fn($value) => is_string($value) ? mb_strtolower($value) : $value)
        ->values();
@endphp

@push('style')
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
    <link rel="stylesheet" href="{{ asset('library/datatables/media/css/jquery.dataTables.css') }}">
    <link rel="stylesheet" href="{{ asset('library/select2/dist/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('library/bootstrap-daterangepicker/daterangepicker.css') }}">
    <style>
        /* Category hierarchy styling */
        .category-row.parent-row {
            font-weight: 600;
        }
        
        .subcategory-row {
            background-color: #f8f9fa !important;
            font-size: 0.95em;
        }
        
        .subcategory-row:hover {
            background-color: #e9ecef !important;
        }
        
        .toggle-children {
            transition: transform 0.2s ease;
        }
        
        .toggle-children:hover {
            transform: scale(1.1);
        }
        
        .toggle-children i {
            transition: transform 0.2s ease;
        }
        
        /* Better indentation for hierarchy */
        .category-row td:nth-child(2),
        .subcategory-row td:nth-child(2) {
            position: relative;
        }
        
        /* Add subtle borders for hierarchy */
        .subcategory-row {
            border-left: 3px solid #dee2e6;
        }
        
        .subcategory-row.child-of- {
            border-left-color: #007bff;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .subcategory-row {
                font-size: 0.9em;
            }
        }
    </style>
@endpush

@section('main')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Report Order - By Category</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                <div class="breadcrumb-item"><a href="#">Reports</a></div>
                <div class="breadcrumb-item">Order by Category</div>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header">
                    <h4>Filter</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('report.byCategory') }}" method="GET">
                        <input type="hidden" name="filtered" value="1">
                        @if(isset($userId))
                            <input type="hidden" name="user_id" value="{{ $userId }}">
                        @endif
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Periode <span class="text-muted" title="Pilih periode terlebih dahulu, lalu filter lainnya akan muncul">?</span></label>
                                    <select name="period" class="form-control" id="periodSelectCat">
                                        <option value="harian" {{ request('period')=='harian' ? 'selected' : '' }}>Harian</option>
                                        <option value="mingguan" {{ request('period')=='mingguan' ? 'selected' : '' }}>Mingguan</option>
                                        <option value="bulanan" {{ request('period')=='bulanan' ? 'selected' : '' }}>Bulanan</option>
                                        <option value="tahunan" {{ request('period')=='tahunan' ? 'selected' : '' }}>Tahunan</option>
                                    </select>
                                </div>
                            </div>
                            <div id="dateRangeContainerCat" class="col-md-5">
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Dari Tanggal</label>
                                            <input type="text"
                                                   name="date_from"
                                                   id="dateFromInputCat"
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
                                                   id="dateToInputCat"
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
                            <div class="col-md-2" id="yearColCat" style="display:none;">
                                <div class="form-group">
                                    <label>Tahun</label>
                                    @php($currentYear = (int) (old('year') ?? ($year ?? request('year') ?? now()->year)))
                                    <select name="year" id="yearSelectCat" class="form-control">
                                        @for($y = $currentYear + 1; $y >= $currentYear - 5; $y--)
                                            <option value="{{ $y }}" {{ $currentYear==$y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2" id="monthColCat" style="display:none;">
                                <div class="form-group">
                                    <label>Bulan</label>
                                    @php($currentMonth = (int) (old('month') ?? ($month ?? request('month') ?? now()->month)))
                                    @php($monthNames = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'])
                                    <select name="month" id="monthSelectCat" class="form-control">
                                        @for($m=1;$m<=12;$m++)
                                            <option value="{{ $m }}" {{ $currentMonth==$m ? 'selected' : '' }}>{{ $monthNames[$m] }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2" id="weekColCat" style="display:none;">
                                <div class="form-group">
                                    <label>Opsi Mingguan</label>
                                    <select id="weekOptionSelectCat" class="form-control">
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
                                    <input type="hidden" name="week_in_month" id="weekInMonthInputCat" value="{{ request('week_in_month') }}">
                                    <input type="hidden" name="last_days" id="lastDaysInputCat" value="{{ request('last_days') }}">
                                </div>
                            </div>
                            <!-- Status filter removed; report always uses Completed -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Metode Bayar</label>
                                    @php($selectedPayments = collect(($paymentMethod ?? (array) request('payment_method', [])))
                                        ->map(fn($v)=> (string)$v)
                                        ->filter(fn($v)=> $v !== '')
                                        ->unique(fn($v)=> mb_strtolower($v))
                                        ->values()
                                        ->all())
                                    <select name="payment_method[]" class="form-control select2" multiple data-placeholder="Pilih metode bayar">
                                        @foreach($paymentMethodOptions as $pm)
                                            <option value="{{ $pm }}" {{ in_array((string)$pm, $selectedPayments, true) ? 'selected' : '' }}>
                                                {{ \Illuminate\Support\Str::of($pm)->replace('_', ' ')->title() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
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
                        </div>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group d-flex">
                                    <button type="submit" class="btn btn-primary btn-lg mr-2">Filter</button>
                                    <button type="button" id="btnResetCat" class="btn btn-light btn-lg">Reset</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="mb-3">
                        @php($chips = [])
                        @if(request('period')) @php($chips[] = 'Periode: '.ucfirst(request('period'))) @endif
                        @if(request('date_from')) @php($chips[] = 'Dari: '.request('date_from')) @endif
                        @if(request('date_to')) @php($chips[] = 'Ke: '.request('date_to')) @endif
                        @php($chips[] = 'Status: Completed')
                        @php($pmSel = collect((array) request('payment_method', ($paymentMethod ?? [])))->filter(fn($v)=>$v!=='')->unique(fn($v)=> mb_strtolower($v))->values())
                        @if($pmSel->isNotEmpty())
                            @php($prettyPm = $pmSel->map(fn($v) => \Illuminate\Support\Str::of($v)->replace('_',' ')->title())->implode(', '))
                            @php($chips[] = 'Metode: '.$prettyPm)
                        @endif
                        @if(request('year')) @php($chips[] = 'Tahun: '.request('year')) @endif
                        @if(request('month')) @php($chips[] = 'Bulan: '.($monthNames[(int)request('month')] ?? request('month'))) @endif
                        @if(request('week_in_month')) @php($chips[] = 'Minggu: '.strtoupper(request('week_in_month'))) @endif
                        @if(request('last_days')) @php($chips[] = 'Terakhir: '.request('last_days').' hari') @endif
                        @php($selectedCats = (array) request('category_id', ($categoryId ?? [])))
                        @if(!empty($selectedCats))
                            @php($names = ($categories ?? collect())->whereIn('id', array_map('intval', $selectedCats))->pluck('name')->all())
                            @foreach($names as $nm)
                                @php($chips[] = 'Kategori: '.$nm)
                            @endforeach
                        @endif
                        @if(count($chips))
                            <div>
                                @foreach($chips as $chip)
                                    <span class="badge badge-primary mr-2">{{ $chip }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if(($filtered ?? false) && isset($categorySales) && $categorySales->count() > 0)
                <div class="card">
                    <div class="card-header"><h4>Category Performance</h4></div>
                    <div class="card-body">
                        <canvas id="categoryChart" height="100"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h4>Table - Category Summary</h4></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div></div>
                            <div>
                                <button type="button" id="btnExportCat" class="btn btn-outline-primary">Export Summary (PDF)</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="categoryTable" class="table table-striped table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th width="40"></th>
                                        <th>Category</th>
                                        <th>Total Quantity</th>
                                        <th>Total Price</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php($counter = 1)
                                    @foreach($categorySales as $category)
                                        <tr class="category-row" data-category-id="{{ $category['id'] }}" data-level="{{ $category['level'] }}">
                                            <td>
                                                @if($category['has_children'])
                                                    <button class="btn btn-sm btn-link p-0 toggle-children" title="Expand/Collapse">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                @endif
                                            </td>
                                            <td class="text-left">
                                                @if($category['level'] > 0)
                                                    @for($i = 0; $i < $category['level']; $i++)
                                                        <span class="ml-3"></span>
                                                    @endfor
                                                @endif
                                                <strong>{{ $category['name'] }}</strong>
                                                @if($category['is_parent'])
                                                    <span class="badge badge-info ml-2">Parent</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="#"
                                                   class="js-cat-details"
                                                   data-url="{{ route('report.byCategory.items', [
                                                        'date_from' => $date_from,
                                                        'date_to' => $date_to,
                                                        'category_id' => $category['id'],
                                                        'payment_method' => $paymentMethod,
                                                        'user_id' => $userId,
                                                   ]) }}"
                                                   title="Lihat detail transaksi untuk kategori ini">
                                                    {{ $category['total_quantity'] }}
                                                </a>
                                                @if($category['direct_quantity'] != $category['total_quantity'])
                                                    <small class="text-muted d-block">({{ $category['direct_quantity'] }} direct)</small>
                                                @endif
                                            </td>
                                            <td>
                                                {{ number_format($category['total_revenue'], 0, ',', '.') }}
                                                @if($category['direct_revenue'] != $category['total_revenue'])
                                                    <small class="text-muted d-block">({{ number_format($category['direct_revenue'], 0, ',', '.') }} direct)</small>
                                                @endif
                                            </td>
                                            <td>
                                                <!-- Actions column kept for consistency but no duplicate button -->
                                            </td>
                                        </tr>
                                        
                                        <!-- Subcategories (hidden by default) -->
                                        @if(!empty($category['children']))
                                            @foreach($category['children'] as $child)
                                                @php($counter++)
                                                <tr class="subcategory-row child-of-{{ $category['id'] }}" data-category-id="{{ $child['id'] }}" data-level="{{ $child['level'] }}" style="display:none;">
                                                    <td></td>
                                                    <td class="text-left">
                                                        @for($i = 0; $i < $child['level']; $i++)
                                                            <span class="ml-3"></span>
                                                        @endfor
                                                        {{ $child['name'] }}
                                                    </td>
                                                    <td>
                                                        <a href="#"
                                                           class="js-cat-details"
                                                           data-url="{{ route('report.byCategory.items', [
                                                                'date_from' => $date_from,
                                                                'date_to' => $date_to,
                                                                'category_id' => $child['id'],
                                                                'payment_method' => $paymentMethod,
                                                                'user_id' => $userId,
                                                           ]) }}"
                                                           title="Lihat detail transaksi untuk subkategori ini">
                                                            {{ $child['total_quantity'] }}
                                                        </a>
                                                    </td>
                                                    <td>{{ number_format($child['total_revenue'], 0, ',', '.') }}</td>
                                                    <td></td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th></th>
                                        <th id="ftQtyCat"></th>
                                        <th id="ftRevCat"></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            @elseif(($filtered ?? false) && isset($date_from, $date_to))
                <div class="alert alert-warning">No data found for the selected date range.</div>
            @endif
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

        function parseCurrency(str){ if(!str) return 0; return parseInt(String(str).replace(/[^0-9\-]/g,'')) || 0; }
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
        function syncDateRangePickerCat(){
            if(typeof $ === 'undefined' || typeof moment === 'undefined') return;
            const $start = $('#dateFromInputCat');
            const $end = $('#dateToInputCat');
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
        function initDateRangePickerCat(){
            if(typeof $ === 'undefined' || typeof moment === 'undefined' || !$.fn.daterangepicker) return;
            const $start = $('#dateFromInputCat');
            const $end = $('#dateToInputCat');
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
            syncDateRangePickerCat();
        }
        let categoryChart;
        let catDetailsLastPayload = null;
        const catData = @json($chart ?? null);
        const CAT_SELECTED = @json($categoryId ?? []);
        const CAT_ITEMS_ALL_URL = "{{ route('report.byCategory.items', [
            'date_from' => $date_from,
            'date_to' => $date_to,
            'payment_method' => $paymentMethod,
            'user_id' => $userId,
        ]) }}";
        if (catData) {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            categoryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: catData.labels,
                    datasets: [
                        { label: 'Quantity', data: catData.quantity, backgroundColor: 'rgba(75,192,192,0.5)', borderColor: 'rgba(75,192,192,1)', yAxisID: 'y1' },
                        { label: 'Revenue', data: catData.revenue, backgroundColor: 'rgba(153,102,255,0.5)', borderColor: 'rgba(153,102,255,1)', yAxisID: 'y2' }
                    ]
                },
                options: { responsive: true, scales: { y1:{ type:'linear', position:'left'}, y2:{ type:'linear', position:'right', grid:{ drawOnChartArea:false}} } }
            });
        }

        function computeRangeAdvancedCat(period, year, month, weekOpt){
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
        function updateVisibilityCat(){
            const period=document.getElementById('periodSelectCat')?.value||'';
            const yc=document.getElementById('yearColCat'); const mc=document.getElementById('monthColCat'); const wc=document.getElementById('weekColCat'); const dr=document.getElementById('dateRangeContainerCat');
            const toggleOthers=(show)=>{
                ['payment_method[]','category_id[]','user_id'].forEach(n=>{ const el=document.querySelector(`[name="${n}"]`); if(!el) return; const col=el.closest('.col-md-1, .col-md-2, .col-md-3, .col-md-6, .col-md-12'); if(col) col.style.display = show ? '' : 'none'; });
            };
            if(!period){ if(yc) yc.style.display='none'; if(mc) mc.style.display='none'; if(wc) wc.style.display='none'; if(dr) dr.style.display='none'; toggleOthers(false); return; }
            toggleOthers(true);
            if(yc) yc.style.display='block'; if(mc) mc.style.display = (period==='tahunan') ? 'none' : 'block'; if(wc) wc.style.display = (period==='mingguan') ? 'block' : 'none'; if(dr) dr.style.display = (period==='harian') ? 'block' : 'none';
        }
        function recomputeRangeCat(){
            const period=document.getElementById('periodSelectCat')?.value||'';
            const year=parseInt(document.getElementById('yearSelectCat')?.value||'{{ now()->year }}');
            const month=parseInt(document.getElementById('monthSelectCat')?.value||'{{ now()->month }}');
            const weekOpt=document.getElementById('weekOptionSelectCat')?.value||'';
            const r=computeRangeAdvancedCat(period,year,month,weekOpt); if(!r) return;
            const df=document.querySelector('input[name="date_from"]'); const dt=document.querySelector('input[name="date_to"]'); if(df && r.from) df.value=r.from; if(dt && r.to) dt.value=r.to;
            const wim=document.getElementById('weekInMonthInputCat'); const ld=document.getElementById('lastDaysInputCat'); if(wim) wim.value = (weekOpt.startsWith('w')?weekOpt:''); if(ld) ld.value=(weekOpt.startsWith('last_')?weekOpt.split('_')[1]:'');
            syncDateRangePickerCat();
        }
        document.getElementById('periodSelectCat')?.addEventListener('change', ()=>{ updateVisibilityCat(); recomputeRangeCat(); });
        document.getElementById('yearSelectCat')?.addEventListener('change', recomputeRangeCat);
        document.getElementById('monthSelectCat')?.addEventListener('change', recomputeRangeCat);
        document.getElementById('weekOptionSelectCat')?.addEventListener('change', recomputeRangeCat);
        // On first load, don’t override server-provided dates
        updateVisibilityCat();
        (function(){
            const dfEl = document.querySelector('input[name="date_from"]');
            const dtEl = document.querySelector('input[name="date_to"]');
            const hasServerDates = !!((dfEl && dfEl.value) || (dtEl && dtEl.value));
            if(document.getElementById('periodSelectCat')?.value && !hasServerDates){
                recomputeRangeCat();
            }
        })();

        function savePrefs(prefix){
            const f=document.querySelector('form');
            const data={};
            const singleNames=['date_from','date_to','period'];
            singleNames.forEach(n=>{ const el=f.querySelector(`[name="${n}"]`); if(el) data[n]=el.value||''; });
            const pmSel=f.querySelector('[name="payment_method[]"]');
            if(pmSel){ data['payment_method'] = Array.from(pmSel.selectedOptions).map(o=>o.value); }
            const catSel=f.querySelector('[name="category_id[]"]');
            if(catSel){ data['category_id'] = Array.from(catSel.selectedOptions).map(o=>o.value); }
            localStorage.setItem(prefix, JSON.stringify(data));
        }
        function loadPrefs(prefix){
            const q=new URLSearchParams(location.search); if([...q.keys()].length) return; const raw=localStorage.getItem(prefix); if(!raw) return; const data=JSON.parse(raw);
            Object.entries(data).forEach(([k,v])=>{
                if(k==='payment_method' && Array.isArray(v)){
                    const el=document.querySelector('[name="payment_method[]"]');
                    if(el){ Array.from(el.options).forEach(o=>{ o.selected = v.includes(o.value); }); }
                } else if(k==='category_id' && Array.isArray(v)){
                    const el=document.querySelector('[name="category_id[]"]');
                    if(el){ Array.from(el.options).forEach(o=>{ o.selected = v.includes(o.value); }); }
                } else {
                    const el=document.querySelector(`[name="${k}"]`); if(el && !el.value) el.value=v;
                }
            });
        }
        function exportDataTableCSV(table, filename){
            const rows=[]; const headers=[]; $(table.table().header()).find('th').each(function(){ headers.push($(this).text().trim()); }); rows.push(headers.join(','));
            table.rows({search:'applied'}).every(function(){ const cols=[]; $(this.node()).find('td').each(function(){ cols.push('"'+$(this).text().trim().replace(/"/g,'""')+'"'); }); rows.push(cols.join(',')); });
            const blob=new Blob([rows.join('\n')],{type:'text/csv;charset=utf-8;'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=filename; a.click();
        }

        function formatIDR(n){ if(n==null) return '-'; return (n).toLocaleString('id-ID'); }
        function exportCatDetailsPDF(){
            if(!catDetailsLastPayload){
                alert('Belum ada data detail yang dapat diekspor.');
                return;
            }
            const payload = catDetailsLastPayload;
            const jsPdfNS = window.jspdf;
            if(!jsPdfNS || typeof jsPdfNS.jsPDF !== 'function'){
                alert('Library PDF belum dimuat.');
                return;
            }
            const doc = new jsPdfNS.jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
            const title = `Detail Transaksi Kategori: ${payload.category_name || 'Kategori'}`;
            const range = `Periode: ${(payload.date_from || '-') } s/d ${(payload.date_to || '-')}`;
            const totals = `Total Qty: ${(payload.total_quantity || 0).toLocaleString('id-ID')} | Total Revenue: Rp ${formatIDR(payload.total_revenue || 0)}`;
            doc.setFontSize(16);
            doc.text(title, 40, 40);
            doc.setFontSize(11);
            doc.text(range, 40, 60);
            doc.text(totals, 40, 76);
            const rows = (payload.items || []).map(it => [
                (it.transaction_number || it.order_id || '-'),
                formatPaymentMethod(it.payment_method),
                formatDateTime(it.created_at_iso || it.created_at),
                (it.product_name || '-'),
                formatIDR(it.price || 0),
                (it.quantity || 0),
                formatIDR(it.total_price || 0),
            ]);
            if(typeof doc.autoTable === 'function'){
                doc.autoTable({
                    head: [['Transaksi','Metode','Waktu','Produk','Harga','Qty','Total']],
                    body: rows,
                    startY: 100,
                    styles: { fontSize: 9 },
                    headStyles: { fillColor: [52, 152, 219] },
                    columnStyles: {
                        4: { halign: 'right' },
                        5: { halign: 'center' },
                        6: { halign: 'right' },
                    }
                });
            }
            const safeName = String(payload.category_name || 'kategori').replace(/[^a-z0-9\-]+/gi,'_').toLowerCase();
            const filename = `detail_kategori_${safeName}_${(payload.date_from || '')}_${(payload.date_to || '')}.pdf`;
            doc.save(filename);
        }
        async function exportCategorySummaryPDF(){
            const jsPdfNS = window.jspdf;
            if(!jsPdfNS || typeof jsPdfNS.jsPDF !== 'function'){
                alert('Library PDF belum dimuat.');
                return;
            }
            const btn = document.getElementById('btnExportCat');
            const originalLabel = btn?.textContent;
            if(btn){
                btn.disabled = true;
                btn.textContent = 'Menyusun PDF...';
            }
            try{
                const doc = new jsPdfNS.jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
                const formatNumber = (n) => (Number(n) || 0).toLocaleString('id-ID');
                const dateFrom = document.getElementById('dateFromInputCat')?.value || '-';
                const dateTo = document.getElementById('dateToInputCat')?.value || '-';
                const title = 'Ringkasan Kategori';
                doc.setFontSize(16);
                doc.text(title, 40, 40);
                doc.setFontSize(11);
                doc.text(`Periode: ${dateFrom} s/d ${dateTo}`, 40, 60);
                const summaryRows = [];
                document.querySelectorAll('#categoryTable tbody tr').forEach((tr, idx)=>{
                    const tds = tr.querySelectorAll('td');
                    if(tds.length >= 5){
                        const level = parseInt(tr.dataset.level) || 0;
                        const categoryName = level > 0 ? '  '.repeat(level) + tds[1].textContent.trim() : tds[1].textContent.trim();
                        summaryRows.push([
                            String(idx + 1),
                            categoryName,
                            tds[2].textContent.trim(),
                            tds[3].textContent.trim()
                        ]);
                    }
                });
                if(summaryRows.length && typeof doc.autoTable === 'function'){
                    doc.autoTable({
                        head: [['No','Kategori','Total Quantity','Total Price']],
                        body: summaryRows,
                        startY: 80,
                        styles: { fontSize: 9 },
                        headStyles: { fillColor: [52, 152, 219] },
                        columnStyles: {
                            2: { halign: 'center' },
                            3: { halign: 'right' },
                        }
                    });
                }
                const detailLinks = Array.from(document.querySelectorAll('#categoryTable tbody .js-cat-details'));
                for(const link of detailLinks){
                    const url = link.getAttribute('data-url');
                    if(!url) continue;
                    const row = link.closest('tr');
                    const catName = row?.querySelector('td:nth-child(2)')?.textContent.trim() || 'Kategori';
                    try{
                        const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
                        if(!resp.ok) throw new Error('HTTP error');
                        const payload = await resp.json();
                        doc.addPage('landscape', 'pt', 'a4');
                        doc.setFontSize(14);
                        doc.text(`Kategori: ${catName}`, 40, 40);
                        doc.setFontSize(10);
                        doc.text(`Periode: ${(payload.date_from || dateFrom)} s/d ${(payload.date_to || dateTo)}`, 40, 58);
                        doc.text(`Total Qty: ${formatNumber(payload.total_quantity || 0)} | Total Revenue: Rp ${formatNumber(payload.total_revenue || 0)}`, 40, 74);
                        if(Array.isArray(payload.items) && payload.items.length && typeof doc.autoTable === 'function'){
                            const rows = payload.items.map(it => [
                                it.transaction_number || it.order_id || '-',
                                formatPaymentMethod(it.payment_method),
                                formatDateTime(it.created_at_iso || it.created_at),
                                it.product_name || '-',
                                formatIDR(it.price || 0),
                                it.quantity || 0,
                                formatIDR(it.total_price || 0),
                            ]);
                            doc.autoTable({
                                head: [['Transaksi','Metode','Waktu','Produk','Harga','Qty','Total']],
                                body: rows,
                                startY: 94,
                                styles: { fontSize: 9 },
                                headStyles: { fillColor: [41, 128, 185] },
                                columnStyles: {
                                    4: { halign: 'right' },
                                    5: { halign: 'center' },
                                    6: { halign: 'right' },
                                }
                            });
                        } else {
                            doc.text('Tidak ada transaksi pada kategori ini.', 40, 94);
                        }
                    } catch (err) {
                        doc.addPage('landscape', 'pt', 'a4');
                        doc.setFontSize(14);
                        doc.text(`Kategori: ${catName}`, 40, 40);
                        doc.setFontSize(10);
                        doc.text('Gagal memuat detail kategori.', 40, 60);
                    }
                }
                const fileName = `ringkasan_kategori_${dateFrom}_${dateTo}.pdf`;
                doc.save(fileName);
            } catch (err) {
                console.error(err);
                alert('Gagal membuat PDF ringkasan kategori.');
            } finally {
                if(btn){
                    btn.disabled = false;
                    btn.textContent = originalLabel || 'Export Summary (PDF)';
                }
            }
        }
        function renderCatModal(payload){
            const wrap=document.getElementById('catDetailsModal'); if(!wrap) return;
            catDetailsLastPayload = payload;
            
            // Display category path if available
            const displayName = payload.items && payload.items.length > 0 && payload.items[0].category_path
                ? payload.items[0].category_path
                : (payload.category_name||'Kategori');
                
            document.getElementById('cdTitle').textContent = displayName + ` — ${payload.date_from||''} s/d ${payload.date_to||''}`;
            document.getElementById('cdTotalQty').textContent = formatIDR(payload.total_quantity||0);
            document.getElementById('cdTotalRev').textContent = formatIDR(payload.total_revenue||0);

            const rows = (payload.items||[]).map(it=>[
                (it.transaction_number || it.order_id || ''),
                formatPaymentMethod(it.payment_method),
                formatDateTime(it.created_at_iso || it.created_at),
                (it.product_name||'-'),
                formatIDR(it.price||0),
                (it.quantity||0),
                formatIDR(it.total_price||0),
            ]);

            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#cdTable')) {
                const dt = $('#cdTable').DataTable();
                dt.clear();
                dt.rows.add(rows).draw();
            } else if ($.fn.DataTable) {
                $('#cdTable').DataTable({
                    data: rows,
                    paging: true,
                    info: true,
                    searching: false,
                    ordering: false,
                    lengthChange: false,
                });
            } else {
                // Fallback without DataTables
                const tb = document.getElementById('cdItems');
                if (tb) {
                    tb.innerHTML='';
                    (payload.items||[]).forEach(it=>{
                        const tr=document.createElement('tr');
                        tr.innerHTML = `<td>${it.transaction_number || it.order_id || '-'}</td>
                                        <td>${formatPaymentMethod(it.payment_method)}</td>
                                        <td>${formatDateTime(it.created_at_iso || it.created_at)}</td>
                                        <td>${it.product_name||'-'}</td>
                                        <td class=\"text-center\">${formatIDR(it.price||0)}</td>
                                        <td class=\"text-center\">${it.quantity||0}</td>
                                        <td class=\"text-right\">${formatIDR(it.total_price||0)}</td>`;
                        tb.appendChild(tr);
                    });
                }
            }

            $('#catDetailsModal').modal('show');
        }

        function initializeCategoryHierarchy() {
            // Toggle children visibility
            $(document).on('click', '.toggle-children', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $button = $(this);
                const $row = $button.closest('tr');
                const categoryId = $row.data('category-id');
                const $icon = $button.find('i');
                
                // Toggle child rows with smooth animation
                const $childRows = $(`.child-of-${categoryId}`);
                const isExpanded = $icon.hasClass('fa-chevron-down');
                
                if (isExpanded) {
                    // Collapse
                    $childRows.hide();
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                } else {
                    // Expand
                    $childRows.show();
                    $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                }
                
                // Update chart to reflect visible rows
                updateChartFromVisibleRows();
            });
            
            // Add visual indicators for expandable rows
            $('.category-row').each(function() {
                const $row = $(this);
                if ($row.find('.toggle-children').length > 0) {
                    $row.addClass('parent-row');
                }
            });
        }

        function updateChartFromVisibleRows() {
            if (!categoryChart) return;
            
            const qtyByLabel = {}, revByLabel = {};
            
            // Only count visible rows
            $('#categoryTable tbody tr:visible').each(function() {
                const $row = $(this);
                const $cells = $row.find('td');
                if ($cells.length >= 4) {
                    const label = $cells.eq(1).text().trim();
                    const qtyText = $cells.eq(2).find('a').text() || $cells.eq(2).text();
                    const qty = parseInt(qtyText) || 0;
                    const rev = parseCurrency($cells.eq(3).text());
                    
                    qtyByLabel[label] = (qtyByLabel[label] || 0) + qty;
                    revByLabel[label] = (revByLabel[label] || 0) + rev;
                }
            });
            
            const labels = Object.keys(qtyByLabel);
            categoryChart.data.labels = labels;
            categoryChart.data.datasets[0].data = labels.map(l => qtyByLabel[l]);
            categoryChart.data.datasets[1].data = labels.map(l => revByLabel[l]);
            categoryChart.update('none');
        }

        $(function(){
            initDateRangePickerCat();
            // Initialize Select2 for category multi-select and payment method if available
            if ($.fn.select2) {
                $('[name="category_id[]"]').select2({
                    width: '100%',
                    placeholder: $("[name='category_id[]']").data('placeholder') || 'Pilih kategori',
                    allowClear: true
                });
                $('[name="payment_method[]"]').select2({
                    width: '100%',
                    placeholder: $("[name='payment_method[]']").data('placeholder') || 'Pilih metode bayar',
                    allowClear: true
                });
            }
            loadPrefs('report_by_category_filters');
            syncDateRangePickerCat();
            
            // Initialize category hierarchy
            initializeCategoryHierarchy();
            
            const dt = $('#categoryTable').DataTable({
                paging:true,
                info:true,
                ordering:false,
                columnDefs: [
                    { orderable: false, targets: [0, 4] }
                ]
            });
            function updateChart(){
                if(!categoryChart) return;
                // Use the same logic as updateChartFromVisibleRows to ensure consistency
                updateChartFromVisibleRows();
                
                // Update footer totals
                let tq=0, tr=0;
                $('#categoryTable tbody tr:visible').each(function(){
                    const $row = $(this);
                    const $cells = $row.find('td');
                    if ($cells.length >= 4) {
                        const qtyText = $cells.eq(2).find('a').text() || $cells.eq(2).text();
                        tq += parseInt(qtyText) || 0;
                        tr += parseCurrency($cells.eq(3).text());
                    }
                });
                // Show grand total as plain text (no detail click on grand total)
                $('#ftQtyCat').text(tq.toLocaleString('id-ID'));
                $('#ftRevCat').text(tr.toLocaleString('id-ID'));
            }
            dt.on('draw', updateChart); updateChart();
            document.getElementById('btnExportCat')?.addEventListener('click', exportCategorySummaryPDF);
            const BY_CAT_BASE_URL = "{{ route('report.byCategory') }}";
            $('#btnResetCat').on('click', function(){
                try { localStorage.removeItem('report_by_category_filters'); } catch(_){ }
                // Navigate to base page (no query params) to fully reset server-side
                window.location.href = BY_CAT_BASE_URL;
            });
            document.querySelector('form')?.addEventListener('submit', ()=>savePrefs('report_by_category_filters'));
            document.addEventListener('click', function(e){
                const a=e.target.closest('.js-cat-details'); if(!a) return;
                e.preventDefault(); let url=a.getAttribute('data-url'); if(!url) return;
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                    .then(r=>r.json())
                    .then(renderCatModal)
                    .catch(()=>alert('Gagal mengambil detail transaksi kategori'));
            });
            document.getElementById('btnCatExportPdf')?.addEventListener('click', exportCatDetailsPDF);
        });
    </script>
    <!-- Modal for Category Details -->
    <div class="modal fade" id="catDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cdTitle">Detail Transaksi Kategori</h5>
                    <button type="button" class="btn btn-outline-primary btn-sm mr-2" id="btnCatExportPdf">Export PDF</button>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-end mb-2">
                        <div class="text-right">
                            <div><strong>Total Qty:</strong> <span id="cdTotalQty">0</span></div>
                            <div><strong>Total Revenue:</strong> <span id="cdTotalRev">0</span></div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="cdTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Transaksi</th>
                                    <th>Metode</th>
                                    <th>Waktu</th>
                                    <th>Produk</th>
                                    <th class="text-center">Harga</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody id="cdItems"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endpush
