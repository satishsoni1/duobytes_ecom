@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h3">{{ translate('Channel Partners') }}</h1>
                <p class="text-muted mb-0 fs-13">{{ translate('Review applications, verify documents and set contract pricing.') }}</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('channel_partner.landing') }}" target="_blank" class="btn btn-soft-primary btn-sm">
                    <i class="las la-external-link-alt"></i> {{ translate('Partner sign-up page') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row gutters-10 mb-2">
        @foreach (['submitted' => ['warning', 'Awaiting review'], 'approved' => ['success', 'Active partners'], 'draft' => ['secondary', 'Incomplete'], 'rejected' => ['danger', 'Changes requested'], 'suspended' => ['dark', 'Suspended']] as $key => [$color, $label])
            <div class="col-6 col-md">
                <a href="{{ route('admin.channel_partners.index', ['status' => $key]) }}" class="card mb-2 text-reset {{ $status === $key ? 'border-' . $color : '' }}" style="{{ $status === $key ? 'border-width:2px' : '' }}">
                    <div class="card-body py-3">
                        <div class="fs-12 text-muted">{{ translate($label) }}</div>
                        <div class="h3 mb-0 fw-700 text-{{ $color }}">{{ $counts[$key] ?? 0 }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="card">
        <form method="GET" class="card-header row gutters-5">
            <div class="col">
                <h5 class="mb-md-0 h6">{{ $status ? translate(ucfirst($status)) : translate('All partners') }}</h5>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">{{ translate('All statuses') }}</option>
                    @foreach (\App\Models\ChannelPartner::STATUSES as $s)
                        <option value="{{ $s }}" @selected($status === $s)>{{ translate(ucfirst($s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" name="search" value="{{ $search }}" placeholder="{{ translate('Company, code, GSTIN, email, phone, city') }}">
                    <div class="input-group-append"><button class="btn btn-primary" type="submit"><i class="las la-search"></i></button></div>
                </div>
            </div>
        </form>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Partner') }}</th>
                        <th data-breakpoints="md">{{ translate('Contact') }}</th>
                        <th data-breakpoints="md">{{ translate('Location') }}</th>
                        <th data-breakpoints="md">{{ translate('Docs') }}</th>
                        <th data-breakpoints="md">{{ translate('Pricing rules') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th data-breakpoints="md">{{ translate('Submitted') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($partners as $partner)
                        <tr>
                            <td>
                                <a href="{{ route('admin.channel_partners.show', $partner) }}" class="fw-600 text-reset">{{ $partner->company_name ?: '—' }}</a>
                                <div class="fs-12 text-muted">{{ $partner->partner_code ?: translate('No code yet') }} · {{ translate(config('channel_partner.business_types.' . $partner->business_type, '-')) }}</div>
                            </td>
                            <td>
                                {{ $partner->contact_person }}
                                <div class="fs-12 text-muted">{{ optional($partner->user)->email }} · {{ optional($partner->user)->phone }}</div>
                            </td>
                            <td>{{ $partner->city }}{{ $partner->state ? ', ' . $partner->state : '' }}</td>
                            <td>{{ $partner->documents_count }}</td>
                            <td>{{ $partner->price_rules_count }}@if ($partner->default_discount > 0) <span class="fs-12 text-muted">+ {{ $partner->default_discount }}%</span>@endif</td>
                            <td><span class="badge badge-inline badge-{{ $partner->statusBadgeClass() }}">{{ translate(ucfirst($partner->status)) }}</span></td>
                            <td>{{ $partner->submitted_at ? $partner->submitted_at->format('d M Y') : '—' }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.channel_partners.show', $partner) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="{{ translate('Review') }}"><i class="las la-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">{{ translate('No channel partners found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-3">{{ $partners->links() }}</div>
        </div>
    </div>
@endsection
