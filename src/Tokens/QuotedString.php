<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Tokens;

use LogicException;

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

    public function append(Token $token): Literal
    {
        if ($token instanceof Literal || $token instanceof Whitespace) {
            return new Literal($this->toString() . $token->value);
        }

        if ($token instanceof Operator) {
            return new Literal($this->toString() . $token->operator);
        }

        if ($token instanceof QuotedString) {
            return new Literal($this->toString() . $token->toString());
        }

        throw new LogicException('This should never happen.');
    }
}