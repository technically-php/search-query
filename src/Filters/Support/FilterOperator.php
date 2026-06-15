<?php

namespace Technically\SearchQuery\Filters\Support;

use InvalidArgumentException;

enum FilterOperator: string
{
    case COLON         = ':';
    case GREATER       = '>';
    case GREATER_EQUAL = '>=';
    case LESS          = '<';
    case LESS_EQUAL    = '<=';

    public static function cast(self | string $operator): self
    {
        if ($operator instanceof self) {
            return $operator;
        }

        if ($instance = self::tryFrom($operator)) {
            return $instance;
        }

        throw new InvalidArgumentException("Unsupported operator given: `{$operator}`.");
    }

    public function toString(): string
    {
        return $this->value;
    }
}