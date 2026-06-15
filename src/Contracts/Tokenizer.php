<?php

namespace Technically\SearchQuery\Contracts;

use Technically\SearchQuery\Tokens\Token;

interface Tokenizer
{
    /**
     * @return iterable<Token>
     */
    public function tokenize(string $query): iterable;
}