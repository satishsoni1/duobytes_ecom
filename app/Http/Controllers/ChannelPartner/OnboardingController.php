<?php

namespace App\Http\Controllers\ChannelPartner;

use App\Http\Controllers\Controller;
use App\Models\ChannelPartner;
use App\Models\ChannelPartnerDocument;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Notifications\ChannelPartnerApplicationSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    private const MAX_FILES_PER_TYPE = 5;

    private $addressStates = null;

    public function show(Request $request)
    {
        $partner = $this->partner($request);

        if ($partner->isApproved()) {
            return redirect()->route('channel_partner.dashboard');
        }

        return view('channel_partner.onboarding.index', [
            'partner' => $partner,
            'businessTypes' => $this->onboardingBusinessTypes(),
            'states' => $this->addressStates()->pluck('name'),
        ]);
    }

    /**
     * Cities of one state, for the business address City dropdown.
     */
    public function cities(Request $request)
    {
        $state = $this->addressStates()->firstWhere('name', (string) $request->query('state'));

        return response()->json([
            'cities' => $state ? $this->cityNames($state->id) : [],
        ]);
    }

    public function saveBusiness(Request $request)
    {
        $partner = $this->partner($request);
        abort_unless($partner->canEditApplication(), 403, translate('Your application is locked while it is being reviewed.'));

        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:150'],
            'business_type' => ['required', Rule::in(array_keys($this->onboardingBusinessTypes()))],
            'contact_person' => ['required', 'string', 'max:100'],
            'address_line' => ['required', 'string', 'max:500'],
            'state' => ['required', 'string', 'max:100', Rule::in($this->addressStates()->pluck('name')->all())],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
        ], [
            'state.in' => translate('Please select your state from the list.'),
        ]);

        $state = $this->addressStates()->firstWhere('name', $data['state']);
        if (!in_array($data['city'], $this->cityNames($state->id), true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'city' => translate('Please select your city from the list.'),
            ]);
        }

        $data['country'] = config('channel_partner.address_country');
        $data['designation'] = null;

        $partner->fill($data)->save();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => translate('Business details saved')]);
        }

        flash(translate('Business details saved'))->success();
        return redirect()->route('channel_partner.onboarding', ['step' => 'review']);
    }

    public function uploadDocument(Request $request)
    {
        $partner = $this->partner($request);
        abort_unless($partner->canEditApplication(), 403, translate('Your application is locked while it is being reviewed.'));

        $request->validate([
            'document_type' => ['required', Rule::in(array_keys(config('channel_partner.document_types')))],
            'file' => [
                'required', 'file',
                'mimes:' . implode(',', config('channel_partner.document_mimes')),
                'max:' . config('channel_partner.document_max_kb'),
            ],
        ], [
            'file.max' => translate('File is too large. Maximum size is') . ' ' . round(config('channel_partner.document_max_kb') / 1024) . ' MB.',
            'file.mimes' => translate('Only PDF, JPG, PNG or WEBP files are allowed.'),
        ]);

        $type = $request->input('document_type');
        if ($partner->documents()->where('document_type', $type)->count() >= self::MAX_FILES_PER_TYPE) {
            return response()->json([
                'message' => translate('You can upload up to') . ' ' . self::MAX_FILES_PER_TYPE . ' ' . translate('files for this document.'),
            ], 422);
        }

        $file = $request->file('file');
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $path = $file->storeAs(
            'channel-partners/' . $partner->id,
            Str::uuid() . '.' . $extension,
            config('channel_partner.document_disk')
        );

        $document = $partner->documents()->create([
            'document_type' => $type,
            'original_name' => Str::limit($file->getClientOriginalName(), 200, ''),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => 'pending',
        ]);

        return response()->json([
            'ok' => true,
            'document' => $this->documentPayload($document),
            'missing' => $partner->missingRequiredDocuments(),
        ]);
    }

    public function deleteDocument(Request $request, ChannelPartnerDocument $document)
    {
        $partner = $this->partner($request);
        abort_unless($document->channel_partner_id === $partner->id, 404);
        abort_unless($partner->canEditApplication() && $document->status !== 'verified', 403, translate('This document can no longer be removed.'));

        $document->deleteFile();
        $document->delete();

        return response()->json(['ok' => true, 'missing' => $partner->missingRequiredDocuments()]);
    }

    public function viewDocument(Request $request, ChannelPartnerDocument $document)
    {
        $partner = $this->partner($request);
        abort_unless($document->channel_partner_id === $partner->id, 404);

        return $this->streamDocument($document);
    }

    public function submit(Request $request)
    {
        $partner = $this->partner($request);
        abort_unless($partner->canEditApplication(), 403, translate('Your application has already been submitted.'));

        if (!$partner->businessDetailsComplete()) {
            flash(translate('Please complete your business details first.'))->warning();
            return redirect()->route('channel_partner.onboarding', ['step' => 'business']);
        }

        $request->validate(['declaration' => ['accepted']], [
            'declaration.accepted' => translate('Please confirm that the information is correct.'),
        ]);

        // Partners are approved automatically on submit and can order straight away
        $partner->status = ChannelPartner::STATUS_APPROVED;
        $partner->submitted_at = now();
        $partner->partner_code = $partner->partner_code ?: ChannelPartner::generateCode();
        $partner->approved_at = $partner->approved_at ?: now();
        $partner->rejection_reason = null;
        $partner->save();

        try {
            $admin = get_admin();
            if ($admin && $admin->email) {
                Notification::send($admin, new ChannelPartnerApplicationSubmitted($partner));
            }
        } catch (\Throwable $e) {
            Log::warning('Channel partner submission mail failed: ' . $e->getMessage());
        }

        flash(translate('Your partner account is active. You can start ordering now.'))->success();
        return redirect()->route('channel_partner.dashboard');
    }

    public static function streamDocument(ChannelPartnerDocument $document)
    {
        $disk = Storage::disk(config('channel_partner.document_disk'));
        abort_unless($disk->exists($document->path), 404);

        $filename = Str::slug(pathinfo($document->original_name, PATHINFO_FILENAME)) ?: 'document';
        $filename .= '.' . pathinfo($document->path, PATHINFO_EXTENSION);

        return $disk->response($document->path, $filename, [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ], 'inline');
    }

    private function documentPayload(ChannelPartnerDocument $document): array
    {
        return [
            'id' => $document->id,
            'type' => $document->document_type,
            'name' => $document->original_name,
            'size' => $document->humanSize(),
            'is_image' => $document->isImage(),
            'status' => $document->status,
            'url' => route('channel_partner.documents.show', $document),
            'delete_url' => route('channel_partner.onboarding.documents.destroy', $document),
        ];
    }

    private function onboardingBusinessTypes(): array
    {
        return array_intersect_key(
            config('channel_partner.business_types'),
            array_flip(config('channel_partner.onboarding_business_types'))
        );
    }

    /**
     * All states of the address country, active or not: partners can be based
     * anywhere, not only where we deliver to customers.
     */
    private function addressStates()
    {
        if ($this->addressStates === null) {
            $country = Country::where('name', config('channel_partner.address_country'))->first();

            $this->addressStates = $country
                ? State::where('country_id', $country->id)->orderBy('name')->get(['id', 'name'])
                : collect();
        }

        return $this->addressStates;
    }

    private function cityNames(int $stateId): array
    {
        return City::where('state_id', $stateId)->orderBy('name')->pluck('name')->unique()->values()->all();
    }

    private function partner(Request $request): ChannelPartner
    {
        return $request->attributes->get('channel_partner');
    }
}
