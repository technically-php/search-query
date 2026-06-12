<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Filters;

use Technically\SearchQuery\Tokens\Literal;
use Technically\SearchQuery\Tokens\Operator;
use Technically\SearchQuery\Tokens\QuotedString;

final readonly class KeywordFilter implements Filter
{
    public function __construct(
        public string $keyword,
        public bool   $quoted = false,
        public bool   $exclude = false,
    ) {
        // Nothing
    }

    public function toString(): string
    {
        $keyword = $this->quoted || empty($this->keyword)
            ? new QuotedString($this->keyword)
            : new Literal($this->keyword);

        return ($this->exclude ? Operator::MINUS : '') . $keyword->toString();
    }
}