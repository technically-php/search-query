<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Tokenizer\Tokens;

use InvalidArgumentException;

final readonly class Whitespace implements Token
{
    public function __construct(
        public string $source = ' ',
    ) {
        if (empty($source)) {
            throw new InvalidArgumentException('Whitespace token cannot be empty.');
        }
        if (trim($source) !== '') {
            throw new InvalidArgumentException('Whitespace token cannot contain non-whitespace characters.');
        }
    }

    public function toString(): string
    {
        return ' ';
    }
}