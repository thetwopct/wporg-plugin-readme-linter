<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

abstract class AbstractRule implements RuleInterface
{
    /**
     * Create a new issue for this rule.
     */
    protected function createIssue(
        string $level,
        string $message,
        ?int $line = null,
        ?int $column = null
    ): Issue {
        return new Issue(
            $this->getRuleId(),
            $level,
            $message,
            $line,
            $column
        );
    }

    /**
     * Find the line number where a specific text appears in the content.
     */
    protected function findLineNumber(string $content, string $searchText): ?int
    {
        $lines = explode("\n", $content);
        foreach ($lines as $index => $line) {
            if (stripos($line, $searchText) !== false) {
                return $index + 1; // Line numbers are 1-indexed
            }
        }
        return null;
    }

    /**
     * Count the number of lines in content.
     */
    protected function countLines(string $content): int
    {
        return count(explode("\n", $content));
    }
}
