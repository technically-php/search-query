<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Tokenizer\Tokens;

interface Token
{
    public function toString(): string;
}