@php
    $successMessage = session('success');
    $errorMessage = session('error');
@endphp

@if ($successMessage)
    <div class="alert alert-success alert-dismissible show fade">
        <div class="alert-body">
            <button class="close" data-dismiss="alert">
                <span>×</span>
            </button>
            <p>{{ $successMessage }}</p>
        </div>
    </div>
@endif

@if ($errorMessage)
    <div class="alert alert-danger alert-dismissible show fade">
        <div class="alert-body">
            <button class="close" data-dismiss="alert">
                <span>×</span>
            </button>
            <p>{{ $errorMessage }}</p>
        </div>
    </div>
@endif
