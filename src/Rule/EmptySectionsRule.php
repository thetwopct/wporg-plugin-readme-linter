<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class EmptySectionsRule extends AbstractRule
{
    private const MIN_CONTENT_LENGTH = 10;

    public function getRuleId(): string
    {
        return 'empty-sections';
    }

    public function getDescription(): string
    {
        return 'Sections should have meaningful content';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];
        $sections = $parsedData['sections'] ?? [];

        foreach ($sections as $name => $content) {
            $trimmedContent = trim(strip_tags($content));

            if (strlen($trimmedContent) < self::MIN_CONTENT_LENGTH) {
                $line = $this->findLineNumber($rawContent, "== {$name} ==");
                $issues[] = $this->createIssue(
                    Issue::LEVEL_WARNING,
                    sprintf('Section "%s" appears to be empty or has very little content', $name),
                    $line
                );
            }
        }

        return $issues;
    }
}
