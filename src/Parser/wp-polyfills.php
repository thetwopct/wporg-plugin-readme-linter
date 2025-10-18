<?php

declare(strict_types=1);

namespace WordPressdotorg\Plugin_Directory\Readme;

// phpcs:ignore PSR12.Files.FileHeader.IncorrectOrder
/**
 * Polyfills for WordPress functions used by the Parser class.
 */

if (!function_exists('WordPressdotorg\Plugin_Directory\Readme\esc_html')) {
    function esc_html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('WordPressdotorg\Plugin_Directory\Readme\wp_kses')) {
    /**
     * @param string|array<string, mixed> $allowed_tags
     * @param array<string> $allowed_protocols
     */
    function wp_kses(string $string, $allowed_tags, array $allowed_protocols = []): string
    {
        // Simplified version - strip all tags if no allowed tags
        if (empty($allowed_tags)) {
            return strip_tags($string);
        }

        // If it's an array, convert to allowed tags for strip_tags
        if (is_array($allowed_tags)) {
            $allowed = '<' . implode('><', array_keys($allowed_tags)) . '>';
            return strip_tags($string, $allowed);
        }

        return strip_tags($string, $allowed_tags);
    }
}

if (!function_exists('WordPressdotorg\Plugin_Directory\Readme\get_user_by')) {
    /**
     * @return object
     */
    function get_user_by(string $field, string $value): object
    {
        // For linting purposes, we just accept the contributor names as-is
        // In a real WordPress.org context, this would validate against the user database
        return (object) [
            'user_nicename' => $value,
            'user_login' => $value,
        ];
    }
}

if (!function_exists('WordPressdotorg\Plugin_Directory\Readme\wp_strip_all_tags')) {
    function wp_strip_all_tags(string $string, bool $remove_breaks = false): string
    {
        $result = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
        $result = strip_tags($result ?? '');

        if ($remove_breaks) {
            $result = preg_replace('/[\r\n\t ]+/', ' ', $result);
        }

        return trim($result ?? '');
    }
}

if (!function_exists('WordPressdotorg\Plugin_Directory\Readme\force_balance_tags')) {
    function force_balance_tags(string $text): string
    {
        // Simplified version - just return the text as-is
        // A full implementation would balance HTML tags
        return $text;
    }
}

if (!function_exists('WordPressdotorg\Plugin_Directory\Readme\make_clickable')) {
    function make_clickable(string $text): string
    {
        // Simplified version - convert URLs to links
        $result = preg_replace(
            '!(https?://[^\s<]+)!',
            '<a href="$1">$1</a>',
            $text
        );
        return $result ?? $text;
    }
}
