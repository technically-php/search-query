<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Filters;

interface Filter
{
    /**
     * Serialize the filter back to the query syntax string.
     *
     * The returned presentation is normalized and might be different
     * from the original source string used to construct this filter.
     */
    public function toString(): string;
}