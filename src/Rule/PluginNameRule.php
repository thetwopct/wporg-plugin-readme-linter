<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class PluginNameRule extends AbstractRule
{
    public function getRuleId(): string
    {
        return 'plugin-name';
    }

    public function getDescription(): string
    {
        return 'Plugin name header must be present and properly formatted';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];

        // Check if plugin name exists
        if (empty($parsedData['name'])) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_ERROR,
                'Missing plugin name header. Expected format: === Plugin Name ==='
            );
        } else {
            // Check if header is properly formatted in raw content
            $headerPattern = '/^===\s+(.+?)\s+===\s*$/m';
            if (!preg_match($headerPattern, $rawContent)) {
                $line = $this->findLineNumber($rawContent, $parsedData['name']);
                $issues[] = $this->createIssue(
                    Issue::LEVEL_ERROR,
                    'Plugin name header must be in the format: === Plugin Name ===',
                    $line
                );
            }
        }

        return $issues;
    }
}
