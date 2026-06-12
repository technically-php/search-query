<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Tokens;

use InvalidArgumentException;
use LogicException;

final readonly class Literal implements Token
{
    public function __construct(
        public string $value,
    ) {
        if (empty($value)) {
            throw new InvalidArgumentException('Literal cannot be empty.');
        }
    }

    public function isEmpty(): bool
    {
        return false; // it can never be empty
    }

    public function toString(): string
    {
        return addcslashes($this->value, ' ');
    }

    public function append(Literal | QuotedString | Operator | Whitespace $token): Literal
    {
        if ($token instanceof Literal || $token instanceof Whitespace) {
            return new self($this->value . $token->value);
        }

        if ($token instanceof Operator) {
            return new self($this->value . $token->operator);
        }

        if ($token instanceof QuotedString) {
            return new self($this->value . $token->toString());
        }

        throw new LogicException('This should never happen.');
    }
}