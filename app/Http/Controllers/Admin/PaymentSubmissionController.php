<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSubmission;
use App\Services\SubscriptionPlanService;
use App\Services\SubscriptionRenewalService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentSubmissionController extends Controller
{
    public function __construct(
        private readonly SubscriptionPlanService $planService,
        private readonly SubscriptionRenewalService $renewalService
    ) {
    }

    public function index(Request $request): View
    {
        $status = (string) $request->string('status')->lower();
        $search = (string) $request->string('search')->trim();

        $submissions = PaymentSubmission::query()
            ->with(['user', 'paymentAccount', 'reviewer'])
            ->when($status && in_array($status, PaymentSubmissionStatusOptions::values(), true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search, function ($query, string $term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('plan_name', 'like', "%{$term}%")
                        ->orWhere('plan_code', 'like', "%{$term}%")
                        ->orWhere('payer_name', 'like', "%{$term}%")
                        ->orWhereHas('user', function ($userQuery) use ($term) {
                            $userQuery->where('name', 'like', "%{$term}%")
                                ->orWhere('store_name', 'like', "%{$term}%")
                                ->orWhere('email', 'like', "%{$term}%");
                        })
                        ->orWhereHas('paymentAccount', function ($accountQuery) use ($term) {
                            $accountQuery->where('account_number', 'like', "%{$term}%")
                                ->orWhere('bank_name', 'like', "%{$term}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('pages.billing.admin.index', [
            'submissions' => $submissions,
            'statusOptions' => PaymentSubmissionStatusOptions::all(),
            'activeStatus' => $status,
            'search' => $search,
        ]);
    }

    public function show(PaymentSubmission $submission): View
    {
        $submission->load(['user', 'paymentAccount', 'reviewer']);

        $proofConfig = config('subscriptions.proof_upload');
        $disk = $proofConfig['disk'] ?? 'public';

        $proofUrl = $submission->proof_path ? Storage::disk($disk)->url($submission->proof_path) : null;

        return view('pages.billing.admin.show', [
            'submission' => $submission,
            'proofUrl' => $proofUrl,
        ]);
    }

    public function approve(Request $request, PaymentSubmission $submission): RedirectResponse
    {
        $submission->load('user');

        if ($submission->status !== PaymentSubmission::STATUS_PENDING) {
            return back()->withErrors([
                'status' => 'Hanya pengajuan dengan status pending yang dapat disetujui.',
            ]);
        }

        $note = $request->string('approval_note')->trim()->value();

        $plan = $this->buildPlanFromSubmission($submission);
        $this->renewalService->extendByPlan($submission->user, $plan);

        $metadata = $submission->metadata ?? [];
        if ($note) {
            $metadata['approval_note'] = $note;
        }

        $submission->forceFill([
            'status' => PaymentSubmission::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => Carbon::now(),
            'rejection_reason' => null,
            'metadata' => $metadata,
        ])->save();

        return redirect()
            ->route('admin.billing.payments.show', $submission)
            ->with('status', 'Pembayaran berhasil disetujui dan langganan sudah diperpanjang.');
    }

    public function reject(Request $request, PaymentSubmission $submission): RedirectResponse
    {
        $submission->load('user');

        if ($submission->status !== PaymentSubmission::STATUS_PENDING) {
            return back()->withErrors([
                'status' => 'Hanya pengajuan dengan status pending yang dapat ditolak.',
            ]);
        }

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $metadata = $submission->metadata ?? [];
        $metadata['last_rejection_note'] = $data['rejection_reason'];

        $submission->forceFill([
            'status' => PaymentSubmission::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => Carbon::now(),
            'rejection_reason' => $data['rejection_reason'],
            'metadata' => $metadata,
        ])->save();

        return redirect()
            ->route('admin.billing.payments.show', $submission)
            ->with('status', 'Pengajuan pembayaran telah ditolak.');
    }

    protected function buildPlanFromSubmission(PaymentSubmission $submission): array
    {
        $duration = $submission->plan_duration;
        if (! $duration) {
            $duration = data_get(config('subscriptions.plans.' . $submission->plan_code), 'duration');
        }

        if (! $duration) {
            throw ValidationException::withMessages([
                'plan_code' => 'Durasi paket tidak ditemukan pada konfigurasi. Periksa pengaturan paket.',
            ]);
        }

        return [
            'code' => $submission->plan_code,
            'name' => $submission->plan_name,
            'duration' => $duration,
            'price' => $submission->base_amount,
        ];
    }
}

final class PaymentSubmissionStatusOptions
{
    public static function all(): array
    {
        return [
            PaymentSubmission::STATUS_PENDING => 'Pending',
            PaymentSubmission::STATUS_APPROVED => 'Disetujui',
            PaymentSubmission::STATUS_REJECTED => 'Ditolak',
        ];
    }

    public static function values(): array
    {
        return array_keys(self::all());
    }
}
