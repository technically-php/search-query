<?php

namespace Technically\SearchQuery\Tokenizer\Tokens;

use InvalidArgumentException;

final readonly class Operator implements Token
{
    public const COLON = ':';
    public const MINUS = '-';

    public function __construct(
        public string $operator,
    ) {
        if ($operator !== self::COLON && $operator !== self::MINUS) {
            throw new InvalidArgumentException("Unknown operator given: `{$operator}`.");
        }
    }

    public function toString(): string
    {
        return $this->operator;
    }
}