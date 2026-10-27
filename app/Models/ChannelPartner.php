<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelPartner extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_SUSPENDED,
    ];

    // Fields the partner may edit during onboarding.
    public const BUSINESS_FIELDS = [
        'company_name', 'business_type', 'contact_person', 'designation',
        'gst_number', 'pan_number', 'registration_number',
        'address_line', 'city', 'state', 'postal_code', 'country',
        'website', 'expected_monthly_volume',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'default_discount' => 'float',
        'credit_limit' => 'float',
        'min_order_value' => 'float',
        'contract_valid_until' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(ChannelPartnerDocument::class);
    }

    public function priceRules()
    {
        return $this->hasMany(ChannelPartnerPriceRule::class);
    }

    public function cartItems()
    {
        return $this->hasMany(ChannelPartnerCartItem::class);
    }

    public function partnerOrders()
    {
        return $this->hasMany(ChannelPartnerOrder::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canEditApplication(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function contractExpired(): bool
    {
        return $this->contract_valid_until !== null && $this->contract_valid_until->endOfDay()->isPast();
    }

    /**
     * Required document types that have no uploaded (non-rejected) file yet.
     */
    public function missingRequiredDocuments(): array
    {
        $uploaded = $this->documents()
            ->where('status', '!=', 'rejected')
            ->pluck('document_type')
            ->unique()
            ->all();

        return collect(config('channel_partner.document_types'))
            ->filter(fn ($type) => $type['required'])
            ->keys()
            ->diff($uploaded)
            ->values()
            ->all();
    }

    public function businessDetailsComplete(): bool
    {
        // Company name is optional: most partners are individuals.
        foreach (['business_type', 'contact_person', 'address_line', 'city', 'state', 'postal_code'] as $field) {
            if (blank($this->{$field})) {
                return false;
            }
        }
        return true;
    }

    /**
     * Business name when given, otherwise the partner's own name.
     */
    public function displayName(): string
    {
        return $this->company_name ?: (string) $this->contact_person;
    }

    public function statusBadgeClass(): string
    {
        return [
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_SUBMITTED => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_SUSPENDED => 'dark',
        ][$this->status] ?? 'secondary';
    }

    public static function generateCode(): string
    {
        $next = (int) static::whereNotNull('partner_code')->count() + 1;
        do {
            $code = 'CP' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (static::where('partner_code', $code)->exists());

        return $code;
    }
}
