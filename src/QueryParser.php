<?php
declare(strict_types=1);

namespace Technically\SearchQuery;

final readonly class QueryParser
{
    public function parse(string $query): SearchQuery
    {
        if (empty(trim($query))) {
            return SearchQuery::empty();
        }

        return SearchQuery::empty(); // FIXME
    }
}