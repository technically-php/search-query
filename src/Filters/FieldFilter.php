<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Filters;

use InvalidArgumentException;

final readonly class FieldFilter implements Filter
{
    public function __construct(
        public string $field,
        public string $value,
        public bool $exact = false,
        public bool $exclude = false,
    ) {
        if (empty($field)) {
            throw new InvalidArgumentException('Field cannot be empty');
        }
    }
}