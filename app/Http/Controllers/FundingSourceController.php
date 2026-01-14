<?php

namespace App\Http\Controllers;

use App\Models\FundingSource;
use App\Models\FundingCategory;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FundingSourceController extends Controller
{
    /**
     * Display listing of funding sources.
     */
    public function index(Request $request)
    {
        $query = FundingSource::query()
            ->where('is_active', true)
            ->with('fundingCategory');
        
        // Filter by category
        if ($request->filled('category')) {
            $query->where('funding_category_id', $request->category);
        }
        
        // Filter by deadline status
        if ($request->filled('deadline')) {
            if ($request->deadline === 'upcoming') {
                $query->where('deadline', '>=', now());
            } elseif ($request->deadline === 'open') {
                $query->where(function ($q) {
                    $q->whereNull('deadline')
                      ->orWhere('deadline', '>=', now());
                });
            }
        }
        
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Sort
        $sortField = $request->get('sort', 'deadline');
        $sortDirection = $request->get('direction', 'asc');
        
        if ($sortField === 'amount') {
            $query->orderBy('amount_max', $sortDirection);
        } elseif ($sortField === 'deadline') {
            $query->orderByRaw('deadline IS NULL, deadline ' . $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }
        
        $fundingSources = $query->paginate(12)->through(fn($source) => [
            'id' => $source->id,
            'title' => $source->title,
            'description' => $source->description,
            'amount_min' => (float) $source->amount_min,
            'amount_max' => (float) $source->amount_max,
            'category' => $source->fundingCategory ? [
                'id' => $source->fundingCategory->id,
                'name' => $source->fundingCategory->name,
                'slug' => $source->fundingCategory->slug,
            ] : null,
            'url' => $source->url,
            'deadline' => $source->deadline?->toIso8601String(),
            'deadline_formatted' => $source->deadline?->format('M d, Y'),
            'days_until_deadline' => $source->deadline ? now()->diffInDays($source->deadline, false) : null,
            'is_expired' => $source->deadline ? $source->deadline->isPast() : false,
        ]);
        
        // Get categories for filter
        $categories = FundingCategory::where('is_active', true)
            ->withCount(['fundingSources' => fn($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'count' => $cat->funding_sources_count,
            ]);
        
        // Stats
        $stats = [
            'total' => FundingSource::where('is_active', true)->count(),
            'total_funding' => FundingSource::where('is_active', true)->sum('amount_max'),
            'expiring_soon' => FundingSource::where('is_active', true)
                ->whereBetween('deadline', [now(), now()->addDays(30)])
                ->count(),
        ];
        
        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('FundingSources/Index', [
            'fundingSources' => $fundingSources,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => [
                'category' => $request->category,
                'deadline' => $request->deadline,
                'search' => $request->search,
                'sort' => $sortField,
                'direction' => $sortDirection,
            ],
            'settings' => $settings,
        ]);
    }
    
    /**
     * Display a single funding source.
     */
    public function show(FundingSource $fundingSource)
    {
        if (!$fundingSource->is_active) {
            abort(404);
        }
        
        $fundingSource->load('fundingCategory');
        
        // Get related funding sources
        $related = FundingSource::where('is_active', true)
            ->where('id', '!=', $fundingSource->id)
            ->when($fundingSource->funding_category_id, function ($q) use ($fundingSource) {
                $q->where('funding_category_id', $fundingSource->funding_category_id);
            })
            ->limit(3)
            ->get()
            ->map(fn($source) => [
                'id' => $source->id,
                'title' => $source->title,
                'amount_min' => (float) $source->amount_min,
                'amount_max' => (float) $source->amount_max,
                'deadline_formatted' => $source->deadline?->format('M d, Y'),
            ]);
        
        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        // Check if user can apply (for internal applications)
        $canApply = null;
        $userApplication = null;
        if (auth()->check() && $fundingSource->is_internal) {
            $canApply = $fundingSource->canUserApply(auth()->user());
            
            // Get user's latest application for this source
            $userApplication = $fundingSource->applications()
                ->where('user_id', auth()->id())
                ->latest()
                ->first();
        }
        
        return Inertia::render('FundingSources/Show', [
            'fundingSource' => [
                'id' => $fundingSource->id,
                'title' => $fundingSource->title,
                'description' => $fundingSource->description,
                'amount_min' => (float) $fundingSource->amount_min,
                'amount_max' => (float) $fundingSource->amount_max,
                'category' => $fundingSource->fundingCategory ? [
                    'id' => $fundingSource->fundingCategory->id,
                    'name' => $fundingSource->fundingCategory->name,
                ] : null,
                'url' => $fundingSource->url,
                'deadline' => $fundingSource->deadline?->toIso8601String(),
                'deadline_formatted' => $fundingSource->deadline?->format('F d, Y'),
                'days_until_deadline' => $fundingSource->deadline ? now()->diffInDays($fundingSource->deadline, false) : null,
                'is_expired' => $fundingSource->deadline ? $fundingSource->deadline->isPast() : false,
                'is_internal' => $fundingSource->is_internal,
                'requirements' => $fundingSource->requirements_list,
                'slots_remaining' => $fundingSource->slots_remaining,
                'total_slots' => $fundingSource->total_slots,
            ],
            'related' => $related,
            'settings' => $settings,
            'canApply' => $canApply,
            'userApplication' => $userApplication ? [
                'id' => $userApplication->id,
                'application_number' => $userApplication->application_number,
                'status' => $userApplication->status,
                'status_label' => \App\Models\FundingApplication::getStatuses()[$userApplication->status] ?? $userApplication->status,
            ] : null,
        ]);
    }
}
