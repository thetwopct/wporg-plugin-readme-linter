<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Reporter;

use WPOrg\Plugin\ReadmeLinter\Issue;

class SarifReporter implements ReporterInterface
{
    /**
     * @param Issue[] $issues
     */
    public function generate(array $issues): string
    {
        $results = [];
        $rules = [];
        $rulesSeen = [];

        foreach ($issues as $issue) {
            $ruleId = $issue->getRuleId();

            // Add rule definition if not seen before
            if (!isset($rulesSeen[$ruleId])) {
                $rules[] = [
                    'id' => $ruleId,
                    'name' => $ruleId,
                    'shortDescription' => [
                        'text' => $issue->getMessage(),
                    ],
                    'fullDescription' => [
                        'text' => $issue->getMessage(),
                    ],
                    'defaultConfiguration' => [
                        'level' => $this->mapLevel($issue->getLevel()),
                    ],
                ];
                $rulesSeen[$ruleId] = true;
            }

            $result = [
                'ruleId' => $ruleId,
                'level' => $this->mapLevel($issue->getLevel()),
                'message' => [
                    'text' => $issue->getMessage(),
                ],
            ];

            if ($issue->getFile() !== null) {
                $result['locations'] = [
                    [
                        'physicalLocation' => [
                            'artifactLocation' => [
                                'uri' => $issue->getFile(),
                            ],
                            'region' => [
                                'startLine' => $issue->getLine() ?? 1,
                            ],
                        ],
                    ],
                ];

                if ($issue->getColumn() !== null) {
                    $result['locations'][0]['physicalLocation']['region']['startColumn'] = $issue->getColumn();
                }
            }

            $results[] = $result;
        }

        $sarif = [
            'version' => '2.1.0',
            '$schema' => 'https://json.schemastore.org/sarif-2.1.0.json',
            'runs' => [
                [
                    'tool' => [
                        'driver' => [
                            'name' => 'wporg-plugin-readme-linter',
                            'informationUri' => 'https://github.com/thetwopct/wporg-plugin-readme-linter',
                            'version' => '1.0.0',
                            'rules' => $rules,
                        ],
                    ],
                    'results' => $results,
                ],
            ],
        ];

        $result = json_encode($sarif, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return $result !== false ? $result : '{}';
    }

    private function mapLevel(string $level): string
    {
        return match ($level) {
            Issue::LEVEL_ERROR => 'error',
            Issue::LEVEL_WARNING => 'warning',
            default => 'note',
        };
    }
}
