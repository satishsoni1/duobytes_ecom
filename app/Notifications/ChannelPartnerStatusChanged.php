<?php

namespace App\Notifications;

use App\Models\ChannelPartner;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChannelPartnerStatusChanged extends Notification
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
        $site = get_setting('website_name') ?: config('app.name');
        $mail = (new MailMessage)->greeting(translate('Hello') . ' ' . $this->partner->contact_person . ',');

        switch ($this->partner->status) {
            case ChannelPartner::STATUS_APPROVED:
                return $mail->subject($site . ': ' . translate('Your partner account is approved'))
                    ->line(translate('Your channel partner application has been approved. You can now browse the catalogue at your contract prices and place orders.'))
                    ->line(translate('Partner code') . ': ' . $this->partner->partner_code)
                    ->action(translate('Open partner portal'), route('channel_partner.dashboard'));

            case ChannelPartner::STATUS_REJECTED:
                return $mail->subject($site . ': ' . translate('Action needed on your partner application'))
                    ->line(translate('We could not approve your application yet.'))
                    ->line(translate('Reason') . ': ' . $this->partner->rejection_reason)
                    ->line(translate('Please update your details or documents and submit again.'))
                    ->action(translate('Update application'), route('channel_partner.onboarding'));

            case ChannelPartner::STATUS_SUSPENDED:
                return $mail->subject($site . ': ' . translate('Your partner account is on hold'))
                    ->line(translate('Ordering on your partner account has been paused. Please contact your account manager.'));

            default:
                return $mail->subject($site . ': ' . translate('Partner application update'))
                    ->action(translate('View status'), route('channel_partner.onboarding'));
        }
    }
}
