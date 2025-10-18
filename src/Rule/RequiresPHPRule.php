<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class RequiresPHPRule extends AbstractRule
{
    public function getRuleId(): string
    {
        return 'requires-php';
    }

    public function getDescription(): string
    {
        return 'Requires PHP field should be present and valid';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];
        $requiresPhp = $parsedData['requires_php'] ?? '';

        if (empty($requiresPhp)) {
            $line = $this->findLineNumber($rawContent, 'Requires PHP:');
            $issues[] = $this->createIssue(
                Issue::LEVEL_WARNING,
                'Requires PHP field is missing. Consider specifying minimum PHP version.',
                $line
            );
            return $issues;
        }

        // Check if it's a valid PHP version format
        if (!preg_match('/^\d+\.\d+(\.\d+)?$/', $requiresPhp)) {
            $line = $this->findLineNumber($rawContent, 'Requires PHP:');
            $issues[] = $this->createIssue(
                Issue::LEVEL_WARNING,
                sprintf('Requires PHP "%s" should be a valid PHP version (e.g., 7.4 or 8.0)', $requiresPhp),
                $line
            );
        }

        return $issues;
    }
}
