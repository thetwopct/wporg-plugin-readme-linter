<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Tests\Unit\Rule;

use PHPUnit\Framework\TestCase;
use WPOrg\Plugin\ReadmeLinter\Issue;
use WPOrg\Plugin\ReadmeLinter\Rule\ContributorsRule;

class ContributorsRuleTest extends TestCase
{
    public function testRestrictedContributor(): void
    {
        $rule = new ContributorsRule();
        $parsedData = [
            'contributors' => 'wordpress, testuser',
        ];
        $rawContent = "Contributors: wordpress, testuser\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('Restricted contributor username: "wordpress"', $issues[0]->getMessage());
    }

    public function testReservedContributor(): void
    {
        $rule = new ContributorsRule();
        $parsedData = [
            'contributors' => 'plugin, testuser',
        ];
        $rawContent = "Contributors: plugin, testuser\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_WARNING, $issues[0]->getLevel());
        $this->assertStringContainsString('Reserved contributor username: "plugin"', $issues[0]->getMessage());
    }

    public function testInvalidContributorFormat(): void
    {
        $rule = new ContributorsRule();
        $parsedData = [
            'contributors' => 'ab, testuser',
        ];
        $rawContent = "Contributors: ab, testuser\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_WARNING, $issues[0]->getLevel());
        $this->assertStringContainsString('Invalid contributor format: "ab"', $issues[0]->getMessage());
    }

    public function testValidContributors(): void
    {
        $rule = new ContributorsRule();
        $parsedData = [
            'contributors' => 'johndoe, jane-doe, user_123',
        ];
        $rawContent = "Contributors: johndoe, jane-doe, user_123\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(0, $issues);
    }

    public function testEmptyContributors(): void
    {
        $rule = new ContributorsRule();
        $parsedData = [];
        $rawContent = "=== Test Plugin ===\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(0, $issues);
    }
}
