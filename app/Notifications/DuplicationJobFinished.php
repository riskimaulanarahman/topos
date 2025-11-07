<?php

namespace App\Notifications;

use App\Models\DuplicationJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class DuplicationJobFinished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly DuplicationJob $job)
    {
    }

    public function via($notifiable): array
    {
        $channels = ['mail'];

        if (Schema::hasTable('notifications')) {
            $channels[] = 'database';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $status = ucfirst($this->job->status);
        $summary = $this->summaryLine();

        return (new MailMessage)
            ->subject(__('Duplikasi katalog :status', ['status' => $status]))
            ->line(__('Proses duplikasi dari :source ke :target telah selesai dengan status :status.', [
                'source' => $this->job->sourceOutlet?->name ?? '#'.$this->job->source_outlet_id,
                'target' => $this->job->targetOutlet?->name ?? '#'.$this->job->target_outlet_id,
                'status' => strtolower($status),
            ]))
            ->line($summary)
            ->action(__('Lihat Detail'), route('catalog-duplication.jobs.show', $this->job));
    }

    public function toArray($notifiable): array
    {
        return [
            'job_id' => $this->job->id,
            'status' => $this->job->status,
            'counts' => $this->job->counts,
            'source_outlet_id' => $this->job->source_outlet_id,
            'target_outlet_id' => $this->job->target_outlet_id,
        ];
    }

    protected function summaryLine(): string
    {
        $counts = $this->job->counts ?? [];

        return __('Kategori: :categories, Bahan: :rawMaterials, Produk: :products', [
            'categories' => $counts['categories'] ?? 0,
            'rawMaterials' => $counts['raw_materials'] ?? 0,
            'products' => $counts['products'] ?? 0,
        ]);
    }
}
