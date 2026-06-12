<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Tokens;

use InvalidArgumentException;

final readonly class Whitespace implements Token
{
    public function __construct(
        public string $value = ' ',
    ) {
        if (empty($value)) {
            throw new InvalidArgumentException('Whitespace token cannot be empty.');
        }
        if (trim($value) !== '') {
            throw new InvalidArgumentException('Whitespace token cannot contain non-whitespace characters.');
        }
    }

    public function toString(): string
    {
        return ' ';
    }
}