<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Tests\Integration;

use PHPUnit\Framework\TestCase;
use WPOrg\Plugin\ReadmeLinter\Issue;
use WPOrg\Plugin\ReadmeLinter\Linter;
use WPOrg\Plugin\ReadmeLinter\Rule\PluginNameRule;
use WPOrg\Plugin\ReadmeLinter\Rule\RequiredFieldsRule;
use WPOrg\Plugin\ReadmeLinter\Rule\RequiredSectionsRule;
use WPOrg\Plugin\ReadmeLinter\Rule\ShortDescriptionRule;
use WPOrg\Plugin\ReadmeLinter\Rule\StableTagRule;

class LinterTest extends TestCase
{
    private Linter $linter;

    protected function setUp(): void
    {
        $this->linter = new Linter();
        $this->linter->addRule(new PluginNameRule());
        $this->linter->addRule(new RequiredFieldsRule());
        $this->linter->addRule(new ShortDescriptionRule());
        $this->linter->addRule(new StableTagRule());
        $this->linter->addRule(new RequiredSectionsRule());
    }

    public function testValidReadme(): void
    {
        $content = file_get_contents(__DIR__ . '/../fixtures/valid-readme.txt');
        $this->assertNotFalse($content);

        $issues = $this->linter->lint($content);

        // Should have minimal issues (maybe just info level)
        $errors = array_filter($issues, fn($i) => $i->getLevel() === Issue::LEVEL_ERROR);
        $this->assertEmpty($errors, 'Valid readme should not have errors');
    }

    public function testInvalidReadme(): void
    {
        $content = file_get_contents(__DIR__ . '/../fixtures/invalid-readme.txt');
        $this->assertNotFalse($content);

        $issues = $this->linter->lint($content);

        // Should have multiple errors
        $errors = array_filter($issues, fn($i) => $i->getLevel() === Issue::LEVEL_ERROR);
        $this->assertNotEmpty($errors, 'Invalid readme should have errors');
    }

    public function testIssuesSortedByLine(): void
    {
        $content = file_get_contents(__DIR__ . '/../fixtures/invalid-readme.txt');
        $this->assertNotFalse($content);

        $issues = $this->linter->lint($content);

        // Verify issues are sorted by line number
        $previousLine = 0;
        foreach ($issues as $issue) {
            $line = $issue->getLine() ?? PHP_INT_MAX;
            $this->assertGreaterThanOrEqual($previousLine, $line);
            $previousLine = $line;
        }
    }

    public function testIssuesHaveFileSet(): void
    {
        $content = "=== Test ===";
        $filename = 'test-readme.txt';

        $issues = $this->linter->lint($content, $filename);

        foreach ($issues as $issue) {
            $this->assertSame($filename, $issue->getFile());
        }
    }
}
