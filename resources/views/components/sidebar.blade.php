<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand mb-5">
            <a href="/home"><img src="{{ asset('img/toga-gold-ts.png') }}" width="100" alt="TOGA POS"></a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="/home">TOGA</a>
        </div>

        @php
            $authUser = Auth::user();
            $isAdmin = $authUser?->roles === 'admin';
            $role = $currentOutletRole ?? null;
            $isOwner = $role?->role === 'owner';
            $isPartner = $role?->role === 'partner';
            $permissions = $outletPermissions ?? [
                'can_manage_stock' => true,
                'can_manage_expense' => true,
                'can_manage_sales' => true,
            ];
            $inventoryActive = Request::is('raw-materials*')
                || Request::is('units*')
                || Request::is('products/*/recipe*')
                || Request::is('products/*/produce*')
                || Request::is('catalog-duplication*');
            $reportsActive = Request::is('report*') || Request::is('summary*') || Request::is('product_sales*');
            $productMenuActive = Request::is('product') || Request::is('product/*') || Request::is('product-options*');
        @endphp

        <ul class="sidebar-menu">
            <li class="menu-header">Menu</li>

            <li class="{{ Request::is('home') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('home') }}">
                    <i class="fas fa-fire"></i> <span>Dashboard</span>
                </a>
            </li>

            @if ($isAdmin)
                <li class="menu-header">Administrator</li>

                <li class="{{ Request::is('user*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('user.index') }}">
                        <i class="fas fa-user-shield"></i> <span>Users</span>
                    </a>
                </li>

                <li class="{{ Request::is('expenses*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('expenses.index') }}">
                        <i class="fas fa-arrow-up"></i> <span>Uang Keluar</span>
                    </a>
                </li>

                <li class="nav-item dropdown {{ $inventoryActive ? 'active' : '' }}">
                    <a href="#" class="nav-link has-dropdown {{ $inventoryActive ? 'active' : '' }}">
                        <i class="fas fa-boxes-stacked"></i><span>Inventory</span>
                    </a>
                    <ul class="dropdown-menu {{ $inventoryActive ? 'show' : '' }}" style="{{ $inventoryActive ? 'display:block;' : '' }}">
                        <li class="{{ Request::is('raw-materials*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('raw-materials.index') }}">
                                <i class="fas fa-flask"></i> <span>Bahan Baku</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('catalog-duplication*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('catalog-duplication.index') }}">
                                <i class="fas fa-copy"></i> <span>Duplikasi Katalog</span>
                            </a>
                        </li>
                        {{-- <li class="{{ Request::is('units*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('units.index') }}">
                                <i class="fas fa-ruler-combined"></i> <span>Satuan</span>
                            </a>
                        </li> --}}
                        {{-- <li>
                            <a class="nav-link" href="{{ route('product.index') }}">
                                <i class="fas fa-utensils"></i> <span>Resep Produk</span>
                            </a>
                        </li> --}}
                    </ul>
                </li>

                <li class="{{ (Request::is('product') || Request::is('product/*')) ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('product.index') }}">
                        <i class="fas fa-list"></i> <span>Products</span>
                    </a>
                </li>

                @php($billingOpen = Request::is('admin/billing/payments*') || Request::is('admin/payment-accounts*') || Request::is('admin/subscriptions*'))
                <li class="nav-item dropdown {{ $billingOpen ? 'active' : '' }}">
                    <a href="#" class="nav-link has-dropdown {{ $billingOpen ? 'active' : '' }}">
                        <i class="fas fa-receipt"></i><span>Billing Admin</span>
                    </a>
                    <ul class="dropdown-menu {{ $billingOpen ? 'show' : '' }}" style="{{ $billingOpen ? 'display:block;' : '' }}">
                        <li class="{{ Request::is('admin/billing/payments*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.billing.payments.index') }}">
                                <i class="fas fa-clipboard-check"></i> <span>Verifikasi</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('admin/payment-accounts*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.billing.accounts.index') }}">
                                <i class="fas fa-piggy-bank"></i> <span>Rekening</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('admin/subscriptions*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.subscriptions.index') }}">
                                <i class="fas fa-user-shield"></i> <span>Langganan User</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            @if ($authUser && $authUser->roles === 'user')
                <li class="menu-header">{{ $isPartner ? 'Menu Mitra' : 'Operasional' }}</li>

                @if ($isOwner)
                    <li class="{{ Request::is('outlets*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('outlets.index') }}">
                            <i class="fas fa-store"></i> <span>Outlet & Mitra</span>
                        </a>
                    </li>

                    <li class="{{ Request::is('billing*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('billing.index') }}">
                            <i class="fas fa-credit-card"></i> <span>Langganan</span>
                        </a>
                    </li>
                @endif

                {{-- Produk & Kategori selalu terlihat untuk owner maupun partner --}}
                @if ($isOwner)
                    <li class="nav-item dropdown {{ $productMenuActive ? 'active' : '' }}">
                        <a href="#" class="nav-link has-dropdown {{ $productMenuActive ? 'active' : '' }}">
                            <i class="fas fa-box-open"></i><span>Produk</span>
                        </a>
                        <ul class="dropdown-menu {{ $productMenuActive ? 'show' : '' }}" style="{{ $productMenuActive ? 'display:block;' : '' }}">
                            <li class="{{ Request::is('product') || Request::is('product/*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('product.index') }}">
                                    <i class="fas fa-list"></i> <span>Daftar Produk</span>
                                </a>
                            </li>
                            <li class="{{ Request::is('product-options*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('product-options.index') }}">
                                    <i class="fas fa-sliders-h"></i> <span>Product Options</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="{{ Request::is('product') || Request::is('product/*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('product.index') }}">
                            <i class="fas fa-box-open"></i> <span>Produk</span>
                        </a>
                    </li>
                @endif

                <li class="{{ Request::is('category*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('category.index') }}">
                        <i class="fas fa-layer-group"></i> <span>Kategori</span>
                    </a>
                </li>

                @if ($isOwner || $isAdmin)
                    <li class="{{ Request::is('discount*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('discount.index') }}">
                            <i class="fas fa-tags"></i> <span>Diskon</span>
                        </a>
                    </li>
                @endif

                @if ($isOwner || $permissions['can_manage_sales'])
                    <li class="{{ Request::is('order*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('order.index') }}">
                            <i class="fas fa-receipt"></i> <span>Orders</span>
                        </a>
                    </li>
                @endif

                @if ($isOwner || $permissions['can_manage_expense'])
                    <li class="{{ Request::is('expenses*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('expenses.index') }}">
                            <i class="fas fa-wallet"></i> <span>Uang Keluar</span>
                        </a>
                    </li>
                @endif

                @if ($isOwner || $permissions['can_manage_stock'])
                    <li class="nav-item dropdown {{ $inventoryActive ? 'active' : '' }}">
                        <a href="#" class="nav-link has-dropdown {{ $inventoryActive ? 'active' : '' }}">
                            <i class="fas fa-boxes-stacked"></i><span>Inventory</span>
                        </a>
                        <ul class="dropdown-menu {{ $inventoryActive ? 'show' : '' }}" style="{{ $inventoryActive ? 'display:block;' : '' }}">
                            <li class="{{ Request::is('raw-materials*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('raw-materials.index') }}">
                                    <i class="fas fa-flask"></i> <span>Bahan Baku</span>
                                </a>
                            </li>
                            @if ($isOwner)
                                <li class="{{ Request::is('catalog-duplication*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('catalog-duplication.index') }}">
                                        <i class="fas fa-copy"></i> <span>Duplikasi Katalog</span>
                                    </a>
                                </li>
                            @endif
                            {{-- @if ($isOwner)
                                <li class="{{ Request::is('units*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('units.index') }}">
                                        <i class="fas fa-ruler-combined"></i> <span>Satuan</span>
                                    </a>
                                </li>
                            @endif --}}
                        </ul>
                    </li>
                @endif

                <li class="nav-item dropdown {{ $reportsActive ? 'active' : '' }}">
                    <a href="#" class="nav-link has-dropdown {{ $reportsActive ? 'active' : '' }}" aria-expanded="{{ $reportsActive ? 'true' : 'false' }}">
                        <i class="fas fa-chart-line"></i><span>Reports</span>
                    </a>
                    <ul class="dropdown-menu {{ $reportsActive ? 'show' : '' }}" style="{{ $reportsActive ? 'display:block;' : '' }}">
                        <li class="{{ Request::is('report') || Request::is('report/filter') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('report.index') }}">
                                <i class="fas fa-book-open"></i> <span>Ringkasan Order</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('report/by-category*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('report.byCategory') }}">
                                <i class="fas fa-layer-group"></i> <span>Order per Kategori</span>
                            </a>
                        </li>
                        {{-- Tempatkan report tambahan bila dibutuhkan --}}
                    </ul>
                </li>
            @endif
        </ul>
    </aside>
</div>
