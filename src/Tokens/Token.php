<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Tokens;

interface Token
{
    public function toString(): string;
}