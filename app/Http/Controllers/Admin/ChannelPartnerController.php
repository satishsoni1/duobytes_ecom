<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ChannelPartner\OnboardingController;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ChannelPartner;
use App\Models\ChannelPartnerDocument;
use App\Models\ChannelPartnerOrder;
use App\Models\ChannelPartnerPriceRule;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\ChannelPartnerStatusChanged;
use App\Services\GstInvoiceBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use PDF;

class ChannelPartnerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_channel_partners'])->only('index', 'show', 'document', 'invoiceSample');
        $this->middleware(['permission:manage_channel_partners'])->only('updateStatus', 'updateTerms', 'documentStatus');
        $this->middleware(['permission:manage_channel_partner_pricing'])->only('storeRule', 'updateRule', 'destroyRule');
    }

    /**
     * Preview of the GST Tax Invoice layout with sample data.
     * ?type=customer for a direct (inter-state) customer, ?download=1 for the PDF.
     */
    public function invoiceSample(Request $request, GstInvoiceBuilder $builder)
    {
        $type = $request->query('type') === 'customer' ? 'customer' : 'partner';
        $invoice = $builder->sample($type);

        if ($request->boolean('download')) {
            return PDF::loadView('invoices.gst_tax_invoice', ['invoice' => $invoice, 'font_family' => 'freeserif'])
                ->download('sample-tax-invoice-' . $type . '.pdf');
        }

        return view('invoices.gst_tax_invoice', [
            'invoice' => $invoice,
            'actions' => [
                translate('Channel partner sample') => route('admin.channel_partners.invoice_sample'),
                translate('Direct customer sample') => route('admin.channel_partners.invoice_sample', ['type' => 'customer']),
                translate('Download PDF') => route('admin.channel_partners.invoice_sample', ['type' => $type, 'download' => 1]),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('search'));

        $partners = ChannelPartner::with('user')
            ->withCount(['documents', 'priceRules'])
            ->when($status && in_array($status, ChannelPartner::STATUSES), fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('company_name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('partner_code', 'like', "%{$search}%")
                        ->orWhere('gst_number', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            // Pending reviews first, oldest submission first
            ->orderByRaw("CASE WHEN status = 'submitted' THEN 0 ELSE 1 END")
            ->orderByRaw('COALESCE(submitted_at, created_at) ASC')
            ->paginate(20)
            ->withQueryString();

        $counts = ChannelPartner::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('backend.channel_partners.index', compact('partners', 'counts', 'status', 'search'));
    }

    public function show(ChannelPartner $partner)
    {
        $partner->load(['user', 'documents', 'priceRules.product.product_translations', 'priceRules.category.category_translations', 'approver']);

        $orders = Order::where('user_id', $partner->user_id)
            ->where('order_from', config('channel_partner.order_from'))
            ->latest('id')
            ->limit(20)
            ->get();
        $orderMeta = ChannelPartnerOrder::whereIn('order_id', $orders->pluck('id'))->get()->keyBy('order_id');

        $products = Product::where('auction_product', 0)->where('digital', 0)->orderBy('name')->get(['id', 'name', 'unit_price']);
        $categories = Category::orderBy('name')->get(['id', 'name', 'parent_id']);

        return view('backend.channel_partners.show', compact('partner', 'orders', 'orderMeta', 'products', 'categories'));
    }

    public function updateStatus(Request $request, ChannelPartner $partner)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject', 'suspend', 'reactivate'])],
            'reason' => ['required_if:action,reject', 'nullable', 'string', 'max:1000'],
        ], [
            'reason.required_if' => translate('Please give the partner a reason so they can fix their application.'),
        ]);

        $allowed = [
            'approve' => [ChannelPartner::STATUS_SUBMITTED, ChannelPartner::STATUS_REJECTED, ChannelPartner::STATUS_DRAFT],
            'reject' => [ChannelPartner::STATUS_SUBMITTED, ChannelPartner::STATUS_DRAFT],
            'suspend' => [ChannelPartner::STATUS_APPROVED],
            'reactivate' => [ChannelPartner::STATUS_SUSPENDED],
        ];
        if (!in_array($partner->status, $allowed[$data['action']], true)) {
            flash(translate('This action is not available for the current status.'))->warning();
            return back();
        }

        switch ($data['action']) {
            case 'approve':
            case 'reactivate':
                $partner->status = ChannelPartner::STATUS_APPROVED;
                $partner->partner_code = $partner->partner_code ?: ChannelPartner::generateCode();
                $partner->approved_at = $partner->approved_at ?: now();
                $partner->approved_by = $partner->approved_by ?: Auth::id();
                $partner->rejection_reason = null;
                $partner->documents()->where('status', 'pending')->update(['status' => 'verified']);
                break;
            case 'reject':
                $partner->status = ChannelPartner::STATUS_REJECTED;
                $partner->rejection_reason = $data['reason'];
                break;
            case 'suspend':
                $partner->status = ChannelPartner::STATUS_SUSPENDED;
                $partner->rejection_reason = $data['reason'] ?? null;
                break;
        }
        $partner->save();

        try {
            $partner->user->notify(new ChannelPartnerStatusChanged($partner));
        } catch (\Throwable $e) {
            Log::warning('Channel partner status mail failed: ' . $e->getMessage());
        }

        flash(translate('Partner status updated'))->success();
        return back();
    }

    public function updateTerms(Request $request, ChannelPartner $partner)
    {
        $data = $request->validate([
            'default_discount' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'contract_reference' => ['nullable', 'string', 'max:100'],
            'contract_valid_until' => ['nullable', 'date'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $data['min_order_value'] = $data['min_order_value'] ?? 0;

        $partner->fill($data)->save();

        flash(translate('Contract terms saved'))->success();
        return redirect()->to(route('admin.channel_partners.show', $partner) . '#terms');
    }

    public function storeRule(Request $request, ChannelPartner $partner)
    {
        $partner->priceRules()->create($this->validatedRule($request));

        flash(translate('Pricing rule added'))->success();
        return redirect()->to(route('admin.channel_partners.show', $partner) . '#pricing');
    }

    public function updateRule(Request $request, ChannelPartnerPriceRule $rule)
    {
        $rule->fill($this->validatedRule($request))->save();

        flash(translate('Pricing rule updated'))->success();
        return redirect()->to(route('admin.channel_partners.show', $rule->channel_partner_id) . '#pricing');
    }

    public function destroyRule(ChannelPartnerPriceRule $rule)
    {
        $partnerId = $rule->channel_partner_id;
        $rule->delete();

        flash(translate('Pricing rule removed'))->success();
        return redirect()->to(route('admin.channel_partners.show', $partnerId) . '#pricing');
    }

    public function document(ChannelPartnerDocument $document)
    {
        return OnboardingController::streamDocument($document);
    }

    public function documentStatus(Request $request, ChannelPartnerDocument $document)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'verified', 'rejected'])],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $document->fill($data)->save();

        flash(translate('Document updated'))->success();
        return redirect()->to(route('admin.channel_partners.show', $document->channel_partner_id) . '#documents');
    }

    private function validatedRule(Request $request): array
    {
        $data = $request->validate([
            'scope' => ['required', Rule::in(['all', 'category', 'product'])],
            'product_id' => ['required_if:scope,product', 'nullable', 'integer', 'exists:products,id'],
            'category_id' => ['required_if:scope,category', 'nullable', 'integer', 'exists:categories,id'],
            'min_qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'discount_type' => ['required', Rule::in(array_keys(ChannelPartnerPriceRule::TYPES))],
            'discount_value' => ['required', 'numeric', 'min:0', $request->input('discount_type') === 'percent' ? 'max:100' : 'max:99999999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'note' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'product_id' => $data['scope'] === 'product' ? $data['product_id'] : null,
            'category_id' => $data['scope'] === 'category' ? $data['category_id'] : null,
            'min_qty' => $data['min_qty'],
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'note' => $data['note'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
