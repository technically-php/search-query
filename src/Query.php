<?php
declare(strict_types=1);

namespace Technically\SearchQuery;

use InvalidArgumentException;
use Technically\SearchQuery\Filters\Filter;

final readonly class Query
{
    public function __construct(
        /**
         * @var Filter[]
         */
        public array $filters = [],
    ) {
        foreach ($filters as $filter) {
            if (! $filter instanceof Filter) {
                throw new InvalidArgumentException('Query can only contain instances of `Filter`.');
            }
        }
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