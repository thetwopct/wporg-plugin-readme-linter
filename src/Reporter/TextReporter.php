<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Reporter;

use WPOrg\Plugin\ReadmeLinter\Issue;

class TextReporter implements ReporterInterface
{
    /**
     * @param Issue[] $issues
     */
    public function generate(array $issues): string
    {
        if (empty($issues)) {
            return '';
        }

        $output = [];

        // Group issues by level
        $byLevel = [
            Issue::LEVEL_ERROR => [],
            Issue::LEVEL_WARNING => [],
            Issue::LEVEL_INFO => [],
        ];

        foreach ($issues as $issue) {
            $byLevel[$issue->getLevel()][] = $issue;
        }

        // Output errors first
        if (!empty($byLevel[Issue::LEVEL_ERROR])) {
            $output[] = "\033[1;31mErrors:\033[0m";
            foreach ($byLevel[Issue::LEVEL_ERROR] as $issue) {
                $output[] = $this->formatIssue($issue, '✗');
            }
            $output[] = '';
        }

        // Then warnings
        if (!empty($byLevel[Issue::LEVEL_WARNING])) {
            $output[] = "\033[1;33mWarnings:\033[0m";
            foreach ($byLevel[Issue::LEVEL_WARNING] as $issue) {
                $output[] = $this->formatIssue($issue, '⚠');
            }
            $output[] = '';
        }

        // Finally info
        if (!empty($byLevel[Issue::LEVEL_INFO])) {
            $output[] = "\033[1;36mInfo:\033[0m";
            foreach ($byLevel[Issue::LEVEL_INFO] as $issue) {
                $output[] = $this->formatIssue($issue, 'ℹ');
            }
            $output[] = '';
        }

        return implode("\n", $output);
    }

    private function formatIssue(Issue $issue, string $icon): string
    {
        $parts = [$icon];

        if ($issue->getLine() !== null) {
            $parts[] = sprintf('Line %d:', $issue->getLine());
        }

        $parts[] = sprintf('[%s]', $issue->getRuleId());
        $parts[] = $issue->getMessage();

        return '  ' . implode(' ', $parts);
    }
}
