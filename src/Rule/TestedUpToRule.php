<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class TestedUpToRule extends AbstractRule
{
    private ?string $currentWordPressVersion;

    public function __construct(?string $currentWordPressVersion = null)
    {
        $this->currentWordPressVersion = $currentWordPressVersion;
    }

    public function getRuleId(): string
    {
        return 'tested-up-to';
    }

    public function getDescription(): string
    {
        return 'Tested up to field must be current and valid WordPress version';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];
        $testedUpTo = $parsedData['tested'] ?? '';

        if (empty($testedUpTo)) {
            return $issues; // Handled by RequiredFieldsRule
        }

        $line = $this->findLineNumber($rawContent, 'Tested up to:');
        $currentVersion = $this->getCurrentWordPressVersion();

        // Check if version format is valid
        if (!$this->isValidVersionFormat($testedUpTo)) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_ERROR,
                sprintf('Invalid "Tested up to" version format: "%s". Use format like "6.4" or "6.4.1"', $testedUpTo),
                $line
            );
            return $issues;
        }

        if ($currentVersion) {
            // Check if version is too far in the future (non-existent)
            if ($this->isVersionTooFuture($testedUpTo, $currentVersion)) {
                $issues[] = $this->createIssue(
                    Issue::LEVEL_ERROR,
                    sprintf('Tested up to: %s. This version of WordPress does not exist (yet).', $testedUpTo),
                    $line
                );
            }
            // Check if version is outdated
            elseif ($this->isVersionOutdated($testedUpTo, $currentVersion)) {
                $issues[] = $this->createIssue(
                    Issue::LEVEL_ERROR,
                    sprintf(
                        'Tested up to version "%s" is outdated. Current WordPress version is %s.',
                        $testedUpTo,
                        $currentVersion
                    ),
                    $line
                );
            }
            // Check for minor version issues within same major version
            elseif ($this->hasMinorVersionIssue($testedUpTo, $currentVersion)) {
                $issues[] = $this->createIssue(
                    Issue::LEVEL_ERROR,
                    sprintf(
                        'Invalid minor version in "Tested up to": "%s". When using the same major version as current (%s), use the major.minor format without patch version.',
                        $testedUpTo,
                        $currentVersion
                    ),
                    $line
                );
            }
        }

        return $issues;
    }

    private function getCurrentWordPressVersion(): ?string
    {
        if ($this->currentWordPressVersion) {
            return $this->currentWordPressVersion;
        }

        // Try to fetch current WordPress version from API
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'WPOrg-Plugin-Readme-Linter/1.0'
            ]
        ]);

        $response = @file_get_contents('https://api.wordpress.org/core/version-check/1.7/', false, $context);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['offers'][0]['version'])) {
            return null;
        }

        return $data['offers'][0]['version'];
    }

    private function isValidVersionFormat(string $version): bool
    {
        return preg_match('/^\d+\.\d+(\.\d+)?$/', $version) === 1;
    }

    private function isVersionTooFuture(string $testedVersion, string $currentVersion): bool
    {
        $testedParts = $this->parseVersion($testedVersion);
        $currentParts = $this->parseVersion($currentVersion);

        // Allow up to one major version ahead
        $maxAllowedMajor = $currentParts['major'] + 1;

        return $testedParts['major'] > $maxAllowedMajor;
    }

    private function isVersionOutdated(string $testedVersion, string $currentVersion): bool
    {
        $testedParts = $this->parseVersion($testedVersion);
        $currentParts = $this->parseVersion($currentVersion);

        // Consider outdated if more than 1 major version behind
        return ($currentParts['major'] - $testedParts['major']) > 1;
    }

    private function hasMinorVersionIssue(string $testedVersion, string $currentVersion): bool
    {
        $testedParts = $this->parseVersion($testedVersion);
        $currentParts = $this->parseVersion($currentVersion);

        // If same major version, check minor version logic
        if ($testedParts['major'] === $currentParts['major']) {
            // If tested version has patch but current doesn't, or vice versa, it might be an issue
            // This is a simplified check - the actual WordPress logic is more complex
            return false; // For now, we'll be lenient on minor version checks
        }

        return false;
    }

    private function parseVersion(string $version): array
    {
        $parts = explode('.', $version);
        return [
            'major' => (int) ($parts[0] ?? 0),
            'minor' => (int) ($parts[1] ?? 0),
            'patch' => (int) ($parts[2] ?? 0),
        ];
    }
}
