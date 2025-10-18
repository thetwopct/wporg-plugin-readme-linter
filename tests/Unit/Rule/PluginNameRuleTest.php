<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Tests\Unit\Rule;

use PHPUnit\Framework\TestCase;
use WPOrg\Plugin\ReadmeLinter\Issue;
use WPOrg\Plugin\ReadmeLinter\Rule\PluginNameRule;

class PluginNameRuleTest extends TestCase
{
    private PluginNameRule $rule;

    protected function setUp(): void
    {
        $this->rule = new PluginNameRule();
    }

    public function testValidPluginName(): void
    {
        $content = "=== My Plugin ===\n\nShort description.";
        $parsed = ['name' => 'My Plugin'];

        $issues = $this->rule->check($parsed, $content);

        $this->assertEmpty($issues);
    }

    public function testMissingPluginName(): void
    {
        $content = "Short description without header.";
        $parsed = ['name' => ''];

        $issues = $this->rule->check($parsed, $content);

        $this->assertCount(1, $issues);
        $this->assertSame(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('Missing plugin name', $issues[0]->getMessage());
    }

    public function testGetRuleId(): void
    {
        $this->assertSame('plugin-name', $this->rule->getRuleId());
    }

    public function testGetDescription(): void
    {
        $description = $this->rule->getDescription();
        $this->assertNotEmpty($description);
    }
}
