<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Tests\Unit\Rule;

use PHPUnit\Framework\TestCase;
use WPOrg\Plugin\ReadmeLinter\Issue;
use WPOrg\Plugin\ReadmeLinter\Rule\LicenseRule;

class LicenseRuleTest extends TestCase
{
    public function testMissingLicense(): void
    {
        $rule = new LicenseRule();
        $parsedData = [
            'name' => 'Test Plugin',
        ];
        $rawContent = "=== Test Plugin ===\nContributors: test\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('Missing license field', $issues[0]->getMessage());
    }

    public function testInvalidLicense(): void
    {
        $rule = new LicenseRule();
        $parsedData = [
            'name' => 'Test Plugin',
            'license' => 'InvalidLicense',
        ];
        $rawContent = "=== Test Plugin ===\nLicense: InvalidLicense\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('Invalid license', $issues[0]->getMessage());
    }

    public function testValidLicense(): void
    {
        $rule = new LicenseRule();
        $parsedData = [
            'name' => 'Test Plugin',
            'license' => 'GPLv2 or later',
        ];
        $rawContent = "=== Test Plugin ===\nLicense: GPLv2 or later\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(0, $issues);
    }

    public function testLicenseMismatch(): void
    {
        // Create a temporary plugin file
        $pluginFile = tempnam(sys_get_temp_dir(), 'plugin');
        file_put_contents($pluginFile, "<?php\n/*\n * Plugin Name: Test Plugin\n * License: MIT\n */");

        $rule = new LicenseRule($pluginFile);
        $parsedData = [
            'name' => 'Test Plugin',
            'license' => 'GPLv2 or later',
        ];
        $rawContent = "=== Test Plugin ===\nLicense: GPLv2 or later\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('License mismatch', $issues[0]->getMessage());

        unlink($pluginFile);
    }
}
