<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WPOrg\Plugin\ReadmeLinter\Config\Configuration;
use WPOrg\Plugin\ReadmeLinter\Issue;
use WPOrg\Plugin\ReadmeLinter\Linter;
use WPOrg\Plugin\ReadmeLinter\Reporter\AnnotationsReporter;
use WPOrg\Plugin\ReadmeLinter\Reporter\JsonReporter;
use WPOrg\Plugin\ReadmeLinter\Reporter\SarifReporter;
use WPOrg\Plugin\ReadmeLinter\Reporter\TextReporter;
use WPOrg\Plugin\ReadmeLinter\Rule\DonateLinkRule;
use WPOrg\Plugin\ReadmeLinter\Rule\EmptySectionsRule;
use WPOrg\Plugin\ReadmeLinter\Rule\FileSizeRule;
use WPOrg\Plugin\ReadmeLinter\Rule\HeadingLevelsRule;
use WPOrg\Plugin\ReadmeLinter\Rule\PluginNameRule;
use WPOrg\Plugin\ReadmeLinter\Rule\RequiredFieldsRule;
use WPOrg\Plugin\ReadmeLinter\Rule\RequiredSectionsRule;
use WPOrg\Plugin\ReadmeLinter\Rule\RequiresPHPRule;
use WPOrg\Plugin\ReadmeLinter\Rule\ShortDescriptionRule;
use WPOrg\Plugin\ReadmeLinter\Rule\StableTagRule;

class LintCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('lint')
            ->setDescription('Lint a WordPress.org plugin readme file')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to readme.txt file', 'readme.txt')
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format (text, annotations, sarif, json)',
                null
            )
            ->addOption(
                'fail-on',
                null,
                InputOption::VALUE_REQUIRED,
                'Fail on level (error, warning, info)',
                'error'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                'Path to configuration file',
                '.wporg-readme-lint.json'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Output file for SARIF/JSON (default: stdout)'
            )
            ->addOption('quiet', 'q', InputOption::VALUE_NONE, 'Quiet mode - only show summary');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFile = $input->getOption('config');
        $config = null;

        // Load configuration if file exists
        if (is_string($configFile) && file_exists($configFile)) {
            try {
                $config = Configuration::fromFile($configFile);
            } catch (\RuntimeException $e) {
                $output->writeln("<error>Error loading configuration: {$e->getMessage()}</error>");
                return Command::FAILURE;
            }
        } else {
            $config = new Configuration();
        }

        // Override config with CLI arguments
        $readmePath = $input->getArgument('path');
        if (is_string($readmePath)) {
            $config->setReadmePath($readmePath);
        }

        $failOn = $input->getOption('fail-on');
        if (is_string($failOn)) {
            $config->setFailOn($failOn);
        }

        // Check if readme file exists
        if (!file_exists($config->getReadmePath())) {
            $output->writeln("<error>Readme file not found: {$config->getReadmePath()}</error>");
            return 2; // Usage error
        }

        // Read readme content
        $content = file_get_contents($config->getReadmePath());
        if ($content === false) {
            $output->writeln("<error>Unable to read file: {$config->getReadmePath()}</error>");
            return 2;
        }

        // Set up linter with rules
        $linter = new Linter();
        $linter->addRule(new PluginNameRule());
        $linter->addRule(new RequiredFieldsRule());
        $linter->addRule(new ShortDescriptionRule());
        $linter->addRule(new StableTagRule($config->isAllowTrunk()));
        $linter->addRule(new RequiresPHPRule());
        $linter->addRule(new RequiredSectionsRule($config->getRequiredSections()));
        $linter->addRule(new EmptySectionsRule());
        $linter->addRule(new HeadingLevelsRule());
        $linter->addRule(new FileSizeRule());
        $linter->addRule(new DonateLinkRule());

        // Run linter
        $issues = $linter->lint($content, $config->getReadmePath());

        // Filter out ignored rules
        $issues = array_filter($issues, fn($issue) => !$config->shouldIgnoreRule($issue->getRuleId()));

        // Generate output
        $format = $input->getOption('format');

        // Auto-detect format if not specified
        if ($format === null) {
            // Use annotations format in CI (GitHub Actions), text format otherwise
            $format = getenv('GITHUB_ACTIONS') === 'true' ? 'annotations' : 'text';
        }

        $reporter = match ($format) {
            'sarif' => new SarifReporter(),
            'json' => new JsonReporter(),
            'annotations' => new AnnotationsReporter(),
            'text' => new TextReporter(),
            default => new TextReporter(),
        };

        $reportOutput = $reporter->generate($issues);

        // Write output
        $outputFile = $input->getOption('output');
        if (is_string($outputFile)) {
            file_put_contents($outputFile, $reportOutput);
            if (!$input->getOption('quiet')) {
                $output->writeln("Output written to: {$outputFile}");
            }
        } else {
            $output->write($reportOutput);
            if (!empty($reportOutput)) {
                $output->writeln(''); // Add newline after output
            }
        }

        // Print summary
        if (!$input->getOption('quiet')) {
            $this->printSummary($output, $issues);
        }

        // Determine exit code
        return $this->determineExitCode($issues, $config->getFailOn());
    }

    /**
     * @param Issue[] $issues
     */
    private function printSummary(OutputInterface $output, array $issues): void
    {
        $errors = count(array_filter($issues, fn($i) => $i->getLevel() === Issue::LEVEL_ERROR));
        $warnings = count(array_filter($issues, fn($i) => $i->getLevel() === Issue::LEVEL_WARNING));
        $info = count(array_filter($issues, fn($i) => $i->getLevel() === Issue::LEVEL_INFO));

        $output->writeln('');
        $output->writeln('Summary:');
        $output->writeln("  Errors: {$errors}");
        $output->writeln("  Warnings: {$warnings}");
        $output->writeln("  Info: {$info}");
        $output->writeln("  Total: " . count($issues));
    }

    /**
     * @param Issue[] $issues
     */
    private function determineExitCode(array $issues, string $failOn): int
    {
        if (empty($issues)) {
            return Command::SUCCESS;
        }

        foreach ($issues as $issue) {
            $shouldFail = match ($failOn) {
                'error' => $issue->getLevel() === Issue::LEVEL_ERROR,
                'warning' => in_array($issue->getLevel(), [Issue::LEVEL_ERROR, Issue::LEVEL_WARNING], true),
                'info' => true,
                default => false,
            };

            if ($shouldFail) {
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
