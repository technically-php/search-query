<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Filters;

use InvalidArgumentException;
use Technically\SearchQuery\Filters\Support\FilterOperator;
use Technically\SearchQuery\Tokens\Literal as LiteralToken;
use Technically\SearchQuery\Tokens\Operator as OperatorToken;
use Technically\SearchQuery\Tokens\QuotedString as QuotedStringToken;

final readonly class FieldFilter implements Filter
{
    public string         $field;
    public FilterOperator $operator;
    public string         $value;
    public bool           $quoted;
    public bool           $exclude;

    public function __construct(
        string                  $field,
        FilterOperator | string $operator,
        string                  $value,
        bool                    $quoted = false,
        bool                    $exclude = false,
    ) {
        if (empty($field)) {
            throw new InvalidArgumentException('Field name cannot be empty.');
        }

        $this->field    = $field;
        $this->operator = FilterOperator::cast($operator);
        $this->value    = $value;
        $this->quoted   = $quoted;
        $this->exclude  = $exclude;
    }

    public function toString(): string
    {
        $field = new LiteralToken($this->field);

        $value = $this->quoted || empty($this->value)
            ? new QuotedStringToken($this->value)
            : new LiteralToken($this->value);

        return ($this->exclude ? OperatorToken::MINUS : '')
               . $field->toString()
               . $this->operator->toString()
               . $value->toString();
    }
}