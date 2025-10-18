<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter;

use WPOrg\Plugin\ReadmeLinter\Parser\ReadmeParser;
use WPOrg\Plugin\ReadmeLinter\Rule\RuleInterface;

class Linter
{
    private ReadmeParser $parser;
    /** @var RuleInterface[] */
    private array $rules = [];

    public function __construct(?ReadmeParser $parser = null)
    {
        $this->parser = $parser ?? new ReadmeParser();
    }

    public function addRule(RuleInterface $rule): void
    {
        $this->rules[] = $rule;
    }

    /**
     * @param RuleInterface[] $rules
     */
    public function setRules(array $rules): void
    {
        $this->rules = $rules;
    }

    /**
     * Lint a readme file and return issues found.
     *
     * @return Issue[]
     */
    public function lint(string $content, string $file = 'readme.txt'): array
    {
        $parsedData = $this->parser->parse($content);
        $issues = [];

        foreach ($this->rules as $rule) {
            $ruleIssues = $rule->check($parsedData, $content);
            foreach ($ruleIssues as $issue) {
                if ($issue->getFile() === null) {
                    $issue->setFile($file);
                }
                $issues[] = $issue;
            }
        }

        // Sort issues by line number, then by rule ID
        usort($issues, function (Issue $a, Issue $b) {
            $lineA = $a->getLine() ?? PHP_INT_MAX;
            $lineB = $b->getLine() ?? PHP_INT_MAX;

            if ($lineA !== $lineB) {
                return $lineA <=> $lineB;
            }

            return $a->getRuleId() <=> $b->getRuleId();
        });

        return $issues;
    }
}
