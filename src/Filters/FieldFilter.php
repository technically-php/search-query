<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Filters;

use InvalidArgumentException;
use Technically\SearchQuery\Tokens\Literal;
use Technically\SearchQuery\Tokens\Operator;
use Technically\SearchQuery\Tokens\QuotedString;

final class FieldFilter implements Filter
{
    private readonly Literal | QuotedString $fieldToken;

    private readonly Operator $operatorToken;

    private readonly Literal | QuotedString $valueToken;

    public readonly bool $exclude;

    public string $field {
        get => $this->fieldToken->value;
    }

    public string $value {
        get => $this->valueToken->value;
    }

    public bool $quoted {
        get => $this->value instanceof QuotedString;
    }

    public string $operator {
        get => $this->operatorToken->operator;
    }

    public function __construct(
        Literal | QuotedString | string $field,
        Operator | string               $operator,
        Literal | QuotedString | string $value,
        bool                            $exclude = false,
    ) {
        $this->fieldToken    = is_string($field) ? new Literal($field) : $field;
        $this->operatorToken = is_string($operator) ? new Operator($operator) : $operator;
        $this->valueToken    = is_string($value) ? new Literal($value) : $value;
        $this->exclude       = $exclude;

        if ($this->fieldToken->isEmpty()) {
            throw new InvalidArgumentException('Field name cannot be empty.');
        }
    }

    public function toString(): string
    {
        return ($this->exclude ? Operator::MINUS : '')
               . $this->fieldToken->toString()
               . $this->operatorToken->toString()
               . $this->valueToken->toString();
    }
}