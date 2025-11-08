@extends('layouts.app')

@section('title', 'Permintaan Akses Kategori')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
            <h1>Permintaan Akses Kategori</h1>
            <div class="section-header-button">
                <a href="{{ route('outlets.partners.index', $outlet) }}" class="btn btn-light"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
            </div>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('outlets.index') }}">Outlet</a></div>
                <div class="breadcrumb-item"><a href="{{ route('outlets.show', $outlet) }}">{{ $outlet->name }}</a></div>
                <div class="breadcrumb-item active">Permintaan Kategori</div>
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

            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Mitra</th>
                                <th>Diminta Oleh</th>
                                <th>Permintaan</th>
                                <th>Status</th>
                                <th>Catatan</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $requestItem)
                                <tr id="member-{{ $requestItem->target_outlet_user_role_id }}">
                                    <td>
                                        {{ $requestItem->target->user->name }}<br>
                                        <small class="text-muted">{{ $requestItem->target->user->email }}</small>
                                    </td>
                                    <td>
                                        {{ $requestItem->requester->name }}<br>
                                        <small class="text-muted">{{ $requestItem->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        @php($payload = $requestItem->payload ?? [])
                                        <div>
                                            <strong>Tambah:</strong>
                                            <span>{{ collect($payload['add'] ?? [])->count() ?: 0 }} kategori</span>
                                        </div>
                                        <div>
                                            <strong>Hapus:</strong>
                                            <span>{{ collect($payload['remove'] ?? [])->count() ?: 0 }} kategori</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $requestItem->status === 'approved' ? 'success' : ($requestItem->status === 'rejected' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($requestItem->status) }}
                                        </span>
                                        @if ($requestItem->reviewed_at)
                                            <div class="small text-muted">{{ $requestItem->reviewed_at->diffForHumans() }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $requestItem->review_notes ?? ($payload['notes'] ?? '—') }}</td>
                                    <td class="text-right">
                                        @can('manageMembers', $outlet)
                                            @if ($requestItem->status === 'pending')
                                                <div class="btn-group" role="group">
                                                    <form action="{{ route('outlets.category-requests.approve', [$outlet, $requestItem]) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#reject-{{ $requestItem->id }}">Reject</button>
                                                </div>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>

                                <div class="modal fade" tabindex="-1" role="dialog" id="reject-{{ $requestItem->id }}">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <form action="{{ route('outlets.category-requests.reject', [$outlet, $requestItem]) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Tolak Permintaan</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Berikan alasan penolakan (opsional):</p>
                                                    <textarea name="reason" class="form-control" rows="3" placeholder="Alasan penolakan"></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger">Tolak</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada permintaan akses kategori.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $requests->links() }}
                </div>
            </div>
            </div>
        </section>
    </div>
@endsection
