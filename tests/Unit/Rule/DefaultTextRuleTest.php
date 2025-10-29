<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Tests\Unit\Rule;

use PHPUnit\Framework\TestCase;
use WPOrg\Plugin\ReadmeLinter\Issue;
use WPOrg\Plugin\ReadmeLinter\Rule\DefaultTextRule;

class DefaultTextRuleTest extends TestCase
{
    public function testDefaultPluginName(): void
    {
        $rule = new DefaultTextRule();
        $parsedData = [
            'name' => 'Plugin Name',
        ];
        $rawContent = "=== Plugin Name ===\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertNotEmpty($issues);
        $hasPluginNameIssue = false;
        foreach ($issues as $issue) {
            if (str_contains($issue->getMessage(), 'Plugin Name')) {
                $hasPluginNameIssue = true;
                break;
            }
        }
        $this->assertTrue($hasPluginNameIssue, 'Should detect default plugin name');
    }

    public function testDefaultContributors(): void
    {
        $rule = new DefaultTextRule();
        $parsedData = [
            'contributors' => 'yourname, username1',
        ];
        $rawContent = "Contributors: yourname, username1\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertNotEmpty($issues);
        $hasContributorIssue = false;
        foreach ($issues as $issue) {
            if (str_contains($issue->getMessage(), 'contributors') || str_contains($issue->getMessage(), 'yourname')) {
                $hasContributorIssue = true;
                break;
            }
        }
        $this->assertTrue($hasContributorIssue, 'Should detect default contributors');
    }

    public function testDefaultShortDescription(): void
    {
        $rule = new DefaultTextRule();
        $parsedData = [];
        $rawContent = "=== Test Plugin ===\nHere is a short description of the plugin\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(1, $issues);
        $this->assertEquals(Issue::LEVEL_ERROR, $issues[0]->getLevel());
        $this->assertStringContainsString('Default readme template text detected', $issues[0]->getMessage());
    }

    public function testDefaultSectionContent(): void
    {
        $rule = new DefaultTextRule();
        $parsedData = [
            'sections' => [
                'Description' => 'This is the long description. No limit, and you can use Markdown.',
            ],
        ];
        $rawContent = "== Description ==\nThis is the long description. No limit, and you can use Markdown.\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertNotEmpty($issues);
        $hasSectionIssue = false;
        foreach ($issues as $issue) {
            if (
                str_contains($issue->getMessage(), 'Description') ||
                str_contains($issue->getMessage(), 'long description')
            ) {
                $hasSectionIssue = true;
                break;
            }
        }
        $this->assertTrue($hasSectionIssue, 'Should detect default section content');
    }

    public function testValidCustomContent(): void
    {
        $rule = new DefaultTextRule();
        $parsedData = [
            'name' => 'My Awesome Plugin',
            'contributors' => 'johndoe, janedoe',
            'sections' => [
                'Description' => 'This plugin does amazing things for your WordPress site.',
            ],
        ];
        $rawContent = "=== My Awesome Plugin ===\nContributors: johndoe, janedoe\n\n" .
            "Custom short description.\n\n== Description ==\n" .
            "This plugin does amazing things for your WordPress site.\n";

        $issues = $rule->check($parsedData, $rawContent);

        $this->assertCount(0, $issues);
    }

    public function testInitialReleaseInChangelogIsAllowed(): void
    {
        $rule = new DefaultTextRule();
        $parsedData = [
            'name' => 'My Plugin',
            'sections' => [
                'Changelog' => "= 1.0 (2025-11-01) =\n* Initial release",
            ],
        ];
        $rawContent = "=== My Plugin ===\n\n== Changelog ==\n\n= 1.0 (2025-11-01) =\n* Initial release\n";

        $issues = $rule->check($parsedData, $rawContent);

        // Should not flag "Initial release" as default text
        foreach ($issues as $issue) {
            $this->assertStringNotContainsString('Initial release', $issue->getMessage());
        }
    }

    public function testVersion001IsNotDefaultText(): void
    {
        $rule = new DefaultTextRule();
        $parsedData = [
            'name' => 'My Plugin',
        ];
        $rawContent = "=== My Plugin ===\nStable tag: 0.0.1\n\n== Changelog ==\n\n= 0.0.1 =\n* Initial release\n";

        $issues = $rule->check($parsedData, $rawContent);

        // Should not flag version 0.0.1 as default text
        foreach ($issues as $issue) {
            $this->assertStringNotContainsString('0.1', $issue->getMessage());
            $this->assertStringNotContainsString('0.0.1', $issue->getMessage());
        }
    }

    public function testVersion01IsNotDefaultText(): void
    {
        $rule = new DefaultTextRule();
        $parsedData = [
            'name' => 'My Plugin',
        ];
        $rawContent = "=== My Plugin ===\nStable tag: 0.1\n\n== Changelog ==\n\n= 0.1 =\n* Initial release\n";

        $issues = $rule->check($parsedData, $rawContent);

        // Should not flag version 0.1 as default text
        foreach ($issues as $issue) {
            $this->assertStringNotContainsString('0.1', $issue->getMessage());
        }
    }

    public function testVersion100IsNotDefaultText(): void
    {
        $rule = new DefaultTextRule();
        $parsedData = [
            'name' => 'My Plugin',
        ];
        $rawContent = "=== My Plugin ===\nStable tag: 1.0.0\n\n== Changelog ==\n\n= 1.0.0 =\n* Initial release\n";

        $issues = $rule->check($parsedData, $rawContent);

        // Should not flag version 1.0.0 as default text
        foreach ($issues as $issue) {
            $this->assertStringNotContainsString('1.0.0', $issue->getMessage());
        }
    }
}
