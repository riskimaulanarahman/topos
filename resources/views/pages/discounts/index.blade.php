@extends('layouts.app')

@section('title', 'Discounts')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Discounts</h1>
                <div class="section-header-button">
                    <a href="{{ route('discount.create') }}" class="btn btn-primary">Add New</a>
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Discounts</a></div>
                    <div class="breadcrumb-item">All Discount</div>
                </div>
            </div>
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>
                {{-- <h2 class="section-title">Users</h2>
                <p class="section-lead">
                    You can manage all Users, such as editing, deleting and more.
                </p> --}}


                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">

                            <div class="card-body">
                                <div class="float-left">
                                    <div class="card-header">
                                        <h4>All Discount</h4>
                                    </div>
                                </div>
                                <div class="float-right mt-2">
                                    <form method="GET" action="{{ route('discount.index') }}">
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Search" name="name">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="clearfix mb-3"></div>

                                <div class="table-responsive">
                                    <table class="table-striped table">
                                        <tr>

                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Type</th>
                                            <th>Value</th>
                                            <th>Status</th>
                                            <th>Scope</th>
                                            <th>Auto Apply</th>
                                            <th>Priority</th>
                                            <th>Expired At</th>
                                            <th>Action</th>
                                        </tr>
                                        @foreach ($discounts as $discount)
                                            <tr>

                                                <td>{{ $discount->name }}
                                                </td>
                                                <td>
                                                    {{ $discount->description }}
                                                </td>
                                                <td>
                                                    {{ $discount->type }}
                                                </td>
                                                <td>
                                                    {{ number_format($discount->value, 0, ',', '.') }}
                                                </td>
                                                <td>{{ $discount->status }}</td>
                                                <td>{{ ucfirst($discount->scope ?? 'global') }}</td>
                                                <td>
                                                    @if ((int) ($discount->auto_apply ?? 0) === 1)
                                                        <span class="badge badge-success">Auto</span>
                                                    @else
                                                        <span class="badge badge-secondary">Manual</span>
                                                    @endif
                                                </td>
                                                <td>{{ $discount->priority ?? 0 }}</td>
                                                <td>
                                                    @if ($discount->expired_date)
                                                        {{ \Illuminate\Support\Carbon::parse($discount->expired_date)->translatedFormat('d F Y') }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center">
                                                        <a href='{{ route('discount.edit', $discount->id) }}'
                                                            class="btn btn-sm btn-info btn-icon">
                                                            <i class="fas fa-edit"></i>
                                                            Edit
                                                        </a>

                                                        <form action="{{ route('discount.destroy', $discount->id) }}" method="POST"
                                                            class="ml-2">
                                                            <input type="hidden" name="_method" value="DELETE" />
                                                            <input type="hidden" name="_token"
                                                                value="{{ csrf_token() }}" />
                                                            <button class="btn btn-sm btn-danger btn-icon confirm-delete">
                                                                <i class="fas fa-times"></i> Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach


                                    </table>
                                </div>
                                <div class="float-right">
                                    {{ $discounts->withQueryString()->links() }}
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
    <script src="{{ asset('js/page/features-posts.js') }}"></script>
@endpush
