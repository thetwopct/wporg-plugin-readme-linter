<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Reporter;

use WPOrg\Plugin\ReadmeLinter\Issue;

interface ReporterInterface
{
    /**
     * Generate a report from the given issues.
     *
     * @param Issue[] $issues
     */
    public function generate(array $issues): string;
}
