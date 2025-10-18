<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Tests\Unit\Rule;

use PHPUnit\Framework\TestCase;
use WPOrg\Plugin\ReadmeLinter\Issue;
use WPOrg\Plugin\ReadmeLinter\Rule\ShortDescriptionRule;

class ShortDescriptionRuleTest extends TestCase
{
    private ShortDescriptionRule $rule;

    protected function setUp(): void
    {
        $this->rule = new ShortDescriptionRule();
    }

    public function testValidShortDescription(): void
    {
        $content = "Short description under limit.";
        $parsed = ['short_description' => 'Short description under limit.'];

        $issues = $this->rule->check($parsed, $content);

        $this->assertEmpty($issues);
    }

    public function testMissingShortDescription(): void
    {
        $content = "=== Plugin ===";
        $parsed = ['short_description' => ''];

        $issues = $this->rule->check($parsed, $content);

        $this->assertCount(1, $issues);
        $this->assertSame(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('Missing short description', $issues[0]->getMessage());
    }

    public function testTooLongDescription(): void
    {
        $longDesc = str_repeat('a', 160);
        $content = $longDesc;
        $parsed = ['short_description' => $longDesc];

        $issues = $this->rule->check($parsed, $content);

        $this->assertCount(1, $issues);
        $this->assertSame(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('too long', $issues[0]->getMessage());
    }

    public function testWarningLength(): void
    {
        $longDesc = str_repeat('a', 145);
        $content = $longDesc;
        $parsed = ['short_description' => $longDesc];

        $issues = $this->rule->check($parsed, $content);

        $this->assertCount(1, $issues);
        $this->assertSame(Issue::LEVEL_WARNING, $issues[0]->getLevel());
        $this->assertStringContainsString('approaching maximum', $issues[0]->getMessage());
    }
}
