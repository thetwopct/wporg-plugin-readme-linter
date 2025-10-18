<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Config;

class Configuration
{
    private string $readmePath;
    private string $failOn;
    /** @var string[] */
    private array $ignoreRules;
    /** @var string[] */
    private array $requiredSections;
    private bool $allowTrunk;

    /**
     * @param string[] $ignoreRules
     * @param string[] $requiredSections
     */
    public function __construct(
        string $readmePath = 'readme.txt',
        string $failOn = 'error',
        array $ignoreRules = [],
        array $requiredSections = ['description', 'installation', 'changelog'],
        bool $allowTrunk = false
    ) {
        $this->readmePath = $readmePath;
        $this->failOn = $failOn;
        $this->ignoreRules = $ignoreRules;
        $this->requiredSections = $requiredSections;
        $this->allowTrunk = $allowTrunk;
    }

    /**
     * Load configuration from a JSON file.
     */
    public static function fromFile(string $path): self
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Configuration file not found: {$path}");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Unable to read configuration file: {$path}");
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            throw new \RuntimeException("Invalid JSON in configuration file: {$path}");
        }

        return new self(
            $data['readmePath'] ?? 'readme.txt',
            $data['failOn'] ?? 'error',
            $data['ignoreRules'] ?? [],
            $data['requiredSections'] ?? ['description', 'installation', 'changelog'],
            $data['allowTrunk'] ?? false
        );
    }

    public function getReadmePath(): string
    {
        return $this->readmePath;
    }

    public function setReadmePath(string $path): void
    {
        $this->readmePath = $path;
    }

    public function getFailOn(): string
    {
        return $this->failOn;
    }

    public function setFailOn(string $level): void
    {
        $this->failOn = $level;
    }

    /**
     * @return string[]
     */
    public function getIgnoreRules(): array
    {
        return $this->ignoreRules;
    }

    /**
     * @return string[]
     */
    public function getRequiredSections(): array
    {
        return $this->requiredSections;
    }

    public function isAllowTrunk(): bool
    {
        return $this->allowTrunk;
    }

    public function shouldIgnoreRule(string $ruleId): bool
    {
        return in_array($ruleId, $this->ignoreRules, true);
    }
}
