<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class UpgradeNoticeRule extends AbstractRule
{
    private const MAX_UPGRADE_NOTICES = 3;
    private const MAX_NOTICE_LENGTH = 300;

    public function getRuleId(): string
    {
        return 'upgrade-notice';
    }

    public function getDescription(): string
    {
        return 'Upgrade notices should be limited in number and length';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];
        $upgradeNotices = $parsedData['upgrade_notice'] ?? [];

        if (empty($upgradeNotices)) {
            return $issues; // No upgrade notices is fine
        }

        $line = $this->findLineNumber($rawContent, '== Upgrade Notice ==');

        // Check number of upgrade notices
        if (is_array($upgradeNotices) && count($upgradeNotices) > self::MAX_UPGRADE_NOTICES) {
            $issues[] = $this->createIssue(
                Issue::LEVEL_WARNING,
                sprintf(
                    'Too many upgrade notices (%d). WordPress.org recommends maximum %d upgrade notices.',
                    count($upgradeNotices),
                    self::MAX_UPGRADE_NOTICES
                ),
                $line
            );
        }

        // Check individual upgrade notice lengths
        if (is_array($upgradeNotices)) {
            foreach ($upgradeNotices as $version => $notice) {
                $noticeLength = strlen(strip_tags($notice));
                if ($noticeLength > self::MAX_NOTICE_LENGTH) {
                    $noticeLine = $this->findLineNumber($rawContent, "= {$version} =");
                    $issues[] = $this->createIssue(
                        Issue::LEVEL_WARNING,
                        sprintf(
                            'Upgrade notice for version %s is too long (%d characters, maximum %d recommended)',
                            $version,
                            $noticeLength,
                            self::MAX_NOTICE_LENGTH
                        ),
                        $noticeLine ?: $line
                    );
                }
            }
        } elseif (is_string($upgradeNotices)) {
            // Handle case where upgrade_notice is a single string
            $noticeLength = strlen(strip_tags($upgradeNotices));
            if ($noticeLength > self::MAX_NOTICE_LENGTH) {
                $issues[] = $this->createIssue(
                    Issue::LEVEL_WARNING,
                    sprintf(
                        'Upgrade notice is too long (%d characters, maximum %d recommended)',
                        $noticeLength,
                        self::MAX_NOTICE_LENGTH
                    ),
                    $line
                );
            }
        }

        return $issues;
    }
}
