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
    | the admin as pricing rules. Orders are paid upfront (no credit).
    |
    */

    'enabled' => env('CHANNEL_PARTNER_ENABLED', true),

    // Disk for KYC documents. Must NOT be a public disk.
    'document_disk' => env('CHANNEL_PARTNER_DOCUMENT_DISK', 'channel_partner_documents'),

    'document_max_kb' => (int) env('CHANNEL_PARTNER_DOCUMENT_MAX_KB', 5120),

    'document_mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],

    // Document types shown in onboarding. `required` ones must be uploaded
    // before the application can be submitted.
    'document_types' => [
        'aadhaar_card' => [
            'label' => 'Aadhaar Card',
            'hint' => 'Front and back of your Aadhaar card',
            'required' => true,
        ],
        'gst_certificate' => [
            'label' => 'GST Registration Certificate',
            'hint' => 'Upload if you want to avail GST credit',
            'required' => false,
        ],
        'other' => [
            'label' => 'Other Supporting Document (if any)',
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
    // Partners pay the full order value upfront; there is no credit.
    'payment_type' => 'advance_payment',

    // Value stored in orders.order_from for partner orders.
    'order_from' => 'channel_partner',

    // Max units per cart line, to stop fat-finger orders.
    'max_line_quantity' => 100000,
];
