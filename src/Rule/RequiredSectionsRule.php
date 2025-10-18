<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class RequiredSectionsRule extends AbstractRule
{
    /** @var string[] */
    private array $requiredSections;

    /**
     * @param string[] $requiredSections
     */
    public function __construct(array $requiredSections = ['description', 'installation', 'changelog'])
    {
        $this->requiredSections = array_map('strtolower', $requiredSections);
    }

    public function getRuleId(): string
    {
        return 'required-sections';
    }

    public function getDescription(): string
    {
        return 'Required sections must be present';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];
        $sections = $parsedData['sections'] ?? [];
        $sectionKeys = array_map(fn($key) => strtolower((string) $key), array_keys($sections));

        foreach ($this->requiredSections as $required) {
            if (!in_array($required, $sectionKeys, true)) {
                $issues[] = $this->createIssue(
                    Issue::LEVEL_ERROR,
                    sprintf('Missing required section: %s', ucfirst($required))
                );
            }
        }

        return $issues;
    }
}
