<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Tests\Unit\Rule;

use PHPUnit\Framework\TestCase;
use WPOrg\Plugin\ReadmeLinter\Issue;
use WPOrg\Plugin\ReadmeLinter\Rule\TrademarkRule;

class TrademarkRuleTest extends TestCase
{
    public function testWordPressInPluginName(): void
    {
        $rule = new TrademarkRule();
        $parsedData = [
            'name' => 'WordPress Super Plugin',
        ];
        $rawContent = "=== WordPress Super Plugin ===\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_WARNING, $issues[0]->getLevel());
        $this->assertStringContainsString('Potential trademark issue in plugin name', $issues[0]->getMessage());
    }

    public function testOfficialWordPressPlugin(): void
    {
        $rule = new TrademarkRule();
        $parsedData = [
            'sections' => [
                'Description' => 'This is the official WordPress plugin for managing your site.',
            ],
        ];
        $rawContent = "== Description ==\nThis is the official WordPress plugin for managing your site.\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_WARNING, $issues[0]->getLevel());
        $this->assertStringContainsString('Potential trademark issue', $issues[0]->getMessage());
    }

    public function testValidWordPressUsage(): void
    {
        $rule = new TrademarkRule();
        $parsedData = [
            'name' => 'Analytics for WordPress',
            'sections' => [
                'Description' => 'This plugin integrates with WordPress to provide analytics.',
            ],
        ];
        $rawContent = "=== Analytics for WordPress ===\n== Description ==\nThis plugin integrates with WordPress to provide analytics.\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(0, $issues);
    }

    public function testOtherTrademarks(): void
    {
        $rule = new TrademarkRule();
        $parsedData = [
            'sections' => [
                'Description' => 'This plugin connects to Google Analytics and Facebook.',
            ],
        ];
        $rawContent = "== Description ==\nThis plugin connects to Google Analytics and Facebook.\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(0, $issues); // Should be allowed when used descriptively
    }

    public function testImproperGoogleUsage(): void
    {
        $rule = new TrademarkRule();
        $parsedData = [
            'name' => 'Google Analytics Plugin',
        ];
        $rawContent = "=== Google Analytics Plugin ===\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_INFO, $issues[0]->getLevel());
        $this->assertStringContainsString('Potential trademark usage', $issues[0]->getMessage());
    }
}
