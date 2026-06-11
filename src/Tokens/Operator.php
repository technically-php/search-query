<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Tokens;

use InvalidArgumentException;

final readonly class Operator implements Token
{
    public const COLON         = ':';
    public const MINUS         = '-';
    public const GREATER       = '>';
    public const GREATER_EQUAL = '>=';
    public const LESS          = '<';
    public const LESS_EQUAL    = '<=';

    public function __construct(
        public string $operator,
    ) {
        if ($operator !== self::COLON
            && $operator !== self::MINUS
            && $operator !== self::GREATER
            && $operator !== self::GREATER_EQUAL
            && $operator !== self::LESS
            && $operator !== self::LESS_EQUAL
        ) {
            throw new InvalidArgumentException("Unknown operator given: `{$operator}`.");
        }
    }

    public function toString(): string
    {
        return $this->operator;
    }
}