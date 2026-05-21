<?php

namespace Technically\SearchQuery\Tokenizer\Tokens;

use InvalidArgumentException;

final readonly class Literal implements Token
{
    public function __construct(
        public string $value,
        public bool   $quoted = false,
    ) {
        if (str_contains($value, ' ') && ! $quoted) {
            throw new InvalidArgumentException('Literal token cannot contain spaces when not quoted.');
        }
    }

    public function toString(): string
    {
        if ($this->quoted) {
            return json_encode($this->value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $this->value;
    }

    public function with(
        string | null $value = null,
        bool | null $quoted = null,
    ): self {
        return new self(
            $value !== null ? $value : $this->value,
            $quoted !== null ? $quoted : $this->quoted,
        );
    }
}