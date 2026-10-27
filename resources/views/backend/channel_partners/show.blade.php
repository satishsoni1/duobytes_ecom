@extends('backend.layouts.app')

@php
    use App\Models\ChannelPartner;
    use App\Models\ChannelPartnerPriceRule;
    $user = $partner->user;
    $canManage = auth()->user()->can('manage_channel_partners');
    $canPrice = auth()->user()->can('manage_channel_partner_pricing');
@endphp

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <a href="{{ route('admin.channel_partners.index') }}" class="fs-13 text-muted"><i class="las la-arrow-left"></i> {{ translate('Channel Partners') }}</a>
        <div class="row align-items-center mt-2">
            <div class="col-md">
                <h1 class="h3 mb-1">{{ $partner->displayName() ?: translate('Unnamed partner') }}
                    <span class="badge badge-inline badge-{{ $partner->statusBadgeClass() }} align-middle fs-12">{{ translate(ucfirst($partner->status)) }}</span>
                </h1>
                <div class="text-muted fs-13">
                    {{ $partner->partner_code ?: translate('Code assigned on approval') }}
                    · {{ translate('Registered') }} {{ $partner->created_at->format('d M Y') }}
                    @if ($partner->submitted_at) · {{ translate('Submitted') }} {{ $partner->submitted_at->format('d M Y, h:i A') }} @endif
                    @if ($partner->approved_at) · {{ translate('Approved') }} {{ $partner->approved_at->format('d M Y') }}{{ $partner->approver ? ' ' . translate('by') . ' ' . $partner->approver->name : '' }} @endif
                </div>
            </div>
            @if ($canManage)
                <div class="col-md-auto mt-3 mt-md-0">
                    @if (in_array($partner->status, [ChannelPartner::STATUS_SUBMITTED, ChannelPartner::STATUS_DRAFT, ChannelPartner::STATUS_REJECTED]))
                        <form method="POST" action="{{ route('admin.channel_partners.status', $partner) }}" class="d-inline">
                            @csrf <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success" onclick="return confirm('{{ translate('Approve this partner? They will be able to order immediately.') }}')"><i class="las la-check"></i> {{ translate('Approve') }}</button>
                        </form>
                    @endif
                    @if (in_array($partner->status, [ChannelPartner::STATUS_SUBMITTED, ChannelPartner::STATUS_DRAFT]))
                        <button type="button" class="btn btn-soft-danger" data-toggle="modal" data-target="#reject-modal"><i class="las la-times"></i> {{ translate('Request changes') }}</button>
                    @endif
                    @if ($partner->status === ChannelPartner::STATUS_APPROVED)
                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#suspend-modal"><i class="las la-pause"></i> {{ translate('Suspend') }}</button>
                    @endif
                    @if ($partner->status === ChannelPartner::STATUS_SUSPENDED)
                        <form method="POST" action="{{ route('admin.channel_partners.status', $partner) }}" class="d-inline">
                            @csrf <input type="hidden" name="action" value="reactivate">
                            <button type="submit" class="btn btn-success"><i class="las la-play"></i> {{ translate('Reactivate') }}</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
        @if ($partner->rejection_reason && in_array($partner->status, [ChannelPartner::STATUS_REJECTED, ChannelPartner::STATUS_SUSPENDED]))
            <div class="alert alert-danger mt-3 mb-0 fs-13"><strong>{{ translate('Reason shared with partner') }}:</strong> {{ $partner->rejection_reason }}</div>
        @endif
    </div>

    <div class="row gutters-10">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('Business details') }}</h5></div>
                <div class="card-body">
                    <table class="table table-sm table-borderless fs-13 mb-0">
                        @foreach ([
                            'Business type' => translate(config('channel_partner.business_types.' . $partner->business_type, '-')),
                            'Contact person' => $partner->contact_person . ($partner->designation ? ' (' . $partner->designation . ')' : ''),
                            'Email' => optional($user)->email,
                            'Mobile' => optional($user)->phone,
                            'GSTIN' => $partner->gst_number,
                            'PAN' => $partner->pan_number,
                            'Registration no.' => $partner->registration_number,
                            'Address' => trim($partner->address_line . ', ' . $partner->city . ', ' . $partner->state . ' ' . $partner->postal_code . ', ' . $partner->country, ', '),
                            'Website' => $partner->website,
                            'Expected monthly purchase' => $partner->expected_monthly_volume,
                        ] as $label => $value)
                            <tr><td class="text-muted pl-0" style="width:40%">{{ translate($label) }}</td><td class="fw-600 text-break">{{ $value ?: '—' }}</td></tr>
                        @endforeach
                    </table>
                </div>
            </div>

            <div class="card" id="documents">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('KYC documents') }}</h5>
                    @if ($partner->missingRequiredDocuments())
                        <span class="badge badge-inline badge-warning">{{ count($partner->missingRequiredDocuments()) }} {{ translate('required missing') }}</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    @forelse ($partner->documents->groupBy('document_type') as $type => $docs)
                        <div class="px-3 pt-3 fs-12 text-uppercase text-muted fw-600">{{ $docs->first()->typeLabel() }}</div>
                        @foreach ($docs as $doc)
                            <div class="d-flex align-items-center px-3 py-2 border-bottom">
                                <a href="{{ route('admin.channel_partners.documents.show', $doc) }}" target="_blank" rel="noopener" class="mr-2 flex-shrink-0">
                                    @if ($doc->isImage())
                                        <img src="{{ route('admin.channel_partners.documents.show', $doc) }}" class="rounded border" style="width:48px;height:48px;object-fit:cover" alt="">
                                    @else
                                        <span class="d-inline-flex align-items-center justify-content-center rounded border bg-light" style="width:48px;height:48px"><i class="las la-file-pdf la-2x text-danger"></i></span>
                                    @endif
                                </a>
                                <div class="flex-grow-1 minw-0">
                                    <a href="{{ route('admin.channel_partners.documents.show', $doc) }}" target="_blank" rel="noopener" class="fs-13 fw-600 text-reset text-truncate d-block">{{ $doc->original_name }}</a>
                                    <div class="fs-12 text-muted">{{ $doc->humanSize() }} · {{ $doc->created_at->format('d M Y') }}
                                        @if ($doc->remarks) · {{ $doc->remarks }} @endif
                                    </div>
                                </div>
                                @if ($canManage)
                                    <form method="POST" action="{{ route('admin.channel_partners.documents.status', $doc) }}" class="ml-2 d-flex align-items-center">
                                        @csrf
                                        <select name="status" class="form-control form-control-sm" style="width:auto" onchange="if(this.value==='rejected'){var r=prompt('{{ translate('Reason (shown to partner)') }}');if(r===null){this.value='{{ $doc->status }}';return;}this.form.remarks.value=r;}this.form.submit()">
                                            @foreach (['pending', 'verified', 'rejected'] as $s)
                                                <option value="{{ $s }}" @selected($doc->status === $s)>{{ translate(ucfirst($s)) }}</option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="remarks" value="{{ $doc->remarks }}">
                                    </form>
                                @else
                                    <span class="badge badge-inline badge-{{ ['verified' => 'success', 'rejected' => 'danger'][$doc->status] ?? 'secondary' }}">{{ translate(ucfirst($doc->status)) }}</span>
                                @endif
                            </div>
                        @endforeach
                    @empty
                        <div class="text-center text-muted py-4 fs-13">{{ translate('No documents uploaded yet.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card" id="terms">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('Contract terms') }}</h5><span class="fs-12 text-muted">{{ translate('As agreed offline with the partner') }}</span></div>
                <form method="POST" action="{{ route('admin.channel_partners.terms', $partner) }}" class="card-body">
                    @csrf
                    <fieldset @disabled(!$canManage)>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="fs-13">{{ translate('Default discount %') }}</label>
                                <input type="number" step="0.01" min="0" max="100" name="default_discount" class="form-control" value="{{ old('default_discount', $partner->default_discount) }}">
                                <small class="text-muted">{{ translate('Applies when no rule matches') }}</small>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="fs-13">{{ translate('Minimum order value') }}</label>
                                <input type="number" step="0.01" min="0" name="min_order_value" class="form-control" value="{{ old('min_order_value', $partner->min_order_value) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label class="fs-13">{{ translate('Contract reference') }}</label>
                                <input type="text" name="contract_reference" class="form-control" value="{{ old('contract_reference', $partner->contract_reference) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label class="fs-13">{{ translate('Valid until') }}</label>
                                <input type="date" name="contract_valid_until" class="form-control" value="{{ old('contract_valid_until', optional($partner->contract_valid_until)->format('Y-m-d')) }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="fs-13">{{ translate('Internal notes') }} <span class="text-muted">({{ translate('not visible to partner') }})</span></label>
                            <textarea name="admin_notes" rows="2" class="form-control">{{ old('admin_notes', $partner->admin_notes) }}</textarea>
                        </div>
                        @if ($canManage)
                            <div class="text-right"><button type="submit" class="btn btn-primary">{{ translate('Save terms') }}</button></div>
                        @endif
                    </fieldset>
                </form>
            </div>

            <div class="card" id="pricing">
                <div class="card-header">
                    <div>
                        <h5 class="mb-0 h6">{{ translate('Pricing rules') }}</h5>
                        <div class="fs-12 text-muted">{{ translate('Product rules override category rules, which override "all products" rules. Within one scope the highest matching min. quantity wins (bulk tiers). Partners never pay above the public price.') }}</div>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-sm fs-13">
                        <thead><tr><th>{{ translate('Applies to') }}</th><th>{{ translate('Min qty') }}</th><th>{{ translate('Discount') }}</th><th>{{ translate('Validity') }}</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($partner->priceRules->sortBy([['product_id', 'desc'], ['category_id', 'desc'], ['min_qty', 'asc']]) as $rule)
                                <tr class="{{ $rule->is_active ? '' : 'text-muted' }}">
                                    <td>
                                        {{ $rule->targetLabel() }}
                                        @if ($rule->note)<div class="fs-11 text-muted">{{ $rule->note }}</div>@endif
                                    </td>
                                    <td>{{ $rule->min_qty }}+</td>
                                    <td>{{ $rule->describe() }}</td>
                                    <td class="fs-12">
                                        {{ $rule->starts_at ? $rule->starts_at->format('d M y') : translate('Now') }} – {{ $rule->ends_at ? $rule->ends_at->format('d M y') : translate('No end') }}
                                        @unless ($rule->is_active)<span class="badge badge-inline badge-secondary">{{ translate('Paused') }}</span>@endunless
                                    </td>
                                    <td class="text-right text-nowrap">
                                        @if ($canPrice)
                                            <form method="POST" action="{{ route('admin.channel_partners.rules.update', $rule) }}" class="d-inline">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="scope" value="{{ $rule->product_id ? 'product' : ($rule->category_id ? 'category' : 'all') }}">
                                                <input type="hidden" name="product_id" value="{{ $rule->product_id }}">
                                                <input type="hidden" name="category_id" value="{{ $rule->category_id }}">
                                                <input type="hidden" name="min_qty" value="{{ $rule->min_qty }}">
                                                <input type="hidden" name="discount_type" value="{{ $rule->discount_type }}">
                                                <input type="hidden" name="discount_value" value="{{ $rule->discount_value }}">
                                                <input type="hidden" name="starts_at" value="{{ optional($rule->starts_at)->format('Y-m-d') }}">
                                                <input type="hidden" name="ends_at" value="{{ optional($rule->ends_at)->format('Y-m-d') }}">
                                                <input type="hidden" name="note" value="{{ $rule->note }}">
                                                <input type="hidden" name="is_active" value="{{ $rule->is_active ? 0 : 1 }}">
                                                <button class="btn btn-soft-secondary btn-icon btn-circle btn-sm" title="{{ $rule->is_active ? translate('Pause') : translate('Resume') }}"><i class="las {{ $rule->is_active ? 'la-pause' : 'la-play' }}"></i></button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.channel_partners.rules.destroy', $rule) }}" class="d-inline" onsubmit="return confirm('{{ translate('Delete this pricing rule?') }}')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-soft-danger btn-icon btn-circle btn-sm" title="{{ translate('Delete') }}"><i class="las la-trash"></i></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">{{ translate('No rules yet. The default discount applies to all products.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($canPrice)
                        <form method="POST" action="{{ route('admin.channel_partners.rules.store', $partner) }}" class="border rounded p-3 bg-light" id="rule-form">
                            @csrf
                            <h6 class="fs-14 fw-700 mb-3">{{ translate('Add pricing rule') }}</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="fs-13">{{ translate('Applies to') }}</label>
                                    <select name="scope" class="form-control" id="rule-scope">
                                        <option value="product" @selected(old('scope', 'product') === 'product')>{{ translate('A product') }}</option>
                                        <option value="category" @selected(old('scope') === 'category')>{{ translate('A category') }}</option>
                                        <option value="all" @selected(old('scope') === 'all')>{{ translate('All products') }}</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-8" data-scope="product">
                                    <label class="fs-13">{{ translate('Product') }}</label>
                                    <select name="product_id" class="form-control aiz-selectpicker" data-live-search="true" title="{{ translate('Search product') }}">
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}" @selected(old('product_id') == $p->id) data-subtext="{{ single_price($p->unit_price) }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-8" data-scope="category" style="display:none">
                                    <label class="fs-13">{{ translate('Category') }}</label>
                                    <select name="category_id" class="form-control aiz-selectpicker" data-live-search="true" title="{{ translate('Search category') }}">
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}" @selected(old('category_id') == $c->id)>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-6 col-md-3">
                                    <label class="fs-13">{{ translate('Min quantity') }}</label>
                                    <input type="number" name="min_qty" min="1" class="form-control" value="{{ old('min_qty', 1) }}" required>
                                </div>
                                <div class="form-group col-6 col-md-4">
                                    <label class="fs-13">{{ translate('Type') }}</label>
                                    <select name="discount_type" class="form-control">
                                        @foreach (ChannelPartnerPriceRule::TYPES as $value => $label)
                                            <option value="{{ $value }}" @selected(old('discount_type') === $value)>{{ translate($label) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-5">
                                    <label class="fs-13">{{ translate('Value') }}</label>
                                    <input type="number" name="discount_value" step="0.01" min="0" class="form-control" value="{{ old('discount_value') }}" required placeholder="{{ translate('e.g. 12 for 12%, or a price') }}">
                                </div>
                                <div class="form-group col-6 col-md-3">
                                    <label class="fs-13">{{ translate('Starts') }}</label>
                                    <input type="date" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
                                </div>
                                <div class="form-group col-6 col-md-3">
                                    <label class="fs-13">{{ translate('Ends') }}</label>
                                    <input type="date" name="ends_at" class="form-control" value="{{ old('ends_at') }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="fs-13">{{ translate('Note') }}</label>
                                    <input type="text" name="note" maxlength="255" class="form-control" value="{{ old('note') }}" placeholder="{{ translate('e.g. Diwali scheme') }}">
                                </div>
                            </div>
                            <input type="hidden" name="is_active" value="1">
                            <div class="text-right"><button type="submit" class="btn btn-primary">{{ translate('Add rule') }}</button></div>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('Recent partner orders') }}</h5></div>
                <div class="card-body">
                    <table class="table aiz-table mb-0 fs-13">
                        <thead><tr><th>{{ translate('Order') }}</th><th data-breakpoints="md">{{ translate('PO') }}</th><th>{{ translate('Total') }}</th><th data-breakpoints="md">{{ translate('Saved') }}</th><th>{{ translate('Status') }}</th><th data-breakpoints="md">{{ translate('Payment') }}</th></tr></thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr>
                                    <td>
                                        @can('view_order_details')
                                            <a href="{{ route('all_orders.show', encrypt($order->id)) }}" class="fw-600">{{ $order->code }}</a>
                                        @else
                                            {{ $order->code }}
                                        @endcan
                                        <div class="fs-12 text-muted">{{ date('d M Y', $order->date) }}</div>
                                    </td>
                                    <td>{{ optional($orderMeta->get($order->id))->po_number ?: '—' }}</td>
                                    <td>{{ single_price($order->grand_total) }}</td>
                                    <td>{{ single_price(optional($orderMeta->get($order->id))->discount_total ?? 0) }}</td>
                                    <td>{{ translate(ucfirst(str_replace('_', ' ', $order->delivery_status))) }}</td>
                                    <td><span class="badge badge-inline badge-{{ $order->payment_status === 'paid' ? 'success' : 'danger' }}">{{ translate(ucfirst($order->payment_status)) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">{{ translate('No orders yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modal')
    @foreach (['reject' => ['reject', translate('Request changes'), translate('Tell the partner what to fix. They can update the application and resubmit.'), true], 'suspend' => ['suspend', translate('Suspend partner'), translate('The partner will not be able to order until reactivated.'), false]] as $id => [$action, $title, $help, $required])
        <div class="modal fade" id="{{ $id }}-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form method="POST" action="{{ route('admin.channel_partners.status', $partner) }}" class="modal-content">
                    @csrf
                    <input type="hidden" name="action" value="{{ $action }}">
                    <div class="modal-header"><h5 class="modal-title h6">{{ $title }}</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <p class="fs-13 text-muted">{{ $help }}</p>
                        <textarea name="reason" rows="4" class="form-control" maxlength="1000" @if ($required) required @endif placeholder="{{ translate('e.g. GST certificate is not readable, please upload a clearer copy.') }}"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ $title }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection

@section('script')
    <script>
        (function () {
            var scope = document.getElementById('rule-scope');
            if (!scope) return;
            function sync() {
                document.querySelectorAll('#rule-form [data-scope]').forEach(function (el) {
                    el.style.display = el.getAttribute('data-scope') === scope.value ? '' : 'none';
                });
            }
            scope.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
