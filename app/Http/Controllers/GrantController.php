<?php

namespace App\Http\Controllers;

use App\Models\Grant;
use App\Models\GrantCategory;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GrantController extends Controller
{
    /**
     * Show the grants discovery page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get filter parameters
        $categoryId = $request->input('category');
        $search = $request->input('search');
        
        // Get active categories with grant counts
        $categories = GrantCategory::where('is_active', true)
            ->withCount(['grants' => function ($query) {
                $query->where('status', 'active');
            }])
            ->orderBy('name')
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'description' => $cat->description,
                'icon' => $cat->icon,
                'color' => $cat->color,
                'grants_count' => $cat->grants_count,
            ]);
        
        // Build grants query
        $grantsQuery = Grant::with('category')
            ->where('status', 'active');
        
        if ($categoryId) {
            $grantsQuery->where('grant_category_id', $categoryId);
        }
        
        if ($search) {
            $grantsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('eligibility_criteria', 'like', "%{$search}%");
            });
        }
        
        $grants = $grantsQuery->orderBy('created_at', 'desc')
            ->paginate(12)
            ->through(fn($grant) => [
                'id' => $grant->id,
                'title' => $grant->title,
                'description' => $grant->description,
                'category' => $grant->category?->name,
                'category_color' => $grant->category?->color,
                'min_amount' => $grant->min_amount ? number_format($grant->min_amount, 0) : null,
                'max_amount' => $grant->max_amount ? number_format($grant->max_amount, 0) : null,
                'eligibility_criteria' => $grant->eligibility_criteria,
                'application_deadline' => $grant->application_deadline,
                'funding_source' => $grant->funding_source,
                'url' => $grant->url,
                'requirements' => $grant->requirements,
            ]);
        
        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('Grants/Index', [
            'grants' => $grants,
            'categories' => $categories,
            'filters' => [
                'category' => $categoryId,
                'search' => $search,
            ],
            'settings' => $settings,
        ]);
    }
    
    /**
     * Show a specific grant.
     */
    public function show(Grant $grant)
    {
        $grant->load('category');
        
        // Get related grants from same category
        $relatedGrants = Grant::where('grant_category_id', $grant->grant_category_id)
            ->where('id', '!=', $grant->id)
            ->where('status', 'active')
            ->take(4)
            ->get()
            ->map(fn($g) => [
                'id' => $g->id,
                'title' => $g->title,
                'max_amount' => $g->max_amount ? number_format($g->max_amount, 0) : null,
            ]);
        
        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('Grants/Show', [
            'grant' => [
                'id' => $grant->id,
                'title' => $grant->title,
                'description' => $grant->description,
                'category' => $grant->category?->name,
                'category_color' => $grant->category?->color,
                'min_amount' => $grant->min_amount ? number_format($grant->min_amount, 0) : null,
                'max_amount' => $grant->max_amount ? number_format($grant->max_amount, 0) : null,
                'eligibility_criteria' => $grant->eligibility_criteria,
                'application_deadline' => $grant->application_deadline,
                'funding_source' => $grant->funding_source,
                'url' => $grant->url,
                'requirements' => $grant->requirements,
            ],
            'relatedGrants' => $relatedGrants,
            'settings' => $settings,
        ]);
    }
}
