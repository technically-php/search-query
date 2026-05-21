<?php

namespace Technically\SearchQuery\Tokenizer;

use Technically\SearchQuery\Tokenizer\Tokens\Literal;
use Technically\SearchQuery\Tokenizer\Tokens\Operator;
use Technically\SearchQuery\Tokenizer\Tokens\Token;
use Technically\SearchQuery\Tokenizer\Tokens\Whitespace;

final class QueryTokenizer
{
    private const WHITESPACE = ' ';
    private const QUOTE      = '"';
    private const ESCAPE     = '\\';
    private const COLON      = ':';
    private const MINUS      = '-';

    private string $sequence;
    private int    $len;
    private int    $position;
    private string $currentChar;

    public function __construct(
        string $sequence,
    ) {
        $this->sequence    = $sequence;
        $this->len         = mb_strlen($sequence);
        $this->position    = 0;
        $this->currentChar = mb_substr($this->sequence, $this->position, 1);
    }

    /**
     * @return Token[]
     */
    public function tokenize(): iterable
    {
        while ( ! $this->eof()) {
            if ($this->currentChar === self::WHITESPACE) {
                $whitespace = $this->consumeWhile(self::WHITESPACE);
                yield new Whitespace($whitespace);

                continue;
            }

            if ($this->consume(self::COLON)) {
                yield new Operator(Operator::COLON);

                continue;
            }

            if ($this->consume(self::MINUS)) {
                yield new Operator(Operator::MINUS);

                continue;
            }

            if ($this->consume(self::QUOTE)) {
                $quoted = $this->consumeUntil(self::QUOTE, allowEscaping: true);
                yield new Literal($quoted, quoted: true);
                $this->consume(self::QUOTE);

                continue;
            }

            $literal = $this->consumeUntil([self::WHITESPACE, self::COLON], allowEscaping: true);

            yield new Literal($literal);
        }
    }

    private function eof(): bool
    {
        return $this->position >= $this->len;
    }

    private function consume(string $char): ?string
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