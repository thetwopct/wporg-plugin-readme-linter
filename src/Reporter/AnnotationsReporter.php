<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Reporter;

use WPOrg\Plugin\ReadmeLinter\Issue;

class AnnotationsReporter implements ReporterInterface
{
    /**
     * @param Issue[] $issues
     */
    public function generate(array $issues): string
    {
        $output = [];

        foreach ($issues as $issue) {
            $output[] = $this->formatAnnotation($issue);
        }

        return implode("\n", $output);
    }

    private function formatAnnotation(Issue $issue): string
    {
        $command = match ($issue->getLevel()) {
            Issue::LEVEL_ERROR => 'error',
            Issue::LEVEL_WARNING => 'warning',
            default => 'notice',
        };

        $file = $issue->getFile() ?? 'readme.txt';
        $line = $issue->getLine() ?? 1;

        $properties = "file={$file},line={$line}";
        if ($issue->getColumn() !== null) {
            $properties .= ",col={$issue->getColumn()}";
        }

        $message = $issue->getMessage();
        $ruleId = $issue->getRuleId();

        return "::{$command} {$properties}::[{$ruleId}] {$message}";
    }
}
