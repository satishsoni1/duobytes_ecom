<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChannelPartnerDocument extends Model
{
    protected $guarded = ['id'];

    public function channelPartner()
    {
        return $this->belongsTo(ChannelPartner::class);
    }

    public function typeLabel(): string
    {
        return config('channel_partner.document_types.' . $this->document_type . '.label', ucwords(str_replace('_', ' ', $this->document_type)));
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function humanSize(): string
    {
        $kb = $this->size / 1024;
        return $kb >= 1024 ? round($kb / 1024, 1) . ' MB' : max(1, round($kb)) . ' KB';
    }

    public function deleteFile(): void
    {
        Storage::disk(config('channel_partner.document_disk'))->delete($this->path);
    }
}
