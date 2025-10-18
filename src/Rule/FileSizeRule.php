<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class FileSizeRule extends AbstractRule
{
    private const MAX_SIZE_BYTES = 20480; // 20KB
    private const WARN_SIZE_BYTES = 10240; // 10KB

    public function getRuleId(): string
    {
        return 'file-size';
    }

    public function getDescription(): string
    {
        return 'Readme file size should be reasonable';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];
        $size = strlen($rawContent);

        if ($size > self::MAX_SIZE_BYTES) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_WARNING,
                sprintf(
                    'Readme file is very large (%s, maximum recommended %s)',
                    $this->formatBytes($size),
                    $this->formatBytes(self::MAX_SIZE_BYTES)
                )
            );
        } elseif ($size > self::WARN_SIZE_BYTES) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_INFO,
                sprintf(
                    'Readme file is getting large (%s)',
                    $this->formatBytes($size)
                )
            );
        }

        return $issues;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024) {
            return sprintf('%.1fKB', $bytes / 1024);
        }
        return $bytes . ' bytes';
    }
}
