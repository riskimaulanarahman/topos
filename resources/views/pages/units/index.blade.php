@extends('layouts.app')

@section('title', 'Master Satuan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Master Satuan</h1>
      <div class="section-header-button">
        <a href="{{ route('units.create') }}" class="btn btn-primary">Tambah</a>
      </div>
    </div>
    <div class="section-body">
      @include('layouts.alert')
      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Nama</th>
                  <th>Deskripsi</th>
                  <th>Dibuat</th>
                  <th class="text-right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($units as $unit)
                  <tr>
                    <td>{{ $unit->code }}</td>
                    <td>{{ $unit->name }}</td>
                    <td>{{ $unit->description }}</td>
                    <td>{{ optional($unit->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="text-right">
                      <a href="{{ route('units.edit', $unit) }}" class="btn btn-sm btn-info">Edit</a>
                      <form action="{{ route('units.destroy', $unit) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus satuan ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted">Belum ada data satuan.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="float-right">{{ $units->links() }}</div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
