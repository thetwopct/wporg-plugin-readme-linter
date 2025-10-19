<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class DefaultTextRule extends AbstractRule
{
    // Common default text patterns found in readme templates
    private const DEFAULT_PATTERNS = [
        // Plugin name patterns
        '/===\s*(Plugin Name|Your Plugin Name|My Plugin|Sample Plugin|Example Plugin)\s*===/i',

        // Description patterns
        '/Here is a short description of the plugin/i',
        '/This is the long description/i',
        '/A brief description of the Plugin/i',
        '/Short description of your plugin/i',
        '/Describe your plugin here/i',

        // Installation patterns
        '/Upload.*plugin.*directory/i',
        '/This section describes how to install the plugin/i',
        '/Upload the plugin files to the.*wp-content.*plugins/i',

        // FAQ patterns
        '/A question that someone might have/i',
        '/What about foo bar\?/i',
        '/Answer to "What about foo bar\?"/i',

        // Changelog patterns
        '/List versions from most recent at top/i',

        // Contributors patterns
        '/yourname/i',
        '/username1, username2/i',

        // Tags patterns
        '/tag1, tag2/i',
        '/tags, go, here/i',

        // Generic placeholder patterns
        '/\[Your Plugin Name\]/i',
        '/\{plugin.*name\}/i',
        '/TODO:/i',
        '/FIXME:/i',
        '/CHANGEME:/i',
        '/REPLACEME:/i',
        '/\[PLACEHOLDER\]/i',
        '/Lorem ipsum/i',

        // Version patterns
        '/0\.1/i', // Very common default version
        '/1\.0\.0-alpha/i',

        // URL patterns
        '/example\.com/i',
        '/yoursite\.com/i',
        '/http:\/\/URI_Of_Page_Describing_Plugin_and_Updates/i',

        // License patterns that suggest defaults
        '/URI_Of_License/i',
    ];

    // Specific default text blocks
    private const DEFAULT_TEXT_BLOCKS = [
        'Here is a short description of the plugin. This should be no more than 150 characters. No markup here.',
        'This is the long description. No limit, and you can use Markdown (as well as in the following sections).',
        'This section describes how to install the plugin and get it working.',
        'A question that someone might have',
        'Answer to "What about foo bar?"',
        'List versions from most recent at top to oldest at bottom.',
    ];

    public function getRuleId(): string
    {
        return 'default-text';
    }

    public function getDescription(): string
    {
        return 'Readme should not contain default template text';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];

        // Check for default patterns in raw content
        foreach (self::DEFAULT_PATTERNS as $pattern) {
            if (preg_match($pattern, $rawContent, $matches, PREG_OFFSET_CAPTURE)) {
                $line = $this->getLineFromOffset($rawContent, $matches[0][1]);
                $issues[] = $this->createIssue(
                    Issue::LEVEL_ERROR,
                    sprintf('Default readme template text detected: "%s"', trim($matches[0][0])),
                    $line
                );
            }
        }

        // Check for default text blocks
        foreach (self::DEFAULT_TEXT_BLOCKS as $defaultText) {
            if (stripos($rawContent, $defaultText) !== false) {
                $line = $this->findLineNumber($rawContent, $defaultText);
                $issues[] = $this->createIssue(
                    Issue::LEVEL_ERROR,
                    sprintf('Default readme template text detected: "%s"', $this->truncateText($defaultText, 50)),
                    $line
                );
            }
        }

        // Check specific fields for default values
        $this->checkFieldForDefaults($parsedData, $rawContent, 'name', $issues);
        $this->checkFieldForDefaults($parsedData, $rawContent, 'contributors', $issues);
        $this->checkFieldForDefaults($parsedData, $rawContent, 'tags', $issues);

        // Check sections for default content
        $sections = $parsedData['sections'] ?? [];
        foreach ($sections as $sectionName => $sectionContent) {
            $this->checkSectionForDefaults($sectionName, $sectionContent, $rawContent, $issues);
        }

        return $issues;
    }

    /**
     * @param array<mixed> $parsedData
     * @param array<Issue> $issues
     */
    private function checkFieldForDefaults(array $parsedData, string $rawContent, string $field, array &$issues): void
    {
        $value = $parsedData[$field] ?? '';
        if (empty($value)) {
            return;
        }

        // Convert array to string if needed
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        $defaultIndicators = [
            'name' => ['Plugin Name', 'Your Plugin Name', 'My Plugin'],
            'contributors' => ['yourname', 'username1', 'username2'],
            'tags' => ['tag1', 'tag2', 'tags', 'go', 'here'],
        ];

        if (isset($defaultIndicators[$field])) {
            foreach ($defaultIndicators[$field] as $defaultValue) {
                if (stripos($value, $defaultValue) !== false) {
                    $searchText = is_string($parsedData[$field]) ? $parsedData[$field] : $value;
                    $line = $this->findLineNumber($rawContent, $searchText);
                    $issues[] = $this->createIssue(
                        Issue::LEVEL_ERROR,
                        sprintf('Default template value in %s field: "%s"', $field, $value),
                        $line
                    );
                    break;
                }
            }
        }
    }

    /**
     * @param array<Issue> $issues
     */
    private function checkSectionForDefaults(
        string $sectionName,
        string $content,
        string $rawContent,
        array &$issues
    ): void {
        $sectionDefaults = [
            'description' => [
                'This is the long description',
                'Here is a short description',
                'Describe your plugin here',
            ],
            'installation' => [
                'This section describes how to install',
                'Upload the plugin files',
            ],
            'faq' => [
                'A question that someone might have',
                'What about foo bar?',
            ],
            'changelog' => [
                'List versions from most recent',
            ],
        ];

        $sectionKey = strtolower($sectionName);
        if (isset($sectionDefaults[$sectionKey])) {
            foreach ($sectionDefaults[$sectionKey] as $defaultText) {
                if (stripos($content, $defaultText) !== false) {
                    $line = $this->findLineNumber($rawContent, "== {$sectionName} ==");
                    $issues[] = $this->createIssue(
                        Issue::LEVEL_ERROR,
                        sprintf(
                            'Default template text in %s section: "%s"',
                            $sectionName,
                            $this->truncateText($defaultText, 40)
                        ),
                        $line
                    );
                    break;
                }
            }
        }
    }

    private function getLineFromOffset(string $content, int $offset): int
    {
        return substr_count($content, "\n", 0, $offset) + 1;
    }

    private function truncateText(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength) . '...';
    }
}
