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
        $content = "=== Test Plugin ===\nTags: test\n\nShort description under limit.";
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
        $content = "=== Test Plugin ===\nTags: test\n\n" . $longDesc;
        $parsed = ['short_description' => $longDesc];

        $issues = $this->rule->check($parsed, $content);

        $this->assertCount(1, $issues);
        $this->assertSame(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('too long', $issues[0]->getMessage());
        $this->assertStringContainsString('160 characters', $issues[0]->getMessage());
    }

    public function testWarningLength(): void
    {
        $longDesc = str_repeat('a', 145);
        $content = "=== Test Plugin ===\nTags: test\n\n" . $longDesc;
        $parsed = ['short_description' => $longDesc];

        $issues = $this->rule->check($parsed, $content);

        $this->assertCount(1, $issues);
        $this->assertSame(Issue::LEVEL_WARNING, $issues[0]->getLevel());
        $this->assertStringContainsString('approaching maximum', $issues[0]->getMessage());
        $this->assertStringContainsString('145 characters', $issues[0]->getMessage());
    }

    public function testRealWorldTruncationScenario(): void
    {
        // This test simulates the real-world scenario where the WordPress parser
        // truncates the description but we should still detect the full length
        $realWorldDesc = 'The FASTEST YouTube Video Embed for WordPress - Improve Core Web Vitals, Lighthouse scores, and site performance with this blazing fast YouTube block. Testing longer description Lorem ipsum dolor sit amet consectetuer adipiscing elit aenean commodo.'; // phpcs:ignore Generic.Files.LineLength.TooLong
        $content = "=== Blazing Fast Embed for YouTube ===\n" .
                   "Description: The FASTEST YouTube Video Embed for WordPress. No cookies. No GDPR.\n" .
                   "Author: YMMV Plugins\n" .
                   "Tags: youtube, video, performance\n" .
                   "Version: 1.0.0\n" .
                   "\n" .
                   $realWorldDesc . "\n" .
                   "\n" .
                   "== Description ==\n";

        // The parsed data would have the truncated version (150 chars)
        $truncatedDesc = substr($realWorldDesc, 0, 150);
        $parsed = ['short_description' => $truncatedDesc];

        $issues = $this->rule->check($parsed, $content);

        $this->assertCount(1, $issues);
        $this->assertSame(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('too long', $issues[0]->getMessage());
        $this->assertStringContainsString('249 characters', $issues[0]->getMessage());
    }
}
