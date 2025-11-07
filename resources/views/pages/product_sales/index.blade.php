@extends('layouts.app')

@section('title', 'Report')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
    <link rel="stylesheet" href="{{ asset('library/datatables/media/css/jquery.dataTables.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Product Sales</h1>

                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Product Sales</a></div>
                    <div class="breadcrumb-item">Product Sales</div>
                </div>
            </div>
            <div class="section-body">


                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Product Sales</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('productSales.index') }}" method="GET">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Periode <span class="text-muted" title="Pilih periode terlebih dahulu, lalu filter lainnya akan muncul">?</span></label>
                                                <select name="period" class="form-control" id="periodSelectPS">
                                                    <option value="harian" {{ request('period')=='harian' ? 'selected' : '' }}>Harian</option>
                                                    <option value="mingguan" {{ request('period')=='mingguan' ? 'selected' : '' }}>Mingguan</option>
                                                    <option value="bulanan" {{ request('period')=='bulanan' ? 'selected' : '' }}>Bulanan</option>
                                                    <option value="tahunan" {{ request('period')=='tahunan' ? 'selected' : '' }}>Tahunan</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div id="dateRangeContainerPS" class="col-md-5">
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
                                                    @error('date_to')<div class="alert alert-danger">{{ $message }}</div>@enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="yearColPS" style="display:none;">
                                            <div class="form-group">
                                                <label>Tahun</label>
                                                @php($currentYear = (int) (old('year') ?? ($year ?? request('year') ?? now()->year)))
                                                <select name="year" id="yearSelectPS" class="form-control">
                                                    @for($y = $currentYear + 1; $y >= $currentYear - 5; $y--)
                                                        <option value="{{ $y }}" {{ $currentYear==$y ? 'selected' : '' }}>{{ $y }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="monthColPS" style="display:none;">
                                            <div class="form-group">
                                                <label>Bulan</label>
                                                @php($currentMonth = (int) (old('month') ?? ($month ?? request('month') ?? now()->month)))
                                                @php($monthNames = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'])
                                                <select name="month" id="monthSelectPS" class="form-control">
                                                    @for($m=1;$m<=12;$m++)
                                                        <option value="{{ $m }}" {{ $currentMonth==$m ? 'selected' : '' }}>{{ $monthNames[$m] }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="weekColPS" style="display:none;">
                                            <div class="form-group">
                                                <label>Opsi Mingguan</label>
                                                <select id="weekOptionSelectPS" class="form-control">
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
                                                <input type="hidden" name="week_in_month" id="weekInMonthInputPS" value="{{ request('week_in_month') }}">
                                                <input type="hidden" name="last_days" id="lastDaysInputPS" value="{{ request('last_days') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
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
                                            <button type="button" id="btnResetPS" class="btn btn-light btn-lg">Reset</button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        @php($chips = [])
                                        @if(request('period')) @php($chips[] = 'Periode: '.ucfirst(request('period'))) @endif
                                        @if(request('date_from')) @php($chips[] = 'Dari: '.request('date_from')) @endif
                                        @if(request('date_to')) @php($chips[] = 'Ke: '.request('date_to')) @endif
                                        @if(request('category_id'))
                                            @php($c = ($categories ?? collect())->firstWhere('id', request('category_id')))
                                            @if($c) @php($chips[] = 'Kategori: '.$c->name) @endif
                                        @endif
                                        @if(request('product_id'))
                                            @php($p = ($products ?? collect())->firstWhere('id', request('product_id')))
                                            @if($p) @php($chips[] = 'Produk: '.$p->name) @endif
                                        @endif
                                        @if(request('year')) @php($chips[] = 'Tahun: '.request('year')) @endif
                                        @if(request('month')) @php($chips[] = 'Bulan: '.($monthNames[(int)request('month')] ?? request('month'))) @endif
                                        @if(request('week_in_month')) @php($chips[] = 'Minggu: '.strtoupper(request('week_in_month'))) @endif
                                        @if(request('last_days')) @php($chips[] = 'Terakhir: '.request('last_days').' hari') @endif
                                        @if(count($chips))
                                            <div>
                                                @foreach($chips as $chip)
                                                    <span class="badge badge-primary mr-2">{{ $chip }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <div class="card">
                                        <div class="card-body">
                                            @if ($totalProductSold ?? '')
                                                <div class="mb-4">
                                                    <canvas id="productSalesChart" height="100"></canvas>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div></div>
                                                    <button type="button" id="btnExportPS" class="btn btn-outline-primary">Export View (CSV)</button>
                                                </div>
                                                <div class="table-responsive">
                                                    <table id="productSalesTable" class="table table-striped table-bordered text-center">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>No</th>
                                                                <th>Product</th>
                                                                <th>Total Quantity</th>
                                                                <th>Total Price</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($totalProductSold as $productSold)
                                                                <tr>
                                                                    <td>{{ $loop->iteration }}</td>
                                                                    <td>{{ $productSold->product_name }}</td>
                                                                    <td>{{ $productSold->total_quantity }}</td>
                                                                    <td>{{ number_format($productSold->total_price, 0, ',', '.') }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                        <tfoot>
                                                            <tr>
                                                                <th colspan="2" class="text-right">Total</th>
                                                                <th id="ftQtyPS"></th>
                                                                <th id="ftRevPS"></th>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <input type="date" hidden name="date_from"
                                                                value="{{ old('date_from') ?? ($date_from ?? request()->query('date_from')) }}"
                                                                class="form-control datepicker">
                                                        </div>
                                                        @error('date_from')
                                                            <div class="alert alert-danger">
                                                                {{ $message }}
                                                            </div>
                                                        @enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <input type="date" hidden name="date_to"
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

                                            </form>

                                            @endif
                                        </div>
                                    </div>
                                    <div class="row float-right w-100">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <a href="{{ route('productSales.download', ['date_from' => request()->query('date_from'), 'date_to' => request()->query('date_to')]) }}"
                                                   class="btn btn-primary btn-lg btn-block">
                                                    Download
                                                </a>
                                            </div>
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
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>

    <!-- Page Specific JS File -->
    {{-- <script src="assets/js/page/forms-advanced-forms.js"></script> --}}
    <script src="{{ asset('js/page/forms-advanced-forms.js') }}"></script>
    <script src="{{ asset('library/datatables/media/js/jquery.dataTables.js') }}"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function parseCurrency(str){ if(!str) return 0; return parseInt(String(str).replace(/[^0-9\-]/g,'')) || 0; }
        let psChart;
        const psData = @json($chart ?? null);
        if (psData) {
            const pctx = document.getElementById('productSalesChart').getContext('2d');
            psChart = new Chart(pctx, {
                type: 'bar',
                data: {
                    labels: psData.labels,
                    datasets: [
                        { label: 'Quantity', data: psData.quantity, backgroundColor: 'rgba(54,162,235,0.5)', borderColor: 'rgba(54,162,235,1)', yAxisID: 'y1' },
                        { label: 'Revenue', data: psData.revenue, backgroundColor: 'rgba(255,159,64,0.5)', borderColor: 'rgba(255,159,64,1)', yAxisID: 'y2' }
                    ]
                },
                options: { responsive: true, scales: { y1:{ type:'linear', position:'left', title:{ display:true, text:'Qty'} }, y2:{ type:'linear', position:'right', grid:{ drawOnChartArea:false}, title:{ display:true, text:'Revenue'} } } }
            });
        }
        function computeRangeAdvancedPS(period, year, month, weekOpt){
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
        function updateVisibilityPS(){
            const period=document.getElementById('periodSelectPS')?.value||'';
            const yc=document.getElementById('yearColPS'); const mc=document.getElementById('monthColPS'); const wc=document.getElementById('weekColPS'); const dr=document.getElementById('dateRangeContainerPS');
            const toggleOthers=(show)=>{
                ['status','payment_method','category_id','product_id','user_id'].forEach(n=>{ const el=document.querySelector(`[name="${n}"]`); if(!el) return; const col=el.closest('.col-md-1, .col-md-2, .col-md-3, .col-md-6, .col-md-12'); if(col) col.style.display = show ? '' : 'none'; });
            };
            if(!period){ if(yc) yc.style.display='none'; if(mc) mc.style.display='none'; if(wc) wc.style.display='none'; if(dr) dr.style.display='none'; toggleOthers(false); return; }
            toggleOthers(true);
            if(yc) yc.style.display='block'; if(mc) mc.style.display = (period==='tahunan') ? 'none' : 'block'; if(wc) wc.style.display = (period==='mingguan') ? 'block' : 'none'; if(dr) dr.style.display = (period==='harian') ? 'block' : 'none';
        }
        function recomputeRangePS(){
            const period=document.getElementById('periodSelectPS')?.value||'';
            const year=parseInt(document.getElementById('yearSelectPS')?.value||'{{ now()->year }}');
            const month=parseInt(document.getElementById('monthSelectPS')?.value||'{{ now()->month }}');
            const weekOpt=document.getElementById('weekOptionSelectPS')?.value||'';
            const r=computeRangeAdvancedPS(period,year,month,weekOpt); if(!r) return;
            const df=document.querySelector('input[name="date_from"]'); const dt=document.querySelector('input[name="date_to"]'); if(df && r.from) df.value=r.from; if(dt && r.to) dt.value=r.to;
            const wim=document.getElementById('weekInMonthInputPS'); const ld=document.getElementById('lastDaysInputPS'); if(wim) wim.value=(weekOpt.startsWith('w')?weekOpt:''); if(ld) ld.value=(weekOpt.startsWith('last_')?weekOpt.split('_')[1]:'');
        }
        document.getElementById('periodSelectPS')?.addEventListener('change', ()=>{ updateVisibilityPS(); recomputeRangePS(); });
        document.getElementById('yearSelectPS')?.addEventListener('change', recomputeRangePS);
        document.getElementById('monthSelectPS')?.addEventListener('change', recomputeRangePS);
        document.getElementById('weekOptionSelectPS')?.addEventListener('change', recomputeRangePS);
        updateVisibilityPS(); if(document.getElementById('periodSelectPS')?.value){ recomputeRangePS(); }

        function savePrefs(prefix){ const f=document.querySelector('form'); const names=['date_from','date_to','period','category_id','product_id']; const data={}; names.forEach(n=>{ const el=f.querySelector(`[name="${n}"]`); if(el) data[n]=el.value||''; }); localStorage.setItem(prefix, JSON.stringify(data)); }
        function loadPrefs(prefix){ const q=new URLSearchParams(location.search); if([...q.keys()].length) return; const raw=localStorage.getItem(prefix); if(!raw) return; const data=JSON.parse(raw); Object.entries(data).forEach(([k,v])=>{ const el=document.querySelector(`[name="${k}"]`); if(el && !el.value) el.value=v; }); }
        function exportDataTableCSV(table, filename){ const rows=[]; const headers=[]; $(table.table().header()).find('th').each(function(){ headers.push($(this).text().trim()); }); rows.push(headers.join(',')); table.rows({search:'applied'}).every(function(){ const cols=[]; $(this.node()).find('td').each(function(){ cols.push('"'+$(this).text().trim().replace(/"/g,'""')+'"'); }); rows.push(cols.join(',')); }); const blob=new Blob([rows.join('\n')],{type:'text/csv;charset=utf-8;'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=filename; a.click(); }

        $(function(){
            loadPrefs('product_sales_filters');
            const dt = $('#productSalesTable').DataTable({ paging:true, info:true });
            function updateAll(){ if(!psChart) return; const qtyBy={}, revBy={};
                dt.rows({ search:'applied' }).every(function(){ const $r=$(this.node()); const tds=$r.find('td'); const label=$(tds.get(1)).text().trim(); const qty=parseInt($(tds.get(2)).text())||0; const rev=parseCurrency($(tds.get(3)).text()); qtyBy[label]=(qtyBy[label]||0)+qty; revBy[label]=(revBy[label]||0)+rev; });
                const labels = Object.keys(qtyBy);
                psChart.data.labels = labels;
                psChart.data.datasets[0].data = labels.map(l=>qtyBy[l]);
                psChart.data.datasets[1].data = labels.map(l=>revBy[l]);
                psChart.update('none');
                let tq=0,tr=0; dt.rows({search:'applied'}).every(function(){ const tds=$(this.node()).find('td'); tq+=parseInt($(tds.get(2)).text())||0; tr+=parseCurrency($(tds.get(3)).text()); }); $('#ftQtyPS').text(tq.toLocaleString('id-ID')); $('#ftRevPS').text(tr.toLocaleString('id-ID'));
            }
            dt.on('draw', updateAll); updateAll();
            $('#btnExportPS').on('click', ()=>exportDataTableCSV(dt,'product_sales_view.csv'));
            $('#btnResetPS').on('click', function(){ const f=document.querySelector('form'); f.querySelector('[name="period"]').value=''; ['category_id','product_id'].forEach(n=>{ const el=f.querySelector(`[name="${n}"]`); if(el) el.value=''; }); const df=f.querySelector('[name="date_from"]'); const dtm=f.querySelector('[name="date_to"]'); if(df) df.value=''; if(dtm) dtm.value=''; });
            document.querySelector('form')?.addEventListener('submit', ()=>savePrefs('product_sales_filters'));
        });
    </script>
@endpush
