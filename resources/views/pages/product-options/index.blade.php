@extends('layouts.app')

@section('title', 'Product Options')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Product Options</h1>
                <div class="section-header-button">
                    <a href="{{ route('product-options.create', ['type' => $type]) }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i>Buat {{ ucfirst($type) }}
                    </a>
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item active">Product Options</div>
                </div>
            </div>

            <div class="section-body">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Kelola Product Options</h4>
                    </div>
                    <div class="card-body">
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
                                        <a class="nav-link {{ $type === $tabType ? 'active' : '' }}" href="{{ route('product-options.index', ['type' => $tabType]) }}">
                                            {{ $label }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 26%">Nama</th>
                                        <th style="width: 20%">Pengaturan</th>
                                        <th style="width: 22%">Opsional</th>
                                        <th style="width: 18%">Terakhir Diperbarui</th>
                                        <th style="width: 14%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($optionGroups as $group)
                                        @php
                                            $meta = [];
                                            $meta[] = $group->selection_type === 'multiple' ? 'Multiple' : 'Single';
                                            $meta[] = $group->is_required ? 'Wajib' : 'Opsional';
                                            if (!is_null($group->min_select)) {
                                                $meta[] = 'Min ' . $group->min_select;
                                            }
                                            if (!is_null($group->max_select)) {
                                                $meta[] = 'Max ' . $group->max_select;
                                            }
                                            $items = $group->items ?? collect();
                                        @endphp
                                        <tr>
                                            <td>
                                                <h6 class="mb-1 font-weight-semibold">{{ $group->name }}</h6>
                                                <div class="text-muted small">
                                                    {{ $items->count() }} opsi • Dimiliki oleh {{ $group->outlet?->name ?? 'Anda' }}
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-light">{{ implode(' • ', $meta) }}</span>
                                            </td>
                                            <td>
                                                @if($items->isEmpty())
                                                    <span class="text-muted small">Belum ada opsi.</span>
                                                @else
                                                    <ul class="list-unstyled mb-0 small">
                                                        @foreach($items->take(3) as $item)
                                                            <li>
                                                                <strong>{{ $item->name }}</strong>
                                                                @php
                                                                    $detail = [];
                                                                    $price = $item->price_adjustment ?? 0;
                                                                    $detail[] = $price == 0 ? 'Harga Rp0' : ($price > 0 ? 'Harga +' : 'Harga -') . 'Rp' . number_format(abs($price), 0, ',', '.');
                                                                    if ($type === 'variant') {
                                                                        if (!is_null($item->stock)) {
                                                                            $detail[] = 'Stok ' . $item->stock;
                                                                        }
                                                                        if ($item->sku) {
                                                                            $detail[] = 'SKU ' . $item->sku;
                                                                        }
                                                                    } else {
                                                                        if (!is_null($item->max_quantity)) {
                                                                            $detail[] = 'Qty Maks ' . $item->max_quantity;
                                                                        }
                                                                    if ($type === 'addon') {
                                                                        if ($item->product) {
                                                                            $label = $item->product->name;
                                                                            if (($productHasSku ?? false) && !empty($item->product->sku ?? null)) {
                                                                                $label .= ' (' . $item->product->sku . ')';
                                                                            }
                                                                            $detail[] = 'Produk: ' . $label;
                                                                        } elseif ($item->product_id) {
                                                                            $detail[] = 'Produk ID: ' . $item->product_id;
                                                                        }
                                                                        if ($item->use_product_price) {
                                                                            $detail[] = 'Harga mengikuti produk';
                                                                        }
                                                                    }
                                                                    }
                                                                    if ($item->is_default) {
                                                                        $detail[] = 'Default';
                                                                    }
                                                                    if (!($item->is_active ?? true)) {
                                                                        $detail[] = 'Nonaktif';
                                                                    }
                                                                @endphp
                                                                <span class="text-muted">• {{ implode(' • ', $detail) }}</span>
                                                            </li>
                                                        @endforeach
                                                        @if($items->count() > 3)
                                                            <li class="text-muted">+{{ $items->count() - 3 }} opsi lainnya…</li>
                                                        @endif
                                                    </ul>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="d-block">{{ optional($group->updated_at)->format('d M Y H:i') ?? '—' }}</span>
                                                <span class="text-muted small">{{ optional($group->updated_at)->diffForHumans() }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('product-options.edit', $group) }}" class="btn btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('product-options.destroy', $group) }}" method="POST" onsubmit="return confirm('Hapus Product Option ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-5">
                                                <i class="fas fa-sliders-h fa-2x mb-3"></i>
                                                <p class="mb-1">Belum ada Product Option {{ ucfirst($type) }}.</p>
                                                <a href="{{ route('product-options.create', ['type' => $type]) }}" class="btn btn-outline-primary btn-sm mt-2">
                                                    <i class="fas fa-plus mr-1"></i>Buat {{ ucfirst($type) }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($optionGroups->hasPages())
                        <div class="card-footer text-right">
                            {{ $optionGroups->appends(['type' => $type])->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection
