<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Filters;

use InvalidArgumentException;

final readonly class KeywordFilter implements Filter
{
    public function __construct(
        public string $keyword,
        public bool $exact = false,
        public bool $exclude = false,
    ) {
        if (empty($keyword)) {
            throw new InvalidArgumentException('Keyword cannot be empty');
        }

        if (str_contains($keyword, ' ') && ! $exact) {
            throw new InvalidArgumentException('Keyword can only contain whitespaces when `exact` mode is on.');
        }
    }

    public function toString(): string
    {
        $json = json_encode($this->keyword, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (str_contains($this->keyword, ' ')) {
            return $json;
        }

        if ($this->keyword === '') {
            return $json; // `""`
        }

        if ($json !== "\"{$this->keyword}\"") {
            return $json;
        }

        return $this->keyword;
    }
}