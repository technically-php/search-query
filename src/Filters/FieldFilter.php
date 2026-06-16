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

    /**
     * Get a new `FieldFilter` instance with the value unquoted.
     */
    public function unquote(): self
    {
        return new self($this->field, $this->operator, $this->value, false, $this->exclude);
    }

    /**
     * Check if the filter field properties match the given values.
     *
     * Only non-null arguments will be used for matching.
     * And `null` values are treated as wildcards ("allow anything").
     */
    public function matches(
        string | null $field = null,
        FilterOperator | string | null $operator = null,
        string | null $value = null,
        bool | null $quoted = null,
        bool | null $exclude = null
    ): bool {
        return ($field === null || $field === $this->field)
            && ($operator === null || FilterOperator::cast($operator) === $this->operator)
            && ($value === null || $value === $this->value)
            && ($quoted === null || $quoted === $this->quoted)
            && ($exclude === null || $exclude === $this->exclude);
    }

    /**
     * Serialize the filter back to the query syntax string.
     *
     * The returned presentation is normalized and might be different
     * from the original source string used to construct this filter.
     */
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