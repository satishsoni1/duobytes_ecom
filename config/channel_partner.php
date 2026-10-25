<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Channel Partner Program
    |--------------------------------------------------------------------------
    |
    | Channel partners replace the public seller sign-up. Partners onboard
    | with business details + KYC documents, are approved by an admin, and
    | then order at contract prices that are agreed offline and entered by
    | the admin as pricing rules.
    |
    */

    'enabled' => env('CHANNEL_PARTNER_ENABLED', true),

    // Disk for KYC documents. Must NOT be a public disk.
    'document_disk' => env('CHANNEL_PARTNER_DOCUMENT_DISK', 'channel_partner_documents'),

    'document_max_kb' => (int) env('CHANNEL_PARTNER_DOCUMENT_MAX_KB', 5120),

    'document_mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],

    // Document types shown in onboarding. `required` ones must be uploaded
    // before the application can be submitted for review.
    'document_types' => [
        'gst_certificate' => [
            'label' => 'GST Registration Certificate',
            'hint' => 'GST certificate or tax registration (PDF or photo)',
            'required' => true,
        ],
        'pan_card' => [
            'label' => 'Business PAN Card',
            'hint' => 'PAN of the business or proprietor',
            'required' => true,
        ],
        'address_proof' => [
            'label' => 'Business Address Proof',
            'hint' => 'Utility bill, rent agreement or trade licence',
            'required' => true,
        ],
        'cancelled_cheque' => [
            'label' => 'Cancelled Cheque / Bank Letter',
            'hint' => 'For payouts and credit set-up',
            'required' => false,
        ],
        'other' => [
            'label' => 'Other Supporting Document',
            'hint' => 'Distributor agreement, catalogue, etc.',
            'required' => false,
        ],
    ],

    'business_types' => [
        'distributor' => 'Distributor',
        'wholesaler' => 'Wholesaler',
        'retailer' => 'Retail Store',
        'institution' => 'School / Institution',
        'reseller' => 'Online Reseller',
        'other' => 'Other',
    ],

    // Payment type stored on orders placed through the partner portal.
    'payment_type' => 'partner_credit',

    // Value stored in orders.order_from for partner orders.
    'order_from' => 'channel_partner',

    // Max units per cart line, to stop fat-finger orders.
    'max_line_quantity' => 100000,
];
