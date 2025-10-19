<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class TrademarkRule extends AbstractRule
{
    // WordPress and related trademarks
    private const WORDPRESS_TRADEMARKS = [
        'WordPress',
        'WordCamp',
        'BuddyPress',
        'bbPress',
        'GlotPress',
        'Automattic',
        'WooCommerce',
        'Jetpack',
        'Akismet',
        'Gravatar',
        'WordPress.com',
        'WordPress.org',
        'Matt Mullenweg',
    ];

    // Other common trademarks to watch for
    private const OTHER_TRADEMARKS = [
        'Google',
        'Facebook',
        'Twitter',
        'Instagram',
        'YouTube',
        'Amazon',
        'Microsoft',
        'Apple',
        'Adobe',
        'PayPal',
        'Stripe',
        'Mailchimp',
        'Salesforce',
        'Shopify',
        'WooCommerce', // Also in WordPress trademarks but important
    ];

    // Allowed usage patterns (these are generally OK)
    private const ALLOWED_PATTERNS = [
        '/for WordPress/i',
        '/WordPress integration/i',
        '/WordPress compatible/i',
        '/works with WordPress/i',
        '/integrates with/i',
        '/connects to/i',
        '/syncs with/i',
    ];

    public function getRuleId(): string
    {
        return 'trademark';
    }

    public function getDescription(): string
    {
        return 'Plugin should not improperly use trademarked names';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];

        // Check plugin name for trademark issues
        $pluginName = $parsedData['name'] ?? '';
        if (!empty($pluginName)) {
            $this->checkForTrademarkIssues($pluginName, 'plugin name', $rawContent, $issues);
        }

        // Check short description
        $shortDescription = $this->extractShortDescription($rawContent);
        if (!empty($shortDescription)) {
            $this->checkForTrademarkIssues($shortDescription, 'short description', $rawContent, $issues);
        }

        // Check sections for trademark issues
        $sections = $parsedData['sections'] ?? [];
        foreach ($sections as $sectionName => $sectionContent) {
            if (in_array(strtolower($sectionName), ['description', 'installation', 'faq'])) {
                $this->checkForTrademarkIssues($sectionContent, "section '{$sectionName}'", $rawContent, $issues);
            }
        }

        return $issues;
    }

    /**
     * @param array<Issue> $issues
     */
    private function checkForTrademarkIssues(string $text, string $context, string $rawContent, array &$issues): void
    {
        // Check WordPress trademarks
        foreach (self::WORDPRESS_TRADEMARKS as $trademark) {
            if ($this->hasImproperTrademarkUsage($text, $trademark)) {
                $line = $this->findLineNumber($rawContent, $text);
                $issues[] = $this->createIssue(
                    Issue::LEVEL_WARNING,
                    sprintf(
                        'Potential trademark issue in %s: "%s" should not be used improperly. ' .
                        'Consider using "for WordPress" or similar.',
                        $context,
                        $trademark
                    ),
                    $line
                );
            }
        }

        // Check other trademarks
        foreach (self::OTHER_TRADEMARKS as $trademark) {
            if ($this->hasImproperTrademarkUsage($text, $trademark)) {
                $line = $this->findLineNumber($rawContent, $text);
                $issues[] = $this->createIssue(
                    Issue::LEVEL_INFO,
                    sprintf(
                        'Potential trademark usage in %s: "%s". Ensure you have permission to use this trademark.',
                        $context,
                        $trademark
                    ),
                    $line
                );
            }
        }
    }

    private function hasImproperTrademarkUsage(string $text, string $trademark): bool
    {
        // Check if trademark appears in the text
        if (stripos($text, $trademark) === false) {
            return false;
        }

        // Check if it's used in an allowed pattern
        foreach (self::ALLOWED_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return false; // Allowed usage
            }
        }

        // Check for problematic patterns
        $problematicPatterns = [
            // Plugin name starting with trademark
            '/^' . preg_quote($trademark, '/') . '\s/i',
            // Trademark used as if it's the plugin's brand
            '/' . preg_quote($trademark, '/') . '\s+(plugin|theme|extension|addon)/i',
            // Claiming to be official
            '/official\s+' . preg_quote($trademark, '/') . '/i',
            '/' . preg_quote($trademark, '/') . '\s+official/i',
            // The official [trademark] pattern
            '/the\s+official\s+' . preg_quote($trademark, '/') . '/i',
        ];

        foreach ($problematicPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        // Special case for WordPress - very strict
        if (strtolower($trademark) === 'wordpress') {
            // WordPress should generally not be the first word in plugin names
            if (stripos(trim($text), 'wordpress') === 0) {
                return true;
            }
        }

        return false;
    }

    private function extractShortDescription(string $rawContent): string
    {
        $lines = explode("\n", $rawContent);
        $foundHeader = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/^===.*===\s*$/', $line)) {
                $foundHeader = true;
                continue;
            }

            if ($foundHeader && !empty($line) && !preg_match('/^[A-Za-z\s]+:/', $line)) {
                return $line;
            }
        }

        return '';
    }
}
