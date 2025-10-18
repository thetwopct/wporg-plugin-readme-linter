<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Tests\Unit\Rule;

use PHPUnit\Framework\TestCase;
use WPOrg\Plugin\ReadmeLinter\Issue;
use WPOrg\Plugin\ReadmeLinter\Rule\StableTagRule;

class StableTagRuleTest extends TestCase
{
    public function testValidSemanticVersion(): void
    {
        $rule = new StableTagRule();
        $content = "Stable tag: 1.0.0";
        $parsed = ['stable_tag' => '1.0.0'];

        $issues = $rule->check($parsed, $content);

        $this->assertEmpty($issues);
    }

    public function testTrunkNotAllowed(): void
    {
        $rule = new StableTagRule(false);
        $content = "Stable tag: trunk";
        $parsed = ['stable_tag' => 'trunk'];

        $issues = $rule->check($parsed, $content);

        $this->assertCount(1, $issues);
        $this->assertSame(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('trunk', $issues[0]->getMessage());
    }

    public function testTrunkAllowedWithWarning(): void
    {
        $rule = new StableTagRule(true);
        $content = "Stable tag: trunk";
        $parsed = ['stable_tag' => 'trunk'];

        $issues = $rule->check($parsed, $content);

        $this->assertCount(1, $issues);
        $this->assertSame(Issue::LEVEL_WARNING, $issues[0]->getLevel());
        $this->assertStringContainsString('trunk', $issues[0]->getMessage());
    }

    public function testInvalidFormat(): void
    {
        $rule = new StableTagRule();
        $content = "Stable tag: v1.0.0-beta";
        $parsed = ['stable_tag' => 'v1.0.0-beta'];

        $issues = $rule->check($parsed, $content);

        $this->assertCount(1, $issues);
        $this->assertSame(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('semantic versioning', $issues[0]->getMessage());
    }
}
