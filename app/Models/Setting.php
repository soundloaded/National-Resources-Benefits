<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    
    protected $fillable = ['group', 'key', 'value', 'type'];
    
    // Helper to get a setting
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) return $default;
        
        return match($setting->type) {
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }
    
    // Helper to set a setting
    public static function set(string $key, $value, string $group = 'general', string $type = 'string')
    {
        $val = $value;
        if ($type === 'json' || is_array($value)) {
            $val = json_encode($value);
            $type = 'json';
        } elseif (is_bool($value)) {
            $val = $value ? '1' : '0';
            $type = 'boolean';
        }
        
        return self::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $val,
                'type' => $type
            ]
        );
    }
}
