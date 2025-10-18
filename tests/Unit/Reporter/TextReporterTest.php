<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Tests\Unit\Reporter;

use PHPUnit\Framework\TestCase;
use WPOrg\Plugin\ReadmeLinter\Issue;
use WPOrg\Plugin\ReadmeLinter\Reporter\TextReporter;

class TextReporterTest extends TestCase
{
    private TextReporter $reporter;

    protected function setUp(): void
    {
        $this->reporter = new TextReporter();
    }

    public function testEmptyIssues(): void
    {
        $output = $this->reporter->generate([]);
        $this->assertSame('', $output);
    }

    public function testSingleError(): void
    {
        $issue = new Issue(
            'test-rule',
            Issue::LEVEL_ERROR,
            'Test error message',
            10,
            null,
            'readme.txt'
        );

        $output = $this->reporter->generate([$issue]);

        $this->assertStringContainsString('Errors:', $output);
        $this->assertStringContainsString('✗', $output);
        $this->assertStringContainsString('Line 10:', $output);
        $this->assertStringContainsString('[test-rule]', $output);
        $this->assertStringContainsString('Test error message', $output);
    }

    public function testSingleWarning(): void
    {
        $issue = new Issue(
            'test-rule',
            Issue::LEVEL_WARNING,
            'Test warning message',
            20,
            null,
            'readme.txt'
        );

        $output = $this->reporter->generate([$issue]);

        $this->assertStringContainsString('Warnings:', $output);
        $this->assertStringContainsString('⚠', $output);
        $this->assertStringContainsString('Line 20:', $output);
        $this->assertStringContainsString('[test-rule]', $output);
        $this->assertStringContainsString('Test warning message', $output);
    }

    public function testSingleInfo(): void
    {
        $issue = new Issue(
            'test-rule',
            Issue::LEVEL_INFO,
            'Test info message',
            30,
            null,
            'readme.txt'
        );

        $output = $this->reporter->generate([$issue]);

        $this->assertStringContainsString('Info:', $output);
        $this->assertStringContainsString('ℹ', $output);
        $this->assertStringContainsString('Line 30:', $output);
        $this->assertStringContainsString('[test-rule]', $output);
        $this->assertStringContainsString('Test info message', $output);
    }

    public function testMultipleIssuesGroupedByLevel(): void
    {
        $issues = [
            new Issue('rule1', Issue::LEVEL_INFO, 'Info message', 10, null, 'readme.txt'),
            new Issue('rule2', Issue::LEVEL_ERROR, 'Error message', 5, null, 'readme.txt'),
            new Issue('rule3', Issue::LEVEL_WARNING, 'Warning message', 15, null, 'readme.txt'),
        ];

        $output = $this->reporter->generate($issues);

        // Should have all three sections
        $this->assertStringContainsString('Errors:', $output);
        $this->assertStringContainsString('Warnings:', $output);
        $this->assertStringContainsString('Info:', $output);

        // Check that errors come before warnings and warnings before info
        $errorPos = strpos($output, 'Errors:');
        $warningPos = strpos($output, 'Warnings:');
        $infoPos = strpos($output, 'Info:');

        $this->assertLessThan($warningPos, $errorPos);
        $this->assertLessThan($infoPos, $warningPos);
    }

    public function testIssueWithoutLineNumber(): void
    {
        $issue = new Issue(
            'test-rule',
            Issue::LEVEL_INFO,
            'Test message',
            null,
            null,
            'readme.txt'
        );

        $output = $this->reporter->generate([$issue]);

        $this->assertStringContainsString('[test-rule]', $output);
        $this->assertStringContainsString('Test message', $output);
        $this->assertStringNotContainsString('Line', $output);
    }
}
