<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequisitionActionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public \App\Models\Requisition $requisition;
    public string $actionTitle;
    public string $emailMessage;
    public string $actionUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(\App\Models\Requisition $requisition, string $actionTitle, string $emailMessage, string $actionUrl = '/')
    {
        $this->requisition = $requisition;
        $this->actionTitle = $actionTitle;
        $this->emailMessage = $emailMessage;
        $this->actionUrl = $actionUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Requisition Update: ' . $this->requisition->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->actionTitle)
            ->line($this->emailMessage)
            ->action('View Requisition', url($this->actionUrl))
            ->line('Amount: ' . number_format($this->requisition->amount_requested, 2))
            ->line('Status: ' . $this->requisition->status)
            ->line('Thank you for using the ChurchDesk Platform.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'requisition_id' => $this->requisition->id,
            'title' => $this->actionTitle,
            'message' => $this->emailMessage,
        ];
    }
}
