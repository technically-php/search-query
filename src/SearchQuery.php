<?php
declare(strict_types=1);

namespace Technically\SearchQuery;

final readonly class SearchQuery
{
    public function __construct(
        public array $filters,
    ) {

    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function isEmpty(): bool
    {
        return empty($this->filters);
    }
}