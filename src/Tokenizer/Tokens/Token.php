<?php

namespace Technically\SearchQuery\Tokenizer\Tokens;

interface Token
{
    public function toString(): string;
}