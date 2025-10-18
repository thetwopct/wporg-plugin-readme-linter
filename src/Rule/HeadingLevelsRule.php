<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class HeadingLevelsRule extends AbstractRule
{
    public function getRuleId(): string
    {
        return 'heading-levels';
    }

    public function getDescription(): string
    {
        return 'Heading levels should be properly structured';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];
        $lines = explode("\n", $rawContent);

        foreach ($lines as $lineNumber => $line) {
            // Check for invalid ==== headings (should only use === for title and == for sections)
            if (preg_match('/^={4,}\s+.+\s+={4,}\s*$/', $line)) {
                $issues[] = $this->createIssue(
                    Issue::LEVEL_ERROR,
                    'Invalid heading level. Use === for plugin name and == for sections',
                    $lineNumber + 1
                );
            }
        }

        return $issues;
    }
}
