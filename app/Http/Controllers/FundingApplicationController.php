<?php

namespace App\Http\Controllers;

use App\Models\FundingApplication;
use App\Models\FundingSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FundingApplicationController extends Controller
{
    /**
     * Show the application form for a funding source
     */
    public function create(FundingSource $fundingSource)
    {
        $user = auth()->user();

        // Check if funding source accepts internal applications
        if (!$fundingSource->is_internal) {
            return redirect()->route('funding-sources.show', $fundingSource)
                ->with('error', 'This funding source does not accept applications on this platform.');
        }

        // Check if user can apply
        $canApply = $fundingSource->canUserApply($user);
        if (!$canApply['can_apply']) {
            return redirect()->route('funding-sources.show', $fundingSource)
                ->with('error', $canApply['reason']);
        }

        return Inertia::render('FundingSources/Apply', [
            'fundingSource' => [
                'id' => $fundingSource->id,
                'title' => $fundingSource->title,
                'description' => $fundingSource->description,
                'amount_min' => $fundingSource->amount_min,
                'amount_max' => $fundingSource->amount_max,
                'deadline' => $fundingSource->deadline?->format('M d, Y'),
                'requirements' => $fundingSource->requirements_list,
                'form_fields' => $fundingSource->form_fields ?? [],
                'category' => $fundingSource->fundingCategory?->name,
            ],
        ]);
    }

    /**
     * Store a new application
     */
    public function store(Request $request, FundingSource $fundingSource)
    {
        $user = auth()->user();

        // Check if user can apply
        $canApply = $fundingSource->canUserApply($user);
        if (!$canApply['can_apply']) {
            return back()->withErrors(['general' => $canApply['reason']]);
        }

        // Base validation
        $rules = [
            'requested_amount' => [
                'required',
                'numeric',
                'min:' . ($fundingSource->amount_min ?? 0),
            ],
            'purpose' => 'required|string|min:50|max:2000',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ];

        // Add max amount validation if set
        if ($fundingSource->amount_max) {
            $rules['requested_amount'][] = 'max:' . $fundingSource->amount_max;
        }

        // Add custom field validations
        if ($fundingSource->form_fields) {
            foreach ($fundingSource->form_fields as $field) {
                $fieldRules = [];
                if ($field['required'] ?? false) {
                    $fieldRules[] = 'required';
                } else {
                    $fieldRules[] = 'nullable';
                }
                
                switch ($field['type'] ?? 'text') {
                    case 'number':
                        $fieldRules[] = 'numeric';
                        break;
                    case 'file':
                        $fieldRules[] = 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240';
                        break;
                    default:
                        $fieldRules[] = 'string|max:1000';
                }
                
                $rules['custom_fields.' . $field['name']] = $fieldRules;
            }
        }

        $validated = $request->validate($rules);

        // Handle document uploads
        $documents = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('funding-applications/' . $user->id, 'public');
                $documents[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        // Handle custom field file uploads
        $customFields = $validated['custom_fields'] ?? [];
        if ($fundingSource->form_fields) {
            foreach ($fundingSource->form_fields as $field) {
                if (($field['type'] ?? 'text') === 'file' && $request->hasFile('custom_fields.' . $field['name'])) {
                    $file = $request->file('custom_fields.' . $field['name']);
                    $path = $file->store('funding-applications/' . $user->id, 'public');
                    $customFields[$field['name']] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                    ];
                }
            }
        }

        // Create the application
        $application = FundingApplication::create([
            'user_id' => $user->id,
            'funding_source_id' => $fundingSource->id,
            'application_number' => FundingApplication::generateApplicationNumber(),
            'requested_amount' => $validated['requested_amount'],
            'purpose' => $validated['purpose'],
            'documents' => $documents,
            'custom_fields' => !empty($customFields) ? $customFields : null,
            'status' => 'pending',
        ]);

        return redirect()->route('my-applications.show', $application)
            ->with('success', 'Your application has been submitted successfully! Application #' . $application->application_number);
    }

    /**
     * List user's applications
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = $user->fundingApplications()
            ->with('fundingSource:id,title')
            ->latest();

        // Filter by status
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $applications = $query->paginate(10)->withQueryString();

        return Inertia::render('FundingSources/MyApplications', [
            'applications' => $applications->through(fn ($app) => [
                'id' => $app->id,
                'application_number' => $app->application_number,
                'funding_source' => $app->fundingSource?->title ?? 'N/A',
                'requested_amount' => $app->requested_amount,
                'approved_amount' => $app->approved_amount,
                'status' => $app->status,
                'status_label' => FundingApplication::getStatuses()[$app->status] ?? $app->status,
                'status_color' => $app->status_color,
                'created_at' => $app->created_at->format('M d, Y'),
                'reviewed_at' => $app->reviewed_at?->format('M d, Y'),
            ]),
            'filters' => [
                'status' => $request->status ?? 'all',
            ],
            'statuses' => FundingApplication::getStatuses(),
        ]);
    }

    /**
     * Show a single application
     */
    public function show(FundingApplication $application)
    {
        $user = auth()->user();

        // Ensure user owns this application
        if ($application->user_id !== $user->id) {
            abort(403);
        }

        $application->load('fundingSource');

        return Inertia::render('FundingSources/ApplicationDetails', [
            'application' => [
                'id' => $application->id,
                'application_number' => $application->application_number,
                'funding_source' => [
                    'id' => $application->fundingSource->id,
                    'title' => $application->fundingSource->title,
                    'category' => $application->fundingSource->fundingCategory?->name,
                ],
                'requested_amount' => $application->requested_amount,
                'approved_amount' => $application->approved_amount,
                'purpose' => $application->purpose,
                'documents' => $application->documents,
                'custom_fields' => $application->custom_fields,
                'status' => $application->status,
                'status_label' => FundingApplication::getStatuses()[$application->status] ?? $application->status,
                'status_color' => $application->status_color,
                'rejection_reason' => $application->rejection_reason,
                'created_at' => $application->created_at->format('M d, Y \a\t g:i A'),
                'reviewed_at' => $application->reviewed_at?->format('M d, Y \a\t g:i A'),
                'disbursed_at' => $application->disbursed_at?->format('M d, Y \a\t g:i A'),
                'can_cancel' => $application->canBeCancelled(),
            ],
        ]);
    }

    /**
     * Cancel an application
     */
    public function cancel(FundingApplication $application)
    {
        $user = auth()->user();

        // Ensure user owns this application
        if ($application->user_id !== $user->id) {
            abort(403);
        }

        if (!$application->canBeCancelled()) {
            return back()->withErrors(['general' => 'This application cannot be cancelled.']);
        }

        $application->update(['status' => 'cancelled']);

        return back()->with('success', 'Application has been cancelled.');
    }
}
