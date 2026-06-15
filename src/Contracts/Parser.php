<?php

namespace Technically\SearchQuery\Contracts;

use Technically\SearchQuery\Query;

interface Parser
{
    public function parse(string $query): Query;
}