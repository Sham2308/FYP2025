<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ReportSubmitted extends Notification // implements ShouldQueue (optional)
{
    use Queueable;

    public function __construct(public Report $report) {}

    public function via($notifiable): array
    {
        // database will show in your bell if you’re using it
        // mail will send email if MAIL_* is configured
        return ['database','mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Report: '.$this->report->subject)
            ->line('Priority: '.$this->report->priority)
            ->line('From: '.$this->report->user->name)
            ->line('Message: '.$this->report->message)
            ->action('Open in Admin Dashboard', url(route('admin.reports.show', $this->report)));
    }

    public function toArray($notifiable): array
    {
        return [
            'type'    => 'report_submitted',
            'report_id' => $this->report->id,
            'subject' => $this->report->subject,
            'priority'=> $this->report->priority,
            'by'      => $this->report->user->name,
        ];
    }
}
