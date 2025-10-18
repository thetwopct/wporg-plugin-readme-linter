<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class ShortDescriptionRule extends AbstractRule
{
    private const MAX_LENGTH = 150;
    private const WARN_LENGTH = 140;

    public function getRuleId(): string
    {
        return 'short-description';
    }

    public function getDescription(): string
    {
        return 'Short description must be present and within length limits';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];
        $shortDesc = $parsedData['short_description'] ?? '';

        if (empty($shortDesc)) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_ERROR,
                'Missing short description'
            );
            return $issues;
        }

        $length = mb_strlen($shortDesc);
        $line = $this->findLineNumber($rawContent, $shortDesc);

        if ($length > self::MAX_LENGTH) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_ERROR,
                sprintf(
                    'Short description is too long (%d characters, maximum %d)',
                    $length,
                    self::MAX_LENGTH
                ),
                $line
            );
        } elseif ($length > self::WARN_LENGTH) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_WARNING,
                sprintf(
                    'Short description is approaching maximum length (%d characters, recommended maximum %d)',
                    $length,
                    self::WARN_LENGTH
                ),
                $line
            );
        }

        return $issues;
    }
}
