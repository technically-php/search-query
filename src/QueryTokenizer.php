<?php
declare(strict_types=1);

namespace Technically\SearchQuery;

use Technically\SearchQuery\Tokens\Literal;
use Technically\SearchQuery\Tokens\Operator;
use Technically\SearchQuery\Tokens\QuotedString;
use Technically\SearchQuery\Tokens\Token;
use Technically\SearchQuery\Tokens\Whitespace;

final class QueryTokenizer
{
    private const WHITESPACE    = ' ';
    private const QUOTE         = '"';
    private const ESCAPE        = '\\';
    private const COLON         = ':';
    private const MINUS         = '-';
    private const GREATER       = '>';
    private const LESS          = '<';
    private const EQUAL         = '=';

    private readonly string $sequence;
    private int             $len;
    private int             $position;
    private string          $currentChar;

    public function __construct(string $sequence)
    {
        $this->sequence = $sequence;
    }

    /**
     * @return Token[]
     */
    public function tokenize(): iterable
    {
        $this->len         = mb_strlen($this->sequence);
        $this->position    = 0;
        $this->currentChar = mb_substr($this->sequence, $this->position, 1);

        while ( ! $this->eof()) {
            if ($this->currentChar === self::WHITESPACE) {
                $whitespace = $this->consumeWhile(self::WHITESPACE);
                yield new Whitespace($whitespace);

                continue;
            }

            if ($this->consumeChar(self::MINUS)) {
                yield new Operator(Operator::MINUS);

                continue;
            }

            if ($this->consumeChar(self::COLON)) {
                yield new Operator(Operator::COLON);

                continue;
            }

            if ($this->consumeChar(self::LESS)) {
                if ($this->consumeChar(self::EQUAL)) {
                    yield new Operator(Operator::LESS_EQUAL);
                } else {
                    yield new Operator(Operator::LESS);
                }

                continue;
            }

            if ($this->consumeChar(self::GREATER)) {
                if ($this->consumeChar(self::EQUAL)) {
                    yield new Operator(Operator::GREATER_EQUAL);
                } else {
                    yield new Operator(Operator::GREATER);
                }

                continue;
            }

            if ($this->consumeChar(self::QUOTE)) {
                $quoted = $this->consumeUntil(self::QUOTE, allowEscaping: true);

                yield new QuotedString($quoted);

                $this->consumeChar(self::QUOTE);

                continue;
            }

            $literal = $this->consumeUntil(
                [self::WHITESPACE, self::COLON, self::GREATER, self::LESS],
                allowEscaping: true,
            );

            yield new Literal($literal);
        }
    }

    private function eof(): bool
    {
        return $this->position >= $this->len;
    }

    private function consumeChar(string $char): ?string
    {
        if ($this->currentChar === $char) {
            $this->advance();
            return $char;
        }

        return null;
    }

    public function consumeWhile(array | string $char, bool $allowEscaping = false): string
    {
        $consumed = '';

        while ( ! $this->eof()) {
            if ($allowEscaping && $this->currentChar === self::ESCAPE) {
                $this->advance();
                $consumed .= $this->currentChar;
                $this->advance();

                continue;
            }

            if (is_string($char) && $this->currentChar === $char
                || is_array($char) && in_array($this->currentChar, $char, strict: true)
            ) {
                $consumed .= $this->currentChar;
                $this->advance();

                continue;
            }

            break;
        }

        return $consumed;
    }

    public function consumeUntil(array | string $char, bool $allowEscaping = false): string
    {
        $consumed = '';

        while ( ! $this->eof()) {
            if ($allowEscaping && $this->currentChar === self::ESCAPE) {
                $this->advance();
                $consumed .= $this->currentChar;
                $this->advance();

                continue;
            }

            if (is_string($char) && $this->currentChar !== $char
                || is_array($char) && ! in_array($this->currentChar, $char, strict: true)
            ) {
                $consumed .= $this->currentChar;
                $this->advance();

                continue;
            }

            break;
        }

        return $consumed;
    }

    private function advance(): void
    {
        $this->position++;
        $this->currentChar = mb_substr($this->sequence, $this->position, 1);
    }
}