<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Tests\Unit\Rule;

use PHPUnit\Framework\TestCase;
use WPOrg\Plugin\ReadmeLinter\Issue;
use WPOrg\Plugin\ReadmeLinter\Rule\UpgradeNoticeRule;

class UpgradeNoticeRuleTest extends TestCase
{
    public function testTooManyUpgradeNotices(): void
    {
        $rule = new UpgradeNoticeRule();
        $parsedData = [
            'upgrade_notice' => [
                '1.0.0' => 'Upgrade notice 1',
                '1.1.0' => 'Upgrade notice 2',
                '1.2.0' => 'Upgrade notice 3',
                '1.3.0' => 'Upgrade notice 4',
                '1.4.0' => 'Upgrade notice 5',
            ],
        ];
        $rawContent = "== Upgrade Notice ==\n= 1.0.0 =\nUpgrade notice 1\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_WARNING, $issues[0]->getLevel());
        $this->assertStringContainsString('Too many upgrade notices', $issues[0]->getMessage());
    }

    public function testUpgradeNoticeTooLong(): void
    {
        $longNotice = str_repeat('This is a very long upgrade notice. ', 20); // Over 300 chars

        $rule = new UpgradeNoticeRule();
        $parsedData = [
            'upgrade_notice' => [
                '1.0.0' => $longNotice,
            ],
        ];
        $rawContent = "== Upgrade Notice ==\n= 1.0.0 =\n{$longNotice}\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_WARNING, $issues[0]->getLevel());
        $this->assertStringContainsString('too long', $issues[0]->getMessage());
    }

    public function testValidUpgradeNotices(): void
    {
        $rule = new UpgradeNoticeRule();
        $parsedData = [
            'upgrade_notice' => [
                '1.0.0' => 'Short upgrade notice',
                '1.1.0' => 'Another short notice',
            ],
        ];
        $rawContent = "== Upgrade Notice ==\n= 1.0.0 =\nShort upgrade notice\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(0, $issues);
    }

    public function testNoUpgradeNotices(): void
    {
        $rule = new UpgradeNoticeRule();
        $parsedData = [];
        $rawContent = "=== Test Plugin ===\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(0, $issues);
    }

    public function testStringUpgradeNotice(): void
    {
        $longNotice = str_repeat('This is a very long upgrade notice. ', 20); // Over 300 chars

        $rule = new UpgradeNoticeRule();
        $parsedData = [
            'upgrade_notice' => $longNotice,
        ];
        $rawContent = "== Upgrade Notice ==\n{$longNotice}\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_WARNING, $issues[0]->getLevel());
        $this->assertStringContainsString('too long', $issues[0]->getMessage());
    }
}
