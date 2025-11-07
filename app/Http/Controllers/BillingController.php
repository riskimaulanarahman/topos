<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentSubmissionRequest;
use App\Mail\PaymentSubmissionSubmitted;
use App\Models\PaymentAccount;
use App\Models\PaymentSubmission;
use App\Models\User;
use App\Services\SubscriptionPlanService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(private readonly SubscriptionPlanService $planService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $subscriptionExpiresAt = $user->subscription_expires_at;
        $now = now();

        $daysRemaining = null;
        $timeRemaining = null;

        if ($subscriptionExpiresAt) {
            $rawDays = $now->diffInDays($subscriptionExpiresAt, false);
            $daysRemaining = $rawDays >= 0 ? $rawDays : 0;

            if ($subscriptionExpiresAt->isFuture()) {
                $timeRemaining = $now->locale(app()->getLocale())->diffForHumans(
                    $subscriptionExpiresAt,
                    [
                        'parts' => 2,
                        'join' => true,
                        'syntax' => CarbonInterface::DIFF_ABSOLUTE,
                    ]
                );
            }
        }

        $eligibleFirstRenewalPromo = $this->planService->eligibleForFirstRenewalPromo($user);
        $plans = $this->planService->getPlansForUser($user)->values()->all();
        $paymentAccounts = PaymentAccount::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $recentSubmissions = PaymentSubmission::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('pages.billing.index', [
            'user' => $user,
            'daysRemaining' => $daysRemaining,
            'timeRemaining' => $timeRemaining,
            'eligibleFirstRenewalPromo' => $eligibleFirstRenewalPromo,
            'plans' => $plans,
            'paymentAccounts' => $paymentAccounts,
            'suggestedUniqueCode' => $this->generateUniqueCode(),
            'recentSubmissions' => $recentSubmissions,
        ]);
    }

    public function store(StorePaymentSubmissionRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $plan = $this->planService->findPlanOrFail($user, $data['plan_code']);

        /** @var PaymentAccount $paymentAccount */
        $paymentAccount = PaymentAccount::query()
            ->whereKey($data['payment_account_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $uniqueCode = (int) $data['unique_code'];
        $this->guardAgainstDuplicateUniqueCode($uniqueCode, $user->id);

        $baseAmount = (int) $plan['price'];
        $paidAmount = $baseAmount + $uniqueCode;

        $proofConfig = config('subscriptions.proof_upload');
        $disk = $proofConfig['disk'] ?? 'public';
        $directory = trim($proofConfig['directory'] ?? 'payment-proofs', '/');
        $storedFilePath = $request->file('proof')->store($directory, $disk);

        $submission = PaymentSubmission::create([
            'user_id' => $user->id,
            'payment_account_id' => $paymentAccount->id,
            'plan_code' => $plan['code'],
            'plan_name' => $plan['name'],
            'plan_duration' => $plan['duration'] ?? null,
            'base_amount' => $baseAmount,
            'unique_code' => $uniqueCode,
            'paid_amount' => $paidAmount,
            'payment_channel' => $data['payment_channel'] ?? null,
            'transferred_at' => Carbon::parse($data['transferred_at'])->timezone(config('app.timezone')),
            'payer_name' => $data['payer_name'],
            'customer_note' => $data['customer_note'] ?? null,
            'destination_snapshot' => [
                'label' => $paymentAccount->label,
                'bank_name' => $paymentAccount->bank_name,
                'account_number' => $paymentAccount->account_number,
                'account_holder' => $paymentAccount->account_holder,
                'channel' => $paymentAccount->channel,
            ],
            'proof_path' => $storedFilePath,
            'status' => PaymentSubmission::STATUS_PENDING,
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        $this->notifyAdminsPaymentSubmitted($submission);

        return redirect()
            ->route('billing.index')
            ->with('status', 'Bukti pembayaran berhasil dikirim. Tim kami akan memverifikasi dalam waktu maksimal 1x24 jam.');
    }

    protected function generateUniqueCode(): int
    {
        $min = (int) config('subscriptions.unique_code.min', 100);
        $max = (int) config('subscriptions.unique_code.max', 999);

        do {
            $code = random_int($min, $max);
        } while (
            PaymentSubmission::query()
                ->whereDate('created_at', now())
                ->where('unique_code', $code)
                ->whereIn('status', [PaymentSubmission::STATUS_PENDING, PaymentSubmission::STATUS_APPROVED])
                ->exists()
        );

        return $code;
    }

    protected function guardAgainstDuplicateUniqueCode(int $uniqueCode, int $currentUserId): void
    {
        $exists = PaymentSubmission::query()
            ->where('unique_code', $uniqueCode)
            ->where('user_id', '!=', $currentUserId)
            ->whereIn('status', [PaymentSubmission::STATUS_PENDING, PaymentSubmission::STATUS_APPROVED])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'unique_code' => 'Kode unik sudah digunakan pengguna lain. Silakan muat ulang halaman untuk memperoleh kode baru.',
            ]);
        }
    }

    protected function notifyAdminsPaymentSubmitted(PaymentSubmission $submission): void
    {
        try {
            $adminEmails = User::query()
                ->where('roles', 'admin')
                ->whereNotNull('email')
                ->pluck('email')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (empty($adminEmails)) {
                return;
            }

            $detailsUrl = route('admin.billing.payments.show', $submission);
            Mail::to($adminEmails)->send(new PaymentSubmissionSubmitted($submission, $detailsUrl));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
