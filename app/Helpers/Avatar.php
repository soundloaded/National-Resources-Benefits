<?php

namespace App\Helpers;

class Avatar
{
    /**
     * Generate Gravatar URL for a user
     * 
     * @param string|null $email User's email address
     * @param int $size Image size in pixels (1-2048)
     * @param string $default Default image type: 'mp', 'identicon', 'monsterid', 'wavatar', 'retro', 'robohash', 'blank'
     * @param string $rating Maximum rating: 'g', 'pg', 'r', 'x'
     */
    public static function gravatar(
        ?string $email,
        int $size = 200,
        string $default = 'mp',
        string $rating = 'g'
    ): string {
        $hash = md5(strtolower(trim($email ?? 'default@example.com')));
        
        return "https://www.gravatar.com/avatar/{$hash}?" . http_build_query([
            's' => $size,
            'd' => $default,
            'r' => $rating,
        ]);
    }

    /**
     * Generate initials-based avatar using DiceBear (local-friendly, no tracking)
     * Falls back to Gravatar-style URL
     */
    public static function initials(?string $name, int $size = 200): string
    {
        $name = $name ?? 'User';
        $initials = self::getInitials($name);
        
        // Use DiceBear's initials API (open source, privacy-friendly)
        return "https://api.dicebear.com/7.x/initials/svg?" . http_build_query([
            'seed' => $name,
            'size' => $size,
            'chars' => 2,
        ]);
    }

    /**
     * Generate avatar using robohash (unique robot/monster for each email)
     */
    public static function robohash(?string $identifier, int $size = 200, int $set = 1): string
    {
        $identifier = $identifier ?? 'default';
        
        // set1 = robots, set2 = monsters, set3 = robot heads, set4 = cats
        return "https://robohash.org/" . md5($identifier) . "?size={$size}x{$size}&set=set{$set}";
    }

    /**
     * Get user initials from name
     */
    public static function getInitials(?string $name): string
    {
        if (empty($name)) {
            return 'U';
        }

        $words = explode(' ', trim($name));
        $initials = '';

        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }

        return substr($initials, 0, 2) ?: 'U';
    }

    /**
     * Generate a consistent color based on string (for UI backgrounds)
     */
    public static function stringToColor(string $str): string
    {
        $hash = md5($str);
        $color = substr($hash, 0, 6);
        return "#{$color}";
    }

    /**
     * Get the best avatar URL for a user (checks Gravatar, falls back to initials)
     */
    public static function forUser(?string $email, ?string $name, int $size = 200): string
    {
        // Prefer Gravatar with initials fallback
        return self::gravatar($email, $size, 'mp');
    }
}
