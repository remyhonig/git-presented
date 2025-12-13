<?php

declare(strict_types=1);

namespace App\Git\Model;

/**
 * Represents a single line in a diff hunk.
 */
class DiffLine
{
    public const TYPE_CONTEXT = 'context';
    public const TYPE_ADD = 'add';
    public const TYPE_REMOVE = 'remove';
    public const TYPE_NO_NEWLINE = 'no_newline';

    public function __construct(
        public readonly string $type,
        public readonly string $content,
        public readonly ?int $oldLineNumber = null,
        public readonly ?int $newLineNumber = null,
    ) {}

    public function isContext(): bool
    {
        return $this->type === self::TYPE_CONTEXT;
    }

    public function isAdd(): bool
    {
        return $this->type === self::TYPE_ADD;
    }

    public function isRemove(): bool
    {
        return $this->type === self::TYPE_REMOVE;
    }

    public function isNoNewline(): bool
    {
        return $this->type === self::TYPE_NO_NEWLINE;
    }

    public function getPrefix(): string
    {
        return match ($this->type) {
            self::TYPE_ADD => '+',
            self::TYPE_REMOVE => '-',
            self::TYPE_CONTEXT => ' ',
            self::TYPE_NO_NEWLINE => '\\',
        };
    }

    public function getCssClass(): string
    {
        return match ($this->type) {
            self::TYPE_ADD => 'diff-line-add',
            self::TYPE_REMOVE => 'diff-line-remove',
            self::TYPE_CONTEXT => 'diff-line-context',
            self::TYPE_NO_NEWLINE => 'diff-line-no-newline',
        };
    }
}
