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

    // Partners sign up with basic details only; no document upload step.
    // Add types here (label, hint, required) to bring the upload step back.
    'document_types' => [],

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
