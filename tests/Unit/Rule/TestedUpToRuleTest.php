<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Tests\Unit\Rule;

use PHPUnit\Framework\TestCase;
use WPOrg\Plugin\ReadmeLinter\Issue;
use WPOrg\Plugin\ReadmeLinter\Rule\TestedUpToRule;

class TestedUpToRuleTest extends TestCase
{
    public function testInvalidVersionFormat(): void
    {
        $rule = new TestedUpToRule();
        $parsedData = [
            'tested' => 'invalid-version',
        ];
        $rawContent = "Tested up to: invalid-version\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('Invalid "Tested up to" version format', $issues[0]->getMessage());
    }

    public function testVersionTooFuture(): void
    {
        $rule = new TestedUpToRule('6.4.0'); // Current version
        $parsedData = [
            'tested' => '8.0', // Too far in future
        ];
        $rawContent = "Tested up to: 8.0\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('This version of WordPress does not exist', $issues[0]->getMessage());
    }

    public function testVersionOutdated(): void
    {
        $rule = new TestedUpToRule('6.4.0'); // Current version
        $parsedData = [
            'tested' => '4.0', // Too old
        ];
        $rawContent = "Tested up to: 4.0\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('outdated', $issues[0]->getMessage());
    }

    public function testValidVersion(): void
    {
        $rule = new TestedUpToRule('6.4.0'); // Current version
        $parsedData = [
            'tested' => '6.4',
        ];
        $rawContent = "Tested up to: 6.4\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(0, $issues);
    }

    public function testValidFutureVersion(): void
    {
        $rule = new TestedUpToRule('6.4.0'); // Current version
        $parsedData = [
            'tested' => '7.0', // One major version ahead is OK
        ];
        $rawContent = "Tested up to: 7.0\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(0, $issues);
    }

    public function testEmptyTestedUpTo(): void
    {
        $rule = new TestedUpToRule();
        $parsedData = [];
        $rawContent = "=== Test Plugin ===\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(0, $issues); // Handled by RequiredFieldsRule
    }
}
