<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class StableTagRule extends AbstractRule
{
    private bool $allowTrunk;

    public function __construct(bool $allowTrunk = false)
    {
        $this->allowTrunk = $allowTrunk;
    }

    public function getRuleId(): string
    {
        return 'stable-tag';
    }

    public function getDescription(): string
    {
        return 'Stable tag must be present and in valid format';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];
        $stableTag = $parsedData['stable_tag'] ?? '';

        if (empty($stableTag)) {
            return $issues; // Handled by RequiredFieldsRule
        }

        $line = $this->findLineNumber($rawContent, "Stable tag:");

        // Check if it's "trunk"
        if (strtolower(trim($stableTag)) === 'trunk') {
            $level = $this->allowTrunk ? Issue::LEVEL_WARNING : Issue::LEVEL_ERROR;
            $issues[] = $this->createIssue(
                $level,
                'Stable tag is set to "trunk". Consider using a semantic version number.',
                $line
            );
            return $issues;
        }

        // Check if it follows semantic versioning pattern
        if (!preg_match('/^\d+(\.\d+)*$/', $stableTag)) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_ERROR,
                sprintf(
                    'Stable tag "%s" should follow semantic versioning (e.g., 1.0.0)',
                    $stableTag
                ),
                $line
            );
        }

        return $issues;
    }
}
