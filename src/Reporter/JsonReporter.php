<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Reporter;

use WPOrg\Plugin\ReadmeLinter\Issue;

class JsonReporter implements ReporterInterface
{
    /**
     * @param Issue[] $issues
     */
    public function generate(array $issues): string
    {
        $data = [
            'issues' => array_map(fn(Issue $issue) => [
                'ruleId' => $issue->getRuleId(),
                'level' => $issue->getLevel(),
                'message' => $issue->getMessage(),
                'file' => $issue->getFile(),
                'line' => $issue->getLine(),
                'column' => $issue->getColumn(),
            ], $issues),
            'summary' => [
                'total' => count($issues),
                'errors' => count(array_filter($issues, fn(Issue $i) => $i->getLevel() === Issue::LEVEL_ERROR)),
                'warnings' => count(array_filter($issues, fn(Issue $i) => $i->getLevel() === Issue::LEVEL_WARNING)),
                'info' => count(array_filter($issues, fn(Issue $i) => $i->getLevel() === Issue::LEVEL_INFO)),
            ],
        ];

        $result = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return $result !== false ? $result : '{}';
    }
}
