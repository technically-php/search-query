<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Tokens;

final readonly class QuotedString implements Token
{
    public function __construct(
        public string $value,
    ) {
        // Nothing
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    public function toString(): string
    {
        return sprintf('"%s"', addcslashes($this->value, '"'));
    }
}