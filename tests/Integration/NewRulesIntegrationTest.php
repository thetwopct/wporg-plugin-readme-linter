<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Tests\Integration;

use PHPUnit\Framework\TestCase;
use WPOrg\Plugin\ReadmeLinter\Issue;
use WPOrg\Plugin\ReadmeLinter\Linter;
use WPOrg\Plugin\ReadmeLinter\Rule\ContributorsRule;
use WPOrg\Plugin\ReadmeLinter\Rule\DefaultTextRule;
use WPOrg\Plugin\ReadmeLinter\Rule\LicenseRule;
use WPOrg\Plugin\ReadmeLinter\Rule\TestedUpToRule;
use WPOrg\Plugin\ReadmeLinter\Rule\TrademarkRule;
use WPOrg\Plugin\ReadmeLinter\Rule\UpgradeNoticeRule;

class NewRulesIntegrationTest extends TestCase
{
    public function testAllNewRulesIntegration(): void
    {
        $linter = new Linter();
        $linter->addRule(new LicenseRule());
        $linter->addRule(new ContributorsRule());
        $linter->addRule(new TestedUpToRule('6.4.0'));
        $linter->addRule(new DefaultTextRule());
        $linter->addRule(new UpgradeNoticeRule());
        $linter->addRule(new TrademarkRule());

        $readmeContent = file_get_contents(__DIR__ . '/../fixtures/default-text-readme.txt');
        $this->assertNotFalse($readmeContent);

        $issues = $linter->lint($readmeContent, 'readme.txt');

        // Should detect multiple issues
        $this->assertNotEmpty($issues);

        // Check for default text issues
        $defaultTextIssues = array_filter($issues, fn($issue) => str_contains($issue->getMessage(), 'Default'));
        $this->assertNotEmpty($defaultTextIssues);

        // Check for contributor issues (yourname, username1)
        $contributorIssues = array_filter($issues, fn($issue) => str_contains($issue->getMessage(), 'contributor'));
        $this->assertNotEmpty($contributorIssues);
    }

    public function testLicenseRuleIntegration(): void
    {
        $linter = new Linter();
        $linter->addRule(new LicenseRule());

        $readmeContent = file_get_contents(__DIR__ . '/../fixtures/license-issues-readme.txt');
        $this->assertNotFalse($readmeContent);

        $issues = $linter->lint($readmeContent, 'readme.txt');

        $licenseIssues = array_filter($issues, fn($issue) => str_contains($issue->getMessage(), 'license'));
        $this->assertNotEmpty($licenseIssues);

        $errorIssues = array_filter($issues, fn($issue) => $issue->getLevel() === Issue::LEVEL_ERROR);
        $this->assertNotEmpty($errorIssues);
    }

    public function testContributorsRuleIntegration(): void
    {
        $linter = new Linter();
        $linter->addRule(new ContributorsRule());

        $readmeContent = file_get_contents(__DIR__ . '/../fixtures/contributors-issues-readme.txt');
        $this->assertNotFalse($readmeContent);

        $issues = $linter->lint($readmeContent, 'readme.txt');

        // Should detect restricted contributors (wordpress, admin)
        $restrictedIssues = array_filter($issues, fn($issue) =>
            str_contains($issue->getMessage(), 'Restricted contributor') &&
            $issue->getLevel() === Issue::LEVEL_ERROR);
        $this->assertNotEmpty($restrictedIssues);
    }

    public function testTrademarkRuleIntegration(): void
    {
        $linter = new Linter();
        $linter->addRule(new TrademarkRule());

        $readmeContent = file_get_contents(__DIR__ . '/../fixtures/trademark-issues-readme.txt');
        $this->assertNotFalse($readmeContent);

        $issues = $linter->lint($readmeContent, 'readme.txt');

        // Should detect WordPress trademark issues
        $trademarkIssues = array_filter($issues, fn($issue) => str_contains($issue->getMessage(), 'trademark'));
        $this->assertNotEmpty($trademarkIssues);
    }

    public function testValidReadmeWithNewRules(): void
    {
        $linter = new Linter();
        $linter->addRule(new LicenseRule());
        $linter->addRule(new ContributorsRule());
        $linter->addRule(new TestedUpToRule('6.4.0'));
        $linter->addRule(new DefaultTextRule());
        $linter->addRule(new UpgradeNoticeRule());
        $linter->addRule(new TrademarkRule());

        $readmeContent = file_get_contents(__DIR__ . '/../fixtures/valid-readme.txt');
        $this->assertNotFalse($readmeContent);

        $issues = $linter->lint($readmeContent, 'readme.txt');

        // Valid readme should have minimal issues with new rules
        $errorIssues = array_filter($issues, fn($issue) => $issue->getLevel() === Issue::LEVEL_ERROR);
        $this->assertEmpty($errorIssues, 'Valid readme should not have errors with new rules');
    }
}
