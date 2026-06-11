<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Tokens;

use InvalidArgumentException;

final readonly class Literal implements Token
{
    public function __construct(
        public string $value,
    ) {
        if (empty($value)) {
            throw new InvalidArgumentException('Literal cannot be empty.');
        }
    }

    public function toString(): string
    {
        return addcslashes($this->value, ' ');
    }
}