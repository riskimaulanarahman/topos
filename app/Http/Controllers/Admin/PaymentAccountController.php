<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentAccountRequest;
use App\Models\PaymentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentAccountController extends Controller
{
    public function index(): View
    {
        $accounts = PaymentAccount::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('bank_name')
            ->get();

        return view('pages.billing.admin.accounts.index', [
            'accounts' => $accounts,
        ]);
    }

    public function create(): View
    {
        return view('pages.billing.admin.accounts.create');
    }

    public function store(PaymentAccountRequest $request): RedirectResponse
    {
        PaymentAccount::create($request->validated());

        return redirect()
            ->route('admin.billing.accounts.index')
            ->with('status', 'Rekening pembayaran berhasil ditambahkan.');
    }

    public function edit(PaymentAccount $payment_account): View
    {
        return view('pages.billing.admin.accounts.edit', [
            'account' => $payment_account,
        ]);
    }

    public function update(PaymentAccountRequest $request, PaymentAccount $payment_account): RedirectResponse
    {
        $payment_account->update($request->validated());

        return redirect()
            ->route('admin.billing.accounts.index')
            ->with('status', 'Rekening pembayaran berhasil diperbarui.');
    }

    public function destroy(PaymentAccount $payment_account): RedirectResponse
    {
        $payment_account->delete();

        return redirect()
            ->route('admin.billing.accounts.index')
            ->with('status', 'Rekening pembayaran berhasil dihapus.');
    }
}

