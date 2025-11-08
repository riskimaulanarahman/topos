<div class="mb-3">
  <button class="btn btn-outline-info btn-sm" type="button" data-toggle="collapse" data-target="#{{ $id }}" aria-expanded="{{ !empty($initially_open) ? 'true' : 'false' }}" aria-controls="{{ $id }}">
    Bantuan
  </button>
  <div id="{{ $id }}" class="collapse {{ !empty($initially_open) ? 'show' : '' }} mt-2">
    <div class="alert alert-info mb-0">
      <h6 class="mb-2">{{ $title }}</h6>
      <ul class="mb-0 pl-3">
        @foreach(($items ?? []) as $it)
          <li>{!! $it !!}</li>
        @endforeach
      </ul>
    </div>
  </div>
</div>

