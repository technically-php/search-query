<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Filters;

interface Filter
{
    /**
     * Serialize the filter back to the query string presentation.
     */
    public function toString(): string;
}