<?php

namespace App\Http\Controllers;

use App\Models\KycDocument;
use App\Models\KycTemplate;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class KycController extends Controller
{
    /**
     * Display KYC overview - status, required templates, submitted documents
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get all active KYC templates applicable to 'user'
        $templates = KycTemplate::where('is_active', true)
            ->whereJsonContains('applicable_to', 'user')
            ->get()
            ->map(function ($template) use ($user) {
                // Check if user has submitted this template
                $submission = $user->kycdocuments()
                    ->where('kyc_template_id', $template->id)
                    ->latest()
                    ->first();

                return [
                    'id' => $template->id,
                    'title' => $template->title,
                    'description' => $template->description,
                    'form_fields' => $template->form_fields,
                    'submission' => $submission ? [
                        'id' => $submission->id,
                        'status' => $submission->status,
                        'rejection_reason' => $submission->rejection_reason,
                        'submitted_at' => $submission->created_at->format('M d, Y H:i'),
                        'verified_at' => $submission->verified_at?->format('M d, Y H:i'),
                    ] : null,
                ];
            });

        // Get all user's KYC documents (including legacy non-template ones)
        $documents = $user->kycdocuments()
            ->with('template')
            ->latest()
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'type' => $doc->document_type ?? $doc->template?->title ?? 'Document',
                    'template_id' => $doc->kyc_template_id,
                    'status' => $doc->status,
                    'rejection_reason' => $doc->rejection_reason,
                    'document_path' => $doc->document_path ? Storage::url($doc->document_path) : null,
                    'data' => $doc->data,
                    'submitted_at' => $doc->created_at->format('M d, Y H:i'),
                    'verified_at' => $doc->verified_at?->format('M d, Y H:i'),
                ];
            });

        // Calculate overall KYC status
        $kycStatus = $this->calculateKycStatus($user, $templates);

        return Inertia::render('Kyc/Index', [
            'templates' => $templates,
            'documents' => $documents,
            'kycStatus' => $kycStatus,
            'isVerified' => $user->kyc_verified_at !== null,
            'verifiedAt' => $user->kyc_verified_at?->format('M d, Y'),
        ]);
    }

    /**
     * Show form to submit a specific KYC template
     */
    public function create(Request $request, KycTemplate $template)
    {
        $user = $request->user();

        // Check if template is active and applicable
        if (!$template->is_active || !in_array('user', $template->applicable_to ?? [])) {
            return redirect()->route('kyc.index')->with('error', 'This KYC form is not available.');
        }

        // Check if user already has a pending or approved submission for this template
        $existingSubmission = $user->kycdocuments()
            ->where('kyc_template_id', $template->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingSubmission) {
            $status = $existingSubmission->status === 'approved' ? 'approved' : 'pending review';
            return redirect()->route('kyc.index')->with('info', "You already have a {$status} submission for this document.");
        }

        return Inertia::render('Kyc/Submit', [
            'template' => [
                'id' => $template->id,
                'title' => $template->title,
                'description' => $template->description,
                'form_fields' => $template->form_fields,
            ],
        ]);
    }

    /**
     * Store a new KYC document submission
     */
    public function store(Request $request, KycTemplate $template)
    {
        $user = $request->user();

        // Validate template is active and applicable
        if (!$template->is_active || !in_array('user', $template->applicable_to ?? [])) {
            return back()->with('error', 'This KYC form is not available.');
        }

        // Check for existing pending/approved submission
        $existingSubmission = $user->kycdocuments()
            ->where('kyc_template_id', $template->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingSubmission) {
            return back()->with('error', 'You already have a submission for this document.');
        }

        // Build validation rules based on template form_fields
        $rules = [];
        $formFields = $template->form_fields ?? [];

        foreach ($formFields as $index => $field) {
            $fieldName = "fields.{$index}";
            $fieldRules = [];

            if (($field['required'] ?? 'false') === 'true') {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            switch ($field['type'] ?? 'text') {
                case 'file':
                    $fieldRules[] = 'file';
                    $fieldRules[] = 'max:10240'; // 10MB max
                    $fieldRules[] = 'mimes:jpg,jpeg,png,pdf';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'text':
                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    break;
            }

            $rules[$fieldName] = $fieldRules;
        }

        $validated = $request->validate($rules);

        // Process form data and file uploads
        $data = [];
        $documentPath = null;

        foreach ($formFields as $index => $field) {
            $fieldValue = $request->input("fields.{$index}");
            $label = $field['label'] ?? "Field {$index}";

            if (($field['type'] ?? 'text') === 'file') {
                if ($request->hasFile("fields.{$index}")) {
                    $file = $request->file("fields.{$index}");
                    $path = $file->store('kyc-documents/' . $user->id, 'public');
                    $data[$label] = Storage::url($path);
                    
                    // Use first file as the main document_path
                    if (!$documentPath) {
                        $documentPath = $path;
                    }
                }
            } else {
                $data[$label] = $fieldValue;
            }
        }

        // Create the KYC document
        $kycDocument = KycDocument::create([
            'user_id' => $user->id,
            'kyc_template_id' => $template->id,
            'document_type' => null, // Using template-based submission
            'document_path' => $documentPath,
            'document_number' => null,
            'status' => 'pending',
            'data' => $data,
        ]);

        return redirect()->route('kyc.index')->with('success', 'Your KYC document has been submitted successfully and is pending review.');
    }

    /**
     * Show a specific KYC document submission
     */
    public function show(Request $request, KycDocument $document)
    {
        $user = $request->user();

        // Ensure the document belongs to the current user
        if ($document->user_id !== $user->id) {
            abort(403);
        }

        return Inertia::render('Kyc/Show', [
            'document' => [
                'id' => $document->id,
                'type' => $document->document_type ?? $document->template?->title ?? 'Document',
                'template' => $document->template ? [
                    'id' => $document->template->id,
                    'title' => $document->template->title,
                    'description' => $document->template->description,
                ] : null,
                'status' => $document->status,
                'rejection_reason' => $document->rejection_reason,
                'document_path' => $document->document_path ? Storage::url($document->document_path) : null,
                'data' => $document->data,
                'submitted_at' => $document->created_at->format('M d, Y H:i'),
                'verified_at' => $document->verified_at?->format('M d, Y H:i'),
            ],
        ]);
    }

    /**
     * Calculate overall KYC status based on submissions
     */
    private function calculateKycStatus($user, $templates): array
    {
        $totalRequired = $templates->count();
        $approved = 0;
        $pending = 0;
        $rejected = 0;

        foreach ($templates as $template) {
            if ($template['submission']) {
                switch ($template['submission']['status']) {
                    case 'approved':
                        $approved++;
                        break;
                    case 'pending':
                        $pending++;
                        break;
                    case 'rejected':
                        $rejected++;
                        break;
                }
            }
        }

        $notSubmitted = $totalRequired - ($approved + $pending + $rejected);

        // Determine overall status
        $status = 'not_started';
        if ($approved === $totalRequired && $totalRequired > 0) {
            $status = 'verified';
        } elseif ($pending > 0) {
            $status = 'pending';
        } elseif ($rejected > 0 && $approved < $totalRequired) {
            $status = 'action_required';
        } elseif ($approved > 0) {
            $status = 'partial';
        }

        $progress = $totalRequired > 0 ? round(($approved / $totalRequired) * 100) : 0;

        return [
            'status' => $status,
            'progress' => $progress,
            'total_required' => $totalRequired,
            'approved' => $approved,
            'pending' => $pending,
            'rejected' => $rejected,
            'not_submitted' => $notSubmitted,
        ];
    }
}
