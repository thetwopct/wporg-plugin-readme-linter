<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class RequiredFieldsRule extends AbstractRule
{
    private const REQUIRED_FIELDS = [
        'contributors' => 'Contributors',
        'tags' => 'Tags',
        'requires' => 'Requires at least',
        'tested' => 'Tested up to',
        'stable_tag' => 'Stable tag',
    ];

    public function getRuleId(): string
    {
        return 'required-fields';
    }

    public function getDescription(): string
    {
        return 'Required metadata fields must be present';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];

        foreach (self::REQUIRED_FIELDS as $field => $label) {
            if (empty($parsedData[$field])) {
                $line = $this->findLineNumber($rawContent, $label);
                $issues[] = $this->createIssue(
                    Issue::LEVEL_ERROR,
                    "Missing required field: {$label}",
                    $line
                );
            }
        }

        return $issues;
    }
}
