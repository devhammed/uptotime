<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Monitor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class MonitorDownNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Monitor $monitor)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('notifications.monitor_down.mail.subject', ['url' => $this->monitor->url]))
            ->line(__('notifications.monitor_down.mail.detected'))
            ->line(__('notifications.monitor_down.mail.url', ['url' => $this->monitor->url]))
            ->line(__('notifications.monitor_down.mail.threshold', ['threshold' => $this->monitor->threshold]));
    }
}
