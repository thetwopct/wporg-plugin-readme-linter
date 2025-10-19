<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class PluginNameRule extends AbstractRule
{
    private ?string $pluginFilePath;

    public function __construct(?string $pluginFilePath = null)
    {
        $this->pluginFilePath = $pluginFilePath;
    }

    public function getRuleId(): string
    {
        return 'plugin-name';
    }

    public function getDescription(): string
    {
        return 'Plugin name header must be present, properly formatted, and match plugin file';
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
            return $issues;
        }

        $pluginName = trim($parsedData['name']);

        // Check for empty plugin name (just whitespace)
        if (empty($pluginName)) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_ERROR,
                'Plugin name header is empty. Provide a meaningful plugin name.'
            );
            return $issues;
        }

        // Check if header is properly formatted in raw content
        $headerPattern = '/^===\s+(.+?)\s+===\s*$/m';
        if (!preg_match($headerPattern, $rawContent, $matches)) {
            $line = $this->findLineNumber($rawContent, $pluginName);
            $issues[] = $this->createIssue(
                Issue::LEVEL_ERROR,
                'Plugin name header must be in the format: === Plugin Name ===',
                $line
            );
        }

        // Check for invalid plugin name patterns
        if ($this->hasInvalidNamePattern($pluginName)) {
            $line = $this->findLineNumber($rawContent, $pluginName);
            $issues[] = $this->createIssue(
                Issue::LEVEL_ERROR,
                sprintf('Invalid plugin name: "%s". Plugin names should not contain WordPress trademarks or be generic.', $pluginName),
                $line
            );
        }

        // Check for plugin name mismatch with plugin file if available
        if ($this->pluginFilePath && file_exists($this->pluginFilePath)) {
            $filePluginName = $this->extractPluginNameFromFile($this->pluginFilePath);
            if ($filePluginName && !$this->namesMatch($pluginName, $filePluginName)) {
                $line = $this->findLineNumber($rawContent, $pluginName);
                $issues[] = $this->createIssue(
                    Issue::LEVEL_WARNING,
                    sprintf(
                        'Plugin name mismatch: readme.txt has "%s" but plugin file has "%s"',
                        $pluginName,
                        $filePluginName
                    ),
                    $line
                );
            }
        }

        return $issues;
    }

    private function hasInvalidNamePattern(string $name): bool
    {
        $invalidPatterns = [
            '/^WordPress/i',
            '/^WP$/i',
            '/Plugin$/i', // Generic "Plugin" suffix
            '/^Test Plugin/i',
            '/^Sample Plugin/i',
            '/^Example Plugin/i',
            '/^My Plugin/i',
            '/^Plugin Name/i',
            '/^Your Plugin/i',
        ];

        foreach ($invalidPatterns as $pattern) {
            if (preg_match($pattern, $name)) {
                return true;
            }
        }

        return false;
    }

    private function extractPluginNameFromFile(string $pluginFilePath): ?string
    {
        $content = file_get_contents($pluginFilePath);
        if ($content === false) {
            return null;
        }

        // Look for plugin name in plugin header
        if (preg_match('/^\s*\*\s*Plugin Name:\s*(.+)$/mi', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function namesMatch(string $readmeName, string $fileName): bool
    {
        // Normalize names for comparison (remove extra whitespace, case insensitive)
        $normalizedReadme = preg_replace('/\s+/', ' ', trim($readmeName));
        if ($normalizedReadme === null) {
            $normalizedReadme = trim($readmeName);
        }
        $normalizedFile = preg_replace('/\s+/', ' ', trim($fileName));
        if ($normalizedFile === null) {
            $normalizedFile = trim($fileName);
        }

        return strtolower($normalizedReadme) === strtolower($normalizedFile);
    }
}
