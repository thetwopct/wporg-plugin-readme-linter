<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class DonateLinkRule extends AbstractRule
{
    // Discouraged domains for donate links
    private const DISCOURAGED_DOMAINS = [
        'paypal.me',
        'gofundme.com',
        'kickstarter.com',
        'indiegogo.com',
        'patreon.com',
        'ko-fi.com',
        'buymeacoffee.com',
        'venmo.com',
        'cashapp.com',
        'zelle.com',
        'example.com',
        'yoursite.com',
        'localhost',
        '127.0.0.1',
    ];

    // Allowed/preferred domains
    private const PREFERRED_DOMAINS = [
        'paypal.com',
        'stripe.com',
        'github.com', // GitHub Sponsors
        'opencollective.com',
        'liberapay.com',
    ];

    public function getRuleId(): string
    {
        return 'donate-link';
    }

    public function getDescription(): string
    {
        return 'Donate link should be present, properly formatted, and use appropriate domains';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];
        $donateLink = $parsedData['donate_link'] ?? '';

        if (empty($donateLink)) {
            $line = $this->findLineNumber($rawContent, 'Donate link:');
            $issues[] = $this->createIssue(
                Issue::LEVEL_INFO,
                'Consider adding a donate link to support the plugin',
                $line
            );
            return $issues;
        }

        $line = $this->findLineNumber($rawContent, 'Donate link:');

        // Validate URL format
        if (!filter_var($donateLink, FILTER_VALIDATE_URL)) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_WARNING,
                sprintf('Donate link "%s" does not appear to be a valid URL', $donateLink),
                $line
            );
            return $issues;
        }

        // Check for discouraged domains
        $domain = $this->extractDomain($donateLink);
        if ($domain && $this->isDiscouragedDomain($domain)) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_ERROR,
                sprintf(
                    'Discouraged donate link domain: "%s". WordPress.org discourages certain donation platforms.',
                    $domain
                ),
                $line
            );
        }

        // Check for invalid donate link patterns
        if ($this->hasInvalidDonatePattern($donateLink)) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_ERROR,
                sprintf(
                    'Invalid donate link: "%s". Donate links should point to legitimate donation pages.',
                    $donateLink
                ),
                $line
            );
        }

        // Provide suggestion for preferred domains
        if ($domain && !$this->isPreferredDomain($domain) && !$this->isDiscouragedDomain($domain)) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_INFO,
                sprintf(
                    'Consider using preferred donation platforms: %s',
                    implode(', ', self::PREFERRED_DOMAINS)
                ),
                $line
            );
        }

        return $issues;
    }

    private function extractDomain(string $url): ?string
    {
        $parsed = parse_url($url);
        if (!isset($parsed['host'])) {
            return null;
        }

        $host = strtolower($parsed['host']);

        // Remove www. prefix
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        return $host;
    }

    private function isDiscouragedDomain(string $domain): bool
    {
        return in_array($domain, self::DISCOURAGED_DOMAINS, true);
    }

    private function isPreferredDomain(string $domain): bool
    {
        return in_array($domain, self::PREFERRED_DOMAINS, true);
    }

    private function hasInvalidDonatePattern(string $url): bool
    {
        $invalidPatterns = [
            '/example\.com/i',
            '/localhost/i',
            '/127\.0\.0\.1/i',
            '/192\.168\./i',
            '/10\.0\./i',
            '/yoursite\.com/i',
            '/test\.com/i',
            '/donate\.html?$/i', // Generic donate.html files
        ];

        foreach ($invalidPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }
}
