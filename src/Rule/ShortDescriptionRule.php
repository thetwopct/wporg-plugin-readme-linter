<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class ShortDescriptionRule extends AbstractRule
{
    private const MAX_LENGTH = 150;
    private const WARN_LENGTH = 140;

    public function getRuleId(): string
    {
        return 'short-description';
    }

    public function getDescription(): string
    {
        return 'Short description must be present and within length limits';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];

        // Extract the raw short description from the content to get the actual length
        // before the WordPress parser truncates it
        $rawShortDesc = $this->extractRawShortDescription($rawContent);

        if (empty($rawShortDesc)) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_ERROR,
                'Missing short description'
            );
            return $issues;
        }

        $length = mb_strlen($rawShortDesc);
        $line = $this->findLineNumber($rawContent, $rawShortDesc);

        if ($length > self::MAX_LENGTH) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_ERROR,
                sprintf(
                    'Short description is too long (%d characters, maximum %d)',
                    $length,
                    self::MAX_LENGTH
                ),
                $line
            );
        } elseif ($length > self::WARN_LENGTH) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_WARNING,
                sprintf(
                    'Short description is approaching maximum length (%d characters, recommended maximum %d)',
                    $length,
                    self::WARN_LENGTH
                ),
                $line
            );
        }

        return $issues;
    }

    /**
     * Extract the raw short description from the readme content.
     * The short description is the first non-empty line after the header section.
     */
    private function extractRawShortDescription(string $content): string
    {
        $lines = explode("\n", $content);
        $inHeader = true;
        $foundBlankLine = false;

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // Skip the header section (lines starting with === or metadata fields)
            if ($inHeader) {
                // If we hit a blank line, we're transitioning out of header
                if (empty($trimmedLine)) {
                    $foundBlankLine = true;
                    continue;
                }

                // If we found a blank line and this is not a metadata field or section header,
                // this should be the short description
                if ($foundBlankLine && !$this->isMetadataField($trimmedLine) && !$this->isSectionHeader($trimmedLine)) {
                    return $trimmedLine;
                }

                // If we encounter a section header after the blank line, we've moved past the short description
                if ($foundBlankLine && $this->isSectionHeader($trimmedLine)) {
                    $inHeader = false;
                }

                // Continue processing header fields
                continue;
            }
        }

        return '';
    }

    /**
     * Check if a line is a metadata field (e.g., "Contributors:", "Tags:", etc.)
     */
    private function isMetadataField(string $line): bool
    {
        $metadataFields = [
            'Contributors:', 'Tags:', 'Requires at least:', 'Tested up to:',
            'Requires PHP:', 'Stable tag:', 'License:', 'License URI:',
            'Donate link:', 'Author:', 'Author URI:', 'Plugin URI:', 'Version:'
        ];

        foreach ($metadataFields as $field) {
            if (stripos($line, $field) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a line is a section header (e.g., "== Description ==")
     */
    private function isSectionHeader(string $line): bool
    {
        return preg_match('/^==+\s+.+\s+==+$/', $line) === 1;
    }
}
