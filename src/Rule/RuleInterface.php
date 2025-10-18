<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

interface RuleInterface
{
    /**
     * Get the unique identifier for this rule.
     */
    public function getRuleId(): string;

    /**
     * Get a short description of what this rule checks.
     */
    public function getDescription(): string;

    /**
     * Check the parsed readme data and return any issues found.
     *
     * @param array<string, mixed> $parsedData
     * @param string $rawContent
     * @return \WPOrg\Plugin\ReadmeLinter\Issue[]
     */
    public function check(array $parsedData, string $rawContent): array;
}
