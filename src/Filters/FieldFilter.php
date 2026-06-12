<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Filters;

use InvalidArgumentException;
use Technically\SearchQuery\Tokens\Literal;
use Technically\SearchQuery\Tokens\Operator;
use Technically\SearchQuery\Tokens\QuotedString;

final readonly class FieldFilter implements Filter
{
    public function __construct(
        public string $field,
        public string $operator,
        public string $value,
        public bool   $quoted = false,
        public bool   $exclude = false,
    ) {
        if (empty($this->field)) {
            throw new InvalidArgumentException('Field name cannot be empty.');
        }
        if (empty($this->operator)) {
            throw new InvalidArgumentException('Operator cannot be empty.');
        }
    }

    public function toString(): string
    {
        $field = new Literal($this->field);

        $value = $this->quoted || empty($this->value)
            ? new QuotedString($this->value)
            : new Literal($this->value);

        return ($this->exclude ? Operator::MINUS : '')
               . $field->toString()
               . $this->operator
               . $value->toString();
    }
}