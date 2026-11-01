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
            ->subject(translate('New channel partner application') . ': ' . $this->partner->displayName())
            ->greeting(translate('New partner application'))
            ->line(($this->partner->company_name ? $this->partner->company_name . ' (' . $this->partner->contact_person . ')' : $this->partner->contact_person) . ' ' . translate('has joined as a channel partner. The account was activated automatically.'))
            ->action(translate('View partner'), route('admin.channel_partners.show', $this->partner->id));
    }
}
