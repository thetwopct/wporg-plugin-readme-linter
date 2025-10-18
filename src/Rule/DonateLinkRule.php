<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class DonateLinkRule extends AbstractRule
{
    public function getRuleId(): string
    {
        return 'donate-link';
    }

    public function getDescription(): string
    {
        return 'Donate link should be present and properly formatted';
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

        // Validate URL format
        if (!filter_var($donateLink, FILTER_VALIDATE_URL)) {
            $line = $this->findLineNumber($rawContent, 'Donate link:');
            $issues[] = $this->createIssue(
                Issue::LEVEL_WARNING,
                sprintf('Donate link "%s" does not appear to be a valid URL', $donateLink),
                $line
            );
        }

        return $issues;
    }
}
