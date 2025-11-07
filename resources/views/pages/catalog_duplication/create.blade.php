@extends('layouts.app')

@section('title', 'Duplikasi Katalog')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Duplikasi Katalog</h1>
    </div>
    <div class="section-body">
      @include('layouts.alert')
      <div class="card">
        <form action="{{ route('catalog-duplication.store') }}" method="POST">
          @csrf
          <div class="card-header">
            <h4>Pilih Outlet & Data</h4>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label for="source_outlet_id">Outlet Sumber</label>
              <select name="source_outlet_id" id="source_outlet_id" class="form-control @error('source_outlet_id') is-invalid @enderror" required>
                <option value="">- Pilih outlet sumber -</option>
                @foreach($ownedOutlets as $outlet)
                  <option value="{{ $outlet->id }}" {{ old('source_outlet_id', $currentOutlet?->id) == $outlet->id ? 'selected' : '' }}>
                    {{ $outlet->name }}
                  </option>
                @endforeach
              </select>
              @error('source_outlet_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label for="target_outlet_id">Outlet Tujuan</label>
              <select name="target_outlet_id" id="target_outlet_id" class="form-control @error('target_outlet_id') is-invalid @enderror" required>
                <option value="">- Pilih outlet tujuan -</option>
                @foreach($ownedOutlets as $outlet)
                  <option value="{{ $outlet->id }}" {{ old('target_outlet_id') == $outlet->id ? 'selected' : '' }}>
                    {{ $outlet->name }}
                  </option>
                @endforeach
              </select>
              @error('target_outlet_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label>Data yang akan diduplikasi</label>
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="duplicate_categories" name="resources[categories]" value="1" {{ old('resources.categories') ? 'checked' : '' }}>
                <label class="custom-control-label" for="duplicate_categories">Kategori</label>
                <button type="button" class="btn btn-sm btn-link" data-role="select-all" data-target="category">Pilih semua</button>
              </div>
              <div id="category-list" class="mt-2 border rounded p-3 bg-light">
                <p class="text-muted small mb-0" data-role="empty">Pilih outlet sumber terlebih dahulu.</p>
                <div data-role="items" class="columns-2"></div>
              </div>
            </div>

            <div class="form-group">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="duplicate_raw_materials" name="resources[raw_materials]" value="1" {{ old('resources.raw_materials') ? 'checked' : '' }}>
                <label class="custom-control-label" for="duplicate_raw_materials">Bahan Baku</label>
                <button type="button" class="btn btn-sm btn-link" data-role="select-all" data-target="raw-material">Pilih semua</button>
              </div>
              <div id="raw-material-list" class="mt-2 border rounded p-3 bg-light">
                <p class="text-muted small mb-0" data-role="empty">Pilih outlet sumber terlebih dahulu.</p>
                <div data-role="items" class="columns-2"></div>
              </div>
            </div>

            <div class="form-group">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="duplicate_products" name="resources[products]" value="1" {{ old('resources.products') ? 'checked' : '' }}>
                <label class="custom-control-label" for="duplicate_products">Produk</label>
                <button type="button" class="btn btn-sm btn-link" data-role="select-all" data-target="product">Pilih semua</button>
              </div>
              <div id="product-list" class="mt-2 border rounded p-3 bg-light">
                <p class="text-muted small mb-0" data-role="empty">Pilih outlet sumber terlebih dahulu.</p>
                <div data-role="items" class="columns-2"></div>
              </div>
            </div>

            <div class="form-group">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="copy_stock" name="options[copy_stock]" value="1" {{ old('options.copy_stock') ? 'checked' : '' }}>
                <label class="custom-control-label" for="copy_stock">Salin stok awal bahan baku</label>
              </div>
              <small class="form-text text-muted">Jika dicentang, stok di outlet sumber akan disalin ke outlet tujuan sebagai stok awal.</small>
            </div>
          </div>
          <div class="card-footer text-right">
            <a href="{{ route('catalog-duplication.index') }}" class="btn btn-light mr-2">Batal</a>
            <button class="btn btn-primary">Mulai Duplikasi</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>
@endsection

@push('scripts')
@php
  $oldCategoryIds = array_map('intval', old('resources.category_ids', []));
  $oldRawMaterialIds = array_map('intval', old('resources.raw_material_ids', []));
  $oldProductIds = array_map('intval', old('resources.product_ids', []));
@endphp
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const sourceSelect = document.getElementById('source_outlet_id');
    const sectionMap = {
      category: {
        container: document.getElementById('category-list'),
        checkbox: document.getElementById('duplicate_categories'),
        name: 'resources[category_ids][]',
        oldValues: @json($oldCategoryIds),
        label: (item) => item.name
      },
      'raw-material': {
        container: document.getElementById('raw-material-list'),
        checkbox: document.getElementById('duplicate_raw_materials'),
        name: 'resources[raw_material_ids][]',
        oldValues: @json($oldRawMaterialIds),
        label: (item) => `${item.name} (${item.unit})`
      },
      product: {
        container: document.getElementById('product-list'),
        checkbox: document.getElementById('duplicate_products'),
        name: 'resources[product_ids][]',
        oldValues: @json($oldProductIds),
        label: (item) => item.name
      }
    };

    Object.keys(sectionMap).forEach((key) => {
      sectionMap[key].oldValues = (sectionMap[key].oldValues || []).map((value) => Number(value));
    });

    const toggleCheckboxFlag = (sectionKey) => {
      const section = sectionMap[sectionKey];
      if (!section) return;
      const inputs = section.container.querySelectorAll('input[type="checkbox"][name="' + section.name + '"]');
      const hasChecked = Array.from(inputs).some(input => input.checked);
      section.checkbox.checked = hasChecked;
    };

    const bindItemListeners = (sectionKey) => {
      const section = sectionMap[sectionKey];
      const itemsContainer = section.container.querySelector('[data-role="items"]');
      itemsContainer.querySelectorAll('input[type="checkbox"]').forEach((input) => {
        input.addEventListener('change', () => toggleCheckboxFlag(sectionKey));
      });
      if (section.checkbox && !section.checkbox.dataset.bound) {
        section.checkbox.addEventListener('change', (event) => {
          if (!event.target.checked) {
            itemsContainer.querySelectorAll('input[type="checkbox"]').forEach((input) => {
              input.checked = false;
            });
          }
        });
        section.checkbox.dataset.bound = '1';
      }
    };

    const renderItems = (sectionKey, items) => {
      const section = sectionMap[sectionKey];
      const emptyState = section.container.querySelector('[data-role="empty"]');
      const itemsContainer = section.container.querySelector('[data-role="items"]');
      itemsContainer.innerHTML = '';

      if (!items.length) {
        emptyState.textContent = 'Tidak ada data tersedia.';
        emptyState.classList.remove('d-none');
        section.checkbox.checked = false;
        return;
      }

      emptyState.classList.add('d-none');

      items.forEach(item => {
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-control custom-checkbox mb-1';

        const input = document.createElement('input');
        input.type = 'checkbox';
        input.className = 'custom-control-input';
        input.id = `${sectionKey}-${item.id}`;
        input.name = section.name;
        input.value = item.id;
        if (section.oldValues.includes(Number(item.id))) {
          input.checked = true;
        }

        const label = document.createElement('label');
        label.className = 'custom-control-label';
        label.setAttribute('for', input.id);
        label.textContent = section.label(item);

        wrapper.appendChild(input);
        wrapper.appendChild(label);
        itemsContainer.appendChild(wrapper);
      });

      bindItemListeners(sectionKey);
      toggleCheckboxFlag(sectionKey);
    };

    const loadData = async (outletId) => {
      Object.keys(sectionMap).forEach((key) => {
        sectionMap[key].oldValues = [];
      });
      const url = new URL('{{ route('catalog-duplication.source-data') }}', window.location.origin);
      url.searchParams.set('outlet_id', outletId);

      Object.keys(sectionMap).forEach((key) => {
        const section = sectionMap[key];
        const emptyState = section.container.querySelector('[data-role="empty"]');
        const itemsContainer = section.container.querySelector('[data-role="items"]');
        emptyState.textContent = 'Memuat...';
        emptyState.classList.remove('d-none');
        itemsContainer.innerHTML = '';
      });

      try {
        const response = await fetch(url.toString(), {
          headers: {
            'Accept': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error('Gagal memuat data outlet sumber.');
        }

        const data = await response.json();
        renderItems('category', data.categories || []);
        renderItems('raw-material', data.raw_materials || []);
        renderItems('product', data.products || []);
      } catch (error) {
        Object.keys(sectionMap).forEach((key) => {
          const section = sectionMap[key];
          const emptyState = section.container.querySelector('[data-role="empty"]');
          const itemsContainer = section.container.querySelector('[data-role="items"]');
          itemsContainer.innerHTML = '';
          emptyState.textContent = error.message;
          emptyState.classList.remove('d-none');
        });
      }
    };

    document.querySelectorAll('[data-role="select-all"]').forEach((btn) => {
      btn.addEventListener('click', (event) => {
        event.preventDefault();
        const targetKey = btn.dataset.target;
        const section = sectionMap[targetKey];
        if (!section) return;
        const itemsContainer = section.container.querySelector('[data-role="items"]');
        const inputs = itemsContainer.querySelectorAll('input[type="checkbox"]');
        const shouldSelect = Array.from(inputs).some(input => !input.checked);
        inputs.forEach((input) => { input.checked = shouldSelect; });
        section.checkbox.checked = shouldSelect && inputs.length > 0;
        toggleCheckboxFlag(targetKey);
      });
    });

    sourceSelect?.addEventListener('change', (event) => {
      const outletId = event.target.value;
      if (!outletId) {
        Object.keys(sectionMap).forEach((key) => {
          const section = sectionMap[key];
          section.container.querySelector('[data-role="items"]').innerHTML = '';
          const emptyState = section.container.querySelector('[data-role="empty"]');
          emptyState.textContent = 'Pilih outlet sumber terlebih dahulu.';
          emptyState.classList.remove('d-none');
          section.checkbox.checked = false;
        });
        return;
      }
      loadData(outletId);
    });

    if (sourceSelect?.value) {
      loadData(sourceSelect.value);
    }
  });
</script>
@endpush
