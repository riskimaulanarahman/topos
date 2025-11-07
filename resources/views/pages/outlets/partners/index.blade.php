@extends('layouts.app')

@section('title', 'Mitra Outlet')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
            <h1>Mitra & Akses</h1>
            <div class="section-header-button">
                <a href="{{ route('outlets.show', $outlet) }}" class="btn btn-light"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
            </div>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('outlets.index') }}">Outlet</a></div>
                <div class="breadcrumb-item"><a href="{{ route('outlets.show', $outlet) }}">{{ $outlet->name }}</a></div>
                <div class="breadcrumb-item active">Mitra</div>
            </div>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @can('manageMembers', $outlet)
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Undang Mitra Baru</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('outlets.partners.store', $outlet) }}" method="POST" class="row">
                            @csrf
                            <div class="form-group col-md-6">
                                <label for="invite-email">Email Pengguna</label>
                                <input type="email" id="invite-email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="user@example.com" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Izin Mitra</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="invite-stock" name="can_manage_stock" value="1" {{ old('can_manage_stock', true) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="invite-stock">Kelola stok & bahan baku</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="invite-expense" name="can_manage_expense" value="1" {{ old('can_manage_expense') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="invite-expense">Kelola pengeluaran</label>
                                </div>
                            </div>
                            <div class="form-group col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Kirim Undangan</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan

            <div class="card">
                <div class="card-header">
                    <h4>Daftar Mitra</h4>
                    <div class="card-header-action">
                        <a href="{{ route('outlets.category-requests.index', $outlet) }}" class="btn btn-outline-primary btn-sm">Permintaan Akses Kategori</a>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Peran</th>
                                <th>Status</th>
                                <th>Izin</th>
                                <th>Kategori</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($members as $member)
                                <tr>
                                    <td>{{ $member->user->name }}</td>
                                    <td>{{ $member->user->email }}</td>
                                    <td>{{ ucfirst($member->role) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $member->status === 'active' ? 'success' : ($member->status === 'pending' ? 'warning' : 'secondary') }}">{{ ucfirst($member->status) }}</span>
                                    </td>
                                    <td>
                                        <div class="small text-muted">
                                            <div>Stok: {{ $member->can_manage_stock ? 'Ya' : 'Tidak' }}</div>
                                            <div>Pengeluaran: {{ $member->can_manage_expense ? 'Ya' : 'Tidak' }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        @php($assignedCategories = $member->categoryAssignments->pluck('category.name')->filter())
                                        @if ($assignedCategories->isNotEmpty())
                                            <span class="badge badge-light text-dark">{{ $assignedCategories->join(', ') }}</span>
                                        @else
                                            <span class="text-muted">Belum ada</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @can('manageMembers', $outlet)
                                            @if ($member->role !== 'owner')
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#permissions-{{ $member->id }}">
                                                        Izin
                                                    </button>
                                                    <form action="{{ route('outlets.partners.destroy', [$outlet, $member]) }}" method="POST" onsubmit="return confirm('Cabut akses mitra ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Cabut</button>
                                                    </form>
                                                </div>
                                            @endif
                                        @endcan
                                        @if (auth()->id() === $member->user_id || auth()->user()->can('manageMembers', $outlet))
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-toggle="modal" data-target="#category-request-{{ $member->id }}">
                                                Permintaan Kategori
                                            </button>
                                        @endif
                                    </td>
                                </tr>

                                @can('manageMembers', $outlet)
                                    @if ($member->role !== 'owner')
                                        <div class="modal fade" tabindex="-1" role="dialog" id="permissions-{{ $member->id }}">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <form action="{{ route('outlets.partners.permissions', [$outlet, $member]) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Perbarui Izin</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="mb-3">{{ $member->user->name }} <br><small class="text-muted">{{ $member->user->email }}</small></p>
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="perm-stock-{{ $member->id }}" name="can_manage_stock" value="1" {{ $member->can_manage_stock ? 'checked' : '' }}>
                                                                <label class="custom-control-label" for="perm-stock-{{ $member->id }}">Kelola stok & bahan baku</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="perm-expense-{{ $member->id }}" name="can_manage_expense" value="1" {{ $member->can_manage_expense ? 'checked' : '' }}>
                                                                <label class="custom-control-label" for="perm-expense-{{ $member->id }}">Kelola pengeluaran</label>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endcan

                                @if (auth()->id() === $member->user_id || auth()->user()->can('manageMembers', $outlet))
                                    <div class="modal fade" tabindex="-1" role="dialog" id="category-request-{{ $member->id }}">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <form action="{{ route('outlets.partners.categories.request', [$outlet, $member]) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Permintaan Akses Kategori</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="mb-3">Pilih kategori yang ingin ditambahkan atau dihapus untuk {{ $member->user->name }}.</p>
                                                        <div class="form-group">
                                                            <label>Kategori untuk Ditambahkan</label>
                                                            <select name="add[]" class="form-control select2" multiple data-placeholder="Pilih kategori">
                                                                @foreach ($categories as $category)
                                                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Kategori untuk Dihapus</label>
                                                            <select name="remove[]" class="form-control select2" multiple data-placeholder="Pilih kategori">
                                                                @foreach ($categories as $category)
                                                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Catatan</label>
                                                            <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan (opsional)"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary">Kirim Permintaan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada mitra terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $members->links() }}
                </div>
            </div>
            </div>
        </section>
    </div>
@endsection
