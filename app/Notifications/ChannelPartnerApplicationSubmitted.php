<?php

namespace App\Notifications;

use App\Models\ChannelPartner;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChannelPartnerApplicationSubmitted extends Notification
{
    use Queueable;

    public function __construct(public ChannelPartner $partner)
    {
    }

    public function via($notifiable)
    {
        return channel_partner_mail_configured() ? ['mail'] : [];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(translate('New channel partner application') . ': ' . $this->partner->company_name)
            ->greeting(translate('New partner application'))
            ->line($this->partner->company_name . ' (' . $this->partner->contact_person . ') ' . translate('has submitted their documents for review.'))
            ->action(translate('Review application'), route('admin.channel_partners.show', $this->partner->id));
    }
}
