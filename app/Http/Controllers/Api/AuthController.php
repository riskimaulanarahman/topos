<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Outlet;
use App\Models\OutletUserRole;

class AuthController extends Controller
{
    // public function register(Request $request)
    // {
    //     $data = $request->validate([
    //         'store_name' => ['required', 'string', 'max:255'],
    //         'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
    //         'phone'      => ['required', 'string', 'max:50', 'unique:users,phone'],
    //         'password'   => ['required', 'string', 'min:6', 'max:255'],
    //     ]);

    //     try {
    //         DB::transaction(function () use ($data) {
    //             $user = User::create([
    //                 'name'        => $data['store_name'],
    //                 'store_name'  => $data['store_name'],
    //                 'email'       => $data['email'],
    //                 'phone'       => $data['phone'],
    //                 'password'    => Hash::make($data['password']),
    //             ]);

    //             // Kirim verifikasi SECARA SINKRON agar kalau gagal -> throw -> rollback
    //             // (JANGAN di-queue di step registrasi)
    //             $user->notifyNow(new CustomVerifyEmail);
    //         });

    //         return response()->json([
    //             'message' => 'Registrasi berhasil. Silakan cek email untuk aktivasi akun.',
    //         ], 201);

    //     } catch (Throwable $e) {
    //         Log::error('Register gagal (rollback): '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
    //         return response()->json([
    //             'message' => 'Registrasi gagal. Silakan coba lagi.',
    //         ], 500);
    //     }
    // }
    // public function register(Request $request)
    // {
    //     $data = $request->validate([
    //         'store_name' => ['required', 'string', 'max:255'],
    //         'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
    //         'phone'      => ['required', 'string', 'max:50', 'unique:users,phone'],
    //         'password'   => ['required', 'string', 'min:6', 'max:255'],
    //     ]);

    //     try {
    //         $user = null;

    //         DB::transaction(function () use ($data, &$user) {
    //             $user = User::create([
    //                 'name'        => $data['store_name'],
    //                 'store_name'  => $data['store_name'],
    //                 'email'       => $data['email'],
    //                 'phone'       => $data['phone'],
    //                 'password'    => Hash::make($data['password']),
    //             ]);
    //         });

    //         // ⬇️ Kirim SETELAH commit + via queue
    //         $user->notify(
    //             (new \App\Notifications\CustomVerifyEmail)
    //                 ->afterCommit()
    //                 ->onQueue('mail')
    //         );

    //         return response()->json([
    //             'message' => 'Registrasi berhasil. Silakan cek email untuk aktivasi akun.',
    //         ], 201);

    //     } catch (\Throwable $e) {
    //         \Log::error('Register gagal: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
    //         return response()->json([
    //             'message' => 'Registrasi gagal. Silakan coba lagi.',
    //         ], 500);
    //     }
    // }

    public function register(Request $request)
    {
        $messages = [
            'store_name.required' => 'Nama toko wajib diisi.',
            'store_name.string'   => 'Nama toko harus berupa teks.',
            'store_name.max'      => 'Nama toko maksimal 255 karakter.',

            'email.required'      => 'Email wajib diisi.',
            'email.email'         => 'Format email tidak valid.',
            'email.max'           => 'Email maksimal 255 karakter.',
            'email.unique'        => 'Email sudah terdaftar.',

            'phone.required'      => 'Nomor telepon wajib diisi.',
            'phone.string'        => 'Nomor telepon harus berupa teks.',
            'phone.max'           => 'Nomor telepon maksimal 50 karakter.',
            'phone.unique'        => 'Nomor telepon sudah terdaftar.',

            'password.required'   => 'Kata sandi wajib diisi.',
            'password.string'     => 'Kata sandi harus berupa teks.',
            'password.min'        => 'Kata sandi minimal 6 karakter.',
            'password.max'        => 'Kata sandi maksimal 255 karakter.',
        ];

        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'      => ['required', 'string', 'max:50', 'unique:users,phone'],
            'password'   => ['required', 'string', 'min:6', 'max:255'],
        ], $messages);

        try {
            $user = null;

            DB::transaction(function () use ($data, &$user) {
                $trialStartedAt = Carbon::now();

                $user = User::create([
                    'name'        => $data['store_name'],
                    'store_name'  => $data['store_name'],
                    'email'       => $data['email'],
                    'phone'       => $data['phone'],
                    'password'    => Hash::make($data['password']),
                    'trial_started_at' => $trialStartedAt,
                    'subscription_expires_at' => $trialStartedAt->copy()->addDays(14),
                    'subscription_status' => SubscriptionStatus::TRIALING,
                ]);

                $code = Str::upper(Str::slug($data['store_name'] ?? 'Outlet '.$user->id, ''));
                if ($code === '') {
                    $code = Str::upper(Str::random(6));
                }
                $originalCode = $code;
                $suffix = 1;
                while (Outlet::where('code', $code)->exists()) {
                    $code = Str::upper($originalCode . $suffix);
                    $suffix++;
                }

                $outlet = Outlet::create([
                    'name' => $data['store_name'],
                    'code' => Str::substr($code, 0, 20),
                    'address' => null,
                    'notes' => null,
                    'created_by' => $user->id,
                ]);

                OutletUserRole::create([
                    'outlet_id' => $outlet->id,
                    'user_id' => $user->id,
                    'role' => 'owner',
                    'status' => 'active',
                    'can_manage_stock' => true,
                    'can_manage_expense' => true,
                    'can_manage_sales' => true,
                    'accepted_at' => now(),
                    'created_by' => $user->id,
                ]);
            });

            $user->notify(
                (new \App\Notifications\CustomVerifyEmail)
                    ->afterCommit()
                    ->onQueue('mail')
            );

            return response()->json([
                'message' => 'Registrasi berhasil. Silakan cek email untuk aktivasi akun.',
            ], 201);

        } catch (\Throwable $e) {
            \Log::error('Register gagal: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Registrasi gagal. Silakan coba lagi.',
            ], 500);
        }
    }


    // public function verify(Request $request, $id, $hash)
    // {
    //     try {
    //         $user = User::findOrFail($id);

    //         if (! hash_equals((string) $hash, sha1($user->email))) {
    //             return response()->json(['message' => 'Link verifikasi tidak valid.'], 400);
    //         }

    //         if ($user->hasVerifiedEmail()) {
    //             if ($redirect = $request->query('redirect')) {
    //                 return redirect()->away($redirect . '?status=already_verified');
    //             }
    //             return response()->json(['message' => 'Email sudah terverifikasi.'], 200);
    //         }

    //         DB::transaction(function () use ($user) {
    //             if ($user->markEmailAsVerified()) {
    //                 event(new Verified($user));
    //             }
    //         });

    //         if ($redirect = $request->query('redirect')) {
    //             return redirect()->away($redirect . '?status=verified');
    //         }

    //         return response()->json(['message' => 'Email berhasil diverifikasi. Silakan login.'], 200);

    //     } catch (Throwable $e) {
    //         Log::error('Verifikasi gagal: '.$e->getMessage());
    //         return response()->json(['message' => 'Terjadi kesalahan saat verifikasi.'], 500);
    //     }
    // }

    public function verify(Request $request, $id, $hash)
    {
        try {
            // 1) Cek kadaluarsa lebih dulu (untuk signed URL)
            $expired = false;
            if ($request->has('expires')) {
                $exp = (int) $request->query('expires');
                if (now()->getTimestamp() > $exp) {
                    $expired = true;
                }
            }

            // Kalau kadaluarsa → tampilkan halaman 'expired'
            if ($expired) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => 'Link verifikasi sudah kadaluarsa.'
                    ], 410); // 410 Gone
                }

                return response()->view('auth.verify-result', [
                    'status'  => 'expired',
                    'title'   => 'Link Kadaluarsa',
                    // optional: kirim id/email untuk memudahkan form kirim ulang
                    'prefillEmail' => $request->query('email'),
                ], 410);
            }

            // 2) Validasi tanda tangan (kalau pakai signed URL)
            //    - Jika gagal tapi tidak kadaluarsa → perlakukan sebagai invalid
            if ($request->has('signature') && ! $request->hasValidSignature()) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Link verifikasi tidak valid.'], 400);
                }
                return response()->view('auth.verify-result', [
                    'status'  => 'invalid',
                    'title'   => 'Link Tidak Valid',
                ], 400);
            }

            // 3) Proses verifikasi seperti biasa
            $user = User::findOrFail($id);

            if (! hash_equals((string) $hash, sha1($user->email))) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Link verifikasi tidak valid.'], 400);
                }
                return response()->view('auth.verify-result', [
                    'status'  => 'invalid',
                    'title'   => 'Link Tidak Valid',
                ], 400);
            }

            if ($user->hasVerifiedEmail()) {
                if ($redirect = $request->query('redirect')) {
                    return redirect()->away($redirect . '?status=already_verified');
                }

                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Email sudah terverifikasi.'], 200);
                }

                return response()->view('auth.verify-result', [
                    'status'  => 'already_verified',
                    'title'   => 'Sudah Terverifikasi',
                ]);
            }

            DB::transaction(function () use ($user) {
                if ($user->markEmailAsVerified()) {
                    event(new Verified($user));
                }
            });

            if ($redirect = $request->query('redirect')) {
                return redirect()->away($redirect . '?status=verified');
            }

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Email berhasil diverifikasi. Silakan login.'], 200);
            }

            return response()->view('auth.verify-result', [
                'status'  => 'verified',
                'title'   => 'Berhasil Diverifikasi!',
            ]);

        } catch (Throwable $e) {
            Log::error('Verifikasi gagal: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Terjadi kesalahan saat verifikasi.'], 500);
            }

            return response()->view('auth.verify-result', [
                'status'  => 'error',
                'title'   => 'Terjadi Kesalahan',
            ], 500);
        }
    }


    public function resendVerification(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['message' => 'Email tidak ditemukan.'], 404);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email sudah terverifikasi.'], 200);
        }

        // Key throttle gabungan email + IP agar adil per user & per klien
        $key = 'resend-email-verification:' . sha1($request->ip() . '|' . strtolower($user->email));

        // Izinkan 1 permintaan per 5 menit (300 detik)
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => 'Terlalu sering meminta kirim ulang. Silakan coba lagi dalam ' . $seconds . ' detik.',
            ], 429);
        }

        try {
            // Lock lebih dulu supaya race-condition tidak kirim ganda
            RateLimiter::hit($key, 300); // 300 detik = 5 menit

            // Kirim ulang email verifikasi (boleh di-queue)
            $user->notify((new \App\Notifications\CustomVerifyEmail)
                ->afterCommit()
                ->onQueue('mail'));

            return response()->json(['message' => 'Email verifikasi telah dikirim ulang.'], 200);

        } catch (Throwable $e) {
            // Kalau gagal kirim, lepaskan lock supaya user bisa coba lagi
            RateLimiter::clear($key);

            Log::warning('Gagal kirim ulang verifikasi: '.$e->getMessage());
            return response()->json([
                'message' => 'Gagal mengirim ulang email verifikasi. Coba beberapa saat lagi.'
            ], 500);
        }
    }

    // LOGIN Anda (pastikan cek verified)
    public function login(Request $request)
    {
        $loginData = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();
        if (! $user) {
            return response(['message' => ['Email not found']], 404);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email belum terverifikasi. Cek email Anda.'], 403);
        }

        if (! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response(['message' => ['Password is wrong']], 404);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $user->load([
            'outletRoles' => function ($query) {
                $query
                    ->where('status', 'active')
                    ->select([
                        'id',
                        'outlet_id',
                        'user_id',
                        'role',
                        'status',
                        'can_manage_stock',
                        'can_manage_expense',
                        'can_manage_sales',
                        'accepted_at',
                        'revoked_at',
                        'pin_last_set_at',
                        'pin_last_verified_at',
                    ])
                    ->with([
                        'outlet:id,name,code,address,notes',
                    ]);
            },
        ]);

        return response(['user' => $user, 'token' => $token], 200);
    }

    //logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logout success',
        ]);
    }
}
