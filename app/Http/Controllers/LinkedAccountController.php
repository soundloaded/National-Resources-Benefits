<?php

namespace App\Http\Controllers;

use App\Models\LinkedWithdrawalAccount;
use App\Models\WithdrawalFormField;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class LinkedAccountController extends Controller
{
    /**
     * Display a listing of the user's linked accounts.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $linkedAccounts = $user->linkedWithdrawalAccounts()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'account_name' => $account->account_name,
                    'display_name' => $account->display_name,
                    'account_data' => $account->account_data,
                    'is_default' => $account->is_default,
                    'is_verified' => $account->is_verified,
                    'is_active' => $account->is_active,
                    'created_at' => $account->created_at->format('M d, Y'),
                    'verified_at' => $account->verified_at?->format('M d, Y'),
                ];
            });

        return Inertia::render('Profile/LinkedAccounts', [
            'linkedAccounts' => $linkedAccounts,
            'formFields' => WithdrawalFormField::getActiveFields(),
            'accountLimit' => LinkedWithdrawalAccount::getAccountLimit(),
            'canAddMore' => !LinkedWithdrawalAccount::hasReachedLimit($user->id),
        ]);
    }

    /**
     * Get form fields and account status for the modal
     */
    public function getFormFields(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'formFields' => WithdrawalFormField::getActiveFields(),
            'accountLimit' => LinkedWithdrawalAccount::getAccountLimit(),
            'currentCount' => $user->linkedWithdrawalAccounts()->active()->count(),
            'canAddMore' => !LinkedWithdrawalAccount::hasReachedLimit($user->id),
        ]);
    }

    /**
     * Get user's linked accounts for selection
     */
    public function getLinkedAccounts(Request $request)
    {
        $user = $request->user();
        
        $accounts = $user->linkedWithdrawalAccounts()
            ->active()
            ->orderBy('is_default', 'desc')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'account_name' => $account->account_name,
                    'display_name' => $account->display_name,
                    'is_default' => $account->is_default,
                    'is_verified' => $account->is_verified,
                ];
            });

        return response()->json([
            'accounts' => $accounts,
            'canAddMore' => !LinkedWithdrawalAccount::hasReachedLimit($user->id),
        ]);
    }

    /**
     * Store a newly created linked account.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Check if user has reached their limit
        if (LinkedWithdrawalAccount::hasReachedLimit($user->id)) {
            return back()->withErrors([
                'limit' => 'You have reached the maximum number of linked accounts (' . LinkedWithdrawalAccount::getAccountLimit() . ').',
            ]);
        }

        // Build validation rules from form fields
        $rules = [
            'account_name' => ['required', 'string', 'max:255'],
        ];
        
        $formFields = WithdrawalFormField::getActiveFields();
        
        foreach ($formFields as $field) {
            $fieldRules = [];
            
            if ($field->is_required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }
            
            // Add type-specific rules
            switch ($field->type) {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'select':
                    if ($field->options) {
                        $validValues = array_column($field->options, 'value');
                        $fieldRules[] = 'in:' . implode(',', $validValues);
                    }
                    break;
            }
            
            // Add custom validation rules
            if ($field->validation_rules) {
                $fieldRules = array_merge($fieldRules, $field->validation_rules);
            }
            
            $rules['account_data.' . $field->name] = $fieldRules;
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check if this is the first account (make it default)
        $isFirst = $user->linkedWithdrawalAccounts()->active()->count() === 0;

        $account = LinkedWithdrawalAccount::create([
            'user_id' => $user->id,
            'account_name' => $request->account_name,
            'account_data' => $request->account_data,
            'is_default' => $isFirst,
            'is_verified' => false,
            'is_active' => true,
        ]);

        return back()->with('success', 'Withdrawal account linked successfully. It will be verified shortly.');
    }

    /**
     * Update the specified linked account.
     */
    public function update(Request $request, LinkedWithdrawalAccount $linkedAccount)
    {
        // Ensure user owns this account
        if ($linkedAccount->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'account_name' => ['required', 'string', 'max:255'],
        ]);

        $linkedAccount->update([
            'account_name' => $request->account_name,
        ]);

        return back()->with('success', 'Account name updated successfully.');
    }

    /**
     * Set account as default.
     */
    public function setDefault(Request $request, LinkedWithdrawalAccount $linkedAccount)
    {
        // Ensure user owns this account
        if ($linkedAccount->user_id !== $request->user()->id) {
            abort(403);
        }

        $linkedAccount->setAsDefault();

        return back()->with('success', 'Default account updated successfully.');
    }

    /**
     * Remove the specified linked account.
     */
    public function destroy(Request $request, LinkedWithdrawalAccount $linkedAccount)
    {
        // Ensure user owns this account
        if ($linkedAccount->user_id !== $request->user()->id) {
            abort(403);
        }

        // Soft delete by deactivating
        $linkedAccount->update(['is_active' => false]);

        // If this was the default, make another one default
        if ($linkedAccount->is_default) {
            $newDefault = $request->user()
                ->linkedWithdrawalAccounts()
                ->active()
                ->first();
            
            if ($newDefault) {
                $newDefault->setAsDefault();
            }
        }

        return back()->with('success', 'Withdrawal account removed successfully.');
    }
}
