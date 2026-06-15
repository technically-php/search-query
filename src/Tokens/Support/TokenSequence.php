<?php

namespace Technically\SearchQuery\Tokens\Support;

use LogicException;
use Technically\SearchQuery\Tokens\Literal;
use Technically\SearchQuery\Tokens\QuotedString;
use Technically\SearchQuery\Tokens\Token;

/**
 * @internal This class is private.
 *           Do not use this class outside of the package.
 *           It is not intended for public use. The interface
 *           may change without updating the version number.
 */
final class TokenSequence
{
    /**
     * @var Token[]
     */
    private array $tokens;

    public function __construct(Token ...$tokens)
    {
        $this->tokens = $tokens;
    }

    public function isEmpty(): bool
    {
        return count($this->tokens) === 0;
    }

    public function append(Token  $tail): void
    {
        $this->tokens[] = $tail;
    }

    public function toString(): string
    {
        if (count($this->tokens) === 0) {
            return '';
        }

        if (count($this->tokens) === 1) {
            $head = $this->head();

            if ($head instanceof QuotedString || $head instanceof Literal) {
                return $head->value;
            }

            return $head->toString();
        }

        $parts = array_map(
            fn (Token $token) => $token instanceof Literal ? $token->value : $token->toString(),
            $this->tokens,
        );

        return implode('', $parts);
    }

    public function isQuoted(): bool
    {
        return count($this->tokens) === 1
               && $this->head() instanceof QuotedString;
    }

    public function head(): Token
    {
        if (count($this->tokens) === 0) {
            throw new LogicException('Cannot get head of empty sequence');
        }

        return $this->tokens[array_key_first($this->tokens)];
    }
}