@extends('channel_partner.layouts.auth')

@php
    use App\Models\ChannelPartner;

    $editable = $partner->canEditApplication();
    $requested = request('step');
    if (!$editable) {
        $step = 'status';
    } elseif (in_array($requested, ['business', 'documents', 'review'], true)) {
        $step = $requested;
    } else {
        $step = $partner->businessDetailsComplete() ? (empty($missingDocuments) ? 'review' : 'documents') : 'business';
    }
    // Do not let partners jump ahead of an incomplete business step
    if (in_array($step, ['documents', 'review'], true) && !$partner->businessDetailsComplete()) {
        $step = 'business';
    }
    $stepNumber = ['business' => 2, 'documents' => 3, 'review' => 4, 'status' => 4][$step];
@endphp

@section('title', translate('Partner application'))
@section('auth_width', 'wide')

@section('content')
    @if ($step !== 'status')
        @include('channel_partner.onboarding.partials.steps', ['current' => $stepNumber])
    @endif

    @if ($partner->status === ChannelPartner::STATUS_REJECTED && $step !== 'status')
        <div class="cp-alert cp-alert-danger" style="margin-bottom:16px" role="alert">
            <x-cp-icon name="alert" class="cp-icon-sm" />
            <div>
                <strong>{{ translate('Changes requested') }}</strong><br>
                {{ $partner->rejection_reason }}
                <div class="cp-small" style="margin-top:4px">{{ translate('Update the details below and submit again.') }}</div>
            </div>
        </div>
    @endif

    {{-- ===================== STEP 2: BUSINESS ===================== --}}
    @if ($step === 'business')
        <h1 class="cp-auth-title">{{ translate('Business details') }}</h1>
        <p class="cp-auth-sub">{{ translate('This appears on your invoices and contract.') }}</p>

        <form method="POST" action="{{ route('channel_partner.onboarding.business') }}" data-cp-ajax
              data-cp-next="{{ route('channel_partner.onboarding', ['step' => 'documents']) }}" novalidate>
            @csrf
            <div class="cp-card cp-card-pad">
                <div class="cp-field">
                    <label class="cp-label" for="company_name">{{ translate('Registered business name') }} <span class="req">*</span></label>
                    <input id="company_name" name="company_name" class="cp-input" value="{{ old('company_name', $partner->company_name) }}" autocomplete="organization" required maxlength="150">
                </div>
                <div class="cp-field">
                    <span class="cp-label">{{ translate('Business type') }} <span class="req">*</span></span>
                    <div class="cp-variants" role="radiogroup">
                        @foreach ($businessTypes as $value => $label)
                            <div class="cp-variant">
                                <input type="radio" id="bt_{{ $value }}" name="business_type" value="{{ $value }}" {{ old('business_type', $partner->business_type) === $value ? 'checked' : '' }}>
                                <label for="bt_{{ $value }}">{{ translate($label) }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="cp-grid cp-md-2">
                    <div class="cp-field">
                        <label class="cp-label" for="contact_person">{{ translate('Contact person') }} <span class="req">*</span></label>
                        <input id="contact_person" name="contact_person" class="cp-input" value="{{ old('contact_person', $partner->contact_person) }}" autocomplete="name" required>
                    </div>
                    <div class="cp-field">
                        <label class="cp-label" for="designation">{{ translate('Designation') }}</label>
                        <input id="designation" name="designation" class="cp-input" value="{{ old('designation', $partner->designation) }}" placeholder="{{ translate('Owner, Purchase Manager…') }}">
                    </div>
                </div>
            </div>

            <div class="cp-card cp-card-pad" style="margin-top:16px">
                <h3 style="margin-bottom:14px">{{ translate('Tax & registration') }}</h3>
                <div class="cp-grid cp-md-2">
                    <div class="cp-field">
                        <label class="cp-label" for="gst_number">{{ translate('GSTIN') }}</label>
                        <input id="gst_number" name="gst_number" class="cp-input" value="{{ old('gst_number', $partner->gst_number) }}" maxlength="15" autocapitalize="characters" spellcheck="false" placeholder="27ABCDE1234F1Z5" style="text-transform:uppercase">
                    </div>
                    <div class="cp-field">
                        <label class="cp-label" for="pan_number">{{ translate('PAN') }}</label>
                        <input id="pan_number" name="pan_number" class="cp-input" value="{{ old('pan_number', $partner->pan_number) }}" maxlength="10" autocapitalize="characters" spellcheck="false" placeholder="ABCDE1234F" style="text-transform:uppercase">
                    </div>
                </div>
                <div class="cp-field">
                    <label class="cp-label" for="registration_number">{{ translate('Trade licence / registration no.') }}</label>
                    <input id="registration_number" name="registration_number" class="cp-input" value="{{ old('registration_number', $partner->registration_number) }}">
                </div>
            </div>

            <div class="cp-card cp-card-pad" style="margin-top:16px">
                <h3 style="margin-bottom:14px">{{ translate('Business address') }}</h3>
                <div class="cp-field">
                    <label class="cp-label" for="address_line">{{ translate('Address') }} <span class="req">*</span></label>
                    <textarea id="address_line" name="address_line" class="cp-input" rows="2" autocomplete="street-address" required>{{ old('address_line', $partner->address_line) }}</textarea>
                </div>
                <div class="cp-grid cp-grid-2">
                    <div class="cp-field">
                        <label class="cp-label" for="city">{{ translate('City') }} <span class="req">*</span></label>
                        <input id="city" name="city" class="cp-input" value="{{ old('city', $partner->city) }}" autocomplete="address-level2" required>
                    </div>
                    <div class="cp-field">
                        <label class="cp-label" for="postal_code">{{ translate('PIN code') }} <span class="req">*</span></label>
                        <input id="postal_code" name="postal_code" class="cp-input" value="{{ old('postal_code', $partner->postal_code) }}" inputmode="numeric" autocomplete="postal-code" required>
                    </div>
                </div>
                <div class="cp-grid cp-grid-2">
                    <div class="cp-field">
                        <label class="cp-label" for="state">{{ translate('State') }} <span class="req">*</span></label>
                        <input id="state" name="state" class="cp-input" value="{{ old('state', $partner->state) }}" autocomplete="address-level1" required>
                    </div>
                    <div class="cp-field">
                        <label class="cp-label" for="country">{{ translate('Country') }}</label>
                        <input id="country" name="country" class="cp-input" value="{{ old('country', $partner->country ?: 'India') }}" autocomplete="country-name">
                    </div>
                </div>
            </div>

            <div class="cp-card cp-card-pad" style="margin-top:16px">
                <h3 style="margin-bottom:14px">{{ translate('About your business') }} <span class="cp-muted cp-small">({{ translate('optional') }})</span></h3>
                <div class="cp-grid cp-md-2">
                    <div class="cp-field">
                        <label class="cp-label" for="expected_monthly_volume">{{ translate('Expected monthly purchase') }}</label>
                        <select id="expected_monthly_volume" name="expected_monthly_volume" class="cp-input">
                            <option value="">{{ translate('Select') }}</option>
                            @foreach (['< 50,000', '50,000 – 2 lakh', '2 – 10 lakh', '10 lakh +'] as $range)
                                <option value="{{ $range }}" {{ old('expected_monthly_volume', $partner->expected_monthly_volume) === $range ? 'selected' : '' }}>{{ $range }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cp-field">
                        <label class="cp-label" for="website">{{ translate('Website') }}</label>
                        <input id="website" name="website" type="url" inputmode="url" class="cp-input" value="{{ old('website', $partner->website) }}" placeholder="https://">
                    </div>
                </div>
            </div>

            <div class="cp-sticky-actions">
                <button type="submit" class="cp-btn cp-btn-primary">{{ translate('Save & continue') }} <x-cp-icon name="arrow-right" class="cp-icon-sm" /></button>
            </div>
        </form>
    @endif

    {{-- ===================== STEP 3: DOCUMENTS ===================== --}}
    @if ($step === 'documents')
        <h1 class="cp-auth-title">{{ translate('Upload documents') }}</h1>
        <p class="cp-auth-sub">{{ translate('Take a clear photo or upload a PDF. Max') }} {{ round(config('channel_partner.document_max_kb') / 1024) }} MB {{ translate('per file.') }}</p>

        <div class="cp-alert cp-alert-info" style="margin-bottom:16px;{{ empty($missingDocuments) ? 'display:none' : '' }}" data-docs-missing-wrap>
            <x-cp-icon name="info" class="cp-icon-sm" />
            <div><span data-docs-missing>{{ count($missingDocuments) }}</span> {{ translate('required document(s) still needed') }}</div>
        </div>

        <div class="cp-stack">
            @foreach ($documentTypes as $type => $meta)
                @php $files = $documents->get($type, collect()); @endphp
                <section class="cp-doc {{ $files->isNotEmpty() ? 'is-complete' : '' }}" data-doc-type="{{ $type }}" data-required="{{ $meta['required'] ? 1 : 0 }}"
                         data-upload-url="{{ route('channel_partner.onboarding.documents.store') }}">
                    <div class="cp-doc-head">
                        <span class="cp-doc-icon"><x-cp-icon name="file" /></span>
                        <div style="flex:1;min-width:0">
                            <div class="cp-between">
                                <span class="cp-doc-title">{{ translate($meta['label']) }}</span>
                                <span class="cp-pill cp-pill-success" data-doc-state @if ($files->isEmpty()) style="display:none" @endif>
                                    <x-cp-icon name="check" class="cp-icon-sm" /> {{ translate('Added') }}
                                </span>
                                <span class="cp-pill {{ $meta['required'] ? 'cp-pill-warning' : 'cp-pill-muted' }}" data-doc-pending @if ($files->isNotEmpty()) style="display:none" @endif>
                                    {{ $meta['required'] ? translate('Required') : translate('Optional') }}
                                </span>
                            </div>
                            <div class="cp-muted cp-small">{{ translate($meta['hint']) }}</div>
                        </div>
                    </div>

                    <ul class="cp-files">
                        @foreach ($files as $doc)
                            <li class="cp-file" data-doc-id="{{ $doc->id }}">
                                @if ($doc->isImage())
                                    <img class="cp-file-thumb" src="{{ route('channel_partner.documents.show', $doc) }}" alt="" loading="lazy">
                                @else
                                    <span class="cp-file-thumb"><x-cp-icon name="file" /></span>
                                @endif
                                <div style="min-width:0;flex:1">
                                    <a class="cp-file-name" href="{{ route('channel_partner.documents.show', $doc) }}" target="_blank" rel="noopener">{{ $doc->original_name }}</a>
                                    <div class="cp-file-meta">
                                        {{ $doc->humanSize() }}
                                        @if ($doc->status === 'rejected')
                                            · <span style="color:var(--cp-danger)">{{ translate('Rejected') }}{{ $doc->remarks ? ': ' . $doc->remarks : '' }}</span>
                                        @elseif ($doc->status === 'verified')
                                            · <span style="color:var(--cp-success)">{{ translate('Verified') }}</span>
                                        @endif
                                    </div>
                                </div>
                                @if ($doc->status !== 'verified')
                                    <button type="button" class="cp-icon-btn" data-doc-delete data-url="{{ route('channel_partner.onboarding.documents.destroy', $doc) }}" aria-label="{{ translate('Remove') }}">
                                        <x-cp-icon name="trash" />
                                    </button>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <div class="cp-doc-actions">
                        <label class="cp-btn cp-btn-light">
                            <x-cp-icon name="camera" class="cp-icon-sm" /> {{ translate('Take photo') }}
                            <input type="file" accept="image/*" capture="environment" class="sr-only">
                        </label>
                        <label class="cp-btn cp-btn-light">
                            <x-cp-icon name="upload" class="cp-icon-sm" /> {{ translate('Upload file') }}
                            <input type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/*" multiple class="sr-only">
                        </label>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="cp-sticky-actions">
            <a href="{{ route('channel_partner.onboarding', ['step' => 'business']) }}" class="cp-btn cp-btn-light" style="flex:0 0 auto">
                <x-cp-icon name="arrow-left" class="cp-icon-sm" /><span class="sr-only">{{ translate('Back') }}</span>
            </a>
            <a href="{{ route('channel_partner.onboarding', ['step' => 'review']) }}" data-docs-next
               class="cp-btn cp-btn-primary {{ empty($missingDocuments) ? '' : 'disabled' }}" aria-disabled="{{ empty($missingDocuments) ? 'false' : 'true' }}">
                {{ translate('Continue to review') }} <x-cp-icon name="arrow-right" class="cp-icon-sm" />
            </a>
        </div>
    @endif

    {{-- ===================== STEP 4: REVIEW ===================== --}}
    @if ($step === 'review')
        <h1 class="cp-auth-title">{{ translate('Review & submit') }}</h1>
        <p class="cp-auth-sub">{{ translate('Check everything once. Our team usually reviews applications within 1–2 working days.') }}</p>

        <div class="cp-card">
            <div class="cp-card-head">
                <h3>{{ translate('Business') }}</h3>
                <a href="{{ route('channel_partner.onboarding', ['step' => 'business']) }}" class="cp-small">{{ translate('Edit') }}</a>
            </div>
            <dl class="cp-review-list" style="padding:0 16px">
                <div><dt>{{ translate('Business') }}</dt><dd>{{ $partner->company_name }}</dd></div>
                <div><dt>{{ translate('Type') }}</dt><dd>{{ translate($businessTypes[$partner->business_type] ?? '-') }}</dd></div>
                <div><dt>{{ translate('Contact') }}</dt><dd>{{ $partner->contact_person }}{{ $partner->designation ? ', ' . $partner->designation : '' }}</dd></div>
                <div><dt>{{ translate('GSTIN') }}</dt><dd>{{ $partner->gst_number ?: '—' }}</dd></div>
                <div><dt>{{ translate('PAN') }}</dt><dd>{{ $partner->pan_number ?: '—' }}</dd></div>
                <div style="border-bottom:0"><dt>{{ translate('Address') }}</dt><dd>{{ $partner->address_line }}, {{ $partner->city }}, {{ $partner->state }} {{ $partner->postal_code }}</dd></div>
            </dl>
        </div>

        <div class="cp-card" style="margin-top:16px">
            <div class="cp-card-head">
                <h3>{{ translate('Documents') }}</h3>
                <a href="{{ route('channel_partner.onboarding', ['step' => 'documents']) }}" class="cp-small">{{ translate('Edit') }}</a>
            </div>
            <ul class="cp-list">
                @foreach ($documentTypes as $type => $meta)
                    @php $count = $documents->get($type, collect())->count(); @endphp
                    @if ($count || $meta['required'])
                        <li class="cp-row" style="padding:12px 16px">
                            <x-cp-icon :name="$count ? 'check-circle' : 'alert'" class="cp-icon-sm" style="color:{{ $count ? 'var(--cp-success)' : 'var(--cp-danger)' }}" />
                            <span>{{ translate($meta['label']) }}</span>
                            <span class="cp-muted cp-small" style="margin-left:auto">{{ $count }} {{ translate('file(s)') }}</span>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>

        <form method="POST" action="{{ route('channel_partner.onboarding.submit') }}" style="margin-top:16px">
            @csrf
            <label class="cp-check cp-card cp-card-pad">
                <input type="checkbox" name="declaration" value="1" required>
                <span>{{ translate('I confirm that the information and documents provided are true and belong to my business.') }}</span>
            </label>
            @error('declaration')<div class="cp-error">{{ $message }}</div>@enderror

            <div class="cp-sticky-actions">
                <a href="{{ route('channel_partner.onboarding', ['step' => 'documents']) }}" class="cp-btn cp-btn-light" style="flex:0 0 auto">
                    <x-cp-icon name="arrow-left" class="cp-icon-sm" /><span class="sr-only">{{ translate('Back') }}</span>
                </a>
                <button type="submit" class="cp-btn cp-btn-primary" @if (!empty($missingDocuments)) disabled @endif>{{ translate('Submit application') }}</button>
            </div>
        </form>
    @endif

    {{-- ===================== STATUS ===================== --}}
    @if ($step === 'status')
        @php
            $suspended = $partner->status === ChannelPartner::STATUS_SUSPENDED;
        @endphp
        <div class="cp-card cp-status-hero" style="margin-top:12px">
            <div class="cp-status-icon" style="background:{{ $suspended ? 'var(--cp-danger-50)' : 'var(--cp-warning-50)' }};color:{{ $suspended ? 'var(--cp-danger)' : 'var(--cp-warning)' }}">
                <x-cp-icon :name="$suspended ? 'lock' : 'clock'" class="cp-icon-lg" />
            </div>
            @if ($suspended)
                <h1>{{ translate('Account on hold') }}</h1>
                <p class="cp-muted" style="margin-top:8px">{{ $partner->rejection_reason ?: translate('Ordering is paused on your partner account. Please contact your account manager.') }}</p>
            @else
                <h1>{{ translate('Application under review') }}</h1>
                <p class="cp-muted" style="margin-top:8px">{{ translate('Thanks') }}, {{ $partner->contact_person }}! {{ translate('We are verifying your details and will email you at') }} <strong>{{ auth()->user()->email }}</strong>.</p>
                <ol class="cp-timeline">
                    <li class="done"><span class="dot"></span><strong>{{ translate('Account created') }}</strong><div class="cp-muted cp-small">{{ $partner->created_at->format('d M Y') }}</div></li>
                    <li class="done"><span class="dot"></span><strong>{{ translate('Application submitted') }}</strong><div class="cp-muted cp-small">{{ optional($partner->submitted_at)->format('d M Y, h:i A') }}</div></li>
                    <li class="current"><span class="dot"></span><strong>{{ translate('Verification & contract set-up') }}</strong><div class="cp-muted cp-small">{{ translate('Usually 1–2 working days') }}</div></li>
                    <li><span class="dot"></span><strong>{{ translate('Start ordering') }}</strong></li>
                </ol>
            @endif
            @if (get_setting('helpline_number'))
                <a href="tel:{{ get_setting('helpline_number') }}" class="cp-btn cp-btn-light cp-btn-block" style="margin-top:8px">
                    <x-cp-icon name="phone" class="cp-icon-sm" /> {{ translate('Call us') }} {{ get_setting('helpline_number') }}
                </a>
            @endif
            <a href="{{ route('channel_partner.onboarding') }}" class="cp-btn cp-btn-ghost cp-btn-block" style="margin-top:8px">
                <x-cp-icon name="refresh" class="cp-icon-sm" /> {{ translate('Refresh status') }}
            </a>
        </div>
    @endif
@endsection
