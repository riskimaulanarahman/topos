<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    protected function generateUniqueCode()
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6));
        } while (Voucher::where('code', $code)->exists());

        return $code;
    }

    // create a new voucher
    public function store(Request $request)
    {
        $request->validate([
            'expired_at' => 'required|date',
        ]);

        $expiresAt = $request->input('expired_at') ? Carbon::parse($request->input('expires_at'))->format('Y-m-d H:i:s') : null;

        $voucher = Voucher::create([
            'code' => $this->generateUniqueCode(),
            'expired_at' => $expiresAt,
        ]);

        return response()->json([
            'success' => true,
            'data' => $voucher
        ], 201);
    }

    // redeem a voucher
    public function redeem(Request $request, $code)
    {
        $voucher = Voucher::where('code', $code)->unredeemed()->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher not found or already redeemed'
            ], 404);
        }

        // redeem at now
        $redeemedAt = Carbon::now()->format('Y-m-d H:i:s');
        $voucher->update([
            'is_redeemed' => true,
            'redeemed_at' => $redeemedAt
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Voucher redeemed successfully with code ' . $voucher->code,
            // 'data' => $voucher
        ]);
    }
}
