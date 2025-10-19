<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class ContributorsRule extends AbstractRule
{
    // WordPress.org restricted usernames that cannot be used as contributors
    private const RESTRICTED_CONTRIBUTORS = [
        'wordpress',
        'wordpressdotorg',
        'admin',
        'root',
        'www',
        'web',
        'ftp',
        'mail',
        'email',
        'blog',
        'forum',
        'support',
        'help',
        'api',
        'dev',
        'developer',
        'test',
        'testing',
        'stage',
        'staging',
        'demo',
        'sample',
        'example',
        'null',
        'undefined',
        'false',
        'true',
        'anonymous',
        'guest',
        'user',
        'users',
        'member',
        'members',
        'staff',
        'team',
        'group',
        'groups',
        'owner',
        'owners',
        'moderator',
        'moderators',
        'editor',
        'editors',
        'author',
        'authors',
        'contributor',
        'contributors',
        'subscriber',
        'subscribers',
    ];

    // Reserved usernames that trigger warnings
    private const RESERVED_CONTRIBUTORS = [
        'plugin',
        'plugins',
        'theme',
        'themes',
        'wp',
        'wordpress-org',
        'wordpressorg',
        'automattic',
        'matt',
        'mullenweg',
    ];

    public function getRuleId(): string
    {
        return 'contributors';
    }

    public function getDescription(): string
    {
        return 'Contributors field must be valid and not use restricted usernames';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];
        $contributors = $parsedData['contributors'] ?? '';

        if (empty($contributors)) {
            return $issues; // Handled by RequiredFieldsRule
        }

        $line = $this->findLineNumber($rawContent, 'Contributors:');

        // Parse contributors (comma-separated)
        if (is_array($contributors)) {
            $contributorList = $contributors;
        } else {
            $contributorList = array_map('trim', explode(',', $contributors));
        }

        foreach ($contributorList as $contributor) {
            if (empty($contributor)) {
                continue;
            }

            // Check for restricted contributors (errors)
            if ($this->isRestrictedContributor($contributor)) {
                $issues[] = $this->createIssue(
                    Issue::LEVEL_ERROR,
                    sprintf('Restricted contributor username: "%s". This username cannot be used.', $contributor),
                    $line
                );
            }

            // Check for reserved contributors (warnings)
            if ($this->isReservedContributor($contributor)) {
                $issues[] = $this->createIssue(
                    Issue::LEVEL_WARNING,
                    sprintf('Reserved contributor username: "%s". Consider using a different username.', $contributor),
                    $line
                );
            }

            // Check for invalid contributor format
            if (!$this->isValidContributorFormat($contributor)) {
                $issues[] = $this->createIssue(
                    Issue::LEVEL_WARNING,
                    sprintf(
                        'Invalid contributor format: "%s". Contributors should be WordPress.org usernames.',
                        $contributor
                    ),
                    $line
                );
            }
        }

        return $issues;
    }

    private function isRestrictedContributor(string $contributor): bool
    {
        return in_array(strtolower($contributor), self::RESTRICTED_CONTRIBUTORS, true);
    }

    private function isReservedContributor(string $contributor): bool
    {
        return in_array(strtolower($contributor), self::RESERVED_CONTRIBUTORS, true);
    }

    private function isValidContributorFormat(string $contributor): bool
    {
        // WordPress.org usernames should be alphanumeric with hyphens and underscores
        // Must be 3-60 characters long
        return preg_match('/^[a-zA-Z0-9_-]{3,60}$/', $contributor) === 1;
    }
}
