@extends('layouts.app')

@section('title', 'Undangan Mitra Outlet')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
            <h1>Undangan Mitra</h1>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Anda diundang bergabung ke outlet berikut:</h5>
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th class="w-25">Nama Outlet</th>
                                <td>{{ $invitation->outlet->name }}</td>
                            </tr>
                            <tr>
                                <th>Kode</th>
                                <td>{{ $invitation->outlet->code ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Alamat</th>
                                <td>{{ $invitation->outlet->address ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Diundang Sebagai</th>
                                <td>{{ ucfirst($invitation->role) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <form action="{{ route('partner-invitations.accept', $invitation->invitation_token) }}" method="POST">
                        @csrf
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="confirm" name="confirm" required>
                            <label for="confirm" class="form-check-label">
                                Saya menyetujui untuk bergabung ke outlet ini dan mematuhi aturan yang berlaku.
                            </label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('outlets.index') }}" class="btn btn-secondary">Nanti Saja</a>
                            <button type="submit" class="btn btn-primary">Terima Undangan</button>
                        </div>
                    </form>
                </div>
            </div>
            </div>
        </section>
    </div>
@endsection
