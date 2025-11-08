@extends('layouts.app')

@section('title', 'Report')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Summary</h1>

                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Summary</a></div>
                    <div class="breadcrumb-item">Summary</div>
                </div>
            </div>
            <div class="section-body">


                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Summary</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('filterSummary.index') }}" method="GET">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Periode <span class="text-muted" title="Pilih periode terlebih dahulu, lalu filter lainnya akan muncul">?</span></label>
                                                <select name="period" class="form-control" id="periodSelectSummary">
                                                    <option value="harian" {{ request('period')=='harian' ? 'selected' : '' }}>Harian</option>
                                                    <option value="mingguan" {{ request('period')=='mingguan' ? 'selected' : '' }}>Mingguan</option>
                                                    <option value="bulanan" {{ request('period')=='bulanan' ? 'selected' : '' }}>Bulanan</option>
                                                    <option value="tahunan" {{ request('period')=='tahunan' ? 'selected' : '' }}>Tahunan</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div id="dateRangeContainerSummary" class="col-md-5">
                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Dari Tanggal</label>
                                                        <input type="date" name="date_from"
                                                            value="{{ old('date_from') ?? ($date_from ?? request()->query('date_from')) }}"
                                                            class="form-control datepicker">
                                                    </div>
                                                    @error('date_from')<div class="alert alert-danger">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Ke Tanggal</label>
                                                        <input type="date" name="date_to"
                                                            value="{{ old('date_to') ?? ($date_to ?? request()->query('date_to')) }}"
                                                            class="form-control datepicker">
                                                    </div>
                                                    @error('date_to')
                                                        <div class="alert alert-danger">
                                                            {{ $message }}
                                                        </div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="yearColSummary" style="display:none;">
                                            <div class="form-group">
                                                <label>Tahun</label>
                                                @php($currentYear = (int) (old('year') ?? ($year ?? request('year') ?? now()->year)))
                                                <select name="year" id="yearSelectSummary" class="form-control">
                                                    @for($y = $currentYear + 1; $y >= $currentYear - 5; $y--)
                                                        <option value="{{ $y }}" {{ $currentYear==$y ? 'selected' : '' }}>{{ $y }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="monthColSummary" style="display:none;">
                                            <div class="form-group">
                                                <label>Bulan</label>
                                                @php($currentMonth = (int) (old('month') ?? ($month ?? request('month') ?? now()->month)))
                                                @php($monthNames = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'])
                                                <select name="month" id="monthSelectSummary" class="form-control">
                                                    @for($m=1;$m<=12;$m++)
                                                        <option value="{{ $m }}" {{ $currentMonth==$m ? 'selected' : '' }}>{{ $monthNames[$m] }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="weekColSummary" style="display:none;">
                                            <div class="form-group">
                                                <label>Opsi Mingguan</label>
                                                <select id="weekOptionSelectSummary" class="form-control">
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
                                                <input type="hidden" name="week_in_month" id="weekInMonthInputSummary" value="{{ request('week_in_month') }}">
                                                <input type="hidden" name="last_days" id="lastDaysInputSummary" value="{{ request('last_days') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="">Semua</option>
                                                    @foreach(($statuses ?? []) as $s)
                                                        <option value="{{ $s }}" {{ ($status ?? request('status')) == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Metode Bayar</label>
                                                <select name="payment_method" class="form-control">
                                                    <option value="">Semua</option>
                                                    @foreach(($paymentMethods ?? []) as $pm)
                                                        <option value="{{ $pm }}" {{ ($paymentMethod ?? request('payment_method')) == $pm ? 'selected' : '' }}>{{ ucfirst($pm) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Kategori</label>
                                                <select name="category_id" class="form-control">
                                                    <option value="">Semua</option>
                                                    @foreach(($categories ?? []) as $cat)
                                                        <option value="{{ $cat->id }}" {{ ($categoryId ?? request('category_id')) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
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
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary btn-lg mr-2" tabindex="4">Filter</button>
                                            <button type="button" id="btnResetSummary" class="btn btn-light btn-lg">Reset</button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        @php($chips = [])
                                        @if(request('period')) @php($chips[] = 'Periode: '.ucfirst(request('period'))) @endif
                                        @if(request('date_from')) @php($chips[] = 'Dari: '.request('date_from')) @endif
                                        @if(request('date_to')) @php($chips[] = 'Ke: '.request('date_to')) @endif
                                        @if(request('status')) @php($chips[] = 'Status: '.ucfirst(request('status'))) @endif
                                        @if(request('payment_method')) @php($chips[] = 'Metode: '.ucfirst(request('payment_method'))) @endif
                                        @if(request('year')) @php($chips[] = 'Tahun: '.request('year')) @endif
                                        @if(request('month')) @php($chips[] = 'Bulan: '.($monthNames[(int)request('month')] ?? request('month'))) @endif
                                        @if(request('week_in_month')) @php($chips[] = 'Minggu: '.strtoupper(request('week_in_month'))) @endif
                                        @if(request('last_days')) @php($chips[] = 'Terakhir: '.request('last_days').' hari') @endif
                                        @if(request('category_id'))
                                            @php($c = ($categories ?? collect())->firstWhere('id', request('category_id')))
                                            @if($c) @php($chips[] = 'Kategori: '.$c->name) @endif
                                        @endif
                                        @if(request('product_id'))
                                            @php($p = ($products ?? collect())->firstWhere('id', request('product_id')))
                                            @if($p) @php($chips[] = 'Produk: '.$p->name) @endif
                                        @endif
                                        @if(count($chips))
                                            <div>
                                                @foreach($chips as $chip)
                                                    <span class="badge badge-primary mr-2">{{ $chip }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    @if ($totalRevenue ?? '')
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div class="card card-statistic-1">
                                                    <div class="card-header"><h4>Total Revenue</h4></div>
                                                    <div class="card-body">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card card-statistic-1">
                                                    <div class="card-header"><h4>Total</h4></div>
                                                    <div class="card-body">Rp {{ number_format($total, 0, ',', '.') }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-lg-8 mb-4">
                                                <div class="card">
                                                    <div class="card-header"><h4>Revenue Trend</h4></div>
                                                    <div class="card-body">
                                                        <canvas id="summaryTrendChart" height="80"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card">
                                            <div class="card-body">
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center border-bottom-0">
                                                        <strong>Total:</strong> <span>{{ number_format($total, 0, ',', '.') }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    @endif
                                </form>

                                {{-- <form action="{{ route('summary.index') }}" method="GET" class="mb-4">
                                    <div class="form-row">
                                        <div class="col">
                                            <input type="date" name="date_from" class="form-control @error('date_from') is-invalid @enderror" value="{{ old('date_from') ?? request('date_from') }}" required>
                                            @error('date_from')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        <div class="col">
                                            <input type="date" name="date_to" class="form-control @error('date_to') is-invalid @enderror" value="{{ old('date_to') ?? request('date_to') }}" required>
                                            @error('date_to')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        <div class="col">
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                        </div>
                                    </div>
                                </form> --}}

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
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>

    <!-- Page Specific JS File -->
    {{-- <script src="assets/js/page/forms-advanced-forms.js"></script> --}}
    <script src="{{ asset('js/page/forms-advanced-forms.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function savePrefs(prefix){ const f=document.querySelector('form'); const names=['date_from','date_to','period','status','payment_method','category_id','product_id']; const data={}; names.forEach(n=>{ const el=f.querySelector(`[name="${n}"]`); if(el) data[n]=el.value||''; }); localStorage.setItem(prefix, JSON.stringify(data)); }
        function loadPrefs(prefix){ const q=new URLSearchParams(location.search); if([...q.keys()].length) return; const raw=localStorage.getItem(prefix); if(!raw) return; const data=JSON.parse(raw); Object.entries(data).forEach(([k,v])=>{ const el=document.querySelector(`[name="${k}"]`); if(el && !el.value) el.value=v; }); }

        function computeRangeAdvancedSummary(period, year, month, weekOpt){
            const pad=n=>String(n).padStart(2,'0');
            const toStr=d=>`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            const clampMonth=(y,m)=>{ const s=new Date(y,m-1,1); const e=new Date(y,m,0); return {s,e}; };
            if(!period) return null;
            if(period==='harian'){ const {s,e}=clampMonth(year,month); return {from:toStr(s), to:toStr(e)}; }
            if(period==='mingguan'){
                if(weekOpt && weekOpt.startsWith('last_')){ const days=parseInt(weekOpt.split('_')[1]); const today=new Date(); const to=new Date(today.getFullYear(),today.getMonth(),today.getDate()); const from=new Date(to); from.setDate(to.getDate()-(days-1)); return {from:toStr(from), to:toStr(to)}; }
                const idx=weekOpt && weekOpt.startsWith('w') ? parseInt(weekOpt.slice(1)) : 1;
                const firstDay=new Date(year,month-1,1); const firstMonday=new Date(firstDay); const day=firstMonday.getDay(); const diff=(day===0?1:(day===1?0:(8-day))); firstMonday.setDate(1+diff);
                const start=new Date(firstMonday); start.setDate(firstMonday.getDate()+7*(idx-1)); const end=new Date(start); end.setDate(start.getDate()+6);
                const {s:ms,e:me}=clampMonth(year,month); const s=start<ms?ms:start; const e=end>me?me:end; return {from:toStr(s), to:toStr(e)};
            }
            if(period==='bulanan'){ const {s,e}=clampMonth(year,month); return {from:toStr(s), to:toStr(e)}; }
            if(period==='tahunan'){ const s=new Date(year,0,1); const e=new Date(year,11,31); return {from:toStr(s), to:toStr(e)}; }
            return null;
        }
        function updateVisibilitySummary(){
            const period=document.getElementById('periodSelectSummary')?.value||'';
            const yc=document.getElementById('yearColSummary'); const mc=document.getElementById('monthColSummary'); const wc=document.getElementById('weekColSummary'); const dr=document.getElementById('dateRangeContainerSummary');
            const toggleOthers=(show)=>{
                ['status','payment_method','category_id','product_id','user_id'].forEach(n=>{ const el=document.querySelector(`[name="${n}"]`); if(!el) return; const col=el.closest('.col-md-1, .col-md-2, .col-md-3, .col-md-6, .col-md-12'); if(col) col.style.display = show ? '' : 'none'; });
            };
            if(!period){ if(yc) yc.style.display='none'; if(mc) mc.style.display='none'; if(wc) wc.style.display='none'; if(dr) dr.style.display='none'; toggleOthers(false); return; }
            toggleOthers(true);
            if(yc) yc.style.display='block'; if(mc) mc.style.display = (period==='tahunan') ? 'none' : 'block'; if(wc) wc.style.display = (period==='mingguan') ? 'block' : 'none'; if(dr) dr.style.display = (period==='harian') ? 'block' : 'none';
        }
        function recomputeRangeSummary(){
            const period=document.getElementById('periodSelectSummary')?.value||'';
            const year=parseInt(document.getElementById('yearSelectSummary')?.value||'{{ now()->year }}');
            const month=parseInt(document.getElementById('monthSelectSummary')?.value||'{{ now()->month }}');
            const weekOpt=document.getElementById('weekOptionSelectSummary')?.value||'';
            const r=computeRangeAdvancedSummary(period,year,month,weekOpt); if(!r) return;
            const df=document.querySelector('input[name="date_from"]'); const dt=document.querySelector('input[name="date_to"]'); if(df && r.from) df.value=r.from; if(dt && r.to) dt.value=r.to;
            const wim=document.getElementById('weekInMonthInputSummary'); const ld=document.getElementById('lastDaysInputSummary'); if(wim) wim.value=(weekOpt.startsWith('w')?weekOpt:''); if(ld) ld.value=(weekOpt.startsWith('last_')?weekOpt.split('_')[1]:'');
        }
        document.getElementById('periodSelectSummary')?.addEventListener('change', ()=>{ updateVisibilitySummary(); recomputeRangeSummary(); });
        document.getElementById('yearSelectSummary')?.addEventListener('change', recomputeRangeSummary);
        document.getElementById('monthSelectSummary')?.addEventListener('change', recomputeRangeSummary);
        document.getElementById('weekOptionSelectSummary')?.addEventListener('change', recomputeRangeSummary);
        updateVisibilitySummary(); if(document.getElementById('periodSelectSummary')?.value){ recomputeRangeSummary(); }

        const trend = @json($chartTrend ?? null);
        const comp = @json($composition ?? null);
        if (trend) {
            const tctx = document.getElementById('summaryTrendChart').getContext('2d');
            new Chart(tctx, {
                type: 'line',
                data: {
                    labels: trend.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: trend.revenue,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.3,
                    }]
                }
            });
        }
        if (comp) {
            const el = document.getElementById('summaryCompositionChart');
            if (el) {
            const cctx = el.getContext('2d');
            new Chart(cctx, {
                type: 'doughnut',
                data: {
                    labels: comp.labels,
                    datasets: [{
                        data: comp.values,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(255, 206, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)'
                        ]
                    }]
                }
            });
        }}
        // reset + prefs
        document.getElementById('btnResetSummary')?.addEventListener('click', function(){ const f=document.querySelector('form'); f.querySelector('[name="period"]').value=''; ['status','payment_method','category_id','product_id'].forEach(n=>{ const el=f.querySelector(`[name="${n}"]`); if(el) el.value=''; }); const df=f.querySelector('[name="date_from"]'); const dt=f.querySelector('[name="date_to"]'); if(df) df.value=''; if(dt) dt.value=''; });
        loadPrefs('summary_filters');
        document.querySelector('form')?.addEventListener('submit', ()=>savePrefs('summary_filters'));
    </script>
@endpush
