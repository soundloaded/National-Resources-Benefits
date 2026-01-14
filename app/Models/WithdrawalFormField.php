<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalFormField extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'type',
        'placeholder',
        'options',
        'validation_rules',
        'help_text',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'validation_rules' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get active form fields ordered by sort_order
     */
    public static function getActiveFields()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get validation rules for all active fields
     */
    public static function getValidationRules(): array
    {
        $rules = [];
        
        foreach (static::getActiveFields() as $field) {
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
                        $fieldRules[] = 'in:' . implode(',', array_column($field->options, 'value'));
                    }
                    break;
            }
            
            // Add custom validation rules
            if ($field->validation_rules) {
                $fieldRules = array_merge($fieldRules, $field->validation_rules);
            }
            
            $rules['account_data.' . $field->name] = $fieldRules;
        }
        
        return $rules;
    }

    /**
     * Get available field types
     */
    public static function getFieldTypes(): array
    {
        return [
            'text' => 'Text Input',
            'number' => 'Number Input',
            'email' => 'Email Input',
            'tel' => 'Phone Number',
            'select' => 'Dropdown Select',
            'textarea' => 'Text Area',
        ];
    }
}
