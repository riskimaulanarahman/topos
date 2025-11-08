@php
    /** @var \App\Models\PaymentAccount|null $account */
    $activeValue = old('is_active', optional($account)->is_active ?? true);
@endphp

@csrf

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="label">Label</label>
        <input type="text" name="label" id="label" class="form-control @error('label') is-invalid @enderror"
               value="{{ old('label', optional($account)->label) }}" placeholder="Contoh: Rekening Utama">
        @error('label')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-6">
        <label for="bank_name">Bank/Provider</label>
        <input type="text" name="bank_name" id="bank_name" class="form-control @error('bank_name') is-invalid @enderror"
               value="{{ old('bank_name', optional($account)->bank_name) }}" placeholder="Contoh: BCA, Mandiri, OVO">
        @error('bank_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="account_number">Nomor Rekening / ID <span class="text-danger">*</span></label>
        <input type="text" name="account_number" id="account_number" class="form-control @error('account_number') is-invalid @enderror"
               value="{{ old('account_number', optional($account)->account_number) }}" required>
        @error('account_number')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-6">
        <label for="account_holder">Atas Nama</label>
        <input type="text" name="account_holder" id="account_holder" class="form-control @error('account_holder') is-invalid @enderror"
               value="{{ old('account_holder', optional($account)->account_holder) }}">
        @error('account_holder')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="channel">Kanal Pembayaran</label>
        <input type="text" name="channel" id="channel" class="form-control @error('channel') is-invalid @enderror"
               value="{{ old('channel', optional($account)->channel) }}" placeholder="Contoh: Mobile Banking, ATM, E-Wallet">
        @error('channel')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-6">
        <label for="sort_order">Urutan</label>
        <input type="number" name="sort_order" id="sort_order" class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', optional($account)->sort_order ?? 0) }}" min="0" max="65535">
        @error('sort_order')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-group">
    <label for="instructions">Instruksi Tambahan</label>
    <textarea name="instructions" id="instructions" rows="3" class="form-control @error('instructions') is-invalid @enderror"
              placeholder="Opsional: petunjuk khusus untuk pembayaran">{{ old('instructions', optional($account)->instructions) }}</textarea>
    @error('instructions')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group form-check">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input"
           {{ filter_var($activeValue, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">Aktif</label>
    @error('is_active')
        <div class="text-danger small d-block mt-1">{{ $message }}</div>
    @enderror
</div>

