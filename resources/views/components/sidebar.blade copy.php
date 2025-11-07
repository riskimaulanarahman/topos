<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand mb-5">
            <a href="/home"><img src="{{ asset('img/roar-logo.png') }}" width="100"></a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="/home">PB</a>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-header">Menu</li>
            <li class={{ Request::is('home') ? 'active' : '' }}>
                <a class="nav-link" href="{{ route('home') }}">
                <i class="fas fa-fire"></i> <span>Dashboard</span></a>
            </li>
            <li class={{ Request::is('user*') ? 'active' : '' }}>
                <a class="nav-link" href="{{ route('user.index') }}">
                <i class="fas fa-user"></i> <span>Users</span></a>
            </li>
            <li class={{ Request::is('product') || Request::is('product/*') ? 'active' : '' }}>
                <a class="nav-link" href="{{ route('product.index') }}">
                <i class="fas fa-shopping-bag"></i> <span>Products</span></a>
            </li>
            <li class={{ Request::is('category*') ? 'active' : '' }}>
                <a class="nav-link" href="{{ route('category.index') }}">
                <i class="fas fa-cart-shopping"></i> <span>Categories</span></a>
            </li>
            <li class={{ Request::is('discount*') ? 'active' : '' }}>
                <a class="nav-link" href="{{ route('discount.index') }}">
                <i class="fas fa-gift"></i> <span>Discounts</span></a>
            </li>
            <li class={{ Request::is('additional_charge*') ? 'active' : '' }}>
                <a class="nav-link" href="{{ route('additional_charge.index') }}">
                <i class="fas fa-file-invoice-dollar"></i> <span>Additional Charges</span></a>
            </li>
            <li class={{ Request::is('order*') ? 'active' : '' }}>
                <a class="nav-link" href="{{ route('order.index') }}">
                <i class="fas fa-truck-fast"></i> <span>Orders</span></a>
            </li>
            {{-- <li class="menu-header">Report</li>
            <li class={{ Request::is('report*') ? 'active' : '' }}>
                <a class="nav-link" href="{{ route('report.index') }}">
                <i class="fas fa-book"></i> <span>Report</span></a>
            </li> --}}
            <li class="nav-item dropdown">
                <a href="#" class="nav-link has-dropdown "><i class="fas fa-dollar"></i><span>Finance</span></a>
                <ul class="dropdown-menu">
                    <li class="{{ Request::is('income*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('income.index') }}">
                            <i class="fas fa-arrow-down"></i> <span>Uang Masuk</span></a>
                    </li>
                    {{-- <li class="{{ Request::is('keluar*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('finance.keluar') }}">
                            <i class="fas fa-arrow-up"></i> <span>Uang Keluar</span></a>
                    </li> --}}

                </ul>
            </li>
            <li class="nav-item dropdown">
                <a href="#" class="nav-link has-dropdown "><i class="fas fa-book"></i><span>Reports</span></a>
                <ul class="dropdown-menu">
                    <li class="{{ Request::is('report*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('report.index') }}">
                            <i class="fas fa-book-open"></i> <span>Report Order</span></a>
                    </li>
                    <li class="{{ Request::is('summary*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('summary.index') }}">
                            <i class="fas fa-chart-pie"></i> <span>Summary</span></a>
                    </li>
                    <li class="{{ Request::is('product_sales*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('product_sales.index') }}">
                            <i class="fas fa-bar-chart"></i> <span>Product Sales</span></a>
                    </li>

                </ul>
            </li>
    </aside>
</div>
