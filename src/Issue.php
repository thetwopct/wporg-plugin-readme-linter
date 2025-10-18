<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter;

class Issue
{
    public const LEVEL_ERROR = 'error';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_INFO = 'info';

    public function __construct(
        private string $ruleId,
        private string $level,
        private string $message,
        private ?int $line = null,
        private ?int $column = null,
        private ?string $file = null
    ) {
    }

    public function getRuleId(): string
    {
        return $this->ruleId;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function getColumn(): ?int
    {
        return $this->column;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(string $file): void
    {
        $this->file = $file;
    }
}
