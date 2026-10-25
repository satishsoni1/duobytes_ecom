<?php

namespace App\Http\Controllers\ChannelPartner;

use App\Http\Controllers\Controller;
use App\Models\ChannelPartner;
use App\Models\ChannelPartnerDocument;
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

    public function show(Request $request)
    {
        $partner = $this->partner($request);

        if ($partner->isApproved()) {
            return redirect()->route('channel_partner.dashboard');
        }

        $documents = $partner->documents()->latest()->get()->groupBy('document_type');

        return view('channel_partner.onboarding.index', [
            'partner' => $partner,
            'documents' => $documents,
            'documentTypes' => config('channel_partner.document_types'),
            'businessTypes' => config('channel_partner.business_types'),
            'missingDocuments' => $partner->missingRequiredDocuments(),
        ]);
    }

    public function saveBusiness(Request $request)
    {
        $partner = $this->partner($request);
        abort_unless($partner->canEditApplication(), 403, translate('Your application is locked while it is being reviewed.'));

        $request->merge([
            'gst_number' => $request->filled('gst_number') ? strtoupper(preg_replace('/\s+/', '', $request->gst_number)) : null,
            'pan_number' => $request->filled('pan_number') ? strtoupper(preg_replace('/\s+/', '', $request->pan_number)) : null,
        ]);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'business_type' => ['required', Rule::in(array_keys(config('channel_partner.business_types')))],
            'contact_person' => ['required', 'string', 'max:100'],
            'designation' => ['nullable', 'string', 'max:100'],
            'gst_number' => ['nullable', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/'],
            'pan_number' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'address_line' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'expected_monthly_volume' => ['nullable', 'string', 'max:50'],
        ], [
            'gst_number.regex' => translate('Enter a valid 15-character GSTIN, e.g. 27ABCDE1234F1Z5.'),
            'pan_number.regex' => translate('Enter a valid 10-character PAN, e.g. ABCDE1234F.'),
        ]);

        $partner->fill($data)->save();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => translate('Business details saved')]);
        }

        flash(translate('Business details saved'))->success();
        return redirect()->route('channel_partner.onboarding', ['step' => 'documents']);
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

        $missing = $partner->missingRequiredDocuments();
        if (!empty($missing)) {
            $labels = collect($missing)->map(fn ($t) => translate(config('channel_partner.document_types.' . $t . '.label')))->implode(', ');
            flash(translate('Please upload') . ': ' . $labels)->warning();
            return redirect()->route('channel_partner.onboarding', ['step' => 'documents']);
        }

        $request->validate(['declaration' => ['accepted']], [
            'declaration.accepted' => translate('Please confirm that the information is correct.'),
        ]);

        $partner->status = ChannelPartner::STATUS_SUBMITTED;
        $partner->submitted_at = now();
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

        return redirect()->route('channel_partner.onboarding');
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

    private function partner(Request $request): ChannelPartner
    {
        return $request->attributes->get('channel_partner');
    }
}
