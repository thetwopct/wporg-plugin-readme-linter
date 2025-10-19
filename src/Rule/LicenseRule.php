<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class LicenseRule extends AbstractRule
{
    private const VALID_LICENSES = [
        'GPL2',
        'GPL2+',
        'GPL3',
        'GPL3+',
        'GPLv2',
        'GPLv2+',
        'GPLv2 or later',
        'GPLv3',
        'GPLv3+',
        'GPLv3 or later',
        'MIT',
        'BSD',
        'Apache',
        'Apache 2.0',
        'LGPL',
        'LGPL2.1',
        'LGPL3',
    ];

    private ?string $pluginFilePath;

    public function __construct(?string $pluginFilePath = null)
    {
        $this->pluginFilePath = $pluginFilePath;
    }

    public function getRuleId(): string
    {
        return 'license';
    }

    public function getDescription(): string
    {
        return 'License field must be present, valid, and match plugin header';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];
        $license = $parsedData['license'] ?? '';

        // Check if license is missing
        if (empty($license)) {
            $line = $this->findLineNumber($rawContent, 'License:');
            $issues[] = $this->createIssue(
                Issue::LEVEL_ERROR,
                'Missing license field. WordPress.org requires a valid license.',
                $line
            );
            return $issues;
        }

        $line = $this->findLineNumber($rawContent, 'License:');

        // Check if license is valid
        if (!$this->isValidLicense($license)) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_ERROR,
                sprintf(
                    'Invalid license "%s". WordPress.org accepts: %s',
                    $license,
                    implode(', ', self::VALID_LICENSES)
                ),
                $line
            );
        }

        // Check for license mismatch with plugin file if available
        if ($this->pluginFilePath && file_exists($this->pluginFilePath)) {
            $pluginLicense = $this->extractPluginLicense($this->pluginFilePath);
            if ($pluginLicense && !$this->licensesMatch($license, $pluginLicense)) {
                $issues[] = $this->createIssue(
                    Issue::LEVEL_ERROR,
                    sprintf(
                        'License mismatch: readme.txt has "%s" but plugin file has "%s"',
                        $license,
                        $pluginLicense
                    ),
                    $line
                );
            }
        }

        return $issues;
    }

    private function isValidLicense(string $license): bool
    {
        $normalizedLicense = $this->normalizeLicense($license);

        foreach (self::VALID_LICENSES as $validLicense) {
            if ($this->normalizeLicense($validLicense) === $normalizedLicense) {
                return true;
            }
        }

        return false;
    }

    private function normalizeLicense(string $license): string
    {
        $normalized = preg_replace('/\s+/', ' ', $license);
        if ($normalized === null) {
            $normalized = $license;
        }
        return strtolower(trim($normalized));
    }

    private function licensesMatch(string $readmeLicense, string $pluginLicense): bool
    {
        return $this->normalizeLicense($readmeLicense) === $this->normalizeLicense($pluginLicense);
    }

    private function extractPluginLicense(string $pluginFilePath): ?string
    {
        $content = file_get_contents($pluginFilePath);
        if ($content === false) {
            return null;
        }

        // Look for license in plugin header
        if (preg_match('/^\s*\*\s*License:\s*(.+)$/mi', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
