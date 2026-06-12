<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Tokens;

use InvalidArgumentException;

final readonly class Operator implements Token
{
    public const string COLON         = ':';
    public const string MINUS         = '-';
    public const string GREATER       = '>';
    public const string GREATER_EQUAL = '>=';
    public const string LESS          = '<';
    public const string LESS_EQUAL    = '<=';

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